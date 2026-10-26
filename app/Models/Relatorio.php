<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Registro de cada planilha impressa/gerada em PDF. */
class Relatorio extends Model
{
    protected $table = 'relatorios';

    protected $fillable = ['planilha_id', 'secoes_ids'];

    public function planilha(): BelongsTo
    {
        return $this->belongsTo(Planilha::class, 'planilha_id');
    }
}
