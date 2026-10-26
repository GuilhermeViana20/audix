<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planilhas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supermercado_id')->constrained('supermercados')->cascadeOnDelete();
            $table->string('tipo', 30);
            $table->string('nome', 120);
            $table->timestamps();

            $table->unique(['supermercado_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planilhas');
    }
};
