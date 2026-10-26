<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinhaPlanilha extends Model
{
    protected $table = 'linhas_planilha';

    protected $fillable = ['secao_id', 'ordem', 'tipo', 'texto'];

    public function secao(): BelongsTo
    {
        return $this->belongsTo(Secao::class, 'secao_id');
    }
}
