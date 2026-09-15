<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustStockRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockMutationResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class StockMutationController extends Controller
{
    /**
     * Display a listing of stock mutations for a product.
     */
    public function index(Request $request, Product $product): AnonymousResourceCollection
    {
        $mutations = $product->stockMutations()
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return StockMutationResource::collection($mutations);
    }

    /**
     * Record a stock mutation (in/out) and update the product stock.
     */
    public function store(AdjustStockRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();
        $quantity = (int) $data['quantity'];
        $type = $data['type'];

        if ($type === 'out' && $product->stock < $quantity) {
            return response()->json([
                'message' => 'Stok tidak mencukupi untuk melakukan pengurangan barang.',
                'current_stock' => $product->stock,
                'requested_quantity' => $quantity,
            ], 422);
        }

        $mutation = DB::transaction(function () use ($product, $type, $quantity, $data) {
            if ($type === 'in') {
                $product->increment('stock', $quantity);
            } else {
                $product->decrement('stock', $quantity);
            }

            return $product->stockMutations()->create([
                'type' => $type,
                'quantity' => $quantity,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return response()->json([
            'message' => 'Mutasi stok berhasil dicatat.',
            'mutation' => new StockMutationResource($mutation),
            'product' => new ProductResource($product->fresh()->load('category')),
        ], 201);
    }
}
