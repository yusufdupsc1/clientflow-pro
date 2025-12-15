<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->json('events');
            $table->string('secret')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['organization_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
