<?php

namespace App\Http\Controllers;

use App\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function adminRegister(Request $request)
    {
        // [CHECK VALIDATION]
        $validator = Validator::make($request->all(), [
            'account' => 'required|email|unique:admins,account',
            'password' => 'required|string|confirmed|min:8',
            'name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return \response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $admin = Admin::create([
            'account' => $request->account,
            'password' => Hash::make($request->password),
            'name' => $request->name,
        ]);

        return \response()->json([
            'message' => 'Admin Registration Success',
            'admin' => $admin,
        ], 201);
    }
}
