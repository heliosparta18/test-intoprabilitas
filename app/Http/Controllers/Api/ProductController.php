<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\StockMutation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource with optional filters and search.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $products = Product::query()
            ->with('category')
            ->when($request->filled('category_id'), function ($query) use ($request): void {
                $query->where('category_id', $request->integer('category_id'));
            })
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($request->boolean('low_stock'), function ($query): void {
                $query->where('stock', '<=', 5);
            })
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return ProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = DB::transaction(function () use ($request): Product {
            $data = $request->validated();
            $initialStock = (int) ($data['stock'] ?? 0);

            $product = Product::create($data);

            if ($initialStock > 0) {
                StockMutation::create([
                    'product_id' => $product->id,
                    'type' => 'in',
                    'quantity' => $initialStock,
                    'notes' => 'Stok awal produk saat dibuat',
                ]);
            }

            return $product;
        });

        return (new ProductResource($product->load('category')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): ProductResource
    {
        $product->load([
            'category',
            'stockMutations' => fn ($query) => $query->latest()->limit(10),
        ]);

        return new ProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product->update($request->validated());

        return new ProductResource($product->load('category'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'message' => 'Produk berhasil dihapus.',
        ], 200);
    }
}
