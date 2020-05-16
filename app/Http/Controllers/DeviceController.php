<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DeviceController extends Controller
{
    public function upsert(Request $request)
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

        $user = $request->user($request->guard);
        $device = $user->devices()
            ->whereDeviceId($request->device_id)->first();

        $input = $request->all();

        if (! $device) {
            $device = $user->devices()->create($input);
        } else {
            $device->update($input);
        }

        return $device;
    }
}
