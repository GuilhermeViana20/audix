<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relatorios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planilha_id')->constrained('planilhas')->cascadeOnDelete();
            $table->string('secoes_ids', 500)->nullable();
            $table->timestamps();

            $table->index(['planilha_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relatorios');
    }
};
