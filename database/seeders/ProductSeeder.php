<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\StockMutation;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Makanan & Minuman',
                'description' => 'Produk konsumsi harian dan cemilan',
                'products' => [
                    ['sku' => 'FNB-001', 'name' => 'Kopi Arabika 250gr', 'price' => 45000, 'stock' => 30],
                    ['sku' => 'FNB-002', 'name' => 'Teh Melati Premium', 'price' => 20000, 'stock' => 50],
                    ['sku' => 'FNB-003', 'name' => 'Keripik Singkong Balado', 'price' => 15000, 'stock' => 25],
                ],
            ],
            [
                'name' => 'Alat Tulis Kantor',
                'description' => 'Perlengkapan alat tulis dan kantor',
                'products' => [
                    ['sku' => 'ATK-001', 'name' => 'Buku Catatan A5 Spiral', 'price' => 25000, 'stock' => 40],
                    ['sku' => 'ATK-002', 'name' => 'Pulpen Gel Hitam 0.5mm', 'price' => 5000, 'stock' => 100],
                    ['sku' => 'ATK-003', 'name' => 'Kertas HVS A4 80gr Rim', 'price' => 55000, 'stock' => 15],
                ],
            ],
            [
                'name' => 'Elektronik & Aksesoris',
                'description' => 'Aksesoris gadget dan elektronik ringkas',
                'products' => [
                    ['sku' => 'ELK-001', 'name' => 'Kabel Data Type-C Fast Charging', 'price' => 35000, 'stock' => 20],
                    ['sku' => 'ELK-002', 'name' => 'Powerbank 10000mAh', 'price' => 150000, 'stock' => 10],
                    ['sku' => 'ELK-003', 'name' => 'Mouse Wireless Ergonomis', 'price' => 85000, 'stock' => 8],
                ],
            ],
            [
                'name' => 'Pakaian & Fashion',
                'description' => 'Pakaian santai dan aksesoris fashion',
                'products' => [
                    ['sku' => 'FSH-001', 'name' => 'Kaos Polos Cotton Combed 30s', 'price' => 65000, 'stock' => 35],
                    ['sku' => 'FSH-002', 'name' => 'Topi Baseball Katun', 'price' => 40000, 'stock' => 18],
                ],
            ],
        ];

        foreach ($categories as $catData) {
            $products = $catData['products'];
            unset($catData['products']);

            $category = Category::create($catData);

            foreach ($products as $prodData) {
                $product = $category->products()->create([
                    'sku' => $prodData['sku'],
                    'name' => $prodData['name'],
                    'description' => 'Deskripsi untuk '.$prodData['name'],
                    'price' => $prodData['price'],
                    'stock' => $prodData['stock'],
                ]);

                StockMutation::create([
                    'product_id' => $product->id,
                    'type' => 'in',
                    'quantity' => $prodData['stock'],
                    'notes' => 'Stok awal gudang',
                ]);
            }
        }
    }
}
