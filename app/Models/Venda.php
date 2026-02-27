<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venda extends Model
{      
    protected $fillable = [
        'cliente_id',
        'data_venda',
        'valor_total'
    ];

    public function cliente()
    {
        return $this->belongsTo(Pessoa::class, 'cliente_id');
    }

    public function produtos()
    {
        return $this->belongsToMany(Produto::class,'celio.produto_venda','venda_id','produto_id')->withPivot('quantidade', 'preco');
    }

    protected static function booted()
    {
        static::created(function ($venda) {
            foreach ($venda->produtos as $produto) {
                $produto->reduzirEstoque($produto->pivot->quantidade);
            }
        });
    }

}
