<?php

namespace App\Http\Controllers;

use App\Cart;
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
        $expected_arrivalTime = 0;
//        $wating_Order = 0;
        $route_travelTime = 0;

        // [QUERY] Registered route search
        for ($route_count = 0; $route_count < 2; $route_count++) {
            $route_travelTime = Route::select('travel_time')
                ->where('starting_point', $startingPoint_id)
                ->where('arrival_point', $arrivalPoint_id)
                ->get()->first();

            if ($route_travelTime == null) {
                $tmp_pointID = $startingPoint_id;
                $startingPoint_id = $arrivalPoint_id;
                $arrivalPoint_id = $tmp_pointID;
            } else  break;
        }

        // [IF] There is no registered route => RETURN
        if ($route_travelTime == null) {
            return response()->json([
                'message' => 'Routes Not Available'
            ], 200);
        }   // [QUERY END]

        $route_travelTime = $route_travelTime->travel_time;

        // [QUERY] Check cart existed at the starting point
        $cart_id = Cart::select('id')
            ->where('status', 0)
            ->where('cart_location', $request->startingPoint_id)
            ->get()->first();

        // [IF] There is a cart at the starting point => RETURN
        if ($cart_id != null) {
            return response()->json([
                'expected_arrivalTime' => $expected_arrivalTime,
//                'wating_Order' => $wating_Order,
                'travel_time' => $route_travelTime,
                'assigned_cart' => $cart_id->id,
            ]);
        }   // [QUERY END]

        // TODO : 주문 요청 시작 시, 차량 배정(상태 변경)
        //$cart_id->update(['status' => 1]);

        // [QUERY] Find cart at nearby waypoint
        $closePaths_first = Route::select('arrival_point', 'travel_time')
            ->where('starting_point', $request->startingPoint_id);
        $closePaths = Route::select('starting_point as waypoint', 'travel_time')
            ->where('arrival_point', $request->startingPoint_id)
            ->union($closePaths_first)
            ->orderBy('travel_time')
            ->get();

        for ($path_count = 0; $path_count < $closePaths->count(); $path_count++) {
            $nearBy_Waypoint = $closePaths[$path_count];
            $nearBy_cart = Cart::select('id')
                ->where('status', 0)
                ->where('cart_location', $nearBy_Waypoint->waypoint)
                ->get()->first();

            if ($nearBy_cart == null) continue;

            // [IF] There is a cart near the starting point => RETURN
            if ($nearBy_cart != null) {
                return response()->json([
                    'expected_arrivalTime' => $nearBy_Waypoint->travel_time,
//                    'wating_Order' => $wating_Order,
                    'travel_time' => $route_travelTime,
                    'assigned_cart' => $nearBy_cart->id,
                ]);
            }
        }

//        dd("주문가능한 차량이 없다고? 언제 만들지");
    }
}
