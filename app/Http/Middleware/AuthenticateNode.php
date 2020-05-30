<?php

namespace App\Http\Middleware;

use App\Model\Authenticator;
use Closure;

class AuthenticateNode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    /**
     * @var Authenticator
     */
    private $authenticator;

    public function __construct(Authenticator $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    public function handle($request, Closure $next)
    {
//        define("client", "master");
//        $client = client . "@node.js";
//        $ip = $request->ip();
//
//        $credentials = array_values(array($client, $ip, 'admins'));
//
//        if (!$this->authenticator->attempt(...$credentials)) {
//            return response()->json([
//                'message' => 'This is an inaccessible request',
//            ], 401);
//        }

        return $next($request);
    }
}
