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
        Schema::create('produto_venda', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venda_id')->constrained()->cascadeOnDelete();
            $table->foreignId('produto_id')->constrained()->cascadeOnDelete();
            $table->integer('quantidade');
            $table->decimal('preco', 10, 2);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produto_venda');
    }
};
