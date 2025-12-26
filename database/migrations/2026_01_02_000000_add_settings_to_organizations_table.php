<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('billing_email')->nullable()->after('owner_user_id');
            $table->string('tax_id')->nullable()->after('billing_email');
            $table->string('default_currency', 3)->default('USD')->after('tax_id');
            $table->decimal('default_tax_rate', 5, 2)->nullable()->after('default_currency');

            $table->string('stripe_mode')->default('test')->after('default_tax_rate');
            $table->string('stripe_live_secret')->nullable()->after('stripe_mode');
            $table->string('stripe_live_publishable_key')->nullable()->after('stripe_live_secret');
            $table->string('stripe_live_webhook_secret')->nullable()->after('stripe_live_publishable_key');
            $table->string('stripe_test_secret')->nullable()->after('stripe_live_webhook_secret');
            $table->string('stripe_test_publishable_key')->nullable()->after('stripe_test_secret');
            $table->string('stripe_test_webhook_secret')->nullable()->after('stripe_test_publishable_key');

            $table->string('branding_logo_path')->nullable()->after('stripe_test_webhook_secret');
            $table->string('branding_color')->nullable()->after('branding_logo_path');

            $table->string('logo_path')->nullable()->after('branding_color');
            $table->text('invoice_footer')->nullable()->after('logo_path');
            $table->string('payment_terms')->nullable()->after('invoice_footer');

            // Address fields for invoices
            $table->string('address_line1')->nullable()->after('payment_terms');
            $table->string('address_line2')->nullable()->after('address_line1');
            $table->string('city')->nullable()->after('address_line2');
            $table->string('state')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('state');
            $table->string('country', 2)->nullable()->after('postal_code');
            $table->string('phone')->nullable()->after('country');
            $table->string('website')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'billing_email',
                'tax_id',
                'default_currency',
                'default_tax_rate',
                'stripe_mode',
                'stripe_live_secret',
                'stripe_live_publishable_key',
                'stripe_live_webhook_secret',
                'stripe_test_secret',
                'stripe_test_publishable_key',
                'stripe_test_webhook_secret',
                'branding_logo_path',
                'branding_color',
                'logo_path',
                'invoice_footer',
                'payment_terms',
                'address_line1',
                'address_line2',
                'city',
                'state',
                'postal_code',
                'country',
                'phone',
                'website',
            ]);
        });
    }
};
