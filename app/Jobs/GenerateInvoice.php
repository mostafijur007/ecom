<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use Mpdf\MpdfException;

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
            // Reload order from database to check if invoice already exists
            $this->order->refresh();
            
            // Check if invoice already exists
            if ($this->order->invoice()->exists()) {
                Log::info('Invoice already exists for order', ['order_id' => $this->order->id]);
                return;
            }

            // Load order relationships
            $this->order->load(['items', 'customer']);

            // Ensure order total is not null
            $orderTotal = $this->order->total ?? 0;

            // Create invoice record
            $invoice = Invoice::create([
                'order_id' => $this->order->id,
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'invoice_date' => now(),
                'due_date' => now()->addDays(30),
                'amount' => $orderTotal,
                'status' => $this->order->payment_status === 'paid' ? 'paid' : 'draft',
            ]);

            // Generate PDF using mPDF
            $html = view('invoices.template', [
                'order' => $this->order,
                'invoice' => $invoice
            ])->render();

            // Create mPDF instance with configuration
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15,
                'margin_header' => 10,
                'margin_footer' => 10,
            ]);

            // Set PDF metadata
            $mpdf->SetTitle('Invoice ' . $invoice->invoice_number);
            $mpdf->SetAuthor('E-Commerce Store');
            $mpdf->SetCreator('E-Commerce System');
            $mpdf->SetSubject('Invoice for Order ' . $this->order->order_number);

            // Write HTML to PDF
            $mpdf->WriteHTML($html);

            // Generate PDF output
            $pdfContent = $mpdf->Output('', 'S'); // 'S' returns the PDF as a string

            // Save PDF to storage (public disk)
            $filename = 'invoices/' . $invoice->invoice_number . '.pdf';
            Storage::disk('public')->put($filename, $pdfContent);

            // Update invoice with PDF path
            $invoice->update(['pdf_path' => $filename]);

            Log::info('Invoice generated successfully', [
                'order_id' => $this->order->id,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'pdf_path' => $filename,
            ]);

        } catch (MpdfException $e) {
            Log::error('Failed to generate PDF', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
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
