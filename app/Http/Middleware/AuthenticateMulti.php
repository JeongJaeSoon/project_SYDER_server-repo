<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthenticateMulti
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $validator = Validator::make($request->all(), [
            'guard' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], 422);
        }

        if (!($request->guard === 'admin' || $request->guard === 'user')) {
            return response()->json([
                'message' => 'Bad guard information',
            ], 403);
        }

        if (!Auth::guard($request->guard)->check()) {
            return response()->json([
                'message' => 'Access Denied'
            ], 401);
        }
        return $next($request);
    }
}
