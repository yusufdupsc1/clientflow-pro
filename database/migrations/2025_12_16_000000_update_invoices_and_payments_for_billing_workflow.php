<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_number')->nullable()->after('project_id');
            $table->date('due_date')->nullable()->after('notes');
            $table->timestamp('sent_at')->nullable()->after('status');
            $table->timestamp('paid_at')->nullable()->after('sent_at');

            $table->unique(['organization_id', 'invoice_number']);
            $table->index('status');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('organization_id')->after('id')->constrained()->cascadeOnDelete();
            $table->string('method')->nullable()->after('amount_cents');
            $table->string('reference')->nullable()->after('method');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['organization_id', 'method', 'reference']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'invoice_number']);
            $table->dropColumn(['invoice_number', 'due_date', 'sent_at', 'paid_at']);
            $table->dropIndex(['status']);
        });
    }
};
