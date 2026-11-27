<?php

namespace App\Http\Controllers;

use App\Models\Planilha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanilhaController extends Controller
{
    /** Troca o modelo (layout) da folha. Por enquanto só a planilha de temperatura tem modelos. */
    public function atualizarModelo(Request $request, Planilha $planilha): JsonResponse
    {
        abort_unless($planilha->tipo === 'temperatura', 422, 'Esta planilha não tem modelos.');

        $dados = $request->validate([
            'modelo' => ['required', Rule::in(array_keys(Planilha::MODELOS_TEMPERATURA))],
        ]);

        $planilha->update($dados);

        return response()->json(['modelo' => $planilha->modelo]);
    }
}
