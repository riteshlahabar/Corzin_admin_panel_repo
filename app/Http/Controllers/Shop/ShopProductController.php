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
            'features' => 'nullable|string',
            'image' => 'nullable|image|max:5120',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|max:5120',
            'is_active' => 'nullable|boolean',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->storeImage($request->file('image'));
        }

        $gallery = [];
        if ($request->hasFile('gallery_images')) {
            foreach ($request->file('gallery_images') as $file) {
                $gallery[] = $this->storeImage($file);
            }
        }

        ShopProduct::create([
            ...$data,
            'category' => strtolower(trim($data['category'])),
            'features' => $data['features'] ?? null,
            'image' => $imagePath,
            'gallery_images' => ! empty($gallery) ? $gallery : null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('shop.index')->with('success', 'Shop product saved successfully.');
    }

    protected function storeImage($file): string
    {
        $directory = public_path('assets/shop_images');
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $filename = 'shop_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'assets/shop_images/'.$filename;
    }
}
