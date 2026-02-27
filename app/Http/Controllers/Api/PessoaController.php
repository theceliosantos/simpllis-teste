<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pessoa;

class PessoaController extends Controller
{

    public function index()
    {
        return Pessoa::orderBy('nome')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'nullable|email|unique:pessoas,email',
            'telefone' => 'nullable|string|max:255',
            'sexo' => 'nullable|in:M,F',
            'data_nascimento' => 'nullable|date',
            'tipo' => 'required|in:cliente,fornecedor,funcionario',
            'ativo' => 'boolean',
        ]);

        $pessoa = Pessoa::create($data);

        return response()->json($pessoa, 201);
    }

    public function show(Pessoa $pessoa)
    {
        return $pessoa;
    }

    public function update(Request $request, Pessoa $pessoa)
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'nullable|email|unique:pessoas,email,' . $pessoa->id,
            'telefone' => 'nullable|string|max:255',
            'sexo' => 'nullable|in:M,F',
            'data_nascimento' => 'nullable|date',
            'tipo' => 'required|in:cliente,fornecedor,funcionario',
            'ativo' => 'boolean',
        ]);

        $pessoa->update($data);

        return response()->json($pessoa);
    }

    public function destroy(Pessoa $pessoa)
    {
        $pessoa->delete();
        return response()->noContent();
    }
    public function fornecedores()
    {
        return Pessoa::where('tipo', 'fornecedor')
            ->where('ativo', true)
            ->orderBy('nome')
            ->get(['id', 'nome', 'email', 'telefone', 'ativo']);
    }
}
