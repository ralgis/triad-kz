<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Human-readable, sequential order number. Format: T-NNNNNN
            // (T = Triad). Generated in OrderService::create() using a
            // counter to avoid race-conditions on raw id-based numbers.
            $table->string('order_number')->unique();

            // --- Customer ---
            // 'individual' (физлицо) | 'legal' (юрлицо). String, not enum:
            // SQLite doesn't have native ENUM and we cast to PHP enum on model.
            $table->string('customer_type');

            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');

            // For 'legal' clients — invoice needs proper company name + БИН.
            // Null for 'individual'.
            $table->string('customer_company_name')->nullable();
            $table->string('customer_bin', 12)->nullable();

            $table->string('customer_address')->nullable();

            // --- Delivery ---
            // 'pickup' (самовывоз) | 'delivery' (доставка по адресу)
            $table->string('delivery_method');
            $table->string('delivery_address')->nullable();

            // --- Payment ---
            // 'bank_transfer' (безнал — счёт-фактура) | 'cash' (наличный)
            // No online gateway integration in v1.
            $table->string('payment_method');

            $table->text('comment')->nullable();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // Order lifecycle:
            //   new → confirmed → invoiced → paid → shipped → completed
            //                              ↘ cancelled (from any state)
            $table->string('status')->default('new');

            // Audit trail: each status change writes a record here
            // {at, by_user_id, from, to, note}.
            $table->json('status_history')->nullable();

            // Relative path to generated PDF invoice
            // (storage/app/invoices/T-000123.pdf). Only set for
            // bank_transfer orders.
            $table->string('invoice_pdf_path')->nullable();

            // Tracking: was the customer/admin email sent successfully?
            // If false, admin sees "resend" button in Filament.
            $table->boolean('notification_sent')->default(false);

            $table->timestamps();

            $table->index('status');
            $table->index('customer_email');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
