<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // Billing & Tax settings
            $table->string('billing_email')->nullable()->after('owner_user_id');
            $table->string('tax_id')->nullable()->after('billing_email');
            $table->string('default_currency', 3)->default('USD')->after('tax_id');
            $table->decimal('default_tax_rate', 5, 2)->nullable()->after('default_currency');

            // Branding
            $table->string('logo_path')->nullable()->after('default_tax_rate');
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
