<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Supermercado extends Model
{
    protected $table = 'supermercados';

    protected $fillable = ['nome', 'slug', 'logo_caminho'];

    protected static function booted(): void
    {
        static::saving(function (Supermercado $supermercado) {
            if (blank($supermercado->slug)) {
                $supermercado->slug = Str::slug($supermercado->nome);
            }
        });
    }

    public function secoes(): HasMany
    {
        return $this->hasMany(Secao::class, 'supermercado_id')->orderBy('ordem')->orderBy('nome');
    }
}
