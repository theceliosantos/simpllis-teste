<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venda;
use App\Models\Produto;
use App\Models\Pessoa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendaController extends Controller
{
    public function index()
    {
        return response()->json(
            Venda::with(['cliente:id,nome', 'produtos'])
                ->orderByDesc('data_venda')
                ->get()
        );
    }

    public function show(Venda $venda)
    {
        return response()->json(
            $venda->load(['cliente:id,nome', 'produtos'])
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente_id' => 'required|exists:pessoas,id',
            'data_venda' => 'required|date',
            'produtos'   => 'required|array|min:1',
            'produtos.*.produto_id' => 'required|exists:produtos,id',
            'produtos.*.quantidade' => 'required|integer|min:1',
            'produtos.*.preco'      => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data) {

            $venda = Venda::create([
                'cliente_id' => $data['cliente_id'],
                'data_venda' => $data['data_venda'],
                'valor_total'=> 0
            ]);

            $valorTotal = 0;

            foreach ($data['produtos'] as $item) {

                $produto = Produto::findOrFail($item['produto_id']);

                if ($produto->estoque < $item['quantidade']) {
                    abort(422, "Estoque insuficiente para '{$produto->nome}'.");
                }

                $venda->produtos()->attach($produto->id, [
                    'quantidade' => $item['quantidade'],
                    'preco'      => $item['preco']
                ]);

                $produto->decrement('estoque', $item['quantidade']);

                $valorTotal += $item['quantidade'] * $item['preco'];
            }

            $venda->update([
                'valor_total' => $valorTotal
            ]);

            return response()->json(
                $venda->load(['cliente:id,nome', 'produtos']),
                201
            );
        });
    }

    public function update(Request $request, Venda $venda)
    {
        $data = $request->validate([
            'cliente_id' => 'required|exists:pessoas,id',
            'data_venda' => 'required|date',
            'produtos'   => 'required|array|min:1',
            'produtos.*.produto_id' => 'required|exists:produtos,id',
            'produtos.*.quantidade' => 'required|integer|min:1',
            'produtos.*.preco'      => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data, $venda) {

            $venda->load('produtos');

            // Devolve estoque anterior
            foreach ($venda->produtos as $produtoAntigo) {
                $produtoAntigo->increment(
                    'estoque',
                    $produtoAntigo->pivot->quantidade
                );
            }

            $sync = [];
            $valorTotal = 0;

            foreach ($data['produtos'] as $item) {

                $produto = Produto::findOrFail($item['produto_id']);

                if ($produto->estoque < $item['quantidade']) {
                    abort(422, "Estoque insuficiente para '{$produto->nome}'.");
                }

                $sync[$produto->id] = [
                    'quantidade' => $item['quantidade'],
                    'preco'      => $item['preco'],
                ];

                $produto->decrement('estoque', $item['quantidade']);

                $valorTotal += $item['quantidade'] * $item['preco'];
            }

            $venda->produtos()->sync($sync);

            $venda->update([
                'cliente_id' => $data['cliente_id'],
                'data_venda' => $data['data_venda'],
                'valor_total'=> $valorTotal
            ]);

            return response()->json(
                $venda->load(['cliente:id,nome', 'produtos']),
                200
            );
        });
    }

    public function destroy(Venda $venda)
    {
        return DB::transaction(function () use ($venda) {

            $venda->load('produtos');

            foreach ($venda->produtos as $produto) {
                $produto->increment('estoque', $produto->pivot->quantidade);
            }

            $venda->delete();

            return response()->noContent();
        });
    }
}