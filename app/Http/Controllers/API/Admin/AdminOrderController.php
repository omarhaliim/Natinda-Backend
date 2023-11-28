<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\Response;
use App\Models\Cart;
use App\Models\Order;
use App\Models\PromoCode;
use App\Models\Configuration;

class AdminOrderController extends Controller
{
    public function getOrder(Request $request)
    {
        $order = Order::all();
        return response()->json(['order' => $order], Response::HTTP_OK);
    }


}
