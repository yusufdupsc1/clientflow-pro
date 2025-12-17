<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('public_hash')->nullable()->after('invoice_number');
            $table->integer('discount_cents')->default(0)->after('subtotal_cents');
            $table->integer('tax_cents')->default(0)->after('discount_cents');
            $table->decimal('tax_rate_percent', 5, 2)->default(0)->after('tax_cents');
            $table->string('currency', 3)->default('USD')->after('total_cents');

            $table->string('stripe_product_id')->nullable()->after('paid_at');
            $table->string('stripe_price_id')->nullable()->after('stripe_product_id');
            $table->integer('stripe_price_amount_cents')->nullable()->after('stripe_price_id');
            $table->string('stripe_price_currency', 3)->nullable()->after('stripe_price_amount_cents');
            $table->string('stripe_payment_link_id')->nullable()->after('stripe_price_id');
            $table->string('stripe_payment_link_url')->nullable()->after('stripe_payment_link_id');
            $table->string('stripe_checkout_session_id')->nullable()->after('stripe_payment_link_url');
            $table->string('stripe_customer_id')->nullable()->after('stripe_checkout_session_id');
            $table->string('stripe_payment_intent_id')->nullable()->after('stripe_customer_id');
            $table->string('stripe_mode')->nullable()->after('stripe_payment_intent_id');

            $table->unique('public_hash');
        });

        DB::table('invoices')->whereNull('public_hash')->orderBy('id')->chunk(100, function ($invoices): void {
            foreach ($invoices as $invoice) {
                DB::table('invoices')
                    ->where('id', $invoice->id)
                    ->update(['public_hash' => (string) Str::uuid()]);
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('currency', 3)->default('USD')->after('amount_cents');
            $table->integer('refunded_cents')->default(0)->after('currency');
            $table->timestamp('refunded_at')->nullable()->after('paid_at');
            $table->string('stripe_payment_intent_id')->nullable()->after('external_id');
            $table->string('stripe_charge_id')->nullable()->after('stripe_payment_intent_id');
            $table->string('stripe_refund_id')->nullable()->after('stripe_charge_id');
            $table->string('stripe_balance_transaction_id')->nullable()->after('stripe_refund_id');
            $table->string('stripe_receipt_url')->nullable()->after('stripe_balance_transaction_id');
            $table->string('stripe_webhook_event_id')->nullable()->after('stripe_receipt_url');
        });

        Schema::create('stripe_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('type');
            $table->boolean('live_mode')->default(false);
            $table->string('status')->default('received');
            $table->json('payload');
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_webhook_events');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'currency',
                'refunded_cents',
                'refunded_at',
                'stripe_payment_intent_id',
                'stripe_charge_id',
                'stripe_refund_id',
                'stripe_balance_transaction_id',
                'stripe_receipt_url',
                'stripe_webhook_event_id',
            ]);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['public_hash']);
            $table->dropColumn([
                'public_hash',
                'discount_cents',
                'tax_cents',
                'tax_rate_percent',
                'currency',
                'stripe_product_id',
                'stripe_price_id',
                'stripe_price_amount_cents',
                'stripe_price_currency',
                'stripe_payment_link_id',
                'stripe_payment_link_url',
                'stripe_checkout_session_id',
                'stripe_customer_id',
                'stripe_payment_intent_id',
                'stripe_mode',
            ]);
        });
    }
};
