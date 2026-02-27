<script setup>
import { ref, computed, watch, onMounted } from 'vue'

import Sidebar from '../components/Sidebar.vue'

import PessoasRelatorio from '../components/relatorios/Pessoa.vue'
import ProdutosRelatorio from '../components/relatorios/Produto.vue'
import VendasRelatorio from '../components/relatorios/Venda.vue'
import ComprasRelatorio from '../components/relatorios/Compra.vue'

// estado atual
const secaoAtiva = ref('pessoas')

onMounted(() => {
  const salva = localStorage.getItem('relatorio_secao')
  if (salva) secaoAtiva.value = salva
})

watch(secaoAtiva, (nova) => {
  localStorage.setItem('relatorio_secao', nova)
})

// mapa de componentes
const componentes = {
  pessoas: PessoasRelatorio,
  produtos: ProdutosRelatorio,
  vendas: VendasRelatorio,
  compras: ComprasRelatorio,
}

const componenteAtual = computed(() => componentes[secaoAtiva.value])

</script>

<template>
  <div class="flex gap-6">

    <!-- Sidebar -->
    <aside class="w-64 shrink-0">
      <Sidebar @change="secaoAtiva = $event" />
    </aside>

    <!-- Conteúdo -->
    <section class="flex-1">
      <component :is="componenteAtual" />
    </section>

  </div>
</template>