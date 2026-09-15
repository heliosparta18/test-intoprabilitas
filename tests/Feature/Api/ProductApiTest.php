<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can list products with pagination', function () {
    Product::factory()->count(5)->create();

    $response = $this->getJson('/api/products');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'sku', 'name', 'price', 'stock', 'category'],
            ],
            'links',
            'meta',
        ]);
});

test('can filter products by category', function () {
    $categoryA = Category::factory()->create();
    $categoryB = Category::factory()->create();

    Product::factory()->count(2)->create(['category_id' => $categoryA->id]);
    Product::factory()->count(3)->create(['category_id' => $categoryB->id]);

    $response = $this->getJson("/api/products?category_id={$categoryA->id}");

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can search products by name or sku', function () {
    Product::factory()->create(['name' => 'Kopi Robusta', 'sku' => 'KPB-001']);
    Product::factory()->create(['name' => 'Teh Manis', 'sku' => 'THM-002']);

    $response = $this->getJson('/api/products?search=Robusta');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Kopi Robusta');
});

test('can create a product and generate initial stock mutation', function () {
    $category = Category::factory()->create();

    $payload = [
        'category_id' => $category->id,
        'sku' => 'TEST-001',
        'name' => 'Produk Baru',
        'description' => 'Ini deskripsi produk baru',
        'price' => 25000,
        'stock' => 10,
    ];

    $response = $this->postJson('/api/products', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.sku', 'TEST-001')
        ->assertJsonPath('data.stock', 10);

    $this->assertDatabaseHas('products', [
        'sku' => 'TEST-001',
        'stock' => 10,
    ]);

    $this->assertDatabaseHas('stock_mutations', [
        'type' => 'in',
        'quantity' => 10,
        'notes' => 'Stok awal produk saat dibuat',
    ]);
});

test('validates sku uniqueness when creating a product', function () {
    Product::factory()->create(['sku' => 'DUP-001']);

    $response = $this->postJson('/api/products', [
        'sku' => 'DUP-001',
        'name' => 'Another Item',
        'price' => 10000,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['sku']);
});

test('can show single product detail', function () {
    $product = Product::factory()->create();

    $response = $this->getJson("/api/products/{$product->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $product->id);
});

test('can update a product', function () {
    $product = Product::factory()->create(['price' => 10000]);

    $response = $this->putJson("/api/products/{$product->id}", [
        'price' => 15000,
        'name' => 'Produk Diperbarui',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.price', 15000)
        ->assertJsonPath('data.name', 'Produk Diperbarui');

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'price' => 15000,
    ]);
});

test('can delete a product', function () {
    $product = Product::factory()->create();

    $response = $this->deleteJson("/api/products/{$product->id}");

    $response->assertOk()
        ->assertJson(['message' => 'Produk berhasil dihapus.']);

    $this->assertDatabaseMissing('products', [
        'id' => $product->id,
    ]);
});
