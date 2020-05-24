<?php

namespace App\Http\Controllers;

use App\Cart;
use App\Order;
use App\Route;
use App\Model\Authenticator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * @var Authenticator
     */
    private $authenticator;

    public function __construct(Authenticator $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    // API : [GET] /api/order
    public function orderIndex(Request $request)
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

        if (!($request->guard === 'admin')) {
            return response()->json([
                'message' => 'This page is only accessible to admin',
            ], 403);
        }

        if (!Auth::guard($request->guard)->check()) {
            return response()->json([
                'message' => 'Access Denied'
            ], 401);
        }   // [Client Errors]

        $orders = Order::get();
        return response()->json([
            'message' => 'Orders Indexing Success',
            'orders' => $orders,
        ], 200);
    }

    // API : [POST] /api/order
    public function orderRegister(Request $request)
    {
        // [CHECK VALIDATION]
        $validator = Validator::make($request->all(), [
            'receiver' => 'required|numeric',
            'order_cart' => 'required|numeric',
            'order_route' => 'required|numeric',
            'reverse_direction' => 'required|boolean',
            'cartMove_needs' => 'required|boolean',
            'guard' => 'required|string',
        ]);

        // [Client Errors]
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], 422);
        }

        // [IF] Cart need to move to the starting point
        if ((bool)$request->cartMove_needs) {
            $validator = Validator::make($request->all(), [
                'cartMove_route' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                ], 422);
            }
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

        $sender = $request->user($request->guard);

        // [QUERY] Register order
        $order = Order::create([
            'status' => 100,
            'sender' => $sender->id,
            'receiver' => $request->receiver,
            'order_cart' => $request->order_cart,
            'order_route' => $request->order_route,
            'reverse_direction' => (boolean)$request->reverse_direction,
            'request_time' => now(),
        ]);

        return response()->json([
            'message' => 'Order Registration Success',
            'order' => $order,
        ], 201);
    }

    // API : [GET] /api/order/check
    public function orderCheck(Request $request)
    {
        // [CHECK VALIDADATION]
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

        $user_id = $request->user($request->guard)->id;

        $order = Order::where('sender', $user_id)
            ->orWhere('receiver', $user_id)
            ->get()
            ->where('status', '<>', 400)
            ->where('status', '<>', 401)
            ->where('status', '<>', 402);

        if ($order->count() === 1) {
            return response()->json([
                'message' => 'There is already a order in progress',
                'order' => $order->first(),
                'availability' => false,
            ], 200);
        } else if ($order->count() === 0) {
            return response()->json([
                'message' => 'There are no orders in progress',
                'availability' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Please contact the Admin',
        ], 404);
    }

// API : [GET] /api/order/show
    public function orderShow(Request $request)
    {
        // [CHECK VALIDATION]
        $validator = Validator::make($request->all(), [
            'starting_id' => 'required|numeric',
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

        // Check for available carts
        $remain_carts = Cart::select('id', 'status', 'cart_location')
            ->where('status', 110)
            ->get();

        // [IF] There is no available cart => RETURN
        if (!$remain_carts->count()) {
            $remain_orders = Order::where('status', 900)->get()->count();

            return response()->json([
                'message' => 'There is no available cart',
                'remain_order' => $remain_orders,
            ], 200);
        }

        // [IF] Cart is at the starting Point
        foreach ($remain_carts as $cart) {
            $cart_location = $cart->cart_location;

            if ($cart_location == $request->starting_id) {
                $cart->update(['status' => 111]);

                return response()->json([
                    'message' => 'Cart is ready for start',
                    'cart_id' => $cart->id,
                    'cartMove_needs' => false,
                ], 200);
            }
        }

        // [QUERY] Find cart at nearby waypoint
        $close_routes_first = Route::select('id', 'arrival_point', 'travel_time')
            ->where('starting_point', $request->starting_id);
        $close_routes = Route::select('id', 'starting_point as waypoint', 'travel_time')
            ->where('arrival_point', $request->starting_id)
            ->union($close_routes_first)
            ->orderBy('travel_time')
            ->get();

        foreach ($close_routes as $route) {
            foreach ($remain_carts as $cart) {

                // [IF] Cart is at the nearby starting waypoint
                if ($cart->cart_location == $route->waypoint) {
                    $cart->update(['status' => 111]);

                    return response()->json([
                        'message' => 'Cart is need to move',
                        'cart_id' => $cart->id,
                        'cartMove_needs' => true,
                        'cartMove_route' => $route->id,
                        'cartMove_time' => $route->travel_time,
                    ], 200);
                }
            }
        }
    }

    public function orderAuthentication(Request $request, Cart $cart)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|numeric',
            'user_id' => 'required|numeric',
            'user_category' => 'required|string',
            'guard' => 'required|string',
        ]);

        // [Client Errors]
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
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

        $sender = $receiver = $status = '';

        if ($request->user_category === 'sender') {
            $sender = $request->user($request->guard)->id;
            $receiver = $request->user_id;
            $status = 200;
        } else if ($request->user_category === 'receiver') {
            $sender = $request->user_id;
            $receiver = $request->user($request->guard)->id;
            $status = 201;
        }

        $order = Order::where('status', $status)
            ->where('sender', $sender)->where('receiver', $receiver)
            ->where('id', $request->order_id)->where('order_cart', $cart->id)
            ->get()->first();

        if ($order == null)
            return response()->json([
                'message' => 'This is an invalid order',
                'result' => false
            ], 404);

        return response()->json([
            'message' => 'This is a valid order',
            'result' => true
        ], 200);
    }

    public function orderUpdate(Request $request, Order $order)
    {
        define("client", "master");
        $client = client."@node.js";
        $ip = $request->ip();

        $credentials = array_values(array($client, $ip, 'admins'));

        if (!$this->authenticator->attempt(...$credentials)) {
            return response()->json([
                'message' => 'This is an inaccessible request',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'status_code' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        $status_codes = array(
            200, 201,
            300, 301,
            400,
        );

        if (!in_array((integer)$request->status_code, $status_codes)) {
            return response()->json([
                'message' => 'This is an invalid status code'
            ], 422);
        }

        $cart = Cart::where('id', $order->order_cart);
        $order_status = (integer)$request->status_code;
        $cart_status = $order_status + 10;

        $order->update(['status' => $order_status]);

        if ($order_status === 300)
            $order->update(['depart_time' => now()]);
        elseif ($order_status === 201)
            $order->update(['arrival_time' => now()]);

        if ($order_status === 400)
            $cart->update(['status' => 110]);
        else
            $cart->update(['status' => $cart_status]);

        return response()->json([
            'message' => 'Status Update Success'
        ]);
    }
}
