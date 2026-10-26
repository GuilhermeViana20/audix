<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cada supermercado passa a ter planilhas por tipo (ex.: higiene, CIP).
 * As seções pertencem à planilha, e não mais direto ao supermercado.
 */
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

        Schema::table('secoes', function (Blueprint $table) {
            $table->foreignId('planilha_id')->nullable()->after('id')->constrained('planilhas')->cascadeOnDelete();
        });

        // Os dados atuais são de higiene: cria a planilha de higiene de cada supermercado e liga as seções a ela.
        $agora = now();
        foreach (DB::table('supermercados')->pluck('id') as $supermercadoId) {
            $planilhaId = DB::table('planilhas')->insertGetId([
                'supermercado_id' => $supermercadoId,
                'tipo' => 'higiene',
                'nome' => 'Higiene e limpeza ambiental',
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);

            DB::table('secoes')->where('supermercado_id', $supermercadoId)->update(['planilha_id' => $planilhaId]);
        }

        Schema::table('secoes', function (Blueprint $table) {
            $table->dropForeign(['supermercado_id']);
            $table->dropUnique(['supermercado_id', 'nome']);
            $table->dropIndex(['supermercado_id', 'ordem']);
        });

        Schema::table('secoes', function (Blueprint $table) {
            $table->dropColumn('supermercado_id');
            $table->foreignId('planilha_id')->nullable(false)->change();
            $table->unique(['planilha_id', 'nome']);
            $table->index(['planilha_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::table('secoes', function (Blueprint $table) {
            $table->foreignId('supermercado_id')->nullable()->after('id')->constrained('supermercados')->cascadeOnDelete();
        });

        foreach (DB::table('planilhas')->get() as $planilha) {
            DB::table('secoes')->where('planilha_id', $planilha->id)->update(['supermercado_id' => $planilha->supermercado_id]);
        }

        Schema::table('secoes', function (Blueprint $table) {
            $table->dropUnique(['planilha_id', 'nome']);
            $table->dropIndex(['planilha_id', 'ordem']);
            $table->dropForeign(['planilha_id']);
            $table->dropColumn('planilha_id');
            $table->foreignId('supermercado_id')->nullable(false)->change();
            $table->unique(['supermercado_id', 'nome']);
            $table->index(['supermercado_id', 'ordem']);
        });

        Schema::dropIfExists('planilhas');
    }
};
