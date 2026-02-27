<script setup>
import { ref, watch, onMounted } from 'vue'

const emit = defineEmits(['change'])

const ativo = ref('pessoas')

onMounted(() => {
  const salva = localStorage.getItem('relatorio_secao')
  if (salva) {
    ativo.value = salva
    emit('change', salva)
  }
})

watch(ativo, (nova) => {
  localStorage.setItem('relatorio_secao', nova)
})

const mudar = (item) => {
  ativo.value = item
  emit('change', item)
}

const itemClass = (item) => [
  'relative w-full text-left px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200',
  ativo.value === item
    ? 'bg-gray-100 text-black'
    : 'text-gray-500 hover:bg-gray-50 hover:text-black'
]
</script>
<template>
  <aside
    class="fixed top-16 left-0 w-64 h-[calc(100vh-4rem)] bg-white border-r border-gray-200 px-6 py-8 flex flex-col">

    <nav class="space-y-8">

      <button @click="mudar('pessoas')" :class="itemClass('pessoas')">
        <span v-if="ativo === 'pessoas'" class="absolute left-0 top-0 h-full w-1 bg-black rounded-r"></span>
        Pessoas
      </button>

      <button @click="mudar('produtos')" :class="itemClass('produtos')">
        <span v-if="ativo === 'produtos'" class="absolute left-0 top-0 h-full w-1 bg-black rounded-r"></span>
        Produtos
      </button>

      <button @click="mudar('vendas')" :class="itemClass('vendas')">
        <span v-if="ativo === 'vendas'" class="absolute left-0 top-0 h-full w-1 bg-black rounded-r"></span>
        Vendas
      </button>

      <button @click="mudar('compras')" :class="itemClass('compras')">
        <span v-if="ativo === 'compras'" class="absolute left-0 top-0 h-full w-1 bg-black rounded-r"></span>
        Compras
      </button>

    </nav>

  </aside>
</template>