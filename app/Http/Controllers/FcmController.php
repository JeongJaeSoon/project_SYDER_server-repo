<?php

namespace App\Http\Controllers;

use App\Order;
use App\Waypoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use LaravelFCM\Facades\FCM;

class FcmController extends Controller
{
    public function consentRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|numeric',
            'guard' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], 422);
        }

        if (!($request->guard === 'user')) {
            return response()->json([
                'message' => 'This page is only accessible to user',
            ], 403);
        }

        if (!Auth::guard($request->guard)->check()) {
            return response()->json([
                'message' => 'Access Denied'
            ], 401);
        }

        $sender = $request->user($request->guard);
        $orderInfo = Order::select('users.name', 'users.fcm_token', 'orders.reverse_direction', 'routes.starting_point', 'routes.arrival_point', 'routes.travel_time')
            ->where('orders.id', $request->order_id)
            ->join('users', 'orders.receiver', 'users.id')
            ->join('routes', 'orders.order_route', 'routes.id')
            ->get()->first();

        $startingPoint = Waypoint::where('id', $orderInfo->starting_point)
            ->get()->first()->name;
        $arrivalPoint = Waypoint::where('id', $orderInfo->arrival_point)
            ->get()->first()->name;
        $travel_time = $orderInfo->travel_time;

        (boolean)$orderInfo->reverse_direction ? list($arrivalPoint, $startingPoint) = array($startingPoint, $arrivalPoint) : '';

        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60 * 20);

        $notificationBuilder = new PayloadNotificationBuilder($orderInfo->name . '님께 새로운 동의 요청이 도착했습니다.');
//        $notificationBuilder->setBody($sender->name . '님께서 ' . $startingPoint . '에서 ' . $arrivalPoint . '으로(출발 후 '.$travel_time.'분 뒤 도착 예정) 물건 배송을 요청하였습니다')
//            ->setSound('default');
        $notificationBuilder->setBody($sender->name . '님께서 ' . $startingPoint . '에서 ' . $arrivalPoint . '으로 물건 배송을 요청하였습니다.')
            ->setSound('default');

        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData(['a_data' => 'my_data']);

        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $data = $dataBuilder->build();

        $token = $orderInfo->fcm_token;

        $downstreamResponse = FCM::sendTo($token, $option, $notification, $data);

        $downstreamResponse->numberSuccess();
        $downstreamResponse->numberFailure();
        $downstreamResponse->numberModification();

        // return Array - you must remove all this tokens in your database
        $downstreamResponse->tokensToDelete();

        // return Array (key : oldToken, value : new token - you must change the token in your database)
        $downstreamResponse->tokensToModify();

        // return Array - you should try to resend the message to the tokens in the array
        $downstreamResponse->tokensToRetry();

        // return Array (key:token, value:error) - in production you should remove from your database the tokens
        $downstreamResponse->tokensWithError();

        return response()->json([
            'sender' => $sender->name,
            'reciver' => $orderInfo->name,
            'starting_point' => $startingPoint,
            'arrival_point' => $arrivalPoint,
            'order' => $orderInfo,
        ]);
    }
}
