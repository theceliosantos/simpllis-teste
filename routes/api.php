<?php
use App\Http\Controllers\Api\CompraController;
use App\Http\Controllers\Api\PessoaController;
use App\Http\Controllers\Api\VendaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RelatorioController;
use App\Http\Controllers\Api\GrupoController;
use App\Http\Controllers\Api\MarcaController;
use App\Http\Controllers\Api\ProdutoController;

Route::prefix('relatorios')->group(function () {

    // Pessoas
    Route::get('/pessoas', [RelatorioController::class, 'pessoasTodas']);
    Route::get('/pessoas/tipo/{tipo}', [RelatorioController::class, 'pessoasPorTipo']);
    Route::get('/pessoas/sexo/{sexo}', [RelatorioController::class, 'pessoasPorSexo']);
    Route::get('/pessoas/idade/{idade}', [RelatorioController::class, 'pessoasPorIdade']);

    // Produtos
    Route::get('/produtos/por-grupo/{grupo}', [RelatorioController::class, 'produtosPorGrupo']);
    Route::get('/produtos/por-marca/{marca}', [RelatorioController::class, 'produtosPorMarca']);
    Route::get('/produtos/ativos-inativos', [RelatorioController::class, 'produtosAtivosInativos']);

    // Vendas
    Route::get('/vendas/periodo', [RelatorioController::class, 'vendasPeriodo']);
    Route::get('/vendas/por-cliente', [RelatorioController::class, 'vendasPorCliente']);
    Route::get('/vendas/produtos-vendidos', [RelatorioController::class, 'produtosVendidosPeriodo']);
    Route::get('/vendas/grupos-marcas', [RelatorioController::class, 'gruposMarcasVendidosPeriodo']);
    Route::get('/vendas/produtos-sem-venda', [RelatorioController::class, 'produtosSemVendas']);
    Route::get('/vendas/por-dia', [RelatorioController::class, 'vendasPorDia']);
    Route::get('/vendas/media-tempo-por-produto', [RelatorioController::class, 'mediaTempoVendaPorProduto']);
    Route::get('/vendas/grupos-marcas',[RelatorioController::class, 'gruposMarcasVendidosPeriodo']);

    // Compras
    Route::get('/compras/periodo', [RelatorioController::class, 'comprasPeriodo']);
    Route::get('/compras/por-fornecedor/{fornecedor}', [RelatorioController::class, 'comprasPorFornecedor']);

    
});

Route::apiResource('grupos', GrupoController::class);
Route::apiResource('marcas', MarcaController::class);

Route::get('/fornecedores', [PessoaController::class, 'fornecedores']);

//Crud Pessoas

Route::get('/pessoas', [PessoaController::class, 'index']);
Route::post('/pessoas', [PessoaController::class, 'store']);
Route::get('/pessoas/{pessoa}', [PessoaController::class, 'show']);
Route::put('/pessoas/{pessoa}', [PessoaController::class, 'update']);
Route::delete('/pessoas/{pessoa}', [PessoaController::class, 'destroy']);

// crud compras

Route::get('/compras', [CompraController::class, 'index']);
Route::post('/compras', [CompraController::class, 'store']);
Route::get('/compras/{compra}', [CompraController::class, 'show']);
Route::put('/compras/{compra}', [CompraController::class, 'update']);
Route::delete('/compras/{compra}', [CompraController::class, 'destroy']);

// crud produtos

Route::get('/produtos', [ProdutoController::class, 'index']);
Route::post('/produtos', [ProdutoController::class, 'store']);
Route::get('/produtos/{produto}', [ProdutoController::class, 'show']);
Route::put('/produtos/{produto}', [ProdutoController::class, 'update']);
Route::delete('/produtos/{produto}', [ProdutoController::class, 'destroy']);
Route::get('/produtos', [ProdutoController::class, 'buscar']);

// crud Vendas
Route::get('/vendas', [VendaController::class, 'index']);
Route::post('/vendas', [VendaController::class, 'store']);
Route::get('/vendas/{venda}', [VendaController::class, 'show']);
Route::put('/vendas/{venda}', [VendaController::class, 'update']);
Route::delete('/vendas/{venda}', [VendaController::class, 'destroy']);
