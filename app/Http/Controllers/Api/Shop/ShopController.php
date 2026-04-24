<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Farmer\Farmer;
use App\Models\Shop\ShopAdminNotification;
use App\Models\Shop\ShopOrder;
use App\Models\Shop\ShopOrderItem;
use App\Models\Shop\ShopProduct;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShopController extends Controller
{
    public function __construct(protected FirebaseService $firebaseService)
    {
    }

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

        $products = $query->get()->map(fn (ShopProduct $product) => $this->productPayload($product));

        return response()->json([
            'status' => true,
            'message' => 'Shop products fetched successfully',
            'data' => $products,
        ]);
    }

    public function prescriptionProducts(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $products = ShopProduct::query()
            ->where('is_active', true)
            ->get();

        $matched = [];
        $unmatched = [];
        foreach ($data['items'] as $item) {
            $requestedName = trim((string) ($item['name'] ?? ''));
            if ($requestedName === '') {
                continue;
            }

            $quantity = (int) ($item['quantity'] ?? 1);
            if ($quantity <= 0) {
                $quantity = 1;
            }

            $product = $this->matchPrescriptionProduct($products, $requestedName);
            if ($product instanceof ShopProduct) {
                $matched[] = [
                    'requested_name' => $requestedName,
                    'quantity' => $quantity,
                    'product' => $this->productPayload($product),
                ];
            } else {
                $unmatched[] = $requestedName;
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Prescription products matched successfully.',
            'data' => [
                'matched' => $matched,
                'unmatched' => array_values(array_unique($unmatched)),
            ],
        ]);
    }

    public function placeOrder(Request $request)
    {
        $data = $request->validate([
            'farmer_id' => ['required', 'exists:farmers,id'],
            'shipping_address' => ['required', 'string'],
            'payment_method' => ['nullable', 'in:cod'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:shop_products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $farmer = Farmer::findOrFail((int) $data['farmer_id']);
        $paymentMethod = $data['payment_method'] ?? 'cod';
        $requestedItems = collect($data['items']);
        $productIds = $requestedItems->pluck('product_id')->map(fn ($id) => (int) $id)->unique()->values();
        $products = ShopProduct::query()
            ->whereIn('id', $productIds->all())
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        if ($products->count() !== $productIds->count()) {
            return response()->json([
                'status' => false,
                'message' => 'Some products are unavailable.',
            ], 422);
        }

        $subtotal = 0.0;
        $lineItems = [];
        foreach ($requestedItems as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $qty = (int) ($row['quantity'] ?? 1);
            /** @var ShopProduct $product */
            $product = $products[$productId];
            $price = (float) $product->price;
            $lineTotal = $price * $qty;
            $subtotal += $lineTotal;

            $lineItems[] = [
                'shop_product_id' => $product->id,
                'product_name' => $product->name,
                'price' => $price,
                'quantity' => $qty,
                'line_total' => $lineTotal,
                'unit' => $product->unit,
            ];
        }

        $deliveryCharge = 0.0;
        $total = $subtotal + $deliveryCharge;

        $order = DB::transaction(function () use ($farmer, $data, $paymentMethod, $subtotal, $deliveryCharge, $total, $lineItems) {
            $order = ShopOrder::create([
                'farmer_id' => $farmer->id,
                'farmer_name' => trim(($farmer->first_name ?? '').' '.($farmer->last_name ?? '')),
                'farmer_phone' => $farmer->mobile ?? null,
                'shipping_address' => trim((string) $data['shipping_address']),
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
                'status' => 'placed',
                'subtotal' => $subtotal,
                'delivery_charge' => $deliveryCharge,
                'total' => $total,
            ]);

            foreach ($lineItems as $item) {
                ShopOrderItem::create([
                    'shop_order_id' => $order->id,
                    ...$item,
                ]);
            }

            return $order->load('items');
        });

        $this->firebaseService->sendToDevice(
            $farmer->fcm_token ?? null,
            'Order Placed',
            'Your order #'.$order->id.' has been placed successfully.',
            [
                'type' => 'shop_order',
                'event' => 'created',
                'order_id' => (string) $order->id,
                'status' => (string) $order->status,
                'payment_status' => (string) ($order->payment_status ?? 'pending'),
            ]
        );

        ShopAdminNotification::create([
            'shop_order_id' => $order->id,
            'title' => 'New Shop Order',
            'message' => 'Farmer '.$farmer->first_name.' created order #'.$order->id.'.',
            'is_read' => false,
        ]);
        $this->firebaseService->sendToWebAdmins(
            'New Shop Order',
            'Order #'.$order->id.' created by '.$farmer->first_name.'.',
            [
                'type' => 'web_admin',
                'event' => 'shop_order_created',
                'order_id' => (string) $order->id,
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Order placed successfully.',
            'data' => [
                'order_id' => $order->id,
                'status' => $order->status,
                'payment_method' => strtoupper($order->payment_method),
                'subtotal' => (float) $order->subtotal,
                'delivery_charge' => (float) $order->delivery_charge,
                'total' => (float) $order->total,
                'created_at' => optional($order->created_at)->toIso8601String(),
            ],
        ], 201);
    }

    public function farmerOrders(Farmer $farmer)
    {
        $orders = ShopOrder::query()
            ->with('items')
            ->where('farmer_id', $farmer->id)
            ->latest()
            ->get()
            ->map(function (ShopOrder $order) {
                return [
                    'id' => $order->id,
                    'status' => $order->status,
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status ?: 'pending',
                    'shipping_address' => $order->shipping_address,
                    'subtotal' => (float) $order->subtotal,
                    'delivery_charge' => (float) $order->delivery_charge,
                    'total' => (float) $order->total,
                    'created_at' => optional($order->created_at)->toIso8601String(),
                    'items' => $order->items->map(function (ShopOrderItem $item) {
                        return [
                            'product_name' => $item->product_name,
                            'price' => (float) $item->price,
                            'quantity' => (int) $item->quantity,
                            'line_total' => (float) $item->line_total,
                            'unit' => $item->unit,
                        ];
                    })->values(),
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Shop orders fetched successfully',
            'data' => $orders,
        ]);
    }

    protected function productPayload(ShopProduct $product): array
    {
        $gallery = collect($product->gallery_images ?? [])
            ->filter()
            ->map(function ($path) {
                if (! is_string($path)) {
                    return null;
                }
                if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                    return $path;
                }

                return asset($path);
            })
            ->filter()
            ->values()
            ->all();

        if ($product->image_url) {
            array_unshift($gallery, $product->image_url);
            $gallery = array_values(array_unique(array_filter($gallery)));
        }

        return [
            'id' => $product->id,
            'category' => $product->category,
            'name' => $product->name,
            'subtitle' => $product->subtitle,
            'price' => (float) $product->price,
            'price_label' => 'Rs '.number_format((float) $product->price, 2),
            'unit' => $product->unit,
            'description' => $product->description,
            'features' => collect(preg_split("/\r\n|\n|\r/", (string) ($product->features ?? '')))
                ->map(fn ($line) => trim((string) $line))
                ->filter()
                ->values()
                ->all(),
            'image_url' => $product->image_url,
            'gallery_image_urls' => $gallery,
        ];
    }

    protected function matchPrescriptionProduct($products, string $requestedName): ?ShopProduct
    {
        $needle = $this->normalizeForMatching($requestedName);
        if ($needle === '') {
            return null;
        }

        $exactMedicine = $products->first(function (ShopProduct $product) use ($needle) {
            return strtolower((string) $product->category) === 'medicine'
                && $this->normalizeForMatching((string) $product->name) === $needle;
        });
        if ($exactMedicine instanceof ShopProduct) {
            return $exactMedicine;
        }

        $exactAny = $products->first(fn (ShopProduct $product) => $this->normalizeForMatching((string) $product->name) === $needle);
        if ($exactAny instanceof ShopProduct) {
            return $exactAny;
        }

        $containsMedicine = $products->first(function (ShopProduct $product) use ($needle) {
            if (strtolower((string) $product->category) !== 'medicine') {
                return false;
            }

            return $this->containsMatch($needle, (string) $product->name)
                || $this->containsMatch($needle, (string) $product->subtitle)
                || $this->containsMatch($needle, (string) $product->description);
        });
        if ($containsMedicine instanceof ShopProduct) {
            return $containsMedicine;
        }

        $containsAny = $products->first(function (ShopProduct $product) use ($needle) {
            return $this->containsMatch($needle, (string) $product->name)
                || $this->containsMatch($needle, (string) $product->subtitle)
                || $this->containsMatch($needle, (string) $product->description);
        });

        return $containsAny instanceof ShopProduct ? $containsAny : null;
    }

    protected function containsMatch(string $needle, string $haystack): bool
    {
        $normalizedHaystack = $this->normalizeForMatching($haystack);
        if ($normalizedHaystack === '') {
            return false;
        }

        if ($needle === $normalizedHaystack) {
            return true;
        }

        if (Str::length($needle) < 3) {
            return false;
        }

        return Str::contains($normalizedHaystack, $needle)
            || Str::contains($needle, $normalizedHaystack);
    }

    protected function normalizeForMatching(string $value): string
    {
        $normalized = Str::of($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/i', ' ')
            ->squish()
            ->toString();

        return trim($normalized);
    }
}
