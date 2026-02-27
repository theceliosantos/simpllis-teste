<?php

namespace Database\Seeders;

use App\Models\Venda;
use App\Models\Pessoa;
use App\Models\Produto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VendaSeeder extends Seeder
{
    public function run(): void
    {
        $clientes = Pessoa::where('tipo', 'cliente')->get();
        $produtos = Produto::all();

        if ($clientes->isEmpty() || $produtos->isEmpty()) {
            return;
        }

        $qtdVendas = 60;

        for ($i = 1; $i <= $qtdVendas; $i++) {
            DB::transaction(function () use ($clientes, $produtos) {
                $data = now()->subDays(rand(0, 30))->setTime(rand(9, 20), rand(0, 59));

                // cria venda zerada
                $venda = Venda::create([
                    'cliente_id' => $clientes->random()->id,
                    'data_venda' => $data->toDateString(),
                    'valor_total' => 0,
                    'created_at' => $data,
                    'updated_at' => $data,
                ]);

                $total = 0;
                $candidatos = $produtos->random(rand(1, min(5, $produtos->count())));

                foreach ($candidatos as $produto) {
                    $produto->refresh();

                    if ($produto->estoque <= 0) {
                        continue;
                    }


                    $qtd = rand(1, min(6, $produto->estoque));
                    $preco = (float) $produto->preco_venda;

                    $venda->produtos()->attach($produto->id, [
                        'quantidade' => $qtd,
                        'preco' => $preco,
                    ]);

                    $produto->decrement('estoque', $qtd);

                    $total += $qtd * $preco;
                }

                if ($total <= 0) {
                    $venda->delete();
                    return;
                }

                $venda->update(['valor_total' => $total]);
            });
        }
    }
}