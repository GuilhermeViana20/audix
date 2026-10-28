<?php

namespace App\Http\Controllers;

use App\Models\Praga;
use App\Models\Secao;
use App\Models\Supermercado;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PragasController extends Controller
{
    /** Seções do controle de pragas do supermercado, com as folhas para impressão. */
    public function index(Supermercado $supermercado): View
    {
        $planilha = $supermercado->planilha('pragas');
        $secoes = $planilha->secoes()->get();
        $pragas = Praga::orderBy('codigo')->get();

        return view('pragas.index', compact('supermercado', 'secoes', 'pragas'));
    }

    public function store(Request $request, Supermercado $supermercado): RedirectResponse
    {
        $dados = $request->validate(['nome' => ['required', 'string', 'max:120']]);
        $planilha = $supermercado->planilha('pragas');
        $nome = mb_strtoupper(trim($dados['nome']));

        if (Secao::where('planilha_id', $planilha->id)->where('nome', $nome)->exists()) {
            return back()->withErrors(['nome' => 'Já existe uma seção com esse nome.'])->withInput();
        }

        Secao::create([
            'planilha_id' => $planilha->id,
            'nome' => $nome,
            'titulo_primeira_coluna' => 'PRAGAS',
            'ordem' => (int) Secao::where('planilha_id', $planilha->id)->max('ordem') + 1,
        ]);

        return redirect()->route('pragas.index', $supermercado)->with('ok', "Seção {$nome} adicionada.");
    }

    public function destroy(Secao $secao): RedirectResponse
    {
        $supermercado = $secao->planilha->supermercado;
        $nome = $secao->nome;
        $secao->delete();

        return redirect()->route('pragas.index', $supermercado)->with('ok', "Seção {$nome} removida.");
    }
}
