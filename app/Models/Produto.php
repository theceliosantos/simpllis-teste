<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    protected $fillable = [
        'nome',
        'grupo_id',
        'marca_id',
        'preco_compra',
        'preco_venda',
        'estoque',
        'ativo'
    ];

    public function aumentarEstoque($quantidade)
    {
        $this->increment('estoque', $quantidade);
    }

    public function reduzirEstoque($quantidade)
    {
        $this->decrement('estoque', $quantidade);
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function compras()
    {
        return $this->belongsToMany(Compra::class,'celio.compra_produto','produto_id','compra_id')->withPivot('quantidade', 'preco');
    }

    public function vendas()
    {
        return $this->belongsToMany(Venda::class,'celio.produto_venda','produto_id','venda_id')->withPivot('quantidade', 'preco');
    }
}
