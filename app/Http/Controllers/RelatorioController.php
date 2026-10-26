<?php

namespace App\Http\Controllers;

use App\Models\Planilha;
use App\Models\Relatorio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RelatorioController extends Controller
{
    /** Registra que a planilha foi impressa/gerada com as seções informadas. */
    public function store(Request $request, Planilha $planilha): JsonResponse
    {
        $dados = $request->validate([
            'secoes' => ['required', 'array', 'min:1', 'max:200'],
            'secoes.*' => ['integer'],
        ]);

        $relatorio = Relatorio::create([
            'planilha_id' => $planilha->id,
            'secoes_ids' => implode(',', $dados['secoes']),
        ]);

        return response()->json(['id' => $relatorio->id], 201);
    }
}
