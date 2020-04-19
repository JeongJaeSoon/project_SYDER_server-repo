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
        $validator = Validator::make($request->all(), [
            'startingPoint_id' => 'required|numeric',
            'arrivalPoint_id' => 'required|numeric'
        ]);

        if ($validator->fails()){
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }
        // Query Route travel time
        $startingPoint_id = $request->startingPoint_id;
        $arrivalPoint_id = $request->arrivalPoint_id;
        $route_travelTime = null;

        for ($query_count = 0; $query_count < 2; $query_count++) {
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

        // If there is no registered route
        if ($route_travelTime == null) {
            return response()->json([
                'message' => 'Routes Not Available'
            ], 200);
        }   // Query Route travel time END

        // Query Cart at starting point
        $cart_id = Cart::select('id')
            ->where('status', 0)
            ->where('cart_location', $request->startingPoint_id)
            ->get()->first();

        if ($cart_id == null) {
            dd("차 찾는거 만들자~~~!!!");
        }

        return response()->json([
            'travel_time' => $route_travelTime->travel_time,
            'assigned_cart' => $cart_id->id
        ], 200);
    }
}
