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
}
