<?php

namespace App\Http\Controllers;

use App\Models\Supermercado;
use Illuminate\Contracts\View\View;

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
}
