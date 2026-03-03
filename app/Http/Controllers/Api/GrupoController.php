<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Grupo;

class GrupoController extends Controller
{
    public function index()
    {
        return Grupo::orderBy('nome')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255|unique:grupos,nome'
        ]);

        return Grupo::create($data);
    }
    public function show(Grupo $grupo)
    {
        return $grupo;  
    }

    public function update(Request $request, Grupo $grupo)
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255|unique:grupos,nome,' . $grupo->id
        ]);

        $grupo->update($data);

        return response()->json($grupo);
    }

    public function destroy(Grupo $grupo)
    {
        $grupo->delete();
        return response()->noContent();
    }
}
