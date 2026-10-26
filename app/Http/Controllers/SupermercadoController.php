<?php

namespace App\Http\Controllers;

use App\Models\Supermercado;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class SupermercadoController extends Controller
{
    public function index(): View
    {
        $supermercados = Supermercado::withCount('secoes')->orderBy('nome')->get();

        return view('supermercados.index', compact('supermercados'));
    }

    public function show(Supermercado $supermercado): View
    {
        $supermercado->load('secoes.linhas');

        return view('supermercados.show', compact('supermercado'));
    }

    /** Seções e linhas do supermercado, em uma só resposta, para montar a planilha. */
    public function planilha(Supermercado $supermercado): JsonResponse
    {
        $secoes = $supermercado->secoes()
            ->orderBy('nome')
            ->with('linhas')
            ->get()
            ->map(fn ($secao) => [
                'id' => $secao->id,
                'nome' => $secao->nome,
                'titulo_primeira_coluna' => $secao->titulo_primeira_coluna,
                'linhas' => $secao->linhas->map(fn ($l) => [
                    'id' => $l->id,
                    'tipo' => $l->tipo,
                    'texto' => $l->texto,
                ])->values(),
            ])->values();

        return response()->json(['secoes' => $secoes]);
    }
}
