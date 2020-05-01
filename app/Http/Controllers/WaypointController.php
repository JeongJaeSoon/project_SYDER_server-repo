<?php

namespace App\Http\Controllers;

use App\Waypoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WaypointController extends Controller
{
    public function waypointIndex()
    {
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

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

    public function waypointDestroy(Waypoint $waypoint)
    {
        $waypoint->delete();

        return response()->json([
            'message' => 'Waypoint Delete Success',
            'waypoint' => $waypoint
        ], 200);
    }
}
