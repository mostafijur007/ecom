<?php

namespace App\Http\Controllers\Api\V1\Schemas;

/**
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *     title="Product",
 *     description="Product model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="vendor_id", type="integer", example=2),
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Wireless Mouse"),
 *     @OA\Property(property="slug", type="string", example="wireless-mouse"),
 *     @OA\Property(property="sku", type="string", example="MOUSE-001"),
 *     @OA\Property(property="description", type="string", example="Ergonomic wireless mouse with USB receiver"),
 *     @OA\Property(property="short_description", type="string", example="Comfortable wireless mouse"),
 *     @OA\Property(property="price", type="number", format="float", example=29.99),
 *     @OA\Property(property="sale_price", type="number", format="float", example=24.99),
 *     @OA\Property(property="cost_price", type="number", format="float", example=15.00),
 *     @OA\Property(property="stock_quantity", type="integer", example=100),
 *     @OA\Property(property="low_stock_threshold", type="integer", example=10),
 *     @OA\Property(property="track_inventory", type="boolean", example=true),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="is_featured", type="boolean", example=false),
 *     @OA\Property(
 *         property="images",
 *         type="array",
 *         @OA\Items(type="string", example="https://example.com/image.jpg")
 *     ),
 *     @OA\Property(
 *         property="attributes",
 *         type="object",
 *         example={"color": "Black", "connectivity": "2.4GHz"}
 *     ),
 *     @OA\Property(
 *         property="dimensions",
 *         type="object",
 *         example={"length": 10, "width": 6, "height": 4}
 *     ),
 *     @OA\Property(property="weight", type="number", format="float", example=0.15),
 *     @OA\Property(property="meta_data", type="object"),
 *     @OA\Property(property="views_count", type="integer", example=150),
 *     @OA\Property(property="rating", type="number", format="float", example=4.5),
 *     @OA\Property(property="reviews_count", type="integer", example=23),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-11-19T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-11-19T10:00:00Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(
 *         property="vendor",
 *         ref="#/components/schemas/User"
 *     ),
 *     @OA\Property(
 *         property="category",
 *         ref="#/components/schemas/Category"
 *     ),
 *     @OA\Property(
 *         property="variants",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/ProductVariant")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Category",
 *     type="object",
 *     title="Category",
 *     description="Product category model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Electronics"),
 *     @OA\Property(property="slug", type="string", example="electronics"),
 *     @OA\Property(property="description", type="string", example="Electronic devices and accessories"),
 *     @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="sort_order", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="ProductVariant",
 *     type="object",
 *     title="Product Variant",
 *     description="Product variant model (size, color, etc.)",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="product_id", type="integer", example=1),
 *     @OA\Property(property="sku", type="string", example="MOUSE-001-BLK"),
 *     @OA\Property(property="name", type="string", example="Black"),
 *     @OA\Property(
 *         property="attributes",
 *         type="object",
 *         example={"color": "Black"}
 *     ),
 *     @OA\Property(property="price", type="number", format="float", example=29.99),
 *     @OA\Property(property="sale_price", type="number", format="float", example=24.99),
 *     @OA\Property(property="stock_quantity", type="integer", example=50),
 *     @OA\Property(property="image", type="string", example="https://example.com/variant.jpg"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
 * )
 */
class ProductSchema
{
    // This class only contains OpenAPI schema annotations
}
