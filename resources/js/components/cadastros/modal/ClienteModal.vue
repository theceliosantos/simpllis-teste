<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  aberto: Boolean,
  clientes: Array
})

const emit = defineEmits(['fechar', 'selecionar', 'atualizado'])

const modo = ref('pesquisa')

const inputNome = ref('')
const criando = ref(false)
const mensagemErro = ref('')

const novoCliente = ref({
  nome: '',
  email: '',
  telefone: '',
  sexo: '',
  data_nascimento: ''
})

function fechar() {
  modo.value = 'pesquisa'
  emit('fechar')
}

function selecionar(cliente) {
  emit('selecionar', cliente)
  fechar()
}

function irParaCadastro() {
  modo.value = 'cadastro'
}

function voltarPesquisa() {
  modo.value = 'pesquisa'
}

const clientesFiltrados = computed(() => {
  const q = inputNome.value.trim().toLowerCase()
  if (!q) return props.clientes

  return props.clientes.filter(c =>
    c.nome.toLowerCase().includes(q)
  )
})

async function criarCliente() {
  mensagemErro.value = ''

  if (!novoCliente.value.nome.trim()) {
    mensagemErro.value = 'O nome do cliente é obrigatório.'
    return
  }

  criando.value = true

  try {
    const { data } = await axios.post('/api/pessoas', {
      ...novoCliente.value,
      tipo: 'cliente',
      ativo: true
    })

    emit('atualizado', data)
    selecionar(data)

  } catch (err) {
    mensagemErro.value = 'Erro ao criar cliente.'
  } finally {
    criando.value = false
  }
}

watch(() => props.aberto, (val) => {
  if (!val) {
    modo.value = 'pesquisa'
    inputNome.value = ''
    mensagemErro.value = ''
    novoCliente.value = {
      nome: '',
      email: '',
      telefone: '',
      sexo: '',
      data_nascimento: ''
    }
  }
})

const limitarTelefone = (event) => {
  let raw = String(event.target.value ?? '').replace(/\D/g, '')

  raw = raw.slice(0, 11)

  let formatado = ''

  if (raw.length <= 10) {
    formatado = raw
      .replace(/^(\d{2})(\d)/, '($1) $2')
      .replace(/(\d{4})(\d)/, '$1-$2')
  } else {
    formatado = raw
      .replace(/^(\d{2})(\d)/, '($1) $2')
      .replace(/(\d{5})(\d)/, '$1-$2')
  }

  novoCliente.value.telefone = formatado

  event.target.value = formatado
}

</script>

<template>
  <div
    v-if="aberto"
    class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50"
    @click.self="fechar"
  >
    <div class="relative bg-white w-[600px] rounded-xl shadow-lg p-6 space-y-5">

      <button
        @click="fechar"
        class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 text-xl font-bold"
      >
        ×
      </button>

      <h3 class="text-lg font-semibold pr-8">
        {{ modo === 'pesquisa' ? 'Selecionar Cliente' : 'Registrar Cliente' }}
      </h3>

      <template v-if="modo === 'pesquisa'">

        <input
          v-model="inputNome"
          placeholder="Digite para pesquisar..."
          class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
        />

        <div class="max-h-60 overflow-y-auto border rounded-lg divide-y">
          <div
            v-for="c in clientesFiltrados"
            :key="c.id"
            @click="selecionar(c)"
            class="px-3 py-3 hover:bg-gray-100 cursor-pointer transition"
          >
            <div class="font-medium">{{ c.nome }}</div>
            <div class="text-sm text-gray-500">
              {{ c.email || 'Sem email' }} • {{ c.telefone || 'Sem telefone' }}
            </div>
          </div>

          <div
            v-if="!clientesFiltrados.length"
            class="px-3 py-4 text-center text-gray-400"
          >
            Nenhum cliente encontrado
          </div>
        </div>

        <button
          @click="irParaCadastro"
          class="w-full border border-blue-600 text-blue-600 rounded-lg py-2 text-sm"
        >
          Registrar novo cliente
        </button>

      </template>

      <template v-else>

  <div class="space-y-3">

    <input
      v-model="novoCliente.nome"
      placeholder="Nome*"
      class="w-full border rounded-lg px-3 py-2 text-sm"
    />
    
    <input
      v-model="novoCliente.email"
      placeholder="Email"
      class="w-full border rounded-lg px-3 py-2 text-sm"
    />

    <input
      :value="novoCliente.telefone"
      @input="limitarTelefone"
      placeholder="Telefone"
      inputmode="numeric"
      class="w-full border rounded-lg px-3 py-2 text-sm"
    />

    <div class="grid grid-cols-2 gap-2">

      <select
        v-model="novoCliente.sexo"
        class="border rounded-lg px-3 py-2 text-sm"
      >
        <option value="">Sexo</option>
        <option value="M">Masculino</option>
        <option value="F">Feminino</option>
      </select>

      <input
        type="date"
        v-model="novoCliente.data_nascimento"
        class="border rounded-lg px-3 py-2 text-sm"
      />

    </div>

  </div>

  <div class="flex gap-2 pt-2">

    <button
      @click="voltarPesquisa"
      class="w-1/2 border rounded-lg py-2 text-sm"
    >
      Voltar
    </button>

    <button
      @click="criarCliente"
      :disabled="criando"
      class="w-1/2 bg-blue-600 text-white rounded-lg py-2 text-sm disabled:opacity-50"
    >
      {{ criando ? 'Salvando...' : 'Salvar' }}
    </button>

  </div>

</template>

      <div v-if="mensagemErro" class="text-sm text-red-600">
        {{ mensagemErro }}
      </div>

    </div>
  </div>
</template>