<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $user = $request->user();
        $cart = $user->activeCart()->first();
        $cartCount = $cart ? $cart->totalItems : 0;

        return ApiResponse::success([
            'deliveryProgress' => '0%',
            'monthlyBuyIn' => 'Rp 0',
            'cartCount' => $cartCount
        ]);
    }
}
