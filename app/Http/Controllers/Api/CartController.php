<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Public\Part;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $cart = $user->activeCart()->with('items.part')->first();

        if (!$cart) {
            return ApiResponse::success([
                'items' => [],
                'summary' => [
                    'totalItems' => 0,
                    'totalPrice' => 0
                ]
            ]);
        }

        return ApiResponse::success([
            'items' => $cart->items->map(function($item) {
                $image = \App\Models\Product::where('part_number', $item->part_number)->first();
                
                $imageUrl = 'https://ik.imagekit.io/zlt25mb52fx/ahmcdn/uploads/hgp/thumbnail/fungsi-cairan-pendingin-coolant-image.png';
                if ($image && $image->image) {
                    $imageUrl = url('storage/' . $image->image);
                }
                
                return [
                    'id' => (string) $item->id,
                    'partId' => (string) $item->part_number,
                    'partNumber' => $item->part_number,
                    'name' => $item->part->nm_part ?? '-',
                    'image' => $imageUrl,
                    'price' => (float) $item->price,
                    'quantity' => $item->qty,
                    'subtotal' => (float) $item->subtotal,
                    'isReady' => true,
                ];
            }),
            'summary' => [
                'totalItems' => $cart->totalItems,
                'totalPrice' => (float) $cart->total
            ]
        ]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'partNumber' => 'required|string',
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $part = Part::where('kd_part', $request->partNumber)->firstOrFail();

        $cart = $user->activeCart()->firstOrCreate([
            'user_id' => $user->id,
            'status' => 'active'
        ]);

        $cartItem = $cart->items()->where('part_number', $part->kd_part)->first();

        if ($cartItem) {
            $cartItem->qty += $request->quantity;
            $cartItem->save();
        } else {
            $cartItem = $cart->items()->create([
                'part_number' => $part->kd_part,
                'qty' => $request->quantity,
                'price' => $part->het,
                'discount' => 0,
            ]);
        }

        return ApiResponse::success([
            'cartItemId' => (string) $cartItem->id,
            'totalItems' => $cart->fresh()->totalItems
        ], 'Item added to cart');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $cartItem = CartItem::whereHas('cart', function($q) use ($user) {
            $q->where('user_id', $user->id)->where('status', 'active');
        })->findOrFail($id);

        $cartItem->qty = $request->quantity;
        $cartItem->save();

        return ApiResponse::success(null, 'Cart updated');
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $cartItem = CartItem::whereHas('cart', function($q) use ($user) {
            $q->where('user_id', $user->id)->where('status', 'active');
        })->findOrFail($id);

        $cartItem->delete();

        return ApiResponse::success(null, 'Item removed from cart');
    }

    public function clear(Request $request)
    {
        $user = $request->user();
        $cart = $user->activeCart()->first();

        if ($cart) {
            $cart->items()->delete();
        }

        return ApiResponse::success(null, 'Cart cleared');
    }
}
