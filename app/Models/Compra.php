<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{

    protected $fillable = [
        'fornecedor_id',
        'data_compra',
        'valor_total'
    ];
    public function fornecedor()
    {
        return $this->belongsTo(Pessoa::class, 'fornecedor_id');
    }

    public function produtos()
    {
        return $this->belongsToMany(
            Produto::class,
            'celio.compra_produto',
            'compra_id',
            'produto_id'
        )->withPivot('quantidade', 'preco');
    }

    protected static function booted()
    {
        static::created(function ($compra) {
            foreach ($compra->produtos as $produto) {
                $produto->aumentarEstoque($produto->pivot->quantidade);
            }
        });
    }

}
