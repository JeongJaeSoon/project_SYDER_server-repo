<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // API : [POST] /api/register/users
    public function userRegister(Request $request)
    {
        // [CHECK VALIDATION]
        $validator = Validator::make($request->all(), [
            'account' => 'required|string|unique:users,account',
            'password' => 'required|string|confirmed|min:8',
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone|digits_between:11,12',
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

    // API : [GET] /api/user/request
    public function receiverSearch(Request $request)
    {
        // [CHECK VALIDATION]
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|digits_between:11,12',
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

        $result = User::select('id', 'name')->where('phone', $request->phone)->get()->first();

        $result->name = iconv_substr($result->name, 0, 1, 'UTF-8').'*'.iconv_substr($result->name, 2,2,'UTF-8');

        if ($result == null) {
            return response()->json([
                'message' => 'Receiver not found',
            ], 404);
        }

        return response()->json([
            'receiver' => $result,
        ], 200);
    }
}
