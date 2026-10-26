<?php

namespace App\Http\Controllers;

use App\Models\Supermercado;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class PdfHigieneController extends Controller
{
    private const ALTURA_UTIL = 570;
    private const CARACTERES_POR_LINHA = 18;

    public function gerar(Request $request, Supermercado $supermercado)
    {
        $ids = collect(explode(',', (string) $request->query('secoes', '')))
            ->map(fn ($id) => (int) $id)->filter()->values();

        $secoes = $supermercado->secoes()->whereIn('id', $ids)->with('linhas')->get();

        $paginas = [];
        foreach ($secoes as $secao) {
            foreach ($this->dividirEmFolhas($secao->linhas) as $bloco) {
                $paginas[] = ['secao' => $secao, 'linhas' => $bloco];
            }
        }

        abort_if(empty($paginas), 422, 'Nenhuma seção selecionada.');

        $logoPath = $supermercado->logo_caminho
            ? Storage::disk('public')->path($supermercado->logo_caminho)
            : null;

        // Logo fixa do sistema (ACCP), no canto esquerdo
        $acoopPath = public_path('images/acoop.png');

        return Pdf::loadView('pdf.higiene', compact('paginas', 'supermercado', 'logoPath', 'acoopPath'))
            ->setPaper('a4', 'landscape')
            ->stream('higiene.pdf');
    }

    private function dividirEmFolhas(Collection $linhas): array
    {
        $folhas = [];
        $atual = [];
        $altura = 0;

        foreach ($linhas as $linha) {
            $h = $this->alturaDaLinha($linha->tipo, $linha->texto);
            if ($atual && $altura + $h > self::ALTURA_UTIL) {
                $folhas[] = $atual;
                $atual = [];
                $altura = 0;
            }
            $atual[] = $linha;
            $altura += $h;
        }

        $folhas[] = $atual;

        return $folhas;
    }

    private function alturaDaLinha(string $tipo, ?string $texto): int
    {
        if ($tipo === 'grupo') {
            return 18;
        }
        $linhas = max(1, (int) ceil(mb_strlen((string) $texto) / self::CARACTERES_POR_LINHA));

        return $linhas <= 1 ? 18 : $linhas * 13 + 5;
    }
}
