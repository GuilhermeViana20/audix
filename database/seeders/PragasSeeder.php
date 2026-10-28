<?php

namespace Database\Seeders;

use App\Models\Praga;
use Illuminate\Database\Seeder;

class PragasSeeder extends Seeder
{
    public function run(): void
    {
        $pragas = [
            1 => 'ABELHA', 2 => 'ARANHA', 3 => 'BARATA', 4 => 'FRANCESINHA',
            5 => 'CUPIM', 6 => 'FORMIGA', 7 => 'MOSCA', 8 => 'MOSQUITO',
            9 => 'RATO', 10 => 'POMBO', 11 => 'ESCORPIÃO',
        ];

        foreach ($pragas as $codigo => $nome) {
            Praga::updateOrCreate(['codigo' => $codigo], ['nome' => $nome, 'imagem_caminho' => "/images/pragas/{$codigo}.png"]);
        }
    }
}
