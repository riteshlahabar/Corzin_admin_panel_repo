<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Shop\ShopProduct;
use Illuminate\Http\Request;

class ShopProductController extends Controller
{
    public function index()
    {
        $products = ShopProduct::latest()->get();
        $summary = [
            'total' => $products->count(),
            'categories' => $products->pluck('category')->filter()->unique()->count(),
            'active' => $products->where('is_active', true)->count(),
        ];

        return view('shop.index', compact('products', 'summary'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'unit' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        ShopProduct::create([
            ...$data,
            'category' => strtolower(trim($data['category'])),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('shop.index')->with('success', 'Shop product saved successfully.');
    }
}
