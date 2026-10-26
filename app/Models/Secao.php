<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Secao extends Model
{
    protected $table = 'secoes';

    protected $fillable = ['planilha_id', 'nome', 'titulo_primeira_coluna', 'ordem'];

    protected $attributes = [
        'titulo_primeira_coluna' => 'ÁREA DE ATENDIMENTO',
    ];

    public function planilha(): BelongsTo
    {
        return $this->belongsTo(Planilha::class, 'planilha_id');
    }

    public function linhas(): HasMany
    {
        return $this->hasMany(LinhaPlanilha::class, 'secao_id')->orderBy('ordem');
    }
}
