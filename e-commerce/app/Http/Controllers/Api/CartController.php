<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CartController extends Controller
{
    // Helper to get or create a cart for the current user/session
    private function getOrCreateCart(Request $request)
    {
        if (Auth::check()) {
            return Cart::firstOrCreate(['user_id' => Auth::id()]);
        } else {
            $sessionId = $request->session()->get('cart_session_id');
            if (!$sessionId) {
                $sessionId = Str::uuid();
                $request->session()->put('cart_session_id', $sessionId);
            }
            return Cart::firstOrCreate(['session_id' => $sessionId]);
        }
    }

    public function index(Request $request)
    {
        $cart = $this->getOrCreateCart($request);
        $cart->load('items.product'); // Eager load cart items and their products
        return response()->json(['status' => 'success', 'data' => $cart], 200);
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = $this->getOrCreateCart($request);
        $product = Product::find($request->product_id);

        if ($product->stock < $request->quantity) {
            return response()->json(['status' => 'error', 'message' => 'Not enough stock available'], 400);
        }

        $cartItem = $cart->items()->where('product_id', $request->product_id)->first();

        if ($cartItem) {
            // Update quantity if item already exists
            $newQuantity = $cartItem->quantity + $request->quantity;
            if ($product->stock < $newQuantity) {
                return response()->json(['status' => 'error', 'message' => 'Adding this quantity exceeds available stock'], 400);
            }
            $cartItem->quantity = $newQuantity;
            $cartItem->save();
        } else {
            // Add new item to cart
            $cartItem = $cart->items()->create([
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
            ]);
        }

        $cart->load('items.product'); // Reload cart with updated items
        return response()->json(['status' => 'success', 'message' => 'Product added to cart', 'data' => $cart], 200);
    }

    public function updateQuantity(Request $request, string $itemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0', // 0 to remove item
        ]);

        $cart = $this->getOrCreateCart($request);
        $cartItem = $cart->items()->find($itemId);

        if (!$cartItem) {
            return response()->json(['status' => 'error', 'message' => 'Cart item not found'], 404);
        }

        if ($request->quantity === 0) {
            $cartItem->delete();
            $message = 'Product removed from cart';
        } else {
            $product = Product::find($cartItem->product_id);
            if ($product->stock < $request->quantity) {
                return response()->json(['status' => 'error', 'message' => 'Requested quantity exceeds available stock'], 400);
            }
            $cartItem->quantity = $request->quantity;
            $cartItem->save();
            $message = 'Cart item quantity updated';
        }

        $cart->load('items.product');
        return response()->json(['status' => 'success', 'message' => $message, 'data' => $cart], 200);
    }

    public function remove(Request $request, string $itemId)
    {
        $cart = $this->getOrCreateCart($request);
        $cartItem = $cart->items()->find($itemId);

        if (!$cartItem) {
            return response()->json(['status' => 'error', 'message' => 'Cart item not found'], 404);
        }

        $cartItem->delete();
        $cart->load('items.product');
        return response()->json(['status' => 'success', 'message' => 'Product removed from cart', 'data' => $cart], 200);
    }

    public function clear(Request $request)
    {
        $cart = $this->getOrCreateCart($request);
        $cart->items()->delete();
        $cart->load('items.product');
        return response()->json(['status' => 'success', 'message' => 'Cart cleared', 'data' => $cart], 200);
    }
}
