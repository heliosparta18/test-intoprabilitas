<?php

use App\Models\Product;
use App\Models\StockMutation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can list stock mutations for a product', function () {
    $product = Product::factory()->create(['stock' => 10]);
    StockMutation::factory()->count(3)->create(['product_id' => $product->id]);

    $response = $this->getJson("/api/products/{$product->id}/mutations");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'product_id', 'type', 'quantity', 'notes', 'created_at'],
            ],
        ]);
});

test('can record stock in and increment product stock', function () {
    $product = Product::factory()->create(['stock' => 5]);

    $response = $this->postJson("/api/products/{$product->id}/stock", [
        'type' => 'in',
        'quantity' => 10,
        'notes' => 'Restock barang masuk dari gudang pusat',
    ]);

    $response->assertCreated()
        ->assertJsonPath('product.stock', 15)
        ->assertJsonPath('mutation.type', 'in')
        ->assertJsonPath('mutation.quantity', 10);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'stock' => 15,
    ]);

    $this->assertDatabaseHas('stock_mutations', [
        'product_id' => $product->id,
        'type' => 'in',
        'quantity' => 10,
        'notes' => 'Restock barang masuk dari gudang pusat',
    ]);
});

test('can record stock out and decrement product stock', function () {
    $product = Product::factory()->create(['stock' => 20]);

    $response = $this->postJson("/api/products/{$product->id}/stock", [
        'type' => 'out',
        'quantity' => 7,
        'notes' => 'Terjual offline di toko',
    ]);

    $response->assertCreated()
        ->assertJsonPath('product.stock', 13)
        ->assertJsonPath('mutation.type', 'out')
        ->assertJsonPath('mutation.quantity', 7);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'stock' => 13,
    ]);
});

test('fails when stock out exceeds current available stock', function () {
    $product = Product::factory()->create(['stock' => 3]);

    $response = $this->postJson("/api/products/{$product->id}/stock", [
        'type' => 'out',
        'quantity' => 5,
        'notes' => 'Mencoba ambil lebih banyak dari stok',
    ]);

    $response->assertUnprocessable()
        ->assertJson([
            'message' => 'Stok tidak mencukupi untuk melakukan pengurangan barang.',
            'current_stock' => 3,
            'requested_quantity' => 5,
        ]);

    // Ensure stock was not modified
    expect($product->fresh()->stock)->toBe(3);
});

test('validates type and quantity rules for stock mutation', function () {
    $product = Product::factory()->create();

    $response = $this->postJson("/api/products/{$product->id}/stock", [
        'type' => 'invalid_type',
        'quantity' => 0,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['type', 'quantity']);
});
