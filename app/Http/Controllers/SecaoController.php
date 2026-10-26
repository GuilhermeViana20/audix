<?php

namespace App\Http\Controllers;

use App\Models\Planilha;
use App\Models\Secao;
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
}
