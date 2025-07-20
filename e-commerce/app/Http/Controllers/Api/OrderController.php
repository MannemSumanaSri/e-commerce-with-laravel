<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        // Only authenticated users can view their orders
        if (!Auth::check()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        $orders = Auth::user()->orders()->with('items.product')->get();
        return response()->json(['status' => 'success', 'data' => $orders], 200);
    }

    public function show(string $id)
    {
        if (!Auth::check()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        $order = Auth::user()->orders()->with('items.product')->find($id);
        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
        }
        return response()->json(['status' => 'success', 'data' => $order], 200);
    }

    public function placeOrder(Request $request)
    {
        // This method simulates the final step after payment confirmation.
        // In a real scenario, a payment gateway webhook would trigger a similar process.

        $request->validate([
            'shipping_address' => 'required|string|max:255',
            'billing_address' => 'required|string|max:255',
            // 'payment_token' => 'required|string', // In a real app, this would be from payment gateway
        ]);

        if (!Auth::check()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized. Please log in to place an order.'], 401);
        }

        $user = Auth::user();
        $cart = $user->carts()->with('items.product')->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'Your cart is empty.'], 400);
        }

        DB::beginTransaction();
        try {
            $totalAmount = $cart->items->sum(function($item) {
                return $item->quantity * $item->product->price;
            }); // Calculate total from cart items

            // Create the order
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => 'ORD-' . Str::upper(Str::random(10)),
                'total_amount' => $totalAmount,
                'status' => 'processing', // Initial status after simulated payment
                'payment_status' => 'paid', // Simulated payment status
                'shipping_address' => $request->shipping_address,
                'billing_address' => $request->billing_address,
            ]);

            // Add items to the order
            foreach ($cart->items as $cartItem) {
                // Check stock before creating order item
                if ($cartItem->product->stock < $cartItem->quantity) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => 'Not enough stock for ' . $cartItem->product->name_en], 400);
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->product->price, // Price at the time of order
                ]);

                // Decrease product stock
                $cartItem->product->decrement('stock', $cartItem->quantity);
            }

            // Clear the user's cart after successful order
            $cart->items()->delete();
            $cart->delete(); // Delete the cart itself

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Order placed successfully!',
                'order' => $order->load('items.product')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to place order: ' . $e->getMessage()], 500);
        }
    }

    // You would also have webhook endpoints for actual payment gateway callbacks
    // public function handlePaymentWebhook(Request $request) { ... }
}

