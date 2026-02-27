<?php

namespace Database\Seeders;

use App\Models\Pessoa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PessoaSeeder extends Seeder
{
    public function run(): void
    {
        // Clientes
        for ($i = 1; $i <= 20; $i++) {
            Pessoa::create([
                'nome' => "Cliente {$i}",
                'email' => "cliente{$i}@teste.com",
                'telefone' => '8899' . str_pad((string)rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                'sexo' => ($i % 2 === 0) ? 'M' : 'F',
                'data_nascimento' => now()->subYears(rand(18, 65))->subDays(rand(0, 365))->toDateString(),
                'tipo' => 'cliente',
                'ativo' => true,
            ]);
        }

        // Fornecedores
        for ($i = 1; $i <= 8; $i++) {
            Pessoa::create([
                'nome' => "Fornecedor {$i}",
                'email' => "fornecedor{$i}@teste.com",
                'telefone' => '8899' . str_pad((string)rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                'sexo' => ($i % 2 === 0) ? 'M' : 'F',
                'data_nascimento' => null,
                'tipo' => 'fornecedor',
                'ativo' => true,
            ]);
        }

        // Funcionários
        for ($i = 1; $i <= 5; $i++) {
            Pessoa::create([
                'nome' => "Funcionario {$i}",
                'email' => "funcionario{$i}@teste.com",
                'telefone' => '8899' . str_pad((string)rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                'sexo' => ($i % 2 === 0) ? 'M' : 'F',
                'data_nascimento' => now()->subYears(rand(20, 50))->subDays(rand(0, 365))->toDateString(),
                'tipo' => 'funcionario',
                'ativo' => true,
            ]);
        }
    }
}