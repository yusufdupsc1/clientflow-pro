<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->timestamps();
        });

        $keys = [
            'clients.viewAny', 'clients.view', 'clients.create', 'clients.update', 'clients.delete',
            'projects.viewAny', 'projects.view', 'projects.create', 'projects.update', 'projects.delete',
            'invoices.viewAny', 'invoices.view', 'invoices.create', 'invoices.update', 'invoices.delete',
            'payments.viewAny', 'payments.view', 'payments.create', 'payments.delete',
        ];

        DB::table('permissions')->insert(array_map(fn ($key) => [
            'key' => $key,
            'created_at' => now(),
            'updated_at' => now(),
        ], $keys));
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
