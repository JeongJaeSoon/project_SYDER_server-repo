<?php

namespace App\Http\Controllers;

use App\Waypoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WaypointController extends Controller
{
    public function waypointIndex(Request $request)
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

        if (!($request->guard === 'admin' || $request->guard === 'user')) {
            return response()->json([
                'message' => 'This page is only accessible to admin or user',
            ], 403);
        }

        if (!Auth::guard($request->guard)->check()) {
            return response()->json([
                'message' => 'Access Denied'
            ], 401);
        }   // [Client Errors]

        $waypoints = Waypoint::get();
        return response()->json([
            'message' => 'Waypoints Indexing Success',
            'waypoints' => $waypoints
        ], 200);
    }

    public function waypointStore(Request $request)
    {
        // [CHECK VALIDATION]
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:waypoints,name',
            'lat' => 'required|numeric|between:35.894756152459216,35.89740573228205',
            'lng' => 'required|numeric|between:128.62000526129742,128.6236530319387',
            'guard' => 'required|string',
        ]);

        // [Client Errors]
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        if (!$request->guard === 'admin') {
            return response()->json([
                'message' => 'This page is only accessible to admin',
            ], 403);
        }

        if (!Auth::guard($request->guard)->check()) {
            return response()->json([
                'message' => 'Access Denied'
            ], 401);
        }   // [Client Errors]

        $waypoint = Waypoint::create([
            'name' => $request->name,
            'lat' => $request->lat,
            'lng' => $request->lng,
        ]);

        return response()->json([
            'message' => 'Waypoint Registration Success',
            'waypoint' => $waypoint,
        ], 201);
    }

    public function waypointUpdate(Request $request, Waypoint $waypoint)
    {
        // [CHECK VALIDATION]
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:35.894756152459216,35.89740573228205',
            'lng' => 'required|numeric|between:128.62000526129742,128.6236530319387',
            'guard' => 'required|string',
        ]);

        // [Client Errors]
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        if (!$request->guard === 'admin') {
            return response()->json([
                'message' => 'This page is only accessible to admin',
            ], 403);
        }

        if (!Auth::guard($request->guard)->check()) {
            return response()->json([
                'message' => 'Access Denied'
            ], 401);
        }   // [Client Errors]

        $waypoint->update([
            'lat' => $request->lat,
            'lng' => $request->lng,
        ]);

        $updated = Waypoint::find($waypoint->id);

        return response()->json([
            'message' => 'Waypoint Update Success',
            'waypoint' => $updated
        ], 200);
    }

    public function waypointDestroy(Request $request, Waypoint $waypoint)
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

        if (!$request->guard === 'admin') {
            return response()->json([
                'message' => 'This page is only accessible to admin',
            ], 403);
        }

        if (!Auth::guard($request->guard)->check()) {
            return response()->json([
                'message' => 'Access Denied'
            ], 401);
        }   // [Client Errors]

        $waypoint->delete();

        return response()->json([
            'message' => 'Waypoint Delete Success',
            'waypoint' => $waypoint
        ], 200);
    }
}
