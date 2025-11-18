<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateInvoice implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Order $order
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Check if invoice already exists
            if ($this->order->invoice) {
                Log::info('Invoice already exists for order', ['order_id' => $this->order->id]);
                return;
            }

            // Create invoice record
            $invoice = Invoice::create([
                'order_id' => $this->order->id,
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'invoice_date' => now(),
                'due_date' => now()->addDays(30),
                'amount' => $this->order->total,
                'status' => $this->order->payment_status === 'paid' ? 'paid' : 'draft',
            ]);

            // Generate PDF (placeholder - would use dompdf in real application)
            // $pdf = PDF::loadView('invoices.template', [
            //     'order' => $this->order,
            //     'invoice' => $invoice
            // ]);
            // 
            // $filename = 'invoices/' . $invoice->invoice_number . '.pdf';
            // Storage::put($filename, $pdf->output());
            // 
            // $invoice->update(['pdf_path' => $filename]);

            Log::info('Invoice generated successfully', [
                'order_id' => $this->order->id,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate invoice', [
                'order_id' => $this->order->id,
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
        Log::error('Generate Invoice job failed', [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
