<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $vendors = User::where('role', 'vendor')->get();
        $categories = Category::whereNotNull('parent_id')->get();

        $products = [
            // Electronics - Smartphones
            [
                'name' => 'iPhone 15 Pro Max',
                'description' => 'The ultimate iPhone with A17 Pro chip, titanium design, and advanced camera system. Features 6.7-inch Super Retina XDR display with ProMotion technology.',
                'category' => 'smartphones',
                'price' => 1199.00,
                'sale_price' => 1099.00,
                'cost_price' => 800.00,
                'sku' => 'IPH-15PM',
                'vendor_index' => 0,
                'is_featured' => true,
                'variants' => [
                    ['name' => 'Natural Titanium - 256GB', 'sku' => 'IPH-15PM-NT-256', 'price' => 1199.00, 'stock' => 50],
                    ['name' => 'Blue Titanium - 256GB', 'sku' => 'IPH-15PM-BT-256', 'price' => 1199.00, 'stock' => 45],
                    ['name' => 'Natural Titanium - 512GB', 'sku' => 'IPH-15PM-NT-512', 'price' => 1399.00, 'stock' => 30],
                    ['name' => 'Black Titanium - 1TB', 'sku' => 'IPH-15PM-BK-1TB', 'price' => 1599.00, 'stock' => 20],
                ]
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'description' => 'Premium Android flagship with built-in S Pen, 200MP camera, and AI-powered features. 6.8-inch Dynamic AMOLED 2X display.',
                'category' => 'smartphones',
                'price' => 1299.00,
                'sale_price' => 1199.00,
                'cost_price' => 850.00,
                'sku' => 'SAM-S24U',
                'vendor_index' => 0,
                'is_featured' => true,
                'variants' => [
                    ['name' => 'Titanium Gray - 256GB', 'sku' => 'SAM-S24U-TG-256', 'price' => 1299.00, 'stock' => 40],
                    ['name' => 'Titanium Black - 512GB', 'sku' => 'SAM-S24U-TB-512', 'price' => 1499.00, 'stock' => 35],
                    ['name' => 'Titanium Violet - 512GB', 'sku' => 'SAM-S24U-TV-512', 'price' => 1499.00, 'stock' => 25],
                ]
            ],

            // Electronics - Laptops
            [
                'name' => 'MacBook Pro 16" M3 Max',
                'description' => 'Professional laptop with M3 Max chip, up to 128GB unified memory, and stunning Liquid Retina XDR display. Perfect for creative professionals.',
                'category' => 'laptops',
                'price' => 3499.00,
                'sale_price' => 3699.00,
                'cost_price' => 2500.00,
                'sku' => 'MBP-16-M3MAX',
                'vendor_index' => 0,
                'is_featured' => true,
                'variants' => [
                    ['name' => 'Space Black - 36GB RAM - 1TB SSD', 'sku' => 'MBP-16-M3MAX-SB-36-1TB', 'price' => 3499.00, 'stock' => 15],
                    ['name' => 'Silver - 48GB RAM - 1TB SSD', 'sku' => 'MBP-16-M3MAX-SL-48-1TB', 'price' => 3899.00, 'stock' => 12],
                    ['name' => 'Space Black - 64GB RAM - 2TB SSD', 'sku' => 'MBP-16-M3MAX-SB-64-2TB', 'price' => 4699.00, 'stock' => 8],
                ]
            ],
            [
                'name' => 'Dell XPS 15',
                'description' => 'Premium Windows laptop with 13th Gen Intel Core i9, NVIDIA RTX 4070 graphics, and InfinityEdge display. Ideal for gaming and content creation.',
                'category' => 'laptops',
                'price' => 2399.00,
                'cost_price' => 1600.00,
                'sku' => 'DELL-XPS15',
                'vendor_index' => 0,
                'variants' => [
                    ['name' => 'Platinum Silver - i7 - 16GB - 512GB', 'sku' => 'DELL-XPS15-PS-I7-16-512', 'price' => 1999.00, 'stock' => 25],
                    ['name' => 'Platinum Silver - i9 - 32GB - 1TB', 'sku' => 'DELL-XPS15-PS-I9-32-1TB', 'price' => 2399.00, 'stock' => 18],
                ]
            ],

            // Electronics - Headphones
            [
                'name' => 'Sony WH-1000XM5',
                'description' => 'Industry-leading noise canceling wireless headphones with exceptional sound quality. 30-hour battery life and multipoint connection.',
                'category' => 'headphones',
                'price' => 399.00,
                'sale_price' => 449.00,
                'cost_price' => 250.00,
                'sku' => 'SONY-WH1000XM5',
                'vendor_index' => 0,
                'is_featured' => true,
                'variants' => [
                    ['name' => 'Black', 'sku' => 'SONY-WH1000XM5-BK', 'price' => 399.00, 'stock' => 60],
                    ['name' => 'Silver', 'sku' => 'SONY-WH1000XM5-SL', 'price' => 399.00, 'stock' => 55],
                ]
            ],
            [
                'name' => 'AirPods Pro (2nd generation)',
                'description' => 'Premium wireless earbuds with adaptive audio, personalized spatial audio, and up to 2x more active noise cancellation.',
                'category' => 'headphones',
                'price' => 249.00,
                'cost_price' => 150.00,
                'sku' => 'APP-2GEN',
                'vendor_index' => 0,
                'stock' => 100,
            ],

            // Electronics - Cameras
            [
                'name' => 'Canon EOS R5',
                'description' => 'Professional mirrorless camera with 45MP full-frame sensor, 8K video recording, and advanced autofocus system.',
                'category' => 'cameras',
                'price' => 3899.00,
                'cost_price' => 2800.00,
                'sku' => 'CAN-R5',
                'vendor_index' => 0,
                'stock' => 12,
            ],
            [
                'name' => 'Sony A7 IV',
                'description' => 'Versatile full-frame mirrorless camera with 33MP sensor, 4K 60p video, and real-time tracking AF.',
                'category' => 'cameras',
                'price' => 2499.00,
                'sale_price' => 2698.00,
                'cost_price' => 1800.00,
                'sku' => 'SONY-A7IV',
                'vendor_index' => 0,
                'stock' => 18,
            ],

            // Fashion - Men's Clothing
            [
                'name' => 'Premium Cotton T-Shirt',
                'description' => 'Comfortable 100% organic cotton t-shirt with modern fit. Breathable fabric perfect for everyday wear.',
                'category' => 'mens-clothing',
                'price' => 29.99,
                'sale_price' => 39.99,
                'cost_price' => 12.00,
                'sku' => 'TSHIRT-PREM',
                'vendor_index' => 1,
                'variants' => [
                    ['name' => 'Black - Small', 'sku' => 'TSHIRT-PREM-BK-S', 'price' => 29.99, 'stock' => 100],
                    ['name' => 'Black - Medium', 'sku' => 'TSHIRT-PREM-BK-M', 'price' => 29.99, 'stock' => 150],
                    ['name' => 'Black - Large', 'sku' => 'TSHIRT-PREM-BK-L', 'price' => 29.99, 'stock' => 120],
                    ['name' => 'White - Small', 'sku' => 'TSHIRT-PREM-WH-S', 'price' => 29.99, 'stock' => 90],
                    ['name' => 'White - Medium', 'sku' => 'TSHIRT-PREM-WH-M', 'price' => 29.99, 'stock' => 140],
                    ['name' => 'White - Large', 'sku' => 'TSHIRT-PREM-WH-L', 'price' => 29.99, 'stock' => 110],
                    ['name' => 'Navy - Medium', 'sku' => 'TSHIRT-PREM-NV-M', 'price' => 29.99, 'stock' => 80],
                    ['name' => 'Navy - Large', 'sku' => 'TSHIRT-PREM-NV-L', 'price' => 29.99, 'stock' => 85],
                ]
            ],
            [
                'name' => 'Slim Fit Dress Shirt',
                'description' => 'Professional dress shirt with wrinkle-resistant fabric. Perfect for office and formal occasions.',
                'category' => 'mens-clothing',
                'price' => 49.99,
                'cost_price' => 20.00,
                'sku' => 'SHIRT-SLIM',
                'vendor_index' => 1,
                'variants' => [
                    ['name' => 'White - 15.5 neck', 'sku' => 'SHIRT-SLIM-WH-155', 'price' => 49.99, 'stock' => 50],
                    ['name' => 'White - 16 neck', 'sku' => 'SHIRT-SLIM-WH-16', 'price' => 49.99, 'stock' => 60],
                    ['name' => 'Light Blue - 15.5 neck', 'sku' => 'SHIRT-SLIM-LB-155', 'price' => 49.99, 'stock' => 45],
                    ['name' => 'Light Blue - 16 neck', 'sku' => 'SHIRT-SLIM-LB-16', 'price' => 49.99, 'stock' => 55],
                ]
            ],

            // Fashion - Women's Clothing
            [
                'name' => 'Elegant Maxi Dress',
                'description' => 'Flowing maxi dress with floral print. Perfect for summer events and casual outings.',
                'category' => 'womens-clothing',
                'price' => 79.99,
                'sale_price' => 99.99,
                'cost_price' => 35.00,
                'sku' => 'DRESS-MAXI',
                'vendor_index' => 1,
                'is_featured' => true,
                'variants' => [
                    ['name' => 'Floral Blue - Small', 'sku' => 'DRESS-MAXI-FB-S', 'price' => 79.99, 'stock' => 40],
                    ['name' => 'Floral Blue - Medium', 'sku' => 'DRESS-MAXI-FB-M', 'price' => 79.99, 'stock' => 50],
                    ['name' => 'Floral Blue - Large', 'sku' => 'DRESS-MAXI-FB-L', 'price' => 79.99, 'stock' => 45],
                    ['name' => 'Floral Pink - Small', 'sku' => 'DRESS-MAXI-FP-S', 'price' => 79.99, 'stock' => 35],
                    ['name' => 'Floral Pink - Medium', 'sku' => 'DRESS-MAXI-FP-M', 'price' => 79.99, 'stock' => 48],
                ]
            ],
            [
                'name' => 'Classic Denim Jeans',
                'description' => 'High-quality stretch denim with perfect fit. Versatile and comfortable for all-day wear.',
                'category' => 'womens-clothing',
                'price' => 69.99,
                'cost_price' => 28.00,
                'sku' => 'JEANS-CLASSIC',
                'vendor_index' => 1,
                'variants' => [
                    ['name' => 'Dark Blue - Size 26', 'sku' => 'JEANS-CLASSIC-DB-26', 'price' => 69.99, 'stock' => 60],
                    ['name' => 'Dark Blue - Size 28', 'sku' => 'JEANS-CLASSIC-DB-28', 'price' => 69.99, 'stock' => 70],
                    ['name' => 'Dark Blue - Size 30', 'sku' => 'JEANS-CLASSIC-DB-30', 'price' => 69.99, 'stock' => 65],
                    ['name' => 'Light Blue - Size 26', 'sku' => 'JEANS-CLASSIC-LB-26', 'price' => 69.99, 'stock' => 55],
                    ['name' => 'Light Blue - Size 28', 'sku' => 'JEANS-CLASSIC-LB-28', 'price' => 69.99, 'stock' => 68],
                ]
            ],

            // Fashion - Shoes
            [
                'name' => 'Nike Air Max 270',
                'description' => 'Iconic sneakers with Air cushioning technology. Lightweight, breathable, and stylish for everyday wear.',
                'category' => 'shoes',
                'price' => 149.99,
                'sale_price' => 169.99,
                'cost_price' => 75.00,
                'sku' => 'NIKE-AM270',
                'vendor_index' => 1,
                'is_featured' => true,
                'variants' => [
                    ['name' => 'Black/White - US 8', 'sku' => 'NIKE-AM270-BW-8', 'price' => 149.99, 'stock' => 45],
                    ['name' => 'Black/White - US 9', 'sku' => 'NIKE-AM270-BW-9', 'price' => 149.99, 'stock' => 50],
                    ['name' => 'Black/White - US 10', 'sku' => 'NIKE-AM270-BW-10', 'price' => 149.99, 'stock' => 55],
                    ['name' => 'White/Blue - US 9', 'sku' => 'NIKE-AM270-WB-9', 'price' => 149.99, 'stock' => 40],
                    ['name' => 'White/Blue - US 10', 'sku' => 'NIKE-AM270-WB-10', 'price' => 149.99, 'stock' => 42],
                ]
            ],

            // Home & Living - Furniture
            [
                'name' => 'Modern L-Shaped Sofa',
                'description' => 'Contemporary sectional sofa with premium fabric upholstery. Comfortable seating for the whole family.',
                'category' => 'furniture',
                'price' => 1299.00,
                'sale_price' => 1599.00,
                'cost_price' => 700.00,
                'sku' => 'SOFA-LSHAPE',
                'vendor_index' => 2,
                'is_featured' => true,
                'variants' => [
                    ['name' => 'Gray - Left Facing', 'sku' => 'SOFA-LSHAPE-GR-L', 'price' => 1299.00, 'stock' => 12],
                    ['name' => 'Gray - Right Facing', 'sku' => 'SOFA-LSHAPE-GR-R', 'price' => 1299.00, 'stock' => 10],
                    ['name' => 'Navy - Left Facing', 'sku' => 'SOFA-LSHAPE-NV-L', 'price' => 1299.00, 'stock' => 8],
                    ['name' => 'Beige - Right Facing', 'sku' => 'SOFA-LSHAPE-BG-R', 'price' => 1299.00, 'stock' => 9],
                ]
            ],
            [
                'name' => 'Solid Wood Dining Table',
                'description' => 'Handcrafted dining table from solid oak wood. Seats 6-8 people comfortably.',
                'category' => 'furniture',
                'price' => 899.00,
                'cost_price' => 450.00,
                'sku' => 'TABLE-DINING',
                'vendor_index' => 2,
                'variants' => [
                    ['name' => 'Natural Oak - 6 seats', 'sku' => 'TABLE-DINING-NO-6', 'price' => 899.00, 'stock' => 15],
                    ['name' => 'Natural Oak - 8 seats', 'sku' => 'TABLE-DINING-NO-8', 'price' => 1099.00, 'stock' => 12],
                    ['name' => 'Walnut - 6 seats', 'sku' => 'TABLE-DINING-WN-6', 'price' => 999.00, 'stock' => 10],
                ]
            ],

            // Home & Living - Kitchen
            [
                'name' => 'Stainless Steel Cookware Set',
                'description' => '12-piece professional cookware set with tri-ply construction. Includes pots, pans, and lids.',
                'category' => 'kitchen-dining',
                'price' => 299.00,
                'sale_price' => 399.00,
                'cost_price' => 150.00,
                'sku' => 'COOK-SET12',
                'vendor_index' => 2,
                'stock' => 35,
            ],
            [
                'name' => 'Espresso Machine Deluxe',
                'description' => 'Professional-grade espresso machine with milk frother. Make barista-quality coffee at home.',
                'category' => 'kitchen-dining',
                'price' => 799.00,
                'cost_price' => 400.00,
                'sku' => 'ESP-DELUXE',
                'vendor_index' => 2,
                'is_featured' => true,
                'variants' => [
                    ['name' => 'Stainless Steel', 'sku' => 'ESP-DELUXE-SS', 'price' => 799.00, 'stock' => 20],
                    ['name' => 'Black', 'sku' => 'ESP-DELUXE-BK', 'price' => 799.00, 'stock' => 18],
                ]
            ],

            // Sports & Outdoors
            [
                'name' => 'Adjustable Dumbbell Set',
                'description' => 'Space-saving adjustable dumbbells with quick-change weight system. 5-52.5 lbs per dumbbell.',
                'category' => 'fitness-equipment',
                'price' => 399.00,
                'cost_price' => 200.00,
                'sku' => 'DUMBBELL-ADJ',
                'vendor_index' => 3,
                'is_featured' => true,
                'stock' => 25,
            ],
            [
                'name' => 'Yoga Mat Premium',
                'description' => 'Extra-thick non-slip yoga mat with alignment marks. Eco-friendly TPE material.',
                'category' => 'fitness-equipment',
                'price' => 49.99,
                'sale_price' => 69.99,
                'cost_price' => 20.00,
                'sku' => 'YOGA-MAT',
                'vendor_index' => 3,
                'variants' => [
                    ['name' => 'Purple', 'sku' => 'YOGA-MAT-PP', 'price' => 49.99, 'stock' => 80],
                    ['name' => 'Blue', 'sku' => 'YOGA-MAT-BL', 'price' => 49.99, 'stock' => 75],
                    ['name' => 'Pink', 'sku' => 'YOGA-MAT-PK', 'price' => 49.99, 'stock' => 70],
                    ['name' => 'Black', 'sku' => 'YOGA-MAT-BK', 'price' => 49.99, 'stock' => 90],
                ]
            ],
            [
                'name' => 'Camping Tent 4-Person',
                'description' => 'Waterproof family camping tent with easy setup. Includes rainfly and storage pockets.',
                'category' => 'outdoor-gear',
                'price' => 199.00,
                'cost_price' => 100.00,
                'sku' => 'TENT-4P',
                'vendor_index' => 3,
                'variants' => [
                    ['name' => 'Green', 'sku' => 'TENT-4P-GR', 'price' => 199.00, 'stock' => 30],
                    ['name' => 'Blue', 'sku' => 'TENT-4P-BL', 'price' => 199.00, 'stock' => 28],
                ]
            ],
            [
                'name' => 'Running Shoes Pro',
                'description' => 'High-performance running shoes with responsive cushioning and breathable mesh upper.',
                'category' => 'sports-apparel',
                'price' => 129.99,
                'cost_price' => 60.00,
                'sku' => 'RUN-SHOE',
                'vendor_index' => 3,
                'variants' => [
                    ['name' => 'Black/Red - US 8', 'sku' => 'RUN-SHOE-BR-8', 'price' => 129.99, 'stock' => 40],
                    ['name' => 'Black/Red - US 9', 'sku' => 'RUN-SHOE-BR-9', 'price' => 129.99, 'stock' => 45],
                    ['name' => 'Black/Red - US 10', 'sku' => 'RUN-SHOE-BR-10', 'price' => 129.99, 'stock' => 50],
                    ['name' => 'White/Blue - US 9', 'sku' => 'RUN-SHOE-WB-9', 'price' => 129.99, 'stock' => 38],
                    ['name' => 'White/Blue - US 10', 'sku' => 'RUN-SHOE-WB-10', 'price' => 129.99, 'stock' => 42],
                ]
            ],
        ];

        foreach ($products as $productData) {
            $category = Category::where('slug', $productData['category'])->first();
            if (!$category) {
                $this->command->warn("Category {$productData['category']} not found, skipping product {$productData['name']}");
                continue;
            }

            $vendor = $vendors[$productData['vendor_index']] ?? $vendors->first();
            
            $variants = $productData['variants'] ?? null;
            $stock = $productData['stock'] ?? null;
            
            unset($productData['variants'], $productData['stock'], $productData['category'], $productData['vendor_index']);

            $product = Product::create([
                'name' => $productData['name'],
                'slug' => Str::slug($productData['name']),
                'description' => $productData['description'],
                'price' => $productData['price'],
                'sale_price' => $productData['sale_price'] ?? null,
                'cost_price' => $productData['cost_price'],
                'sku' => $productData['sku'],
                'track_inventory' => true,
                'stock_quantity' => $stock ?? 0,
                'low_stock_threshold' => 10,
                'weight' => rand(100, 5000) / 100, // Random weight between 1-50 kg
                'category_id' => $category->id,
                'vendor_id' => $vendor->id,
                'is_active' => true,
                'is_featured' => $productData['is_featured'] ?? false,
            ]);

            // Create variants if they exist
            if ($variants) {
                foreach ($variants as $variantData) {
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'name' => $variantData['name'],
                        'sku' => $variantData['sku'],
                        'price' => $variantData['price'],
                        'sale_price' => $productData['sale_price'] ?? null,
                        'stock_quantity' => $variantData['stock'],
                        'attributes' => json_decode('{}'), // Empty for now
                    ]);
                }
            }

            $this->command->info("Created product: {$product->name}");
        }

        $this->command->info('Products seeded successfully!');
    }
}
