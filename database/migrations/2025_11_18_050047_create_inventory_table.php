<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->onDelete('cascade');
            $table->enum('transaction_type', ['purchase', 'sale', 'adjustment', 'return']);
            $table->integer('quantity'); // positive for increase, negative for decrease
            $table->integer('balance_after')->default(0); // running balance
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->string('reference')->nullable(); // PO number, adjustment note, etc.
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained('users'); // who made the transaction
            $table->timestamps();
            
            $table->index('product_id');
            $table->index('product_variant_id');
            $table->index('transaction_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
