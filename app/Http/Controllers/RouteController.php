<?php

namespace App\Http\Controllers;

use App\Route;
use App\Model\Authenticator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RouteController extends Controller
{
    public function routeIndex(Request $request)
    {
        // [CHECK VALIDATION]
        $validator = Validator::make($request->all(), [
            'guard' => 'required|string'
        ]);

        // [Client Errors]
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        if (!($request->guard === 'admin' || $request->guard === 'user')) {
            return response()->json([
                'message' => 'This page is only accessible to admin or user',
            ], 403);
        }

        if (!Auth::guard($request->guard)->check()) {
            return response()->json([
                'message' => 'Access Denied',
            ], 401);
        }   // [Client Errors]

        $routes = Route::get();
        return response()->json([
            'message' => 'Routes Indexing Success',
            'routes' => $routes
        ], 200);
    }

    public function routeStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'starting_point' => 'required|numeric',
            'arrival_point' => 'required|numeric',
            'travel_time' => 'required|numeric',
            'travel_distance' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        if ($request->starting_point === $request->arrival_point) {
            return response()->json([
                'message' => 'Route that cannot be registered'
            ], 422);
        }

        if (
            empty(Route::where('starting_point', $request->starting_point)
                ->where('arrival_point', $request->arrival_point)
                ->get()->first()) &&
            empty(Route::where('starting_point', $request->arrival_point)
                ->where('arrival_point', $request->starting_point)
                ->get()->first())
        ) {
            $route = Route::create([
                'starting_point' => $request->starting_point,
                'arrival_point' => $request->arrival_point,
                'travel_time' => $request->travel_time,
                'travel_distance' => $request->travel_distance
            ]);

            return response()->json([
                'message' => 'Route Registration Success',
                'route' => $route
            ], 201);
        }

        return response()->json([
            'message' => 'This route already exists',
        ], 422);
    }

    public function routeDestroy(Request $request, Route $route)
    {
        if (!($request->guard === 'admin')) {
            return response()->json([
                'message' => 'This page is only accessible to admin',
            ], 403);
        }

        $route->delete();

        return response()->json([
            'message' => 'Route Delete Success',
            'route' => $route
        ], 200);
    }
}
