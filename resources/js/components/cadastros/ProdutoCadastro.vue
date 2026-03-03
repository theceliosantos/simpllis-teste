<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import GrupoModal from '@/components/cadastros/modal/GrupoModal.vue'
import MarcaModal from '@/components/cadastros/modal/MarcaModal.vue'

const BASE_URL = '/api/produtos'

const abaSelecionada = ref('lista')
const carregando = ref(false)
const produtos = ref([])
const busca = ref('')
const paginaAtual = ref(1)
const itensPorPagina = 10
const mensagemErro = ref('')
const modalGrupoAberto = ref(false)
const modalMarcaAberto = ref(false)
const grupos = ref([])
const marcas = ref([])
const confirmandoId = ref(null)


const form = reactive({
  id: null,
  nome: '',
  grupo_id: null,
  grupo_nome: '',
  marca_id: null,
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
  form.grupo_id = null
  form.grupo_nome = ''
  form.marca_id = null
  form.marca_nome = ''
  form.ativo = true
  mensagemErro.value = ''
}

function abrirNovo() {
  resetForm()
  abaSelecionada.value = 'novo'
}

function abrirLista() {
  abaSelecionada.value = 'lista'
}

function abrirEdicao(p) {
  form.id = p.id
  form.nome = p.nome
  form.grupo_id = p.grupo?.id ?? null
  form.grupo_nome = p.grupo?.nome ?? ''
  form.marca_id = p.marca?.id ?? null
  form.marca_nome = p.marca?.nome ?? ''
  form.ativo = p.ativo
  abaSelecionada.value = 'novo'
}

async function salvar() {
  mensagemErro.value = ''

  if (!form.nome.trim()) {
    mensagemErro.value = 'Informe o nome do produto.'
    return
  }

  if (!form.grupo_nome) {
    mensagemErro.value = 'Informe o grupo.'
    return
  }

  if (!form.marca_nome) {
    mensagemErro.value = 'Informe a marca.'
    return
  }

  try {
    const payload = {
      nome: form.nome,
      grupo_nome: form.grupo_nome,
      marca_nome: form.marca_nome,
      ativo: form.ativo
    }

    if (modoEdicao.value) {
      await axios.put(`${BASE_URL}/${form.id}`, payload)
    } else {
      await axios.post(BASE_URL, payload)
    }

    await carregarProdutos()
    resetForm()
    abrirLista()

  } catch (err) {
    mensagemErro.value = 'Erro ao salvar produto.'
  }
}

const excluir = async (p) => {

  if (confirmandoId.value !== p.id) {
    confirmandoId.value = p.id
    return
  }

  try {
    await axios.delete(`${BASE_URL}/${p.id}`)
    confirmandoId.value = null
    await carregarProdutos()
  } catch (err) {
    mensagemErro.value = 'Erro ao excluir produto.'
    setTimeout(() => mensagemErro.value = '', 3000)
  }
}

watch(confirmandoId, (val) => {
  if (val) {
    setTimeout(() => {
      confirmandoId.value = null
    }, 3000)
  }
})

async function abrirModalGrupo() {
  modalGrupoAberto.value = true
  const { data } = await axios.get('/api/grupos')
  grupos.value = data
}

async function abrirModalMarca() {
  modalMarcaAberto.value = true
  const { data } = await axios.get('/api/marcas')
  marcas.value = data
}

function selecionarGrupo(g) {
  form.grupo_nome = g.nome
  form.grupo_id = g.id
  modalGrupoAberto.value = false
}

function selecionarMarca(m) {
  form.marca_nome = m.nome
  form.marca_id = m.id
  modalMarcaAberto.value = false
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

          <!-- NOME -->
          <div class="md:col-span-2">
            <label class="text-sm text-gray-600">Nome *</label>
            <input v-model="form.nome" class="w-full border rounded-lg px-3 py-2 text-sm" />
          </div>

          <!-- GRUPO -->
          <div>
            <label class="text-sm text-gray-600">Grupo *</label>

            <div @click="abrirModalGrupo"
              class="mt-1 flex items-center justify-between w-full border rounded-lg px-3 py-2 text-sm bg-white cursor-pointer hover:border-blue-500 transition">
              <span :class="form.grupo_nome ? 'text-gray-800' : 'text-gray-400'">
                {{ form.grupo_nome || 'Selecione um grupo' }}
              </span>

              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </div>
          </div>

          <!-- MARCA -->
          <div>
            <label class="text-sm text-gray-600">Marca *</label>

            <div @click="abrirModalMarca"
              class="mt-1 flex items-center justify-between w-full border rounded-lg px-3 py-2 text-sm bg-white cursor-pointer hover:border-blue-500 transition">
              <span :class="form.marca_nome ? 'text-gray-800' : 'text-gray-400'">
                {{ form.marca_nome || 'Selecione uma marca' }}
              </span>

              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </div>
          </div>

          <div class="md:col-span-3 flex items-center gap-2">
            <input type="checkbox" v-model="form.ativo" />
            <label class="text-sm text-gray-700">Ativo</label>
          </div>

          <div class="md:col-span-3 flex gap-2">
            <button type="submit" class="px-3 py-1 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
              {{ modoEdicao ? 'Salvar' : 'Cadastrar' }}
            </button>

            <button type="button" @click="abrirLista"
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
        <h3 class="font-semibold text-gray-700">
          Produtos cadastrados
        </h3>

        <input v-model="busca" placeholder="Buscar produto..." class="border rounded-lg px-3 py-1 text-sm w-72" />
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
                <button class="text-blue-600 hover:underline" @click="abrirEdicao(p)">
                  Editar
                </button>

                <button @click="excluir(p)" class="ml-3 hover:underline"
                  :class="confirmandoId === p.id ? 'text-red-600' : 'text-red-600'">
                  {{ confirmandoId === p.id ? 'Confirma' : 'Excluir' }}
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
    </div>

  </div>

  <!-- MODAIS (fora do bloco principal) -->
  <GrupoModal :aberto="modalGrupoAberto" :grupos="grupos" @fechar="modalGrupoAberto = false"
    @selecionar="selecionarGrupo" @atualizado="grupos.push($event)" />

  <MarcaModal :aberto="modalMarcaAberto" :marcas="marcas" @fechar="modalMarcaAberto = false"
    @selecionar="selecionarMarca" />

</template>