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
            $table->foreignId('planilha_id')->constrained('planilhas')->cascadeOnDelete();
            $table->string('nome', 120);
            $table->string('titulo_primeira_coluna', 120)->default('ÁREA DE ATENDIMENTO');
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();

            $table->unique(['planilha_id', 'nome']);
            $table->index(['planilha_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secoes');
    }
};
