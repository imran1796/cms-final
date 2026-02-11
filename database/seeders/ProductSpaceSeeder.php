<?php

namespace Database\Seeders;

use App\Models\Space;
use App\Models\Collection;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class ProductSpaceSeeder extends Seeder
{
    /**
     * Seed the product space with collections and entries.
     */
    public function run(): void
    {
        // Get the first admin user for created_by/updated_by
        $admin = User::first();

        // Create or get the Products Space
        $productSpace = Space::firstOrCreate(
            ['handle' => 'products'],
            [
                'name' => 'Products Space',
                'settings' => [
                    'description' => 'E-commerce products and catalog management',
                    'currency' => 'USD',
                    'tax_rate' => 0.08,
                ],
                'storage_prefix' => 'spaces/products',
                'is_active' => true,
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ]
        );

        $spaceId = $productSpace->id;

        // Create Categories Collection
        $categoriesCollection = Collection::firstOrCreate(
            ['space_id' => $spaceId, 'handle' => 'categories'],
            [
                'type' => 'collection',
                'fields' => [
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'name',
                        'label' => 'Category Name',
                        'type' => 'text',
                        'required' => true,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'slug',
                        'label' => 'Slug',
                        'type' => 'text',
                        'required' => true,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'description',
                        'label' => 'Description',
                        'type' => 'textarea',
                        'required' => false,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'image',
                        'label' => 'Category Image',
                        'type' => 'text',
                        'required' => false,
                    ],
                ],
                'settings' => [
                    'drafts' => true,
                    'singleton' => false,
                ],
            ]
        );

        // Create Brands Collection
        $brandsCollection = Collection::firstOrCreate(
            ['space_id' => $spaceId, 'handle' => 'brands'],
            [
                'type' => 'collection',
                'fields' => [
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'name',
                        'label' => 'Brand Name',
                        'type' => 'text',
                        'required' => true,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'slug',
                        'label' => 'Slug',
                        'type' => 'text',
                        'required' => true,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'logo',
                        'label' => 'Logo URL',
                        'type' => 'text',
                        'required' => false,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'description',
                        'label' => 'Description',
                        'type' => 'textarea',
                        'required' => false,
                    ],
                ],
                'settings' => [
                    'drafts' => true,
                    'singleton' => false,
                ],
            ]
        );

        // Create Products Collection
        $productsCollection = Collection::firstOrCreate(
            ['space_id' => $spaceId, 'handle' => 'products'],
            [
                'type' => 'collection',
                'fields' => [
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'name',
                        'label' => 'Product Name',
                        'type' => 'text',
                        'required' => true,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'slug',
                        'label' => 'Slug',
                        'type' => 'text',
                        'required' => true,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'sku',
                        'label' => 'SKU',
                        'type' => 'text',
                        'required' => true,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'description',
                        'label' => 'Description',
                        'type' => 'richtext',
                        'required' => false,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'short_description',
                        'label' => 'Short Description',
                        'type' => 'textarea',
                        'required' => false,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'price',
                        'label' => 'Price',
                        'type' => 'number',
                        'required' => true,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'compare_at_price',
                        'label' => 'Compare at Price',
                        'type' => 'number',
                        'required' => false,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'images',
                        'label' => 'Product Images',
                        'type' => 'json',
                        'required' => false,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'category_id',
                        'label' => 'Category',
                        'type' => 'number',
                        'required' => false,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'brand_id',
                        'label' => 'Brand',
                        'type' => 'number',
                        'required' => false,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'stock_quantity',
                        'label' => 'Stock Quantity',
                        'type' => 'number',
                        'required' => false,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'in_stock',
                        'label' => 'In Stock',
                        'type' => 'boolean',
                        'required' => false,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'weight',
                        'label' => 'Weight (kg)',
                        'type' => 'number',
                        'required' => false,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'handle' => 'dimensions',
                        'label' => 'Dimensions',
                        'type' => 'json',
                        'required' => false,
                    ],
                ],
                'settings' => [
                    'drafts' => true,
                    'singleton' => false,
                ],
            ]
        );

        // Create sample categories
        $categories = [
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'Electronic devices and gadgets',
                'image' => '/images/categories/electronics.jpg',
            ],
            [
                'name' => 'Clothing',
                'slug' => 'clothing',
                'description' => 'Fashion and apparel',
                'image' => '/images/categories/clothing.jpg',
            ],
            [
                'name' => 'Home & Garden',
                'slug' => 'home-garden',
                'description' => 'Home decor and garden supplies',
                'image' => '/images/categories/home-garden.jpg',
            ],
            [
                'name' => 'Sports & Outdoors',
                'slug' => 'sports-outdoors',
                'description' => 'Sports equipment and outdoor gear',
                'image' => '/images/categories/sports-outdoors.jpg',
            ],
        ];

        $categoryEntries = [];
        foreach ($categories as $categoryData) {
            $categoryEntry = Entry::create([
                'space_id' => $spaceId,
                'collection_id' => $categoriesCollection->id,
                'status' => 'published',
                'published_at' => now(),
                'data' => $categoryData,
            ]);
            $categoryEntries[$categoryData['slug']] = $categoryEntry;
        }

        // Create sample brands
        $brands = [
            [
                'name' => 'TechCorp',
                'slug' => 'techcorp',
                'logo' => '/images/brands/techcorp-logo.png',
                'description' => 'Leading technology brand',
            ],
            [
                'name' => 'FashionHub',
                'slug' => 'fashionhub',
                'logo' => '/images/brands/fashionhub-logo.png',
                'description' => 'Premium fashion brand',
            ],
            [
                'name' => 'HomeStyle',
                'slug' => 'homestyle',
                'logo' => '/images/brands/homestyle-logo.png',
                'description' => 'Quality home and garden products',
            ],
        ];

        $brandEntries = [];
        foreach ($brands as $brandData) {
            $brandEntry = Entry::create([
                'space_id' => $spaceId,
                'collection_id' => $brandsCollection->id,
                'status' => 'published',
                'published_at' => now(),
                'data' => $brandData,
            ]);
            $brandEntries[$brandData['slug']] = $brandEntry;
        }

        // Create sample products
        $products = [
            [
                'name' => 'Wireless Bluetooth Headphones',
                'slug' => 'wireless-bluetooth-headphones',
                'sku' => 'ELEC-001',
                'description' => '<p>Premium wireless headphones with noise cancellation and 30-hour battery life. Perfect for music lovers and professionals.</p><ul><li>Active noise cancellation</li><li>30-hour battery life</li><li>Quick charge in 15 minutes</li><li>Premium sound quality</li></ul>',
                'short_description' => 'Premium wireless headphones with noise cancellation',
                'price' => 199.99,
                'compare_at_price' => 249.99,
                'images' => [
                    '/images/products/headphones-1.jpg',
                    '/images/products/headphones-2.jpg',
                    '/images/products/headphones-3.jpg',
                ],
                'category_id' => $categoryEntries['electronics']->id,
                'brand_id' => $brandEntries['techcorp']->id,
                'stock_quantity' => 50,
                'in_stock' => true,
                'weight' => 0.25,
                'dimensions' => ['length' => 20, 'width' => 18, 'height' => 8],
            ],
            [
                'name' => 'Smartphone Pro Max',
                'slug' => 'smartphone-pro-max',
                'sku' => 'ELEC-002',
                'description' => '<p>Latest flagship smartphone with advanced camera system, powerful processor, and all-day battery life.</p><ul><li>6.7-inch OLED display</li><li>Triple camera system (48MP)</li><li>8GB RAM, 256GB storage</li><li>5G connectivity</li></ul>',
                'short_description' => 'Flagship smartphone with advanced features',
                'price' => 999.99,
                'compare_at_price' => 1199.99,
                'images' => [
                    '/images/products/smartphone-1.jpg',
                    '/images/products/smartphone-2.jpg',
                ],
                'category_id' => $categoryEntries['electronics']->id,
                'brand_id' => $brandEntries['techcorp']->id,
                'stock_quantity' => 25,
                'in_stock' => true,
                'weight' => 0.21,
                'dimensions' => ['length' => 16, 'width' => 7.8, 'height' => 0.8],
            ],
            [
                'name' => 'Cotton T-Shirt Premium',
                'slug' => 'cotton-tshirt-premium',
                'sku' => 'CLOTH-001',
                'description' => '<p>High-quality cotton t-shirt with premium fabric. Comfortable, breathable, and perfect for everyday wear.</p><ul><li>100% organic cotton</li><li>Machine washable</li><li>Available in multiple colors</li><li>Classic fit</li></ul>',
                'short_description' => 'Premium cotton t-shirt for everyday wear',
                'price' => 29.99,
                'compare_at_price' => 39.99,
                'images' => [
                    '/images/products/tshirt-1.jpg',
                    '/images/products/tshirt-2.jpg',
                ],
                'category_id' => $categoryEntries['clothing']->id,
                'brand_id' => $brandEntries['fashionhub']->id,
                'stock_quantity' => 100,
                'in_stock' => true,
                'weight' => 0.15,
                'dimensions' => ['length' => 30, 'width' => 25, 'height' => 2],
            ],
            [
                'name' => 'Garden Tool Set',
                'slug' => 'garden-tool-set',
                'sku' => 'HOME-001',
                'description' => '<p>Complete garden tool set with everything you need for your gardening projects. Durable and ergonomic design.</p><ul><li>8-piece tool set</li><li>Stainless steel construction</li><li>Ergonomic handles</li><li>Storage case included</li></ul>',
                'short_description' => 'Complete 8-piece garden tool set',
                'price' => 79.99,
                'compare_at_price' => 99.99,
                'images' => [
                    '/images/products/garden-tools-1.jpg',
                ],
                'category_id' => $categoryEntries['home-garden']->id,
                'brand_id' => $brandEntries['homestyle']->id,
                'stock_quantity' => 30,
                'in_stock' => true,
                'weight' => 2.5,
                'dimensions' => ['length' => 40, 'width' => 30, 'height' => 10],
            ],
            [
                'name' => 'Yoga Mat Premium',
                'slug' => 'yoga-mat-premium',
                'sku' => 'SPORT-001',
                'description' => '<p>Non-slip yoga mat with extra cushioning. Perfect for yoga, pilates, and floor exercises.</p><ul><li>6mm thickness</li><li>Non-slip surface</li><li>Eco-friendly material</li><li>Carrying strap included</li></ul>',
                'short_description' => 'Non-slip premium yoga mat',
                'price' => 49.99,
                'compare_at_price' => null,
                'images' => [
                    '/images/products/yoga-mat-1.jpg',
                    '/images/products/yoga-mat-2.jpg',
                ],
                'category_id' => $categoryEntries['sports-outdoors']->id,
                'brand_id' => null,
                'stock_quantity' => 75,
                'in_stock' => true,
                'weight' => 1.2,
                'dimensions' => ['length' => 183, 'width' => 61, 'height' => 0.6],
            ],
            [
                'name' => 'Running Shoes Pro',
                'slug' => 'running-shoes-pro',
                'sku' => 'SPORT-002',
                'description' => '<p>Professional running shoes with advanced cushioning and breathable mesh upper. Ideal for long-distance running.</p><ul><li>Lightweight design</li><li>Advanced cushioning technology</li><li>Breathable mesh upper</li><li>Durable rubber outsole</li></ul>',
                'short_description' => 'Professional running shoes for athletes',
                'price' => 129.99,
                'compare_at_price' => 159.99,
                'images' => [
                    '/images/products/running-shoes-1.jpg',
                    '/images/products/running-shoes-2.jpg',
                    '/images/products/running-shoes-3.jpg',
                ],
                'category_id' => $categoryEntries['sports-outdoors']->id,
                'brand_id' => $brandEntries['fashionhub']->id,
                'stock_quantity' => 0,
                'in_stock' => false,
                'weight' => 0.3,
                'dimensions' => ['length' => 28, 'width' => 11, 'height' => 10],
            ],
            [
                'name' => 'Draft: Smart Watch Series 5',
                'slug' => 'smart-watch-series-5-draft',
                'sku' => 'ELEC-003',
                'description' => '<p>Upcoming smartwatch with advanced health tracking features. Coming soon!</p>',
                'short_description' => 'Advanced smartwatch with health tracking',
                'price' => 299.99,
                'compare_at_price' => null,
                'images' => [],
                'category_id' => $categoryEntries['electronics']->id,
                'brand_id' => $brandEntries['techcorp']->id,
                'stock_quantity' => 0,
                'in_stock' => false,
                'weight' => 0.05,
                'dimensions' => ['length' => 4, 'width' => 4, 'height' => 1],
            ],
        ];

        foreach ($products as $index => $productData) {
            Entry::create([
                'space_id' => $spaceId,
                'collection_id' => $productsCollection->id,
                'status' => $index === 6 ? 'draft' : 'published', // Last one is a draft
                'published_at' => $index === 6 ? null : now()->subDays(15 - $index),
                'data' => $productData,
            ]);
        }

        $this->command->info("Product space seeded successfully!");
        $this->command->info("Space ID: {$spaceId}");
        $this->command->info("Created {$categoriesCollection->handle} collection with " . count($categories) . " entries");
        $this->command->info("Created {$brandsCollection->handle} collection with " . count($brands) . " entries");
        $this->command->info("Created {$productsCollection->handle} collection with " . count($products) . " entries");
    }
}
