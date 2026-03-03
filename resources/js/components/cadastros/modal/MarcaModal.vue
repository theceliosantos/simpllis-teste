<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  aberto: Boolean,
  marcas: Array
})

const emit = defineEmits(['fechar', 'selecionar', 'atualizado'])

const inputNome = ref('')
const criando = ref(false)
const mensagemErro = ref('')

function fechar() {
  emit('fechar')
}

function selecionar(marca) {
  emit('selecionar', marca)
  fechar()
}

const marcasFiltradas = computed(() => {
  const q = inputNome.value.trim().toLowerCase()
  if (!q) return props.marcas

  return props.marcas.filter(m =>
    m.nome.toLowerCase().includes(q)
  )
})

async function criarOuSelecionar() {

  mensagemErro.value = ''
  const nome = inputNome.value.trim()
  if (!nome) return

  const existente = props.marcas.find(
    m => m.nome.toLowerCase() === nome.toLowerCase()
  )

  if (existente) {
    selecionar(existente)
    return
  }

  criando.value = true

  try {
    const { data } = await axios.post('/api/marcas', { nome })

    emit('atualizado', data)
    selecionar(data)

  } catch (err) {
    mensagemErro.value = 'Erro ao criar marca.'
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

      <!-- Botão X -->
      <button
        @click="fechar"
        class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 text-xl font-bold"
      >
        ×
      </button>

      <!-- Título -->
      <h3 class="text-lg font-semibold pr-8">
        Selecionar ou Criar Marca
      </h3>

      <!-- Input único -->
      <div class="space-y-2">
        <input
          v-model="inputNome"
          placeholder="Digite para pesquisar ou criar..."
          class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
          @keyup.enter="criarOuSelecionar"
        />

      </div>

      <!-- Lista -->
      <div class="max-h-60 overflow-y-auto border rounded-lg">
        <div
          v-for="m in marcasFiltradas"
          :key="m.id"
          @click="selecionar(m)"
          class="px-3 py-2 hover:bg-gray-100 cursor-pointer transition"
        >
          {{ m.nome }}
        </div>

        <div
          v-if="!marcasFiltradas.length"
          class="px-3 py-4 text-center text-gray-400"
        >
          Nenhuma marca encontrada
        </div>
      </div>

      <div
        v-if="mensagemErro"
        class="text-sm text-red-600"
      >
        {{ mensagemErro }}
      </div>

       <button
          @click="criarOuSelecionar"
          :disabled="criando"
          class="w-full px-3 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition"
        >
          Confirmar
        </button>

    </div>
  </div>
</template>