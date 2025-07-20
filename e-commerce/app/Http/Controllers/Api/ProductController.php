<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->get(); // Eager load category
        return response()->json(['status' => 'success', 'data' => $products], 200);
    }

    public function featured()
    {
        $featuredProducts = Product::where('is_featured', true)->with('category')->get();
        return response()->json(['status' => 'success', 'data' => $featuredProducts], 200);
    }

    public function show(string $id)
    {
        $product = Product::with('category')->find($id);
        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Product not found'], 404);
        }
        return response()->json(['status' => 'success', 'data' => $product], 200);
    }

    // Admin-only: Store a new product
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:categories,id',
            'name_en' => 'required|string|max:255',
            'name_hi' => 'nullable|string|max:255',
            'description_en' => 'nullable|string',
            'description_hi' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image_url' => 'nullable|url|max:255',
            'stock' => 'required|integer|min:0',
            'is_featured' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $product = Product::create($request->all());
        return response()->json(['status' => 'success', 'message' => 'Product created', 'data' => $product], 201);
    }

    // Admin-only: Update a product
    public function update(Request $request, string $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Product not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:categories,id',
            'name_en' => 'sometimes|required|string|max:255',
            'name_hi' => 'nullable|string|max:255',
            'description_en' => 'nullable|string',
            'description_hi' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'image_url' => 'nullable|url|max:255',
            'stock' => 'sometimes|required|integer|min:0',
            'is_featured' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $product->update($request->all());
        return response()->json(['status' => 'success', 'message' => 'Product updated', 'data' => $product], 200);
    }

    // Admin-only: Delete a product
    public function destroy(string $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Product not found'], 404);
        }
        $product->delete();
        return response()->json(['status' => 'success', 'message' => 'Product deleted'], 200);
    }
}
