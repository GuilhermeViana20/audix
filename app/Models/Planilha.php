<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Planilha extends Model
{
    protected $table = 'planilhas';

    protected $fillable = ['supermercado_id', 'tipo', 'nome', 'modelo'];

    /** Modelos (layouts) da folha de temperatura: código => nome exibido no seletor. */
    public const MODELOS_TEMPERATURA = [
        'padrao' => 'Legenda na primeira folha; dias 1 a 16 e 17 a 31',
        'equipamentos' => 'Equipamentos em colunas e dias nas linhas; legenda em todas as folhas',
    ];

    /** Tipos de planilha disponíveis: código => nome exibido. */
    public const TIPOS = [
        'higiene' => 'Planilha de Monitoramento - Higiene e Limpeza Ambiental',
        'pragas' => 'Controle de Pragas',
        'temperatura' => 'Planilha de Monitoramento - Controle de Temperatura',
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
