<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'Latest electronic devices and gadgets',
                'is_active' => true,
                'sort_order' => 1,
                'children' => [
                    [
                        'name' => 'Smartphones',
                        'slug' => 'smartphones',
                        'description' => 'Latest smartphones from top brands',
                        'is_active' => true,
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Laptops',
                        'slug' => 'laptops',
                        'description' => 'High-performance laptops for work and gaming',
                        'is_active' => true,
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Headphones',
                        'slug' => 'headphones',
                        'description' => 'Premium audio equipment',
                        'is_active' => true,
                        'sort_order' => 3,
                    ],
                    [
                        'name' => 'Cameras',
                        'slug' => 'cameras',
                        'description' => 'Professional and consumer cameras',
                        'is_active' => true,
                        'sort_order' => 4,
                    ],
                ]
            ],
            [
                'name' => 'Fashion',
                'slug' => 'fashion',
                'description' => 'Trendy clothing and accessories',
                'is_active' => true,
                'sort_order' => 2,
                'children' => [
                    [
                        'name' => 'Men\'s Clothing',
                        'slug' => 'mens-clothing',
                        'description' => 'Fashion for men',
                        'is_active' => true,
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Women\'s Clothing',
                        'slug' => 'womens-clothing',
                        'description' => 'Fashion for women',
                        'is_active' => true,
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Shoes',
                        'slug' => 'shoes',
                        'description' => 'Footwear for all occasions',
                        'is_active' => true,
                        'sort_order' => 3,
                    ],
                    [
                        'name' => 'Accessories',
                        'slug' => 'accessories',
                        'description' => 'Bags, watches, and jewelry',
                        'is_active' => true,
                        'sort_order' => 4,
                    ],
                ]
            ],
            [
                'name' => 'Home & Living',
                'slug' => 'home-living',
                'description' => 'Everything for your home',
                'is_active' => true,
                'sort_order' => 3,
                'children' => [
                    [
                        'name' => 'Furniture',
                        'slug' => 'furniture',
                        'description' => 'Quality furniture for every room',
                        'is_active' => true,
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Kitchen & Dining',
                        'slug' => 'kitchen-dining',
                        'description' => 'Cookware and dining essentials',
                        'is_active' => true,
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Home Decor',
                        'slug' => 'home-decor',
                        'description' => 'Decorative items for your home',
                        'is_active' => true,
                        'sort_order' => 3,
                    ],
                ]
            ],
            [
                'name' => 'Sports & Outdoors',
                'slug' => 'sports-outdoors',
                'description' => 'Equipment for active lifestyle',
                'is_active' => true,
                'sort_order' => 4,
                'children' => [
                    [
                        'name' => 'Fitness Equipment',
                        'slug' => 'fitness-equipment',
                        'description' => 'Home gym and fitness gear',
                        'is_active' => true,
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Outdoor Gear',
                        'slug' => 'outdoor-gear',
                        'description' => 'Camping and hiking equipment',
                        'is_active' => true,
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Sports Apparel',
                        'slug' => 'sports-apparel',
                        'description' => 'Athletic clothing and footwear',
                        'is_active' => true,
                        'sort_order' => 3,
                    ],
                ]
            ],
            [
                'name' => 'Books & Media',
                'slug' => 'books-media',
                'description' => 'Books, music, and entertainment',
                'is_active' => true,
                'sort_order' => 5,
                'children' => [
                    [
                        'name' => 'Books',
                        'slug' => 'books',
                        'description' => 'Fiction and non-fiction',
                        'is_active' => true,
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Movies & TV',
                        'slug' => 'movies-tv',
                        'description' => 'DVDs and Blu-rays',
                        'is_active' => true,
                        'sort_order' => 2,
                    ],
                ]
            ],
        ];

        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            $category = Category::create($categoryData);

            foreach ($children as $childData) {
                $childData['parent_id'] = $category->id;
                Category::create($childData);
            }
        }

        $this->command->info('Categories seeded successfully!');
    }
}
