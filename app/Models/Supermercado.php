<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Supermercado extends Model
{
    protected $table = 'supermercados';

    protected $fillable = ['nome', 'nome_social', 'slug', 'logo_caminho'];

    protected static function booted(): void
    {
        static::saving(function (Supermercado $supermercado) {
            if (blank($supermercado->slug)) {
                $supermercado->slug = Str::slug($supermercado->nome);
            }
        });
    }

    public function planilhas(): HasMany
    {
        return $this->hasMany(Planilha::class, 'supermercado_id')->orderBy('tipo');
    }

    /** Planilha do tipo informado; cria a planilha vazia se ainda não existir. */
    public function planilha(string $tipo): Planilha
    {
        abort_unless(array_key_exists($tipo, Planilha::TIPOS), 404);

        return $this->planilhas()->firstOrCreate(
            ['tipo' => $tipo],
            ['nome' => Planilha::TIPOS[$tipo]],
        );
    }

    /** Logo: o link da imagem informado no cadastro. */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_caminho ?: null;
    }
}
