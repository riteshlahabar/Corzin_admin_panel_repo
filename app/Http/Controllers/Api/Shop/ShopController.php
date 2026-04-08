<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Shop\ShopProduct;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function categories()
    {
        $categories = ShopProduct::query()
            ->where('is_active', true)
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'Shop categories fetched successfully',
            'data' => $categories,
        ]);
    }

    public function products(Request $request)
    {
        $query = ShopProduct::query()
            ->where('is_active', true)
            ->latest();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('subtitle', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $products = $query->get()->map(function (ShopProduct $product) {
            return [
                'id' => $product->id,
                'category' => $product->category,
                'name' => $product->name,
                'subtitle' => $product->subtitle,
                'price' => (float) $product->price,
                'price_label' => 'Rs '.number_format((float) $product->price, 2),
                'unit' => $product->unit,
                'description' => $product->description,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Shop products fetched successfully',
            'data' => $products,
        ]);
    }
}
