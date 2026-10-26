<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supermercado_id')->constrained('supermercados')->cascadeOnDelete();
            $table->string('nome', 120);
            $table->string('titulo_primeira_coluna', 120)->default('ÁREA DE ATENDIMENTO');
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();

            $table->unique(['supermercado_id', 'nome']);
            $table->index(['supermercado_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secoes');
    }
};
