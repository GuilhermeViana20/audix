<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tabela de produtos e temperatura do setor (temperatura, modelo equipamentos): [[produto, temperatura], ...]. */
    public function up(): void
    {
        Schema::table('secoes', function (Blueprint $table) {
            $table->json('legenda')->nullable()->after('titulo_primeira_coluna');
        });
    }

    public function down(): void
    {
        Schema::table('secoes', function (Blueprint $table) {
            $table->dropColumn('legenda');
        });
    }
};
