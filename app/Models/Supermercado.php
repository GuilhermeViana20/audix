<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
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

    public function secoes(): HasMany
    {
        return $this->hasMany(Secao::class, 'supermercado_id')->orderBy('ordem')->orderBy('nome');
    }

    /** Logo pronta para exibir: link externo é usado como está; arquivo enviado vem do storage. */
    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_caminho) {
            return null;
        }

        return self::ehLinkExterno($this->logo_caminho)
            ? $this->logo_caminho
            : Storage::disk('public')->url($this->logo_caminho);
    }

    /** Apaga o arquivo da logo no storage, se ele existir ali (links externos não são apagados). */
    public function apagarLogoDoStorage(): void
    {
        if ($this->logo_caminho && ! self::ehLinkExterno($this->logo_caminho)) {
            Storage::disk('public')->delete($this->logo_caminho);
        }
    }

    public static function ehLinkExterno(string $caminho): bool
    {
        return (bool) preg_match('#^https?://#i', $caminho);
    }
}
