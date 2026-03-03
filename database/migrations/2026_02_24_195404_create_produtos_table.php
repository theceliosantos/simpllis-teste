<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS citext');

        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
             $table->string('nome')->unique();
            $table->foreignId('grupo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marca_id')->constrained()->cascadeOnDelete();
            $table->decimal('preco_compra', 10, 2);
            $table->decimal('preco_venda', 10, 2);
            $table->integer('estoque')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

         DB::statement('ALTER TABLE produtos ALTER COLUMN nome TYPE CITEXT');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
