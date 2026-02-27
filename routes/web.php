<?php

use App\Http\Controllers\PessoaController;
use Illuminate\Support\Facades\Route;


Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
