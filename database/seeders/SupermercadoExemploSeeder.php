<?php

namespace Database\Seeders;

use App\Models\LinhaPlanilha;
use App\Models\Secao;
use App\Models\Supermercado;
use Illuminate\Database\Seeder;

class SupermercadoExemploSeeder extends Seeder
{
    /** Linhas padrão da planilha de higiene. "grupo" é a faixa cinza. */
    private array $linhasPadrao = [
        ['linha', 'PISO'], ['linha', 'PAREDE'], ['linha', 'TETO'],
        ['linha', 'ESTRADOS'], ['linha', 'MESA INOX'], ['linha', 'SERRAS'],
        ['linha', 'BALANÇA 1'], ['linha', 'BALANÇA 2'], ['linha', 'MOEDOR'],
        ['linha', 'PIA/SIFÃO'], ['linha', 'LIXEIRA'], ['linha', 'BALCÃO DE ATENDIMENTO'],
        ['linha', ''],
        ['grupo', 'ÁREA DE VENDAS'],
        ['linha', 'ILHA CONGELADOS'], ['linha', 'AUTOSSERVIÇO'], ['linha', ''],
        ['grupo', 'CÂMARA RESFRIADA'],
        ['linha', 'PISO'], ['linha', 'PAREDE'], ['linha', 'TETO'],
        ['linha', 'ESTRADOS'], ['linha', 'ESTANTES'], ['linha', 'PORTA'],
        ['linha', 'CORTINA'], ['linha', 'LUMINÁRIAS'], ['linha', 'RALO'],
        ['linha', 'CAIXAS BRANCAS'],
    ];

    public function run(): void
    {
        $supermercado = Supermercado::firstOrCreate(
            ['slug' => 'aconchemax'],
            ['nome' => 'Aconchemax']
        );

        $planilha = $supermercado->planilha('higiene');

        foreach (['AÇOUGUE', 'FLV', 'FRENTE DE LOJA', 'FRIOS'] as $i => $nome) {
            $secao = Secao::firstOrCreate(
                ['planilha_id' => $planilha->id, 'nome' => $nome],
                ['ordem' => $i]
            );

            if ($secao->linhas()->exists()) {
                continue;
            }

            foreach ($this->linhasPadrao as $pos => [$tipo, $texto]) {
                LinhaPlanilha::create([
                    'secao_id' => $secao->id,
                    'ordem' => $pos,
                    'tipo' => $tipo,
                    'texto' => $texto,
                ]);
            }
        }
    }
}
