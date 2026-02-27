<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <div class="pt-20 px-6 md:px-12 max-w-7xl mx-auto space-y-10">

    <!-- Cabeçalho -->
    <div class="space-y-2">
      <h2 class="text-3xl font-bold text-gray-800">
        Central de Cadastros
      </h2>
      <p class="text-gray-500">
        Gerencie todas as entidades principais do sistema
      </p>
    </div>

    <!-- Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

      <!-- Pessoas -->
      <RouterLink
        to="/cadastros/pessoas"
        class="group bg-white border border-gray-200 rounded-2xl p-6 shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-1"
      >
        <div class="flex flex-col items-center text-center space-y-4">

          <div class="w-14 h-14 flex items-center justify-center rounded-xl bg-blue-100 text-blue-600 text-2xl">
            👤
          </div>

          <h3 class="text-lg font-semibold text-gray-800 group-hover:text-blue-600 transition">
            Pessoas
          </h3>

          <p class="text-sm text-gray-500">
            Clientes, fornecedores e funcionários
          </p>

        </div>
      </RouterLink>

      <!-- Produtos -->
      <RouterLink
        to="/cadastros/produtos"
        class="group bg-white border border-gray-200 rounded-2xl p-6 shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-1"
      >
        <div class="flex flex-col items-center text-center space-y-4">

          <div class="w-14 h-14 flex items-center justify-center rounded-xl bg-green-100 text-green-600 text-2xl">
            📦
          </div>

          <h3 class="text-lg font-semibold text-gray-800 group-hover:text-green-600 transition">
            Produtos
          </h3>

          <p class="text-sm text-gray-500">
            Cadastro e organização de produtos
          </p>

        </div>
      </RouterLink>

      <!-- Compras -->
      <RouterLink
        to="/cadastros/compras"
        class="group bg-white border border-gray-200 rounded-2xl p-6 shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-1"
      >
        <div class="flex flex-col items-center text-center space-y-4">

          <div class="w-14 h-14 flex items-center justify-center rounded-xl bg-purple-100 text-purple-600 text-2xl">
            🛒
          </div>

          <h3 class="text-lg font-semibold text-gray-800 group-hover:text-purple-600 transition">
            Compras
          </h3>

          <p class="text-sm text-gray-500">
            Lançamento de compras e controle de estoque
          </p>

        </div>
      </RouterLink>

      <!-- Vendas -->
      <RouterLink
        to="/cadastros/vendas"
        class="group bg-white border border-gray-200 rounded-2xl p-6 shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-1"
      >
        <div class="flex flex-col items-center text-center space-y-4">

          <div class="w-14 h-14 flex items-center justify-center rounded-xl bg-red-100 text-red-600 text-2xl">
            💰
          </div>

          <h3 class="text-lg font-semibold text-gray-800 group-hover:text-red-600 transition">
            Vendas
          </h3>

          <p class="text-sm text-gray-500">
            Registro de vendas e saída de estoque
          </p>

        </div>
      </RouterLink>

    </div>

  </div>
</template>