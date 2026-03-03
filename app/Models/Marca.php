<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Marca extends Model
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
        static::saving(function ($marca) {
            $marca->nome = mb_convert_case($marca->nome, MB_CASE_TITLE, "UTF-8");
        });
    }
}
