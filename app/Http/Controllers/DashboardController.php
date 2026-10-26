<?php

namespace App\Http\Controllers;

use App\Models\Planilha;
use App\Models\Relatorio;
use App\Models\Secao;
use App\Models\Supermercado;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'supermercados' => Supermercado::count(),
            'planilhas_ativas' => Planilha::whereHas('secoes')->count(),
            'secoes' => Secao::count(),
            'relatorios' => Relatorio::count(),
        ];

        $supermercados = Supermercado::with('planilhas')->orderBy('nome')->get();
        $recentes = Relatorio::with('planilha.supermercado')->latest()->limit(8)->get();

        return view('dashboard.index', compact('stats', 'supermercados', 'recentes'));
    }
}
