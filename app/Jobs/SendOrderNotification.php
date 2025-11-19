<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use App\Mail\OrderCreatedMail;
use App\Mail\OrderStatusChangedMail;
use App\Mail\OrderCancelledMail;
use App\Mail\NewOrderAdminNotification;
use App\Mail\VendorOrderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderNotification implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Order $order,
        public string $event,
        public ?string $oldStatus = null
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $customer = $this->order->customer;

            // Send email to customer based on event
            match ($this->event) {
                'created' => $this->sendOrderCreatedEmail($customer),
                'status_updated' => $this->sendStatusChangedEmail($customer),
                'cancelled' => $this->sendOrderCancelledEmail($customer),
                default => Log::warning('Unknown order event', ['event' => $this->event])
            };

            // Send notifications to admins and vendors for new orders
            if ($this->event === 'created') {
                $this->notifyAdmins();
                $this->notifyVendors();
            }

            Log::info('Order Notification Sent', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'event' => $this->event,
                'customer_email' => $customer->email,
                'order_status' => $this->order->status,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send order notification', [
                'order_id' => $this->order->id,
                'event' => $this->event,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Send order created email to customer
     */
    private function sendOrderCreatedEmail(User $customer): void
    {
        Mail::to($customer->email)
            ->send(new OrderCreatedMail($this->order));
            
        Log::info('Order created email sent', [
            'order_id' => $this->order->id,
            'customer_email' => $customer->email,
        ]);
    }

    /**
     * Send status changed email to customer
     */
    private function sendStatusChangedEmail(User $customer): void
    {
        Mail::to($customer->email)
            ->send(new OrderStatusChangedMail($this->order, $this->oldStatus ?? 'unknown'));
            
        Log::info('Order status changed email sent', [
            'order_id' => $this->order->id,
            'customer_email' => $customer->email,
            'old_status' => $this->oldStatus,
            'new_status' => $this->order->status,
        ]);
    }

    /**
     * Send order cancelled email to customer
     */
    private function sendOrderCancelledEmail(User $customer): void
    {
        Mail::to($customer->email)
            ->send(new OrderCancelledMail($this->order));
            
        Log::info('Order cancelled email sent', [
            'order_id' => $this->order->id,
            'customer_email' => $customer->email,
        ]);
    }

    /**
     * Notify all admins about new order
     */
    private function notifyAdmins(): void
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)
                ->send(new NewOrderAdminNotification($this->order));
                
            Log::info('Admin notification sent', [
                'order_id' => $this->order->id,
                'admin_email' => $admin->email,
            ]);
        }
    }

    /**
     * Notify relevant vendors about their products in the order
     */
    private function notifyVendors(): void
    {
        // Load order items with product relationships
        $this->order->load('items.product.vendor');

        // Group items by vendor
        $vendorGroups = $this->order->items->groupBy(function ($item) {
            return $item->product->vendor_id;
        });

        foreach ($vendorGroups as $vendorId => $items) {
            if (!$vendorId) {
                continue; // Skip items without vendor
            }

            $vendor = User::find($vendorId);
            if (!$vendor || $vendor->role !== 'vendor') {
                continue;
            }

            Mail::to($vendor->email)
                ->send(new VendorOrderNotification(
                    $this->order,
                    $items,
                    $vendor->name
                ));
                
            Log::info('Vendor notification sent', [
                'order_id' => $this->order->id,
                'vendor_id' => $vendorId,
                'vendor_email' => $vendor->email,
                'items_count' => $items->count(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Order Notification job failed', [
            'order_id' => $this->order->id,
            'event' => $this->event,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
