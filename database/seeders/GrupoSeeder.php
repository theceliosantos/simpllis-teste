<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Grupo;

class GrupoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $grupos = [
            'Eletrônicos',
            'Alimentos',
            'Roupas',
            'Limpeza',
            'Informática'
        ];

        foreach ($grupos as $nome) {
            Grupo::create(['nome' => $nome]);
        }
    }
}
