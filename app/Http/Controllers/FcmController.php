<?php

namespace App\Http\Controllers;

use App\Cart;
use App\Order;
use App\Route;
use App\User;
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
    private function FcmBuilder($arg_token, $arg_message_title, $arg_message_body, $arg_activity, array $arg_data)
    {
        $token = $arg_token;

        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60 * 20);

        $notificationBuilder = new PayloadNotificationBuilder($arg_message_title);
        $notificationBuilder
            ->setBody($arg_message_body)
            ->setSound('default')
            ->setClickAction($arg_activity);

        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData($arg_data);

        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $data = $dataBuilder->build();

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
    }

    public function consentRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|numeric',
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

        $sender = $request->user($request->guard);

        $order_info = Order::select('users.name', 'users.fcm_token', 'orders.reverse_direction', 'routes.starting_point', 'routes.arrival_point', 'routes.travel_time')
            ->where('orders.id', $request->order_id)
            ->where('orders.sender', $sender->id)
            ->join('users', 'orders.receiver', 'users.id')
            ->join('routes', 'orders.order_route', 'routes.id')
            ->get()->first();

        if (empty($order_info)) {
            return response()->json([
                'message' => 'There is no matching order information'
            ], 404);
        }

        $starting_point = Waypoint::where('id', $order_info->starting_point)
            ->get()->first()->name;
        $arrival_point = Waypoint::where('id', $order_info->arrival_point)
            ->get()->first()->name;

        (boolean)$order_info->reverse_direction ? list($arrival_point, $starting_point) = array($starting_point, $arrival_point) : '';

        $token = $order_info->fcm_token;
        $message_title = $order_info->name . '님께 새로운 동의 요청이 도착했습니다.';
        $message_body = $sender->name . '님께서 ' . $starting_point . '에서 ' . $arrival_point . '으로 물건 배송을 요청하였습니다.';
        $activity = 'ConsentActivity';
        $data = [];

        $this->FcmBuilder($token, $message_title, $message_body, $activity, $data);

        return response()->json([
            'message' => 'Consent Request Success',
        ]);
    }

    public function consentResponse(Request $request)
    {
        $receiver_activity = '';

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|numeric',
            'consent_or_not' => 'required|boolean',
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

        $receiver = $request->user($request->guard);

        $order_info = Order::select('users.name', 'users.fcm_token', 'orders.status')
            ->where('orders.id', $request->order_id)
            ->where('orders.receiver', $receiver->id)
            ->join('users', 'orders.sender', 'users.id')
            ->get()->first();

        if (empty($order_info)) {
            return response()->json([
                'message' => 'There is no matching order information'
            ], 404);
        }

        $order = Order::where('id', $request->order_id);
        $order->update(['approved_time' => now()]);

        $token = $order_info->fcm_token;
        $message_title = $order_info->name . '님께 요청 결과가 도착했습니다.';
        $message_body = $receiver->name . '님께서 주문 요청을 ';

        if ((boolean)$request->consent_or_not) {
            $message_body .= '동의하셨습니다.';
            $receiver_activity = 'AgreeActivity';
            $order->update(['status' => 101]);
        } else {
            $message_body .= '거절하셨습니다.';
            $order->update(['status' => 402]);
            $receiver_activity = 'DisagreeActivity';
        }

        $activity = $receiver_activity;
        $data = ['receiver_fcm_token' => $receiver->fcm_token];

        $this->FcmBuilder($token, $message_title, $message_body, $activity, $data);

        return response()->json([
            'message' => 'Consent Response Success',
        ]);
    }

    public function waitingOrderProcessing(Order $order, Cart $cart)
    {
        $estimated_time = 0;
        $route_info = Route::where('id', $order->order_route)
            ->get()->first();

        if ((bool)$order->reverse_direction)
            $starting_point = $route_info->arrival_point;
        else
            $starting_point = $route_info->starting_point;

        $sender = User::select('name', 'fcm_token')
            ->where('id', $order->sender)->get()->first();

        $token = $sender->fcm_token;
        $message_title = $sender->name . '님께서 요청하신 주문이 준비되었습니다.';
        $message_body = '';

        if ($cart->cart_location === $starting_point)
            $message_body = '요청하신 출발지로 가셔서 차량에 물건을 넣으세요.';
        else {
            $estimated_time_first = Route::select('travel_time')
                ->where('starting_point', $cart->cart_location)
                ->where('arrival_point', $starting_point);
            $estimated_time = Route::select('travel_time')
                ->where('starting_point', $starting_point)
                ->where('arrival_point', $cart->cart_location)
                ->union($estimated_time_first)
                ->get()->first()->travel_time;
            $message_body = $estimated_time . '분 내에 차량이 출발지에 도착할 예정입니다.';
        }

        $activity = 'MainActivity';
        $data = ['$estimated_time' => $estimated_time];

        $this->FcmBuilder($token, $message_title, $message_body, $activity, $data);
    }
}
