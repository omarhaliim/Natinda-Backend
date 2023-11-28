<?php

namespace App\Http\Controllers\API\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\Response;
use App\Models\Cart;
use App\Models\Order;
use App\Models\PromoCode;
use App\Models\Configuration;


class OrderController extends Controller
{

    /**
     * Create a new order based on the request data.
     *
     * @param Request $request The HTTP request object containing order details.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the order creation.
     */

    public function createOrder(Request $request)
    {

        $cart_id = $request->input('cart_id');

        if (!$cart_id) {
            return response()->json(['error' => 'Invalid cart ID.'], Response::HTTP_BAD_REQUEST);
        }
        $cart = Cart::find($cart_id);
        if (!$cart) {
            return response()->json(['error' => 'Cart is not found.'], Response::HTTP_BAD_REQUEST);
        }
        $authUser = $cart->user_id;

        $user = User::find($authUser);
        if ($cart->promocode_id) {
            $promoCode = PromoCode::find($cart->promocode_id);
            if (!$promoCode) {
                return response()->json(['error' => 'Invalid promo code.'], Response::HTTP_BAD_REQUEST);
            }
            $promoCode->decrement('max_number_of_used');
        }
        if ($cart->points) {
            if (!$user) {
                return response()->json(['error' => 'User is not found.'], Response::HTTP_BAD_REQUEST);
            }
            $user->decrement('points', $cart->points);
        }
        $order = Order::create([
            'user_id' => $authUser ?? null,
            'shipping_address_id' => $cart->shipping_address_id,
            'subtotal' => $cart->subtotal,
            'shipping_fees' => $cart->shipping_fees,
            'tax' => $cart->tax,
            'promocode_id' => $cart->promocode_id,
            'promocode_price' => $cart->promocode_price,
            'points' => $cart->points,
            'points_price' => $cart->points_price,
            'total_price' => $cart->total_price,
            'is_paid' => 0,
            'status' => 1
        ]);
        // Cash back
        $discountPointsPercentage = Configuration::getDefaultConfiguration(Configuration::DISCOUNT_POINTS_PERCENTAGE);
        $riyalToPoint = Configuration::getDefaultConfiguration(Configuration::RIYAL_TO_POINT);

        if ($user) {
            if($order->promocode_id)
            {
                $cashbackamount= (($order->subtotal -$order->promocode_price) *($discountPointsPercentage/100))*$riyalToPoint;
                $user->increment('points', $cashbackamount);
                return response()->json(['message' => $user], Response::HTTP_CREATED);

            }
            elseif($order->points)
            {
                $cashbackamount= (($order->subtotal -$order->points_price) *($discountPointsPercentage/100))*$riyalToPoint;
                $user->increment('points', $cashbackamount);
                return response()->json(['message' => $user], Response::HTTP_CREATED);

            }
            else
            {
                $cashbackamount= (($order->subtotal) *($discountPointsPercentage/100))*$riyalToPoint;
                $user->increment('points', $cashbackamount);
                return response()->json(['message' => $user], Response::HTTP_CREATED);
            }

        }
        return response()->json(['message' => "Order Successful"], Response::HTTP_CREATED);

    }

    public function viewOrderDetials(Request $request)
    {
        $authUser = getTokenUserId($request->header('Authorization'));
        $order_id = $request->input('order_id');

        if (!$order_id) {
            return response()->json(['error' => 'Invalid Order ID.'], Response::HTTP_BAD_REQUEST);
        }

        $order = Order::find($order_id);
        if (!$order) {
            $message = 'Not Found.';
            return response()->json(['message' => $message], 404);
        }
        $orderDetail = [
            'user_id' => $authUser,
            'cart_id' => $order->cart->id,
            'is_paid' => $order->is_paid,
            'status' => $order->status,
            'subtotal' => $order->cart->subtotal,
            'shipping_fees' => $order->cart->shipping_fees,
            'tax' => $order->cart->tax,
            'promocode' => $order->cart->promocode,
            'pointing_system' => $order->cart->pointing_system,
            'Total_price' => $order->cart->Total_price,
            'shipping_address_id' => $order->cart->shipping_address_id,

        ];

        return response()->json(['orderDetail' => $orderDetail], Response::HTTP_OK);
    }

    public function viewOrders(Request $request)
    {
        $authUser = getTokenUserId($request->header('Authorization'));

        $orders = Order::with('cart')->where('user_id', $authUser)->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'No orders found for the user.'], Response::HTTP_NOT_FOUND);
        }
        $orderDetails = [];
        foreach ($orders as $order) {
            $orderDetails[] = [
                'order_id' => $order->id,
                'cart_id' => $order->cart->id,
                'is_paid' => $order->is_paid,
                'status' => $order->status,
                'subtotal' => $order->cart->subtotal,
                'shipping_fees' => $order->cart->shipping_fees,
                'tax' => $order->cart->tax,
                'promocode' => $order->cart->promocode,
                'pointing_system' => $order->cart->pointing_system,
                'Total_price' => $order->cart->Total_price,
                'shipping_address_id' => $order->cart->shipping_address_id,
            ];
        }

        return response()->json(['orders' => $orderDetails], Response::HTTP_OK);
    }
}


