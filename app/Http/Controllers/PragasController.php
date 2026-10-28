<?php

namespace App\Http\Controllers;

use App\Models\Praga;
use App\Models\Secao;
use App\Models\Supermercado;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    /** Salva de uma vez todas as seções montadas na tela (nomes[]). */
    public function store(Request $request, Supermercado $supermercado): RedirectResponse
    {
        $dados = $request->validate([
            'nomes' => ['required', 'array', 'min:1', 'max:50'],
            'nomes.*' => ['required', 'string', 'max:120'],
        ], [
            'nomes.required' => 'Adicione pelo menos uma seção antes de salvar.',
            'nomes.min' => 'Adicione pelo menos uma seção antes de salvar.',
        ]);

        $planilha = $supermercado->planilha('pragas');
        $nomes = collect($dados['nomes'])
            ->map(fn (string $nome) => mb_strtoupper(trim($nome)))
            ->filter()
            ->unique()
            ->values();

        $existentes = Secao::where('planilha_id', $planilha->id)->whereIn('nome', $nomes)->pluck('nome');
        if ($existentes->isNotEmpty()) {
            return back()->withErrors(['nomes' => 'Já existe seção com o nome: '.$existentes->implode(', ').'.'])->withInput();
        }

        DB::transaction(function () use ($planilha, $nomes) {
            $ordem = (int) Secao::where('planilha_id', $planilha->id)->max('ordem');
            foreach ($nomes as $nome) {
                Secao::create([
                    'planilha_id' => $planilha->id,
                    'nome' => $nome,
                    'titulo_primeira_coluna' => 'PRAGAS',
                    'ordem' => ++$ordem,
                ]);
            }
        });

        return redirect()->route('pragas.index', $supermercado)->with('ok', $nomes->count().' seção(ões) salva(s).');
    }

    public function destroy(Secao $secao): RedirectResponse
    {
        $supermercado = $secao->planilha->supermercado;
        $nome = $secao->nome;
        $secao->delete();

        return redirect()->route('pragas.index', $supermercado)->with('ok', "Seção {$nome} removida.");
    }
}
