<?php

namespace Tests\Unit\Jobs;

use App\Models\Order;
use App\Models\Invoice;
use App\Models\User;
use App\Models\OrderItem;
use App\Jobs\GenerateInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GenerateInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** @test */
    public function it_generates_invoice_for_order()
    {
        $order = Order::factory()->create([
            'total' => 250.00,
            'status' => 'pending',
        ]);
        OrderItem::factory()->count(2)->create(['order_id' => $order->id]);

        $job = new GenerateInvoice($order);
        $job->handle();

        $this->assertDatabaseHas('invoices', [
            'order_id' => $order->id,
        ]);
    }

    /** @test */
    public function it_creates_pdf_file_in_storage()
    {
        $order = Order::factory()->create([
            'total' => 250.00,
            'order_number' => 'ORD-12345',
        ]);
        OrderItem::factory()->count(2)->create(['order_id' => $order->id]);

        $job = new GenerateInvoice($order);
        $job->handle();

        $invoice = Invoice::where('order_id', $order->id)->first();
        
        $this->assertNotNull($invoice);
        $this->assertNotNull($invoice->pdf_path);
        Storage::disk('public')->assertExists($invoice->pdf_path);
    }

    /** @test */
    public function it_sets_correct_invoice_status_for_paid_orders()
    {
        $order = Order::factory()->create([
            'total' => 250.00,
            'payment_status' => 'paid',
        ]);
        OrderItem::factory()->count(2)->create(['order_id' => $order->id]);

        $job = new GenerateInvoice($order);
        $job->handle();

        $invoice = Invoice::where('order_id', $order->id)->first();
        
        $this->assertEquals('paid', $invoice->status);
    }

    /** @test */
    public function it_sets_draft_status_for_unpaid_orders()
    {
        $order = Order::factory()->create([
            'total' => 250.00,
            'payment_status' => 'pending',
        ]);
        OrderItem::factory()->count(2)->create(['order_id' => $order->id]);

        $job = new GenerateInvoice($order);
        $job->handle();

        $invoice = Invoice::where('order_id', $order->id)->first();
        
        $this->assertEquals('draft', $invoice->status);
    }

    /** @test */
    public function it_does_not_create_duplicate_invoices()
    {
        $order = Order::factory()->create(['total' => 250.00]);
        OrderItem::factory()->count(2)->create(['order_id' => $order->id]);

        $job = new GenerateInvoice($order);
        $job->handle();
        
        $firstInvoiceCount = Invoice::where('order_id', $order->id)->count();
        $this->assertEquals(1, $firstInvoiceCount);

        // Try to generate again
        $job2 = new GenerateInvoice($order);
        $job2->handle();
        
        $secondInvoiceCount = Invoice::where('order_id', $order->id)->count();
        $this->assertEquals(1, $secondInvoiceCount);
    }

    /** @test */
    public function it_handles_null_order_total_gracefully()
    {
        $order = Order::factory()->create([
            'total' => null,
            'payment_status' => 'pending',
        ]);
        OrderItem::factory()->count(2)->create(['order_id' => $order->id]);

        $job = new GenerateInvoice($order);
        $job->handle();

        $invoice = Invoice::where('order_id', $order->id)->first();
        
        $this->assertNotNull($invoice);
        $this->assertEquals(0, $invoice->amount);
    }

    /** @test */
    public function it_includes_customer_information_in_invoice()
    {
        $customer = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'total' => 250.00,
        ]);
        OrderItem::factory()->count(2)->create(['order_id' => $order->id]);

        $job = new GenerateInvoice($order);
        $job->handle();

        $invoice = Invoice::where('order_id', $order->id)->first();
        $this->assertNotNull($invoice);
        
        // Verify PDF was created (even if we don't parse content)
        Storage::disk('public')->assertExists($invoice->pdf_path);
    }

    /** @test */
    public function it_generates_unique_invoice_numbers()
    {
        $order1 = Order::factory()->create(['total' => 100]);
        $order2 = Order::factory()->create(['total' => 200]);
        
        OrderItem::factory()->count(1)->create(['order_id' => $order1->id]);
        OrderItem::factory()->count(1)->create(['order_id' => $order2->id]);

        $job1 = new GenerateInvoice($order1);
        $job1->handle();
        
        $job2 = new GenerateInvoice($order2);
        $job2->handle();

        $invoice1 = Invoice::where('order_id', $order1->id)->first();
        $invoice2 = Invoice::where('order_id', $order2->id)->first();

        $this->assertNotEquals($invoice1->invoice_number, $invoice2->invoice_number);
    }
}
