<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Produto;
use App\Models\Grupo;
use App\Models\Marca;

class ProdutoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $grupos = Grupo::all();
        $marcas = Marca::all();

        for ($i = 1; $i <= 50; $i++) {
            $precoCompra = rand(10, 400);
            $precoVenda  = $precoCompra + rand(5, 120);

            Produto::create([
                'nome' => "Produto {$i}",
                'grupo_id' => $grupos->random()->id,
                'marca_id' => $marcas->random()->id,
                'preco_compra' => $precoCompra,
                'preco_venda' => $precoVenda,
                'estoque' => rand(0, 80),
                'ativo' => (bool) rand(0, 1),
            ]);
        }
    }
}
