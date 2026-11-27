<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Modelo (layout) da folha: cada supermercado pode ter o seu na planilha de temperatura. */
    public function up(): void
    {
        Schema::table('planilhas', function (Blueprint $table) {
            $table->string('modelo', 30)->default('padrao')->after('nome');
        });
    }

    public function down(): void
    {
        Schema::table('planilhas', function (Blueprint $table) {
            $table->dropColumn('modelo');
        });
    }
};
