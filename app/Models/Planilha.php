<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Planilha extends Model
{
    protected $table = 'planilhas';

    protected $fillable = ['supermercado_id', 'tipo', 'nome'];

    /** Tipos de planilha disponíveis: código => nome exibido. */
    public const TIPOS = [
        'higiene' => 'Higiene e limpeza ambiental',
        'pragas' => 'Controle de Pragas',
    ];

    public function supermercado(): BelongsTo
    {
        return $this->belongsTo(Supermercado::class, 'supermercado_id');
    }

    public function secoes(): HasMany
    {
        return $this->hasMany(Secao::class, 'planilha_id')->orderBy('ordem')->orderBy('nome');
    }
}
