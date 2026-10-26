<?php

use App\Http\Controllers\LinhaPlanilhaController;
use App\Http\Controllers\SecaoController;
use App\Http\Controllers\SupermercadoController;
use App\Http\Controllers\PdfHigieneController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SupermercadoController::class, 'index'])->name('supermercados.index');
Route::get('/supermercados/{supermercado}', [SupermercadoController::class, 'show'])->name('supermercados.show');

Route::get('/higiene/{supermercado?}', function (?\App\Models\Supermercado $supermercado = null) {
    $supermercados = \App\Models\Supermercado::orderBy('nome')->get(['id', 'nome']);

    return view('planilha.index', compact('supermercado', 'supermercados'));
})->name('higiene.index');

Route::get('/higiene/{supermercado}/pdf', [PdfHigieneController::class, 'gerar'])->name('higiene.pdf');

Route::prefix('api')->group(function () {
    // Seções
    Route::get('supermercados/{supermercado}/secoes', [SecaoController::class, 'index']);
    Route::post('supermercados/{supermercado}/secoes', [SecaoController::class, 'store']);
    Route::put('secoes/{secao}', [SecaoController::class, 'update']);
    Route::delete('secoes/{secao}', [SecaoController::class, 'destroy']);

    // Linhas da planilha de higiene
    Route::get('supermercados/{supermercado}/planilha', [SupermercadoController::class, 'planilha']);

    Route::get('secoes/{secao}/linhas', [LinhaPlanilhaController::class, 'index']);
    Route::put('secoes/{secao}/linhas', [LinhaPlanilhaController::class, 'sincronizar']);
    Route::post('secoes/{secao}/copiar-para-todas', [LinhaPlanilhaController::class, 'copiarParaTodas']);

    Route::get('supermercados/{supermercado}/logo', [\App\Http\Controllers\LogoController::class, 'atual']);
    Route::post('supermercados/{supermercado}/logo', [\App\Http\Controllers\LogoController::class, 'enviar']);
    Route::delete('supermercados/{supermercado}/logo', [\App\Http\Controllers\LogoController::class, 'remover']);
});
