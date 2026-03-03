<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    protected $fillable = [
        'nome',
    ];
    public function produtos()
    {
        return $this->hasMany(Produto::class);
    }
    
    protected static function booted()
    {
        static::saving(function ($grupo) {
            $grupo->nome = mb_convert_case($grupo->nome, MB_CASE_TITLE, "UTF-8");
        });
    }
}
