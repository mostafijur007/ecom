<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LowStockAlert implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Product $product
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get vendor and admin users
            $vendor = $this->product->vendor;
            $admins = User::where('role', 'admin')->get();

            $recipients = collect([$vendor])->merge($admins)->unique('id');

            foreach ($recipients as $recipient) {
                // In a real application, you would send an email here
                // Mail::to($recipient->email)->send(new LowStockAlertMail($this->product));
                
                Log::info('Low Stock Alert', [
                    'product_id' => $this->product->id,
                    'product_name' => $this->product->name,
                    'sku' => $this->product->sku,
                    'current_stock' => $this->product->stock_quantity,
                    'threshold' => $this->product->low_stock_threshold,
                    'recipient' => $recipient->email,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send low stock alert', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Low Stock Alert job failed', [
            'product_id' => $this->product->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
