<?php

namespace Database\Seeders;

use App\Models\Compra;
use App\Models\Pessoa;
use App\Models\Produto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompraSeeder extends Seeder
{
    public function run(): void
    {
        $fornecedores = Pessoa::where('tipo', 'fornecedor')->get();
        $produtos = Produto::all();

        if ($fornecedores->isEmpty() || $produtos->isEmpty()) {
            return;
        }

        $qtdCompras = 25;

        for ($i = 1; $i <= $qtdCompras; $i++) {
            DB::transaction(function () use ($fornecedores, $produtos) {
                $data = now()->subDays(rand(0, 45))->setTime(rand(8, 18), rand(0, 59));

                $compra = Compra::create([
                    'fornecedor_id' => $fornecedores->random()->id,
                    'data_compra' => $data->toDateString(),
                    'valor_total' => 0,
                    'created_at' => $data,
                    'updated_at' => $data,
                ]);

                $total = 0;

                $itens = $produtos->random(rand(1, min(6, $produtos->count())));

                foreach ($itens as $produto) {
                    $qtd = rand(5, 30);
                    $preco = (float) $produto->preco_compra;

                    $compra->produtos()->attach($produto->id, [
                        'quantidade' => $qtd,
                        'preco' => $preco,
                    ]);

                    $produto->increment('estoque', $qtd);

                    $total += $qtd * $preco;
                }

                $compra->update(['valor_total' => $total]);
            });
        }
    }
}