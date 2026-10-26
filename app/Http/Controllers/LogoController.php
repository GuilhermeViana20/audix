<?php

namespace App\Http\Controllers;

use App\Models\Supermercado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LogoController extends Controller
{
    /** Recebe a logo do supermercado (PNG, JPG, WEBP ou SVG) e guarda no disco público. */
    public function enviar(Request $request, Supermercado $supermercado): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:4096'],
        ]);

        if ($supermercado->logo_caminho) {
            Storage::disk('public')->delete($supermercado->logo_caminho);
        }

        $caminho = $request->file('logo')->store("logos/{$supermercado->id}", 'public');
        $supermercado->update(['logo_caminho' => $caminho]);

        return response()->json(['logo_url' => Storage::disk('public')->url($caminho)]);
    }

    /** Volta para o padrão (sem logo). */
    public function remover(Supermercado $supermercado): JsonResponse
    {
        if ($supermercado->logo_caminho) {
            Storage::disk('public')->delete($supermercado->logo_caminho);
        }
        $supermercado->update(['logo_caminho' => null]);

        return response()->json(['logo_url' => null]);
    }

    public function atual(Supermercado $supermercado): JsonResponse
    {
        return response()->json([
            'logo_url' => $supermercado->logo_caminho ? Storage::disk('public')->url($supermercado->logo_caminho) : null,
        ]);
    }
}
