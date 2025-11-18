<?php

namespace App\Jobs;

use App\Models\Order;
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
        public string $event
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $customer = $this->order->customer;

            // In a real application, you would send different emails based on the event
            // Mail::to($customer->email)->send(new OrderNotificationMail($this->order, $this->event));

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
            ]);

            throw $e;
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
        ]);
    }
}
