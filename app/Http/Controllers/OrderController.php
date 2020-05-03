<?php

namespace App\Http\Controllers;

use App\Cart;
use App\Order;
use App\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    // API : [GET] /api/order
    public function orderIndex(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guard' => 'required|string',
        ]);

        // [Client Errors]
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], 422);
        }

        if (!($request->guard === 'admin')) {
            return response()->json([
                'message' => 'This page is only accessible to admin',
            ], 403);
        }

        if (!Auth::guard($request->guard)->check()) {
            return response()->json([
                'message' => 'Access Denied'
            ], 401);
        }   // [Client Errors]

        $orders = Order::get();
        return response()->json([
            'message' => 'Orders Indexing Success',
            'orders' => $orders,
        ], 200);
    }

    // API : [POST] /api/order
    public function orderRegister(Request $request)
    {
        // [CHECK VALIDATION]
        $validator = Validator::make($request->all(), [
//            'sender' => 'required|numeric',
            'receiver' => 'required|numeric',
            'order_cart' => 'required|numeric',
            'order_route' => 'required|numeric',
            'cartMove_needs' => 'required|boolean',
            'guard' => 'required|string',
        ]);

        // [Client Errors]
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], 422);
        }

        // [IF] Cart need to move to the starting point
        if ((bool)$request->cartMove_needs) {
            $validator = Validator::make($request->all(), [
                'cartMove_routeId' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                ], 422);
            }

            // TODO : 수신자에게 동의여부를 확인
            // TODO : 동의 시, node.js 를 통해 출발지로 차량이동 명령 전달
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
        }   // [Client Errors]

        $sender = $request->user($request->guard);

        // [QUERY] Register order
        $order = Order::create([
            'order_status' => 0,
//            'sender' => $request->sender,
            'sender' => $sender->id,
            'receiver' => $request->receiver,
            'order_cart' => $request->order_cart,
            'order_route' => $request->order_route,
            'request_time' => now(),
        ]);

        return response()->json([
            'message' => 'Order Registration Success',
            'waypoint' => $order,
        ], 201);
    }

    // API : [GET] /api/order/check
    public function orderCheck(Request $request)
    {
        // [CHECK VALIDADATION]
        $validator = Validator::make($request->all(), [
            'guard' => 'required|string'
        ]);

        // [Client Errors]
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
        }   // [Client Errors]

        $userId = $request->user($request->guard)->id;

        $order = Order::where('sender', $userId)
            ->where('order_status', '<>', '3')
            ->get();

        if ($order->count() >= 1) {
            return response()->json([
                'message' => 'There is already a order in progress',
                'order' => $order,
                'availability' => false,
            ], 200);
        }

        return response()->json([
            'message' => 'There are no orders in progress',
            'availability' => true,
        ], 200);
    }

    // API : [GET] /api/order/show
    public function orderShow(Request $request)
    {
        // [CHECK VALIDATION]
        $validator = Validator::make($request->all(), [
            'startingId' => 'required|numeric',
            'arrivalId' => 'required|numeric',
            'guard' => 'required|string',
        ]);

        // [Client Errors]
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
        }   // [Client Errors]

        // [SET] Variable
        $startingId = $request->startingId;
        $arrivalId = $request->arrivalId;
        $routeInfo = null;

        // [QUERY] Registered route search
        for ($routeCount = 0; $routeCount < 2; $routeCount++) {
            $routeInfo = Route::select('id', 'travel_time')
                ->where('starting_point', $startingId)
                ->where('arrival_point', $arrivalId)
                ->get()->first();

            if ($routeInfo == null) {
                $tmpID = $startingId;
                $startingId = $arrivalId;
                $arrivalId = $tmpID;
            } else  break;
        }

        // [IF] There is no registered route => RETURN
        if ($routeInfo == null) {
            return response()->json([
                'message' => 'There is no available routes'
            ], 200);
        }   // [QUERY END]

        // [QUERY] Check cart existed at the starting point
        $cartId = Cart::select('id')
            ->where('status', 0)
            ->where('cart_location', $request->startingId)
            ->get()->first();

        // [IF] There is a cart at the starting point => RETURN
        if ($cartId != null) {
            // TODO : 일정 시간 초과 후 운행 상태 1 지속 시, 0으로 초기화
            $cartId->update(['status' => 1]);
            return response()->json([
                'message' => 'There is a cart at the starting point',
                'expected_arrivalTime' => 0,
                'travel_time' => $routeInfo->travel_time,
                'order_cart' => $cartId->id,
                'order_route' => $routeInfo->id,
                'cartMove_needs' => false,
                'cartMove_routeId' => null,
            ], 200);
        }   // [QUERY END]


        // [QUERY] Find cart at nearby waypoint
        $closePathsFirst = Route::select('id', 'arrival_point', 'travel_time')
            ->where('starting_point', $request->startingId);
        $closePaths = Route::select('id', 'starting_point as waypoint', 'travel_time')
            ->where('arrival_point', $request->startingId)
            ->union($closePathsFirst)
            ->orderBy('travel_time')
            ->get();

        for ($pathCount = 0; $pathCount < $closePaths->count(); $pathCount++) {
            $nearWaypoint = $closePaths[$pathCount];
            $cartId = Cart::select('id')
                ->where('status', 0)
                ->where('cart_location', $nearWaypoint->waypoint)
                ->get()->first();

            if ($cartId == null) continue;

            // [IF] There is a cart near the starting point => RETURN
            if ($cartId != null) {
                // TODO : 일정 시간 초과 후 운행 상태 1 지속 시, 0으로 초기화
                $cartId->update(['status' => 1]);
                return response()->json([
                    'message' => 'Cart moves to the starting point',
                    'expected_arrivalTime' => $nearWaypoint->travel_time,
                    'travel_time' => $routeInfo->travel_time,
                    'order_cart' => $cartId->id,
                    'order_route' => $routeInfo->id,
                    'cartMove_needs' => true,
                    'cartMove_routeId' => $nearWaypoint->id,
                ], 200);
            }
        }

        $waiting_order = Order::where('order_status', 9)->get()->count();

        return response()->json([
            'message' => 'There is no cart that can move',
            'waiting_orderNum' => $waiting_order,
        ], 200);
    }
}
