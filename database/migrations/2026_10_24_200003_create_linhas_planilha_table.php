<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('linhas_planilha', function (Blueprint $table) {
            $table->id();
            $table->foreignId('secao_id')->constrained('secoes')->cascadeOnDelete();
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->enum('tipo', ['linha', 'grupo'])->default('linha');
            $table->string('texto', 255)->default('');
            $table->timestamps();

            $table->index(['secao_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('linhas_planilha');
    }
};
