<?php

namespace App\Http\Controllers;

use App\Cart;
use App\Order;
use App\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function orderShow(Request $request)
    {
        // [CHECK VALIDATION]
        $validator = Validator::make($request->all(), [
            'startingPoint_id' => 'required|numeric',
            'arrivalPoint_id' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        // [SET] Variable
        $startingPoint_id = $request->startingPoint_id;
        $arrivalPoint_id = $request->arrivalPoint_id;
        $routeInfo = null;

        // [QUERY] Registered route search
        for ($route_count = 0; $route_count < 2; $route_count++) {
            $routeInfo = Route::select('id', 'travel_time')
                ->where('starting_point', $startingPoint_id)
                ->where('arrival_point', $arrivalPoint_id)
                ->get()->first();

            if ($routeInfo == null) {
                $tmp_pointID = $startingPoint_id;
                $startingPoint_id = $arrivalPoint_id;
                $arrivalPoint_id = $tmp_pointID;
            } else  break;
        }

        // [IF] There is no registered route => RETURN
        if ($routeInfo == null) {
            return response()->json([
                'message' => 'Routes Not Available'
            ], 200);
        }   // [QUERY END]

        // [QUERY] Check cart existed at the starting point
        $cart_id = Cart::select('id')
            ->where('status', 0)
            ->where('cart_location', $request->startingPoint_id)
            ->get()->first();

        // [IF] There is a cart at the starting point => RETURN
        if ($cart_id != null) {
            // TODO : 일정 시간 초과 후 운행 상태 1 지속 시, 0으로 초기화
            $cart_id->update(['status' => 1]);
            return response()->json([
                'message' => 'There is a cart at the starting point',
                'expected_arrivalTime' => 0,
                'travel_time' => $routeInfo->travel_time,
                'order_cart' => $cart_id->id,
                'order_route' => $routeInfo->id,
                'cartMove_needs' => false,
                'cartMove_routeId' => null,
            ], 200);
        }   // [QUERY END]


        // [QUERY] Find cart at nearby waypoint
        $closePaths_first = Route::select('id', 'arrival_point', 'travel_time')
            ->where('starting_point', $request->startingPoint_id);
        $closePaths = Route::select('id', 'starting_point as waypoint', 'travel_time')
            ->where('arrival_point', $request->startingPoint_id)
            ->union($closePaths_first)
            ->orderBy('travel_time')
            ->get();

        for ($path_count = 0; $path_count < $closePaths->count(); $path_count++) {
            $nearBy_Waypoint = $closePaths[$path_count];
            $cart_id = Cart::select('id')
                ->where('status', 0)
                ->where('cart_location', $nearBy_Waypoint->waypoint)
                ->get()->first();

            if ($cart_id == null) continue;

            // [IF] There is a cart near the starting point => RETURN
            if ($cart_id != null) {
                // TODO : 일정 시간 초과 후 운행 상태 1 지속 시, 0으로 초기화
                $cart_id->update(['status' => 1]);
                return response()->json([
                    'message' => 'Cart moves to the starting point',
                    'expected_arrivalTime' => $nearBy_Waypoint->travel_time,
                    'travel_time' => $routeInfo->travel_time,
                    'order_cart' => $cart_id->id,
                    'order_route' => $routeInfo->id,
                    'cartMove_needs' => true,
                    'cartMove_routeId' => $nearBy_Waypoint->id,
                ], 200);
            }
        }

        $waiting_order = Order::where('order_status', 9)->get()->count();

        return response()->json([
            'message' => 'There is no cart that can move',
            'waiting_orderNum' => $waiting_order,
        ], 200);
    }

    public function orderRegister(Request $request)
    {
        // [CHECK VALIDATION]
        $validator = Validator::make($request->all(), [
            'sender' => 'required|numeric',
            'receiver' => 'required|numeric',
            'order_cart' => 'required|numeric',
            'order_route' => 'required|numeric',
            'cartMove_needs' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
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

            // TODO : node.js 를 통해 출발지로 차량이동 명령 전달
            // TODO : 수신자에게 동의여부를 확인
        }

        // [QUERY] Register order
        $order = Order::create([
            'order_status' => 0,
            'sender' => $request->sender,
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
}
