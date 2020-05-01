<?php

namespace App\Http\Controllers;

use App\Model\Authenticator;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    /**
     * @var Authenticator
     */
    private $authenticator;

    // Authenticator where 수정함
    public function __construct(Authenticator $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     * @throws AuthenticationException
     */
    public function login(Request $request)
    {
        // [CHECK VALIDATION]
        $validator = Validator::make($request->all(), [
            'account' => 'required|string',
            'password' => 'required|string|min:8',
            'provider' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], 422);
        }

        $credentials = array_values($request->only('account', 'password', 'provider'));

        if (!$user = $this->authenticator->attempt(...$credentials)) {
//            throw new AuthenticationException();
            return response()->json([
                'message' => 'Incorrect Account or Password'
            ], 401);
        }

        $token = $user->createToken(ucfirst($credentials[2]) . ' Token')->accessToken;

        return response()->json([
            'message' => ucfirst($credentials[2]) . ' Login Success',
            'user' => $user,
            'access_token' => $token,
        ], 200);
    }

    public function logout(Request $request)
    {
        // [CHECK VALIDATION]
        $validator = Validator::make($request->all(), [
            'guard' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], 422);
        }

        if (!Auth::guard($request->guard)->check()) {
            return response()->json([
                'message' => 'Access Denied'
            ], 401);
        }

        $request->user($request->guard)->token()->revoke();
        Auth::guard()->logout();
        Session::flush();

        return response()->json([
            'message' => ucfirst($request->guard) . ' Logout Success',
        ], 200);
    }
}
