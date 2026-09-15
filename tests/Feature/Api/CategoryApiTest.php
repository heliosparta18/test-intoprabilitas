<?php

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can list all categories', function () {
    Category::factory()->count(3)->create();

    $response = $this->getJson('/api/categories');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'description', 'created_at', 'updated_at'],
            ],
        ]);
});

test('can create a new category', function () {
    $payload = [
        'name' => 'Minuman Dingin',
        'description' => 'Aneka jus dan minuman es',
    ];

    $response = $this->postJson('/api/categories', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Minuman Dingin');

    $this->assertDatabaseHas('categories', [
        'name' => 'Minuman Dingin',
    ]);
});

test('validates duplicate category name', function () {
    Category::factory()->create(['name' => 'Snack']);

    $response = $this->postJson('/api/categories', [
        'name' => 'Snack',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

test('can show a category with products', function () {
    $category = Category::factory()->create();

    $response = $this->getJson("/api/categories/{$category->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $category->id);
});

test('can update a category', function () {
    $category = Category::factory()->create(['name' => 'Lama']);

    $response = $this->putJson("/api/categories/{$category->id}", [
        'name' => 'Baru',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Baru');

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Baru',
    ]);
});

test('can delete a category', function () {
    $category = Category::factory()->create();

    $response = $this->deleteJson("/api/categories/{$category->id}");

    $response->assertOk()
        ->assertJson(['message' => 'Kategori berhasil dihapus.']);

    $this->assertDatabaseMissing('categories', [
        'id' => $category->id,
    ]);
});
