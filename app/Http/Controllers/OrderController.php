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
                'cartMove_route' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                ], 422);
            }

            // TODO : 수신자에게 동의여부를 확인
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
            'status' => 100,
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

        // Check for available carts
        $remainCarts = Cart::select('id', 'status', 'cart_location')
            ->where('status', 0)
            ->get();

        // [IF] There is no available cart => RETURN
        if (!$remainCarts->count()) {
            $remainOrders = Order::where('status', 900)->get()->count();

            return response()->json([
                'message' => 'There is no available cart',
                'remain_order' => $remainOrders,
            ], 200);
        }

        // [IF] Cart is at the starting Point
        foreach ($remainCarts as $cart) {
            $cart_location = $cart->cart_location;

            if ($cart_location == $request->startingId) {
                $cart->update(['status' => 1]);

                return response()->json([
                    'message' => 'Cart is ready for start',
                    'cart_id' => $cart->id,
                    'cartMove_needs' => false,
                ], 200);
            }
        }

        // [QUERY] Find cart at nearby waypoint
        $closeRoutesFirst = Route::select('id', 'arrival_point', 'travel_time')
            ->where('starting_point', $request->startingId);
        $closeRoutes = Route::select('id', 'starting_point as waypoint', 'travel_time')
            ->where('arrival_point', $request->startingId)
            ->union($closeRoutesFirst)
            ->orderBy('travel_time')
            ->get();

        foreach ($closeRoutes as $route) {
            foreach ($remainCarts as $cart) {

                // [IF] Cart is at the nearby starting waypoint
                if ($cart->cart_location == $route->waypoint) {
                    $cart->update(['status' => 1]);

                    return response()->json([
                        'message' => 'Cart is need to move',
                        'cart_id' => $cart->id,
                        'cartMove_needs' => true,
                        'cartMove_route' => $route->id,
                        'cartMove_time' => $route->travel_time,
                    ], 200);
                }
            }
        }
    }

    // API : [PATCH] /api/orders/{order}
    public function orderConsentUpdate(Request $request, Order $order)
    {
        // [CHECK VALIDATION]
        $validator = Validator::make($request->all(), [
            'consent' => 'required|boolean',
            'guard' => 'required|string',
        ]);

        // [Client Errors]
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
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

        $message = $request->consent ? $message = "User consent is registered" : $message = "User reject is registered";

        if ($request->consent) {
            $order->update(['status' => 101]);
        } else {
            $order->update(['status' => 102]);
        }

        $updated = Order::find($order->id);

        return response()->json([
            'message' => $message,
            'order' => $updated,
        ], 200);
    }

    public function orderAuthentication(Request $request, Cart $cart)
    {
        $validator = Validator::make($request->all(), [
            'orderId' => 'required|numeric',
            'userId' => 'required|numeric',
            'userCategory' => 'required|string',
            'guard' => 'required|string',
        ]);

        // [Client Errors]
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
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

        $sender = $receiver = $status = '';

        if ($request->userCategory === 'sender') {
            $sender = $request->user($request->guard)->id;
            $receiver = $request->userId;
            $status = 200;
        } else if ($request->userCategory === 'receiver') {
            $sender = $request->userId;
            $receiver = $request->user($request->guard)->id;
            $status = 201;
        }

        $order = Order::where('status', $status)
            ->where('sender', $sender)->where('receiver', $receiver)
            ->where('id', $request->orderId)->where('order_cart', $cart->id)
            ->get()->first();

        if ($order == null)
            return response()->json([
                'message' => 'This is an invalid order',
                'result' => false
            ], 404);

        return response()->json([
            'message' => 'This is a valid order',
            'result' => true
        ], 200);


    }
}
