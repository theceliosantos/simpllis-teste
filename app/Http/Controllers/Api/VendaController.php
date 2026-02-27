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
            'nome_cliente' => 'required|string|max:255',
            'data_venda'   => 'required|date',
            'produtos'     => 'required|array|min:1',
            'produtos.*.nome_produto' => 'required|string|max:255',
            'produtos.*.quantidade'   => 'required|integer|min:1',
            'produtos.*.preco'        => 'required|numeric|min:0'
        ]);

        return DB::transaction(function () use ($data) {

            // Buscar ou criar cliente
            $cliente = Pessoa::whereRaw(
                'LOWER(nome) = ? AND tipo = ?',
                [strtolower(trim($data['nome_cliente'])), 'cliente']
            )->first();

            if (!$cliente) {
                abort(422, 'Cliente não cadastrado.');
            }

            $venda = Venda::create([
                'cliente_id' => $cliente->id,
                'data_venda' => $data['data_venda'],
                'valor_total'=> 0
            ]);

            $valorTotal = 0;

            foreach ($data['produtos'] as $item) {

                $produto = Produto::whereRaw(
                    'LOWER(nome) = ?',
                    [strtolower(trim($item['nome_produto']))]
                )->first();

                if (!$produto) {
                    abort(422, "Produto '{$item['nome_produto']}' não existe.");
                }

                if ($produto->estoque < $item['quantidade']) {
                    abort(422, "Estoque insuficiente para '{$produto->nome}'.");
                }

                $produto->update([
                    'preco_venda' => $item['preco']
                ]);

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
            'nome_cliente' => 'required|string|max:255',
            'data_venda'   => 'required|date',
            'produtos'     => 'required|array|min:1',
            'produtos.*.nome_produto' => 'required|string|max:255',
            'produtos.*.quantidade'   => 'required|integer|min:1',
            'produtos.*.preco'        => 'required|numeric|min:0'
        ]);

        return DB::transaction(function () use ($data, $venda) {

            $venda->load('produtos');

            // Devolver estoque antigo
            foreach ($venda->produtos as $produtoAntigo) {
                $produtoAntigo->increment('estoque', $produtoAntigo->pivot->quantidade);
            }

            $venda->produtos()->detach();

            // Buscar ou criar cliente
            $cliente = Pessoa::whereRaw(
                'LOWER(nome) = ? AND tipo = ?',
                [strtolower(trim($data['nome_cliente'])), 'cliente']
            )->first();

            if (!$cliente) {
                abort(422, 'Cliente não cadastrado.');
            }

            $valorTotal = 0;

            foreach ($data['produtos'] as $item) {

                $produto = Produto::whereRaw(
                    'LOWER(nome) = ?',
                    [strtolower(trim($item['nome_produto']))]
                )->first();

                if (!$produto) {
                    abort(422, "Produto '{$item['nome_produto']}' não existe.");
                }

                if ($produto->estoque < $item['quantidade']) {
                    abort(422, "Estoque insuficiente para '{$produto->nome}'.");
                }

                $produto->update([
                    'preco_venda' => $item['preco']
                ]);

                $venda->produtos()->attach($produto->id, [
                    'quantidade' => $item['quantidade'],
                    'preco'      => $item['preco']
                ]);

                $produto->decrement('estoque', $item['quantidade']);

                $valorTotal += $item['quantidade'] * $item['preco'];
            }

            $venda->update([
                'cliente_id' => $cliente->id,
                'data_venda' => $data['data_venda'],
                'valor_total'=> $valorTotal
            ]);

            return response()->json(
                $venda->load(['cliente:id,nome','produtos'])
            );
        });
    }

    public function destroy(Venda $venda)
    {
        return DB::transaction(function () use ($venda) {

            foreach ($venda->produtos as $produto) {
                $produto->increment('estoque', $produto->pivot->quantidade);
            }

            $venda->delete();

            return response()->noContent();
        });
    }
}