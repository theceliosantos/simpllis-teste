<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pessoa extends Model
{
    protected $fillable = [
        'nome',
        'email',
        'telefone',
        'sexo',
        'data_nascimento',
        'tipo',
        'ativo'
    ];
    
    public function compras()
    {
        return $this->hasMany(Compra::class, 'fornecedor_id');
    }

    public function vendas()
    {
        return $this->hasMany(Venda::class, 'cliente_id');
    }
}
