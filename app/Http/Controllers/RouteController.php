<?php

namespace App\Http\Controllers;

use App\Route;
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
}
