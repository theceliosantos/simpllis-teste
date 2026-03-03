<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Models\Grupo;
use App\Models\Marca;
use Illuminate\Http\Request;

class ProdutoController extends Controller
{
    /**
     * Lista produtos
     */
    public function index(Request $request)
    {
        $query = Produto::with(['grupo:id,nome', 'marca:id,nome'])
            ->orderBy('nome');

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->whereRaw('LOWER(nome) LIKE ?', ["%{$search}%"]);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255',
            'grupo_nome' => 'required|string|max:255',
            'marca_nome' => 'required|string|max:255',
            'ativo' => 'boolean'
        ]);

        $grupo = Grupo::whereRaw(
            'LOWER(nome) = ?',
            [strtolower($data['grupo_nome'])]
        )->first();

        if (!$grupo) {
            $grupo = Grupo::create([
                'nome' => $data['grupo_nome']
            ]);
        }

        $marca = Marca::whereRaw(
            'LOWER(nome) = ?',
            [strtolower($data['marca_nome'])]
        )->first();

        if (!$marca) {
            $marca = Marca::create([
                'nome' => $data['marca_nome']
            ]);
        }

        $produto = Produto::create([
            'nome' => $data['nome'],
            'grupo_id' => $grupo->id,
            'marca_id' => $marca->id,
            'preco_compra' => 0,
            'preco_venda' => 0,
            'ativo' => true
        ]);

        return response()->json(
            $produto->load(['grupo:id,nome', 'marca:id,nome']),
            201
        );
    }


    public function show(Produto $produto)
    {
        return response()->json(
            $produto->load(['grupo:id,nome', 'marca:id,nome'])
        );
    }

    public function update(Request $request, Produto $produto)
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255',
            'grupo_nome' => 'required|string|max:255',
            'marca_nome' => 'required|string|max:255',
            'ativo' => 'boolean'
        ]);

        $grupo = Grupo::whereRaw(
            'LOWER(nome) = ?',
            [strtolower($data['grupo_nome'])]
        )->first();

        if (!$grupo) {
            $grupo = Grupo::create([
                'nome' => $data['grupo_nome']
            ]);
        }

        $marca = Marca::whereRaw(
            'LOWER(nome) = ?',
            [strtolower($data['marca_nome'])]
        )->first();

        if (!$marca) {
            $marca = Marca::create([
                'nome' => $data['marca_nome']
            ]);
        }

        $produto->update([
            'nome' => $data['nome'],
            'grupo_id' => $grupo->id,
            'marca_id' => $marca->id,
            'ativo' => $data['ativo'] ?? true
        ]);

        return response()->json(
            $produto->load(['grupo:id,nome', 'marca:id,nome'])
        );
    }

    public function destroy(Produto $produto)
    {
        $produto->delete();

        return response()->noContent();
    }

    public function buscar(Request $request)
    {
        $search = $request->query('search');

        return Produto::where('ativo', true)
            ->when($search, function ($q) use ($search) {
                $q->where('nome', 'ilike', "%{$search}%");
            })
            ->orderBy('nome')
            ->limit(15)
            ->get(['id', 'nome', 'estoque', 'preco_venda']);
    }
}