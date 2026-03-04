<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pessoa;
use App\Models\Produto;
use App\Models\Venda;
use App\Models\Compra;
use App\Models\Grupo;
use App\Models\Marca;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Email;

class RelatorioController extends Controller
{

    public function pessoasTodas()
    {
        return Pessoa::orderBy('nome')->get();
    }

    public function pessoasPorTipo($tipo)
    {
        $tiposPermitidos = ['funcionario', 'cliente', 'fornecedor'];

        abort_unless(in_array($tipo, $tiposPermitidos, true), 422);

        return Pessoa::where('tipo', $tipo)
            ->select( 'nome', 'tipo','email','telefone', 'sexo',)
            ->orderBy('nome')
            ->get();
    }

    public function pessoasPorSexo($sexo)
    {
        $sexo = strtoupper(trim($sexo));

        $sexosPermitidos = ['M', 'F'];

        abort_unless(
            in_array($sexo, $sexosPermitidos, true),
            422,
            'Sexo inválido. Use M ou F.'
        );

        return Pessoa::where('sexo', $sexo)
            ->orderBy('nome')
            ->get();
    }

    public function pessoasPorIdade($idade)
    {
        $idade = (int) $idade;

        abort_unless($idade > 0 && $idade < 120, 422, 'Idade inválida.');

        return Pessoa::whereNotNull('data_nascimento')
            ->whereBetween('data_nascimento', [
                now()->subYears($idade + 1)->addDay()->toDateString(),
                now()->subYears($idade)->toDateString()
            ])
            ->select('id', 'nome', 'data_nascimento')
            ->orderBy('nome')
            ->get();
    }

    public function produtosPorGrupo($grupoId)
    {
        return Produto::where('grupo_id', $grupoId)
            ->with(['grupo', 'marca'])
            ->orderBy('nome')
            ->get();
    }

    public function produtosPorMarca($marcaId)
    {
        return Produto::where('marca_id', $marcaId)
            ->with(['grupo', 'marca'])
            ->orderBy('nome')
            ->get();
    }

    public function produtosAtivosInativos()
    {
        return Produto::select('ativo')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('ativo')
            ->orderBy('ativo')
            ->get();
    }

    public function periodo(Request $request): array
    {
        $start = $request->query('start');
        $end = $request->query('end');

        if (!$start || !$end) {
            $end = now()->endOfDay();
            $start = now()->subDays(30)->startOfDay();
            return [$start, $end];
        }

        return [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()];
    }

    public function vendasPeriodo(Request $request)
    {
        [$start, $end] = $this->periodo($request);

        return Venda::whereBetween('data_venda', [$start, $end])
            ->with([
                'cliente:id,nome',
                'produtos:id,nome'
            ])
            ->select('id', 'cliente_id', 'data_venda', 'valor_total')
            ->orderBy('data_venda')
            ->orderByDesc('valor_total')
            ->get();
    }

    public function vendasPorCliente(Request $request)
    {
        [$start, $end] = $this->periodo($request);

        return Venda::select('cliente_id')
            ->selectRaw('SUM(valor_total) as total_vendas')
            ->selectRaw('COUNT(*) as qtd_vendas')
            ->whereBetween('data_venda', [$start->toDateString(), $end->toDateString()])
            ->groupBy('cliente_id')
            ->with('cliente:id,nome')
            ->orderByDesc('total_vendas')
            ->get();
    }

    public function produtosVendidosPeriodo(Request $request)
    {
        [$start, $end] = $this->periodo($request);

        return Produto::select('produtos.id', 'produtos.nome')
            ->selectRaw('SUM(produto_venda.quantidade) as qtd_vendida')
            ->selectRaw('SUM(produto_venda.quantidade * produto_venda.preco) as valor_total')
            ->join('produto_venda', 'produto_venda.produto_id', '=', 'produtos.id')
            ->join('vendas', 'vendas.id', '=', 'produto_venda.venda_id')
            ->whereBetween('data_venda', [$start->toDateString(), $end->toDateString()])
            ->groupBy('produtos.id', 'produtos.nome')
            ->orderByDesc('qtd_vendida')
            ->get();
    }

   public function produtosSemVendas()
    {
        return Produto::doesntHave('vendas')
            ->select('id', 'nome', 'grupo_id', 'marca_id')
            ->with([
                'grupo:id,nome',
                'marca:id,nome'
            ])
            ->orderBy('nome')
            ->get();
    }

    public function mediaTempoVendaPorProduto()
    {
        $compras = DB::table('compra_produto as cp')
            ->join('compras as c', 'c.id', '=', 'cp.compra_id')
            ->select(
                'cp.compra_id',
                'cp.produto_id',
                'c.data_compra',
                'cp.quantidade'
            )
            ->selectRaw("
                SUM(cp.quantidade)
                OVER (PARTITION BY cp.produto_id ORDER BY c.data_compra)
                as saldo_compra
            ");

        $vendas = DB::table('produto_venda as pv')
            ->join('vendas as v', 'v.id', '=', 'pv.venda_id')
            ->select(
                'pv.produto_id',
                'v.data_venda',
                'pv.quantidade'
            )
            ->selectRaw("
                SUM(pv.quantidade)
                OVER (PARTITION BY pv.produto_id ORDER BY v.data_venda)
                as saldo_venda
            ");

        return DB::query()
            ->fromSub($vendas, 'vendas')
            ->joinSub($compras, 'compras', function ($join) {
                $join->on('compras.produto_id', '=', 'vendas.produto_id')
                    ->whereColumn('vendas.saldo_venda', '<=', 'compras.saldo_compra')
                    ->whereColumn('vendas.data_venda', '>=', 'compras.data_compra');
            })
            ->join('produtos as p', 'p.id', '=', 'compras.produto_id')

            ->select(
                'p.nome',
                'compras.compra_id',
                'compras.data_compra',
                DB::raw('compras.quantidade as qtd_comprada')
            )

            ->selectRaw('MIN(vendas.data_venda) as primeira_venda')
            ->selectRaw('MAX(vendas.data_venda) as ultima_venda')

            ->selectRaw("
                ROUND(
                    AVG(vendas.data_venda - compras.data_compra)::numeric
                ,1) as media_dias_lote
            ")

            ->groupBy(
                'p.nome',
                'compras.compra_id',
                'compras.data_compra',
                'compras.quantidade'
            )

            ->orderBy('p.nome')
            ->orderBy('nome')
            ->get();
    }

    public function vendasPorDia(Request $request)
    {
        [$start, $end] = $this->periodo($request);

        return Venda::selectRaw('data_venda as dia')
            ->selectRaw('COUNT(*) as qtd_vendas')
            ->selectRaw('SUM(valor_total) as total_vendas')
            ->whereBetween('data_venda', [$start->toDateString(), $end->toDateString()])
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();
    }

    public function gruposMarcasVendidosPeriodo(Request $request)
    {
        [$start, $end] = $this->periodo($request);

        return DB::table('produto_venda')
            ->join('vendas', 'vendas.id', '=', 'produto_venda.venda_id')
            ->join('produtos', 'produtos.id', '=', 'produto_venda.produto_id')
            ->join('grupos', 'grupos.id', '=', 'produtos.grupo_id')
            ->join('marcas', 'marcas.id', '=', 'produtos.marca_id')

            ->whereBetween('vendas.data_venda', [
                $start->toDateString(),
                $end->toDateString()
            ])

            ->select(
                'grupos.nome as grupo',
                'marcas.nome as marca',

                DB::raw('SUM(produto_venda.quantidade) as qtd_vendida'),
                DB::raw('SUM(produto_venda.quantidade * produto_venda.preco) as valor_total')
            )

            ->groupBy(
                'grupos.nome',
                'marcas.nome'
            )

            ->orderByDesc('qtd_vendida')
            ->get();
    }

    public function comprasPeriodo(Request $request)
    {
        [$start, $end] = $this->periodo($request);

        return Compra::whereBetween('data_compra', [$start->toDateString(), $end->toDateString()])
            ->with(['fornecedor', 'produtos'])
            ->orderBy('data_compra')
            ->get();
    }

    public function comprasPorFornecedor($fornecedorId)
    {
        return Compra::where('fornecedor_id', $fornecedorId)
            ->with(['fornecedor', 'produtos'])
            ->orderBy('created_at')
            ->get();
    }
}