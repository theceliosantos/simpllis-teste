<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  aberto: Boolean,
  produtos: Array
})

const emit = defineEmits(['fechar', 'selecionar', 'atualizado'])

const inputNome = ref('')
const criando = ref(false)
const mensagemErro = ref('')

function fechar() {
  emit('fechar')
}

function selecionar(produto) {
  emit('selecionar', produto)
  fechar()
}

const produtosFiltrados = computed(() => {
  const q = inputNome.value.trim().toLowerCase()
  if (!q) return props.produtos

  return props.produtos.filter(p =>
    p.nome.toLowerCase().includes(q)
  )
})

async function criarOuSelecionar() {
  mensagemErro.value = ''
  const nome = inputNome.value.trim()
  if (!nome) return

  const existente = props.produtos.find(
    p => p.nome.toLowerCase() === nome.toLowerCase()
  )

  if (existente) {
    selecionar(existente)
    return
  }

  criando.value = true

  try {
    const { data } = await axios.post('/api/produtos', { nome })

    emit('atualizado', data)
    selecionar(data)

  } catch (err) {
    mensagemErro.value = 'Produto não existe.'
  } finally {
    criando.value = false
  }
}

watch(() => props.aberto, (val) => {
  if (val) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
    inputNome.value = ''
    mensagemErro.value = ''
  }
})
</script>

<template>
  <div
    v-if="aberto"
    class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50"
    @click.self="fechar"
  >
    <div class="relative bg-white w-[500px] rounded-xl shadow-lg p-6 space-y-5">

      <button
        @click="fechar"
        class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 text-xl font-bold"
      >
        ×
      </button>

      <h3 class="text-lg font-semibold pr-8">
        Selecionar Produto
      </h3>

      <input
        v-model="inputNome"
        placeholder="Digite para pesquisar"
        class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
        @keyup.enter="criarOuSelecionar"
      />

      <div class="max-h-60 overflow-y-auto border rounded-lg">
        <div
          v-for="p in produtosFiltrados"
          :key="p.id"
          @click="selecionar(p)"
          class="px-3 py-2 hover:bg-gray-100 cursor-pointer"
        >
          {{ p.nome }}
        </div>

        <div v-if="!produtosFiltrados.length"
             class="px-3 py-4 text-center text-gray-400">
          Nenhum produto encontrado
        </div>
      </div>

      <div v-if="mensagemErro"
           class="text-sm text-red-600">
        {{ mensagemErro }}
      </div>

      <button
        @click="criarOuSelecionar"
        :disabled="criando"
        class="w-full px-3 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
      >
        Confirmar
      </button>

    </div>
  </div>
</template>