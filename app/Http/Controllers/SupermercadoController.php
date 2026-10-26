<?php

namespace App\Http\Controllers;

use App\Models\Supermercado;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    public function criar(): View
    {
        return view('supermercados.form', ['supermercado' => new Supermercado()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $this->validar($request);

        $supermercado = Supermercado::create([
            'nome' => $dados['nome'],
            'nome_social' => $dados['nome_social'] ?? null,
            'slug' => $this->slugUnico($dados['nome']),
        ]);

        $this->guardarLogo($request, $supermercado);

        return redirect()->route('supermercados.index')->with('ok', "{$supermercado->nome} cadastrado.");
    }

    public function editar(Supermercado $supermercado): View
    {
        return view('supermercados.form', compact('supermercado'));
    }

    public function update(Request $request, Supermercado $supermercado): RedirectResponse
    {
        $dados = $this->validar($request);

        $supermercado->update([
            'nome' => $dados['nome'],
            'nome_social' => $dados['nome_social'] ?? null,
            'slug' => $this->slugUnico($dados['nome'], $supermercado->id),
        ]);

        $this->guardarLogo($request, $supermercado);

        return redirect()->route('supermercados.index')->with('ok', "{$supermercado->nome} atualizado.");
    }

    /** Remove o supermercado, junto com suas seções, linhas e logo. */
    public function destroy(Supermercado $supermercado): RedirectResponse
    {
        $nome = $supermercado->nome;

        if ($supermercado->logo_caminho) {
            Storage::disk('public')->delete($supermercado->logo_caminho);
        }
        $supermercado->delete();

        return redirect()->route('supermercados.index')->with('ok', "{$nome} removido.");
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

    private function validar(Request $request): array
    {
        return $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'nome_social' => ['nullable', 'string', 'max:120'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:4096'],
        ], [
            'nome.required' => 'Informe o nome fantasia do supermercado.',
        ]);
    }

    /** Guarda a imagem enviada no formulário, substituindo a anterior. */
    private function guardarLogo(Request $request, Supermercado $supermercado): void
    {
        if (! $request->hasFile('logo')) {
            return;
        }

        if ($supermercado->logo_caminho) {
            Storage::disk('public')->delete($supermercado->logo_caminho);
        }

        $caminho = $request->file('logo')->store("logos/{$supermercado->id}", 'public');
        $supermercado->update(['logo_caminho' => $caminho]);
    }

    /** Gera o slug a partir do nome, acrescentando -2, -3… se já existir. */
    private function slugUnico(string $nome, ?int $ignorarId = null): string
    {
        $base = Str::slug($nome) ?: 'supermercado';
        $slug = $base;
        $n = 2;

        while (Supermercado::where('slug', $slug)
            ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
            ->exists()) {
            $slug = "{$base}-{$n}";
            $n++;
        }

        return $slug;
    }
}
