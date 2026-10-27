<?php

namespace App\Http\Controllers;

use App\Models\Planilha;
use App\Models\Secao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportacaoPlanilhaController extends Controller
{
    /**
     * Grava as seções e linhas confirmadas pelo usuário depois da leitura do PDF.
     * Seções já existentes (mesmo nome) são atualizadas; as linhas são substituídas.
     */
    public function store(Request $request, Planilha $planilha): JsonResponse
    {
        $dados = $request->validate([
            'secoes' => ['required', 'array', 'min:1', 'max:200'],
            'secoes.*.nome' => ['required', 'string', 'max:120'],
            'secoes.*.titulo_primeira_coluna' => ['nullable', 'string', 'max:120'],
            'secoes.*.linhas' => ['present', 'array', 'max:500'],
            'secoes.*.linhas.*.tipo' => ['required', 'in:linha,grupo'],
            'secoes.*.linhas.*.texto' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($dados, $planilha) {
            foreach ($dados['secoes'] as $dadosSecao) {
                $nome = mb_strtoupper(trim($dadosSecao['nome']));

                $secao = Secao::firstOrNew(['planilha_id' => $planilha->id, 'nome' => $nome]);
                if (! $secao->exists) {
                    $secao->ordem = (int) Secao::where('planilha_id', $planilha->id)->max('ordem') + 1;
                }
                if (! empty($dadosSecao['titulo_primeira_coluna'])) {
                    $secao->titulo_primeira_coluna = mb_strtoupper($dadosSecao['titulo_primeira_coluna']);
                }
                $secao->save();

                $secao->linhas()->delete();
                foreach (array_values($dadosSecao['linhas']) as $ordem => $linha) {
                    $secao->linhas()->create([
                        'ordem' => $ordem,
                        'tipo' => $linha['tipo'],
                        'texto' => $linha['tipo'] === 'grupo'
                            ? mb_strtoupper(trim((string) $linha['texto']))
                            : trim((string) ($linha['texto'] ?? '')),
                    ]);
                }
            }
        });

        return response()->json(['ok' => true, 'secoes' => count($dados['secoes'])]);
    }
}
