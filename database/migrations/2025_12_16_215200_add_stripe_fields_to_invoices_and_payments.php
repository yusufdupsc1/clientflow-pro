<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('stripe_checkout_session_id')->nullable()->after('status');
            $table->string('stripe_payment_intent_id')->nullable()->after('stripe_checkout_session_id');
            $table->string('payment_link_url')->nullable()->after('stripe_payment_intent_id');
            $table->timestamp('payment_link_expires_at')->nullable()->after('payment_link_url');
            $table->string('currency', 3)->default('USD')->after('total_cents');
            $table->decimal('tax_rate', 5, 2)->nullable()->after('currency');
            $table->unsignedBigInteger('tax_cents')->default(0)->after('tax_rate');
            $table->unsignedBigInteger('discount_cents')->default(0)->after('tax_cents');
            $table->string('discount_type')->nullable()->after('discount_cents');

            $table->index('stripe_checkout_session_id');
            $table->index('stripe_payment_intent_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('stripe_payment_intent_id')->nullable()->after('external_id');
            $table->string('stripe_charge_id')->nullable()->after('stripe_payment_intent_id');

            $table->index('stripe_payment_intent_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['stripe_payment_intent_id']);
            $table->dropColumn(['stripe_payment_intent_id', 'stripe_charge_id']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['stripe_checkout_session_id']);
            $table->dropIndex(['stripe_payment_intent_id']);
            $table->dropColumn([
                'stripe_checkout_session_id',
                'stripe_payment_intent_id',
                'payment_link_url',
                'payment_link_expires_at',
                'currency',
                'tax_rate',
                'tax_cents',
                'discount_cents',
                'discount_type',
            ]);
        });
    }
};
