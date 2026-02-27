<script setup>
import { RouterLink, useRoute } from 'vue-router'
const route = useRoute()
</script>

<template>
<header class="fixed top-0 left-0 right-0 h-16 bg-white border-b border-gray-200 flex items-center px-8 z-50">

  <div class=" flex items-center gap-8 flex-1">

    <h1 class="text-lg font-semibold ">
      Simpllis Teste
    </h1>

    <nav class="flex gap-6 text-sm font-medium">
      <RouterLink
        to="/"
        class="transition-colors duration-200 text-gray-500 hover:text-black"
        :class="{ 'text-black border-b-2 border-black pb-1': route.path === '/' }"
      >
        Dashboard
      </RouterLink>

      <RouterLink
        to="/relatorios"
        class="transition-colors duration-200 text-gray-500 hover:text-black"
        :class="{ 'text-black border-b-2 border-black pb-1': route.path.startsWith('/relatorios') }"
      >
        Relatórios
      </RouterLink>

      <RouterLink
        to="/cadastros"
        class="transition-colors duration-200 text-gray-500 hover:text-black"
        :class="{ 'text-black border-b-2 border-black pb-1': route.path.startsWith('/cadastros') }"
      >
        Cadastros
      </RouterLink>
    </nav>

  </div>

  <div class="flex items-center gap-4">
    <div class="text-sm text-gray-500">
      Célio Santos
    </div>

    <div class="w-8 h-8 rounded-full bg-black text-white flex items-center justify-center text-xs font-semibold">
      C
    </div>
  </div>
</header>
</template>