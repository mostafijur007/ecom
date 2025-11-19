<?php

namespace App\Http\Controllers\Api\V1\Schemas;

/**
 * @OA\Schema(
 *     schema="Order",
 *     title="Order",
 *     description="Order model with items, customer, and invoice information",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="order_number", type="string", example="ORD-20240118-0001"),
 *     @OA\Property(property="customer_id", type="integer", example=5),
 *     @OA\Property(
 *         property="customer",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=5),
 *         @OA\Property(property="name", type="string", example="John Doe"),
 *         @OA\Property(property="email", type="string", example="john@example.com")
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"pending", "processing", "shipped", "delivered", "cancelled"},
 *         example="processing"
 *     ),
 *     @OA\Property(
 *         property="payment_status",
 *         type="string",
 *         enum={"pending", "paid", "failed", "refunded"},
 *         example="paid"
 *     ),
 *     @OA\Property(
 *         property="payment_method",
 *         type="string",
 *         enum={"credit_card", "debit_card", "paypal", "bank_transfer", "cash_on_delivery"},
 *         example="credit_card"
 *     ),
 *     @OA\Property(property="shipping_name", type="string", example="John Doe"),
 *     @OA\Property(property="shipping_email", type="string", example="john@example.com"),
 *     @OA\Property(property="shipping_phone", type="string", example="+1234567890"),
 *     @OA\Property(property="shipping_address", type="string", example="123 Main St"),
 *     @OA\Property(property="shipping_city", type="string", example="New York"),
 *     @OA\Property(property="shipping_state", type="string", example="NY"),
 *     @OA\Property(property="shipping_postal_code", type="string", example="10001"),
 *     @OA\Property(property="shipping_country", type="string", example="USA"),
 *     @OA\Property(property="subtotal", type="number", format="float", example=149.98),
 *     @OA\Property(property="tax_amount", type="number", format="float", example=12.00),
 *     @OA\Property(property="shipping_cost", type="number", format="float", example=10.00),
 *     @OA\Property(property="discount_amount", type="number", format="float", example=0.00),
 *     @OA\Property(property="total_amount", type="number", format="float", example=171.98),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Please ring doorbell"),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/OrderItem")
 *     ),
 *     @OA\Property(property="invoice", ref="#/components/schemas/Invoice", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-18T10:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-18T11:00:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="OrderItem",
 *     title="Order Item",
 *     description="Individual item within an order with product snapshot",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="order_id", type="integer", example=1),
 *     @OA\Property(property="product_id", type="integer", example=10),
 *     @OA\Property(property="product_variant_id", type="integer", nullable=true, example=5),
 *     @OA\Property(property="product_name", type="string", example="Wireless Headphones"),
 *     @OA\Property(property="product_sku", type="string", example="WH-001-BLK"),
 *     @OA\Property(property="variant_name", type="string", nullable=true, example="Black"),
 *     @OA\Property(property="quantity", type="integer", example=2),
 *     @OA\Property(property="unit_price", type="number", format="float", example=74.99),
 *     @OA\Property(property="subtotal", type="number", format="float", example=149.98),
 *     @OA\Property(
 *         property="product",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=10),
 *         @OA\Property(property="name", type="string", example="Wireless Headphones"),
 *         @OA\Property(property="sku", type="string", example="WH-001"),
 *         @OA\Property(property="price", type="number", format="float", example=74.99),
 *         @OA\Property(property="image_url", type="string", example="https://example.com/images/headphones.jpg")
 *     ),
 *     @OA\Property(
 *         property="variant",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="id", type="integer", example=5),
 *         @OA\Property(property="name", type="string", example="Black"),
 *         @OA\Property(property="sku", type="string", example="WH-001-BLK"),
 *         @OA\Property(property="price", type="number", format="float", example=74.99)
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-18T10:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-18T10:30:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="Invoice",
 *     title="Invoice",
 *     description="Invoice generated for an order",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="order_id", type="integer", example=1),
 *     @OA\Property(property="invoice_number", type="string", example="INV-20240118-0001"),
 *     @OA\Property(property="invoice_date", type="string", format="date", example="2024-01-18"),
 *     @OA\Property(property="due_date", type="string", format="date", example="2024-02-18"),
 *     @OA\Property(property="amount", type="number", format="float", example=171.98),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"pending", "paid", "overdue", "cancelled"},
 *         example="paid"
 *     ),
 *     @OA\Property(property="pdf_path", type="string", nullable=true, example="/storage/invoices/INV-20240118-0001.pdf"),
 * @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-18T10:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-18T10:30:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="OrderRequest",
 *     title="Order Request",
 *     description="Request body for creating a new order",
 *     required={"user_id", "items"},
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         description="Customer user ID",
 *         example=5
 *     ),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         description="Array of order items",
 *         @OA\Items(
 *             type="object",
 *             required={"product_id", "quantity"},
 *             @OA\Property(property="product_id", type="integer", example=10),
 *             @OA\Property(property="quantity", type="integer", example=2)
 *         )
 *     ),
 *     @OA\Property(
 *         property="payment_method",
 *         type="string",
 *         enum={"cod", "credit_card", "debit_card", "paypal", "stripe"},
 *         example="credit_card"
 *     ),
 *     @OA\Property(
 *         property="payment_status",
 *         type="string",
 *         enum={"pending", "paid", "failed", "refunded"},
 *         example="pending"
 *     ),
 *     @OA\Property(
 *         property="shipping_cost",
 *         type="number",
 *         format="float",
 *         example=10.00
 *     ),
 *     @OA\Property(
 *         property="discount",
 *         type="number",
 *         format="float",
 *         example=0.00
 *     ),
 *     @OA\Property(
 *         property="tax",
 *         type="number",
 *         format="float",
 *         example=12.00
 *     ),
 *     @OA\Property(
 *         property="notes",
 *         type="string",
 *         nullable=true,
 *         example="Please ring doorbell"
 *     ),
 *     @OA\Property(
 *         property="shipping_address",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="name", type="string", example="John Doe"),
 *         @OA\Property(property="phone", type="string", example="+1234567890"),
 *         @OA\Property(property="address", type="string", example="123 Main St"),
 *         @OA\Property(property="city", type="string", example="New York"),
 *         @OA\Property(property="state", type="string", example="NY"),
 *         @OA\Property(property="postal_code", type="string", example="10001"),
 *         @OA\Property(property="country", type="string", example="USA")
 *     ),
 *     @OA\Property(
 *         property="billing_address",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="name", type="string", example="John Doe"),
 *         @OA\Property(property="phone", type="string", example="+1234567890"),
 *         @OA\Property(property="address", type="string", example="123 Main St"),
 *         @OA\Property(property="city", type="string", example="New York"),
 *         @OA\Property(property="state", type="string", example="NY"),
 *         @OA\Property(property="postal_code", type="string", example="10001"),
 *         @OA\Property(property="country", type="string", example="USA")
 *     )
 * )
 */
class OrderSchema
{
    // This class exists solely for OpenAPI documentation
}
