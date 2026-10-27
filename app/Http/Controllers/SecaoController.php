<?php

namespace App\Http\Controllers;

use App\Models\Planilha;
use App\Models\Secao;
use App\Models\Supermercado;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SecaoController extends Controller
{
    /** Cria uma seção na planilha (o nome é salvo em maiúsculas). */
    public function store(Request $request, Planilha $planilha): JsonResponse
    {
        $dados = $this->validar($request, $planilha);

        $secao = $planilha->secoes()->create([
            'nome' => $dados['nome'],
            'titulo_primeira_coluna' => $dados['titulo_primeira_coluna'] ?? 'ÁREA DE ATENDIMENTO',
            'ordem' => ($planilha->secoes()->max('ordem') ?? 0) + 1,
        ]);

        return response()->json($secao, 201);
    }

    /** Renomeia a seção ou troca o título da primeira coluna. */
    public function update(Request $request, Secao $secao): JsonResponse
    {
        $dados = $this->validar($request, $secao->planilha, $secao);

        $secao->update($dados);

        return response()->json($secao);
    }

    /** Remove a seção (as linhas são removidas junto, pela chave estrangeira). */
    public function destroy(Secao $secao): JsonResponse
    {
        $secao->delete();

        return response()->json(['ok' => true]);
    }

    private function validar(Request $request, Planilha $planilha, ?Secao $atual = null): array
    {
        $dados = $request->validate([
            'nome' => [
                'required', 'string', 'max:120',
                Rule::unique('secoes', 'nome')
                    ->where('planilha_id', $planilha->id)
                    ->ignore($atual?->id),
            ],
            'titulo_primeira_coluna' => ['nullable', 'string', 'max:120'],
        ], [
            'nome.required' => 'Informe o nome da seção.',
            'nome.unique' => 'Já existe uma seção com esse nome.',
        ]);

        $dados['nome'] = mb_strtoupper(trim($dados['nome']), 'UTF-8');
        $dados['planilha_id'] = $planilha->id;

        if (! array_key_exists('titulo_primeira_coluna', $dados) || blank($dados['titulo_primeira_coluna'])) {
            unset($dados['titulo_primeira_coluna']);
        }

        return $dados;
    }

    /** Copia a seção inteira (título e linhas) para o mesmo tipo de planilha de outro supermercado. */
    public function copiarParaSupermercado(Request $request, Secao $secao): JsonResponse
    {
        $dados = $request->validate([
            'supermercado_id' => ['required', 'integer', 'exists:supermercados,id'],
        ]);

        $destino = Supermercado::findOrFail($dados['supermercado_id']);
        $planilhaDestino = $destino->planilha($secao->planilha->tipo);

        if (Secao::where('planilha_id', $planilhaDestino->id)->where('nome', $secao->nome)->exists()) {
            return response()->json([
                'message' => "{$destino->nome} já tem a seção {$secao->nome}. Renomeie ou remova a seção de lá antes de copiar.",
            ], 422);
        }

        DB::transaction(function () use ($secao, $planilhaDestino) {
            $nova = Secao::create([
                'planilha_id' => $planilhaDestino->id,
                'nome' => $secao->nome,
                'titulo_primeira_coluna' => $secao->titulo_primeira_coluna,
                'ordem' => (int) Secao::where('planilha_id', $planilhaDestino->id)->max('ordem') + 1,
            ]);

            foreach ($secao->linhas as $linha) {
                $nova->linhas()->create([
                    'ordem' => $linha->ordem,
                    'tipo' => $linha->tipo,
                    'texto' => $linha->texto,
                ]);
            }
        });

        return response()->json(['ok' => true, 'supermercado' => $destino->nome]);
    }
}
