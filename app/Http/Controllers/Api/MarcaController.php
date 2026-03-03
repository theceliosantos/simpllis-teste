<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Marca;

class MarcaController extends Controller
{
    public function index()
    {
        return Marca::orderBy('nome')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255|unique:grupos,nome'
        ]);

        return Marca::create($data);
    }

    public function show(Marca $marca)
    {
        return $marca;  
    }

    public function update(Request $request, Marca $marca)
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255|unique:grupos,nome,' . $marca->id
        ]);

        $marca->update($data);

        return response()->json($marca);
    }

    public function destroy(Marca $marca)
    {
        $marca->delete();
        return response()->noContent();
    }
}
