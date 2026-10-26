<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Praga extends Model
{
    protected $table = 'pragas';

    protected $fillable = ['codigo', 'nome', 'imagem_caminho'];
}
