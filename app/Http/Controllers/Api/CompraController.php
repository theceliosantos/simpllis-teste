<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\Produto;
use App\Models\Grupo;
use App\Models\Marca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    public function index()
    {
        return response()->json(
            Compra::with(['fornecedor:id,nome', 'produtos.marca', 'produtos.grupo'])
                ->orderByDesc('data_compra')
                ->get()
        );
    }

   public function store(Request $request)
    {
        $data = $request->validate([
            'fornecedor_id' => 'required|exists:pessoas,id',
            'data_compra'   => 'required|date',
            'produtos'      => 'required|array|min:1',

            'produtos.*.produto_id'   => 'nullable|exists:produtos,id',
            'produtos.*.nome_produto' => 'required_without:produtos.*.produto_id|string|max:255',
            'produtos.*.marca_nome'   => 'nullable|string|max:255',
            'produtos.*.grupo_nome'   => 'nullable|string|max:255',
            'produtos.*.quantidade'   => 'required|integer|min:1',
            'produtos.*.preco_compra' => 'required|numeric|min:0',
            'produtos.*.preco_venda'  => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data) {

            $compra = Compra::create([
                'fornecedor_id' => $data['fornecedor_id'],
                'data_compra'   => $data['data_compra'],
                'valor_total'   => 0
            ]);

            $valorTotal = 0;

            foreach ($data['produtos'] as $item) {

                if (!empty($item['produto_id'])) {

                    $produto = Produto::findOrFail($item['produto_id']);

                } else {

                    $nome = ucfirst(strtolower(trim($item['nome_produto'])));

                    $grupo = Grupo::firstOrCreate([
                        'nome' => $item['grupo_nome'] ?? 'Sem Grupo'
                    ]);

                    $marca = Marca::firstOrCreate([
                        'nome' => $item['marca_nome'] ?? 'Sem Marca'
                    ]);

                    $produto = Produto::firstOrCreate(
                        ['nome' => $nome],
                        [
                            'grupo_id'     => $grupo->id,
                            'marca_id'     => $marca->id,
                            'preco_compra' => $item['preco_compra'],
                            'preco_venda'  => $item['preco_venda'],
                            'estoque'      => 0,
                            'ativo'        => true
                        ]
                    );

                    // Atualiza dados caso já existisse
                    $produto->update([
                        'grupo_id'     => $grupo->id,
                        'marca_id'     => $marca->id,
                        'preco_compra' => $item['preco_compra'],
                        'preco_venda'  => $item['preco_venda'],
                    ]);
                }

                $compra->produtos()->attach($produto->id, [
                    'quantidade' => $item['quantidade'],
                    'preco'      => $item['preco_compra']
                ]);

                $produto->increment('estoque', $item['quantidade']);

                $valorTotal += $item['quantidade'] * $item['preco_compra'];
            }

            $compra->update([
                'valor_total' => $valorTotal
            ]);

            return response()->json(
                $compra->load(['fornecedor:id,nome', 'produtos.marca', 'produtos.grupo']),
                201
            );
        });
    }

    public function show(Compra $compra)
    {
        return response()->json(
            $compra->load(['fornecedor:id,nome', 'produtos.marca', 'produtos.grupo'])
        );
    }

    public function update(Request $request, Compra $compra)
    {
        $data = $request->validate([
            'fornecedor_id' => 'required|exists:pessoas,id',
            'data_compra'   => 'required|date',
            'produtos'      => 'required|array|min:1',

            'produtos.*.produto_id'   => 'nullable|exists:produtos,id',
            'produtos.*.nome_produto' => 'required_without:produtos.*.produto_id|string|max:255',
            'produtos.*.marca_nome'   => 'nullable|string|max:255',
            'produtos.*.grupo_nome'   => 'nullable|string|max:255',
            'produtos.*.quantidade'   => 'required|integer|min:1',
            'produtos.*.preco_compra' => 'required|numeric|min:0',
            'produtos.*.preco_venda'  => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data, $compra) {

            $compra->load('produtos');

            foreach ($compra->produtos as $produtoAntigo) {
                $produtoAntigo->decrement(
                    'estoque',
                    (int) $produtoAntigo->pivot->quantidade
                );
            }

            $compra->update([
                'fornecedor_id' => $data['fornecedor_id'],
                'data_compra'   => $data['data_compra'],
            ]);

            $valorTotal = 0;
            $sync = [];

            foreach ($data['produtos'] as $item) {

                $precoCompra = (float) str_replace(',', '.', $item['preco_compra']);
                $precoVenda  = (float) str_replace(',', '.', $item['preco_venda']);
                $quantidade  = (int) $item['quantidade'];

                if (!empty($item['produto_id'])) {

                    $produto = Produto::findOrFail($item['produto_id']);

                    $produto->update([
                        'preco_compra' => $precoCompra,
                        'preco_venda'  => $precoVenda,
                    ]);

                } else {

                    $grupo = Grupo::firstOrCreate([
                        'nome' => $item['grupo_nome'] ?? 'Sem Grupo'
                    ]);

                    $marca = Marca::firstOrCreate([
                        'nome' => $item['marca_nome'] ?? 'Sem Marca'
                    ]);

                    $produto = Produto::create([
                        'nome'         => trim($item['nome_produto']),
                        'grupo_id'     => $grupo->id,
                        'marca_id'     => $marca->id,
                        'preco_compra' => $precoCompra,
                        'preco_venda'  => $precoVenda,
                        'estoque'      => 0,
                        'ativo'        => true
                    ]);
                }

                $sync[$produto->id] = [
                    'quantidade' => $quantidade,
                    'preco'      => $precoCompra,
                ];

                $produto->increment('estoque', $quantidade);

                $valorTotal += $quantidade * $precoCompra;
            }

            $compra->produtos()->sync($sync);

            $compra->update([
                'valor_total' => $valorTotal
            ]);

            return response()->json(
                $compra->load([
                    'fornecedor:id,nome',
                    'produtos.marca',
                    'produtos.grupo'
                ]),
                200
            );
        });
    }
    public function destroy(Compra $compra)
    {
        return DB::transaction(function () use ($compra) {

            foreach ($compra->produtos as $produto) {
                $produto->decrement('estoque', $produto->pivot->quantidade);
            }

            $compra->delete();

            return response()->noContent();
        });
    }
}