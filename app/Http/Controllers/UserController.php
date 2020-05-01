<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function userRegister(Request $request)
    {
        // [CHECK VALIDATION]
        $validator = Validator::make($request->all(), [
            'account' => 'required|string|unique:users,account',
            'password' => 'required|string|confirmed|min:8',
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'account' => $request->account,
            'password' => Hash::make($request->password),
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        return response()->json([
            'message' => 'User Registration Success',
            'user' => $user,
        ], 201);
    }

}
