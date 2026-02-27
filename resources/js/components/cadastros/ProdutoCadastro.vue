<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import axios from 'axios'

const BASE_URL = '/api/produtos'
const abaSelecionada = ref('lista')
const carregando = ref(false)
const produtos = ref([])
const busca = ref('')
const paginaAtual = ref(1)
const itensPorPagina = 10
const mensagemErro = ref('')

const form = reactive({
  id: null,
  nome: '',
  grupo_nome: '',
  marca_nome: '',
  ativo: true
})

const modoEdicao = computed(() => form.id !== null)

async function carregarProdutos() {
  carregando.value = true
  try {
    const { data } = await axios.get(BASE_URL)
    produtos.value = data
  } finally {
    carregando.value = false
  }
}

onMounted(carregarProdutos)

const produtosFiltrados = computed(() => {
  const q = busca.value.trim().toLowerCase()
  if (!q) return produtos.value

  return produtos.value.filter(p =>
    p.nome.toLowerCase().includes(q)
  )
})

watch(busca, () => paginaAtual.value = 1)

const totalPaginas = computed(() =>
  Math.ceil(produtosFiltrados.value.length / itensPorPagina) || 1
)

const dadosPaginados = computed(() => {
  const inicio = (paginaAtual.value - 1) * itensPorPagina
  return produtosFiltrados.value.slice(inicio, inicio + itensPorPagina)
})

function btnClass(tipo) {
  return [
    'px-3 py-1 text-sm border rounded-lg',
    abaSelecionada.value === tipo
      ? 'bg-blue-600 text-white border-blue-600'
      : 'bg-white hover:bg-gray-100'
  ]
}

function resetForm() {
  form.id = null
  form.nome = ''
  form.grupo_nome = ''
  form.marca_nome = ''
  form.ativo = true
  mensagemErro.value = ''
}

function abrirNovo() {
  resetForm()
  abaSelecionada.value = 'novo'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function abrirLista() {
  abaSelecionada.value = 'lista'
}

function abrirEdicao(p) {
  form.id = p.id
  form.nome = p.nome
  form.grupo_nome = p.grupo?.nome ?? ''
  form.marca_nome = p.marca?.nome ?? ''
  form.ativo = p.ativo
  mensagemErro.value = ''

  abaSelecionada.value = 'novo'
}

async function salvar() {

  mensagemErro.value = ''

  if (!form.nome.trim()) {
    mensagemErro.value = 'Informe o nome do produto.'
    return
  }

  if (!form.grupo_nome.trim()) {
    mensagemErro.value = 'Informe o grupo do produto.'
    return
  }

  if (!form.marca_nome.trim()) {
    mensagemErro.value = 'Informe a marca do produto.'
    return
  }

  try {

    if (modoEdicao.value) {
      await axios.put(`${BASE_URL}/${form.id}`, form)
    } else {
      await axios.post(BASE_URL, form)
    }

    await carregarProdutos()
    resetForm()
    abrirLista()

  } catch (err) {

    if (err.response?.status === 422) {
      mensagemErro.value = err.response.data.message ?? 'Dados inválidos.'
    } else {
      mensagemErro.value = 'Erro interno ao salvar produto.'
    }

    setTimeout(() => {
      mensagemErro.value = ''
    }, 3000)
  }
}

async function excluir(p) {
  if (!confirm(`Excluir "${p.nome}"?`)) return
  await axios.delete(`${BASE_URL}/${p.id}`)
  await carregarProdutos()
}
</script>

<template>
  <div class="pt-15 space-y-8">
    <h2 class="text-2xl font-bold">Cadastro de Produtos</h2>

    <!-- Abas -->
    <div class="flex gap-2 flex-wrap">
      <button @click="abrirNovo" :class="btnClass('novo')">
        Novo cadastro
      </button>

      <button @click="abrirLista" :class="btnClass('lista')">
        Produtos cadastrados
      </button>
    </div>

    <!-- FORM -->
    <div v-if="abaSelecionada === 'novo'" class="bg-white border border-gray-200 rounded-xl shadow-sm">
      <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl">
        <h3 class="font-semibold text-gray-700">
          {{ modoEdicao ? 'Editar produto' : 'Novo produto' }}
        </h3>
      </div>

      <div class="p-4">

        <div v-if="mensagemErro"
             class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded-lg text-sm">
          {{ mensagemErro }}
        </div>

        <form class="grid grid-cols-1 md:grid-cols-3 gap-4" @submit.prevent="salvar">

          <div class="md:col-span-2">
            <label class="text-sm text-gray-600">Nome *</label>
            <input
              v-model="form.nome"
              class="mt-1 w-full border rounded-lg px-3 py-2 text-sm"
            />
          </div>

          <div>
            <label class="text-sm text-gray-600">Grupo *</label>
            <input
              v-model="form.grupo_nome"
              placeholder="Digite o grupo"
              class="mt-1 w-full border rounded-lg px-3 py-2 text-sm"
            />
          </div>

          <div>
            <label class="text-sm text-gray-600">Marca *</label>
            <input
              v-model="form.marca_nome"
              placeholder="Digite a marca"
              class="mt-1 w-full border rounded-lg px-3 py-2 text-sm"
            />
          </div>

          <div class="md:col-span-3 flex items-center gap-2">
            <input type="checkbox" v-model="form.ativo" />
            <label class="text-sm text-gray-700">Ativo</label>
          </div>

          <div class="md:col-span-3 flex gap-2">
            <button type="submit"
              class="px-3 py-1 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
              {{ modoEdicao ? 'Salvar' : 'Cadastrar' }}
            </button>

            <button type="button"
              @click="abrirLista"
              class="px-3 py-1 text-sm border rounded-lg bg-white hover:bg-gray-100">
              Cancelar
            </button>
          </div>

        </form>
      </div>
    </div>

    <!-- LISTA -->
    <div v-else class="bg-white border border-gray-200 rounded-xl shadow-sm">

      <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl flex justify-between items-center">
        <h3 class="font-semibold text-gray-700">Produtos cadastrados</h3>

        <input
          v-model="busca"
          placeholder="Buscar produto..."
          class="border rounded-lg px-3 py-1 text-sm w-72"
        />
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
            <tr>
              <th class="px-4 py-3">Nome</th>
              <th class="px-4 py-3">Grupo</th>
              <th class="px-4 py-3">Marca</th>
              <th class="px-4 py-3">Ativo</th>
              <th class="px-4 py-3">Ações</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-100">
            <tr v-for="p in dadosPaginados" :key="p.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-800">
                {{ p.nome }}
              </td>

              <td class="px-4 py-3">
                {{ p.grupo?.nome }}
              </td>

              <td class="px-4 py-3">
                {{ p.marca?.nome }}
              </td>

              <td class="px-4 py-3">
                <span :class="p.ativo ? 'text-green-600' : 'text-gray-400'">
                  {{ p.ativo ? 'Sim' : 'Não' }}
                </span>
              </td>

              <td class="px-4 py-3">
                <button
                  class="text-blue-600 hover:underline"
                  @click="abrirEdicao(p)">
                  Editar
                </button>

                <button
                  class="text-red-600 hover:underline ml-3"
                  @click="excluir(p)">
                  Excluir
                </button>
              </td>
            </tr>

            <tr v-if="!dadosPaginados.length">
              <td colspan="5" class="px-4 py-6 text-center text-gray-400">
                Nenhum produto encontrado
              </td>
            </tr>

          </tbody>
        </table>
      </div>

      <!-- Paginação -->
      <div class="flex items-center justify-between px-4 py-3 border-t bg-gray-50 rounded-b-xl">
        <span class="text-sm text-gray-600">
          Página {{ paginaAtual }} de {{ totalPaginas }}
        </span>

        <div class="flex gap-1">
          <button
            @click="paginaAtual--"
            :disabled="paginaAtual === 1"
            class="px-3 py-1 text-sm border rounded-lg bg-white disabled:opacity-50">
            Anterior
          </button>

          <button
            v-for="n in totalPaginas"
            :key="n"
            @click="paginaAtual = n"
            class="px-3 py-1 text-sm border rounded-lg"
            :class="{
              'bg-blue-600 text-white border-blue-600': paginaAtual === n,
              'bg-white hover:bg-gray-100': paginaAtual !== n
            }">
            {{ n }}
          </button>

          <button
            @click="paginaAtual++"
            :disabled="paginaAtual === totalPaginas"
            class="px-3 py-1 text-sm border rounded-lg bg-white disabled:opacity-50">
            Próxima
          </button>
        </div>
      </div>

    </div>
  </div>
</template>