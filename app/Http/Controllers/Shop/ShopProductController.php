<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Shop\ShopAdminNotification;
use App\Models\Shop\ShopCategory;
use App\Models\Shop\ShopOrder;
use App\Models\Shop\ShopProduct;
use App\Models\Shop\ShopUnit;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShopProductController extends Controller
{
    public function __construct(protected FirebaseService $firebaseService)
    {
    }

    public function index()
    {
        $activeTab = request()->query('tab', 'add-product');
        $products = ShopProduct::latest()->get();
        $categories = $this->shopCategoryOptions();
        $units = $this->shopUnitOptions();
        $orders = ShopOrder::query()
            ->with(['items', 'farmer'])
            ->latest()
            ->get();

        $summary = [
            'total' => $products->count(),
            'categories' => $products->pluck('category')->filter()->unique()->count(),
            'active' => $products->where('is_active', true)->count(),
            'new_orders' => $orders->whereIn('status', ['placed', 'new', 'pending'])->count(),
            'in_progress_orders' => $orders->where('status', 'in_progress')->count(),
            'completed_orders' => $orders->where('status', 'completed')->count(),
            'payment_pending' => $orders->where('payment_status', 'pending')->count(),
        ];

        $newOrders = $orders->whereIn('status', ['placed', 'new', 'pending'])->values();
        $inProgressOrders = $orders->where('status', 'in_progress')->values();
        $completedOrders = $orders->where('status', 'completed')->values();
        $paymentOrders = $orders->values();
        $shopNotifications = ShopAdminNotification::query()
            ->latest()
            ->limit(5)
            ->get();

        return view('shop.index', compact(
            'products',
            'categories',
            'units',
            'summary',
            'newOrders',
            'inProgressOrders',
            'completedOrders',
            'paymentOrders',
            'activeTab',
            'shopNotifications',
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'hsn_code' => 'nullable|string|max:100',
            'subtitle' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'unit' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'features' => 'nullable|string',
            'medicine_aliases' => 'nullable|string|max:5000',
            'pack_size' => 'nullable|integer|min:1|max:5000',
            'allow_partial_units' => 'nullable|boolean',
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

        $normalizedCategory = $this->normalizeLookupName($data['category']);
        $normalizedUnit = $this->normalizeLookupName($data['unit'] ?? '');
        $isMedicine = $normalizedCategory === 'medicine';

        if ($normalizedCategory !== '') {
            ShopCategory::firstOrCreate(['name' => $normalizedCategory]);
        }

        if ($normalizedUnit !== '') {
            ShopUnit::firstOrCreate(['name' => $normalizedUnit]);
        }

        ShopProduct::create([
            ...$data,
            'category' => $normalizedCategory,
            'company_name' => $this->nullableTrim($data['company_name'] ?? null),
            'hsn_code' => $this->nullableTrim($data['hsn_code'] ?? null),
            'unit' => $normalizedUnit !== '' ? $normalizedUnit : null,
            'features' => $data['features'] ?? null,
            'medicine_aliases' => $isMedicine ? $this->nullableTrim($data['medicine_aliases'] ?? null) : null,
            'pack_size' => $data['pack_size'] ?? null,
            'allow_partial_units' => $isMedicine ? $request->boolean('allow_partial_units', false) : false,
            'image' => $imagePath,
            'gallery_images' => ! empty($gallery) ? $gallery : null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('shop.index', ['tab' => 'add-product'])
            ->with('success', 'Shop product saved successfully.')
            ->with('shop_product_saved', true);
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $name = $this->normalizeLookupName($data['name']);
        if ($name === '') {
            return redirect()->route('shop.index', ['tab' => 'add-product', 'modal' => 'add-product'])
                ->withErrors(['name' => 'Category name is required.'])
                ->withInput();
        }

        ShopCategory::firstOrCreate(['name' => $name]);

        return redirect()->route('shop.index', ['tab' => 'add-product', 'modal' => 'add-product', 'selected_category' => $name])
            ->with('success', 'Shop category saved successfully.');
    }

    public function updateCategory(Request $request, ShopCategory $category)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $name = $this->normalizeLookupName($data['name']);
        if ($name === '') {
            return redirect()->route('shop.index', ['tab' => 'add-product', 'modal' => 'add-product'])
                ->withErrors(['edit_category_name' => 'Category name is required.'])
                ->withInput();
        }

        if ($name !== $category->name && ShopCategory::where('name', $name)->exists()) {
            return redirect()->route('shop.index', ['tab' => 'add-product', 'modal' => 'add-product'])
                ->withErrors(['edit_category_name' => 'This category already exists.'])
                ->withInput();
        }

        DB::transaction(function () use ($category, $name) {
            $oldName = $category->name;
            $category->update(['name' => $name]);
            ShopProduct::query()->where('category', $oldName)->update(['category' => $name]);
        });

        return redirect()->route('shop.index', ['tab' => 'add-product', 'modal' => 'add-product', 'selected_category' => $name])
            ->with('success', 'Shop category updated successfully.');
    }

    public function storeUnit(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $name = $this->normalizeLookupName($data['name']);
        if ($name === '') {
            return redirect()->route('shop.index', ['tab' => 'add-product', 'modal' => 'add-product'])
                ->withErrors(['name' => 'Unit name is required.'])
                ->withInput();
        }

        ShopUnit::firstOrCreate(['name' => $name]);

        return redirect()->route('shop.index', ['tab' => 'add-product', 'modal' => 'add-product', 'selected_unit' => $name])
            ->with('success', 'Shop unit saved successfully.');
    }

    public function updateUnit(Request $request, ShopUnit $unit)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $name = $this->normalizeLookupName($data['name']);
        if ($name === '') {
            return redirect()->route('shop.index', ['tab' => 'add-product', 'modal' => 'add-product'])
                ->withErrors(['edit_unit_name' => 'Unit name is required.'])
                ->withInput();
        }

        if ($name !== $unit->name && ShopUnit::where('name', $name)->exists()) {
            return redirect()->route('shop.index', ['tab' => 'add-product', 'modal' => 'add-product'])
                ->withErrors(['edit_unit_name' => 'This unit already exists.'])
                ->withInput();
        }

        DB::transaction(function () use ($unit, $name) {
            $oldName = $unit->name;
            $unit->update(['name' => $name]);
            ShopProduct::query()->where('unit', $oldName)->update(['unit' => $name]);
        });

        return redirect()->route('shop.index', ['tab' => 'add-product', 'modal' => 'add-product', 'selected_unit' => $name])
            ->with('success', 'Shop unit updated successfully.');
    }

    public function updateOrderStatus(Request $request, ShopOrder $order)
    {
        $data = $request->validate([
            'status' => 'required|in:placed,in_progress,completed,cancelled',
            'payment_status' => 'nullable|in:pending,paid',
        ]);

        $nextStatus = $data['status'];
        $paymentStatus = $data['payment_status'] ?? $order->payment_status;
        if ($nextStatus === 'completed' && blank($data['payment_status'] ?? null) && strtolower((string) $order->payment_method) === 'cod') {
            $paymentStatus = 'paid';
        }

        $order->update([
            'status' => $nextStatus,
            'payment_status' => $paymentStatus,
        ]);

        $this->firebaseService->sendToDevice(
            optional($order->farmer)->fcm_token,
            'Shop Order Updated',
            'Order #'.$order->id.' is now '.$order->status.' (Payment: '.($order->payment_status ?? 'pending').').',
            [
                'type' => 'shop_order',
                'event' => 'updated',
                'order_id' => (string) $order->id,
                'status' => (string) $order->status,
                'payment_status' => (string) ($order->payment_status ?? 'pending'),
            ]
        );
        $this->firebaseService->sendToWebAdmins(
            'Shop Order Updated',
            'Order #'.$order->id.' is now '.$order->status.' (Payment: '.($order->payment_status ?? 'pending').').',
            [
                'type' => 'web_admin',
                'event' => 'shop_order_updated',
                'order_id' => (string) $order->id,
                'status' => (string) $order->status,
                'payment_status' => (string) ($order->payment_status ?? 'pending'),
            ]
        );

        return redirect()->route('shop.index', ['tab' => $request->input('tab', 'new-order')])
            ->with('success', 'Order status updated successfully.');
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

    protected function shopCategoryOptions()
    {
        return ShopCategory::query()
            ->pluck('name')
            ->merge(
                ShopProduct::query()
                    ->select('category')
                    ->pluck('category')
            )
            ->map(fn ($value) => $this->normalizeLookupName($value))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    protected function shopUnitOptions()
    {
        return ShopUnit::query()
            ->pluck('name')
            ->merge(
                ShopProduct::query()
                    ->select('unit')
                    ->pluck('unit')
            )
            ->map(fn ($value) => $this->normalizeLookupName($value))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    protected function normalizeLookupName($value): string
    {
        return Str::of((string) $value)->trim()->squish()->lower()->value();
    }

    protected function nullableTrim($value): ?string
    {
        $cleaned = Str::of((string) $value)->trim()->squish()->value();

        return $cleaned === '' ? null : $cleaned;
    }
}
