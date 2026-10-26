<?php

namespace App\Http\Controllers;

use App\Models\LinhaPlanilha;
use App\Models\Secao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LinhaPlanilhaController extends Controller
{
    /** Lista as linhas da seção, na ordem da planilha. */
    public function index(Secao $secao): JsonResponse
    {
        $linhas = $secao->linhas()->get(['id', 'tipo', 'texto']);

        return response()->json($linhas);
    }

    /**
     * Salva a lista inteira de linhas da seção.
     * Substitui as linhas atuais pelas recebidas, na mesma ordem.
     */
    public function sincronizar(Request $request, Secao $secao): JsonResponse
    {
        $dados = $request->validate([
            'linhas' => ['present', 'array', 'max:300'],
            'linhas.*.tipo' => ['required', 'in:linha,grupo'],
            'linhas.*.texto' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($secao, $dados) {
            $secao->linhas()->delete();

            foreach (array_values($dados['linhas']) as $pos => $linha) {
                LinhaPlanilha::create([
                    'secao_id' => $secao->id,
                    'ordem' => $pos,
                    'tipo' => $linha['tipo'],
                    'texto' => $linha['tipo'] === 'grupo'
                        ? mb_strtoupper(trim($linha['texto'] ?? ''), 'UTF-8')
                        : trim($linha['texto'] ?? ''),
                ]);
            }
        });

        return response()->json($secao->linhas()->get(['id', 'tipo', 'texto']));
    }

    /** Copia as linhas e o título desta seção para todas as seções do mesmo supermercado. */
    public function copiarParaTodas(Secao $secao): JsonResponse
    {
        $modelo = $secao->linhas()->get(['tipo', 'texto'])->toArray();
        $titulo = $secao->titulo_primeira_coluna;

        DB::transaction(function () use ($secao, $modelo, $titulo) {
            $outras = Secao::where('supermercado_id', $secao->supermercado_id)
                ->where('id', '!=', $secao->id)
                ->get();

            foreach ($outras as $outra) {
                $outra->update(['titulo_primeira_coluna' => $titulo]);
                $outra->linhas()->delete();

                foreach ($modelo as $pos => $linha) {
                    LinhaPlanilha::create([
                        'secao_id' => $outra->id,
                        'ordem' => $pos,
                        'tipo' => $linha['tipo'],
                        'texto' => $linha['texto'],
                    ]);
                }
            }
        });

        return response()->json(['ok' => true, 'secoes_atualizadas' => Secao::where('supermercado_id', $secao->supermercado_id)->count() - 1]);
    }
}
