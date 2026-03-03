<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import ClienteModal from './modal/ClienteModal.vue'

const BASE_URL = '/api/vendas'
const abaSelecionada = ref('lista')
const vendas = ref([])
const pessoas = ref([])
const busca = ref('')
const paginaAtual = ref(1)
const itensPorPagina = 10
const mensagemErro = ref('')
const modalPessoaAberto = ref(false)
const confirmandoId = ref(null)
const buscaProduto = ref({})
const produtosEncontrados = ref({})
let debounceTimeoutProduto = null

const form = reactive({
  id: null,
  cliente_id: null,
  cliente_nome: '',
  data_venda: '',
  produtos: []
})

const modoEdicao = computed(() => form.id !== null)

function dataHoje() {
  const hoje = new Date()
  const ano = hoje.getFullYear()
  const mes = String(hoje.getMonth() + 1).padStart(2, '0')
  const dia = String(hoje.getDate()).padStart(2, '0')
  return `${ano}-${mes}-${dia}`
}

async function carregarVendasEProdutos() {
  const { data } = await axios.get(BASE_URL)
  vendas.value = data
}

async function carregarPessoas() {
  const { data } = await axios.get('/api/pessoas?tipo=cliente')
  pessoas.value = data
}

onMounted(async () => {
  await Promise.all([carregarVendasEProdutos(), carregarPessoas()])
  resetForm()
})

function adicionarProduto() {
  form.produtos.push({
    produto_id: null,
    quantidade: 1,
    preco: 0
  })
}

function removerProduto(index) {
  form.produtos.splice(index, 1)
}

function resetForm() {
  form.id = null
  form.cliente_id = null
  form.cliente_nome = ''
  form.data_venda = dataHoje()
  form.produtos = []
  buscaProduto.value = {}
  produtosEncontrados.value = {}
  adicionarProduto()
  mensagemErro.value = ''
}

const valorTotal = computed(() =>
  form.produtos.reduce((t, i) => t + (Number(i.quantidade) * Number(i.preco)), 0)
)


function selecionarPessoa(pessoa) {
  form.cliente_id = pessoa.id
  form.cliente_nome = pessoa.nome
  modalPessoaAberto.value = false
}

async function salvar() {
  mensagemErro.value = ''

  if (!form.cliente_id) {
    mensagemErro.value = 'Selecione um cliente.'
    return
  }

  if (!form.data_venda) {
    mensagemErro.value = 'Informe a data da venda.'
    return
  }

  if (!form.produtos.length) {
    mensagemErro.value = 'Adicione pelo menos um produto.'
    return
  }

  for (const item of form.produtos) {
    if (!item.produto_id) {
      mensagemErro.value = 'Selecione um produto.'
      return
    }

    if (!item.quantidade || Number(item.quantidade) < 1) {
      mensagemErro.value = 'Quantidade inválida.'
      return
    }

    if (item.preco === null || item.preco === '' || Number(item.preco) < 0) {
      mensagemErro.value = 'Preço inválido.'
      return
    }
  }

  const payload = {
    cliente_id: form.cliente_id,
    data_venda: form.data_venda,
    produtos: form.produtos.map(item => ({
      produto_id: item.produto_id,
      quantidade: Number(item.quantidade),
      preco: Number(item.preco)
    }))
  }

  try {
    if (modoEdicao.value) {
      await axios.put(`${BASE_URL}/${form.id}`, payload)
    } else {
      await axios.post(BASE_URL, payload)
    }

    resetForm()
    await carregarVendasEProdutos()
    abaSelecionada.value = 'lista'
  } catch (err) {
    if (err.response?.status === 422) {
      mensagemErro.value = err.response.data.message ?? 'Dados inválidos.'
    } else {
      console.error(err)
      mensagemErro.value = 'Erro interno ao salvar venda.'
    }

    setTimeout(() => (mensagemErro.value = ''), 3000)
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
    await carregarVendasEProdutos()
  } catch (err) {
    mensagemErro.value = 'Erro ao excluir venda.'
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

function abrirEdicao(v) {
  resetForm()

  form.id = v.id
  form.cliente_id = v.cliente?.id ?? null
  form.cliente_nome = v.cliente?.nome ?? ''
  form.data_venda = v.data_venda

  form.produtos = v.produtos.map((p, index) => {

    buscaProduto.value[index] = p.nome

    return {
      produto_id: p.id,
      quantidade: Number(p.pivot?.quantidade ?? 1),
      preco: Number(p.pivot?.preco ?? 0)
    }
  })

  abaSelecionada.value = 'novo'
}

const vendasFiltradas = computed(() => {
  const q = busca.value.trim().toLowerCase()
  if (!q) return vendas.value
  return vendas.value.filter(v => v.cliente?.nome?.toLowerCase().includes(q))
})

watch(busca, () => (paginaAtual.value = 1))

const totalPaginas = computed(() =>
  Math.ceil(vendasFiltradas.value.length / itensPorPagina) || 1
)

const dadosPaginados = computed(() => {
  const inicio = (paginaAtual.value - 1) * itensPorPagina
  return vendasFiltradas.value.slice(inicio, inicio + itensPorPagina)
})

function btnClass(tipo) {
  return [
    'px-3 py-1 text-sm border rounded-lg',
    abaSelecionada.value === tipo
      ? 'bg-blue-600 text-white border-blue-600'
      : 'bg-white hover:bg-gray-100'
  ]
}

function abrirModalPessoa() {
  console.log('Abrindo modal')
  modalPessoaAberto.value = true
}

const limitarQuantidade = (event, item) => {
  let raw = String(event.target.value ?? '').replace(/\D/g, '')

  raw = raw.slice(0, 5)

  const numero = raw ? Number(raw) : 1

  item.quantidade = numero
  event.target.value = numero
}


const limitarPreco = (event, item) => {
  let raw = String(event.target.value ?? '').replace(/\D/g, '')

  raw = raw.slice(0, 8)

  const numero = raw ? Number(raw) / 100 : 0

  item.preco = numero

  const formatado = numero.toLocaleString('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })

  event.target.value = formatado
}

const formatarDataBR = (data) => {
  if (!data) return ''

  return new Date(data).toLocaleDateString('pt-BR')
}

const formatarmoedaBR = (valor) => {
  if (valor === null || valor === undefined) return ''

  return Number(valor).toLocaleString('pt-BR', {
    style: 'currency',
    currency: 'BRL'
  })
}

function buscarProduto(index, valor = '') {

  if (debounceTimeoutProduto) clearTimeout(debounceTimeoutProduto)

  debounceTimeoutProduto = setTimeout(async () => {

    const { data } = await axios.get('/api/produtos', {
      params: { search: valor || null }
    })

    produtosEncontrados.value[index] = data

  })
}

function selecionarProduto(index, produto) {

  const item = form.produtos[index]

  item.produto_id = produto.id
  item.preco = Number(produto.preco_venda ?? 0)

  buscaProduto.value[index] = produto.nome
  produtosEncontrados.value[index] = []
}

</script>

<template>
  <div class="pt-15 space-y-8">
    <h2 class="text-2xl font-bold">Cadastro de Vendas</h2>

    <!-- Abas -->
    <div class="flex gap-2 flex-wrap">
      <button @click="resetForm(); abaSelecionada = 'novo'" :class="btnClass('novo')">
        Nova venda
      </button>

      <button @click="abaSelecionada = 'lista'" :class="btnClass('lista')">
        Vendas cadastradas
      </button>
    </div>

    <!-- FORM -->
    <div v-if="abaSelecionada === 'novo'" class="bg-white border border-gray-200 rounded-xl shadow-sm">
      <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl">
        <h3 class="font-semibold text-gray-700">
          {{ modoEdicao ? 'Editar venda' : 'Nova venda' }}
        </h3>
      </div>

      <div class="p-4 space-y-6">
        <!-- Cliente + Data -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm text-gray-600">Cliente *</label>

            <div
              @click="abrirModalPessoa"
              class="mt-1 w-full border rounded-lg px-3 py-2 text-sm bg-white cursor-pointer"
            >
              <span :class="form.cliente_nome ? 'text-gray-800' : 'text-gray-400'">
                {{ form.cliente_nome || 'Selecione um cliente' }}
              </span>
            </div>
          </div>

          <div>
            <label class="text-sm text-gray-600">Data *</label>
            <input
              type="date"
              v-model="form.data_venda"
              class="mt-1 w-full border rounded-lg px-3 py-2 text-sm"
            />
          </div>
        </div>

        <!-- Produtos -->
        <div class="space-y-4">
          <div class="flex justify-between items-center">
            <h4 class="font-semibold text-gray-700">Produtos</h4>
            <button @click="adicionarProduto" class="px-3 py-1 text-sm bg-black text-white rounded-lg">
              + Adicionar
            </button>
          </div>

          <!-- Cabeçalho -->
          <div class="hidden md:grid grid-cols-4 gap-3 text-sm text-gray-600 font-semibold text-center">
            <div>Produto</div>
            <div>Quantidade</div>
            <div>Preço</div>
            <div>Ação</div>
          </div>

          <!-- Linhas -->
          <div
            v-for="(item, index) in form.produtos"
            :key="index"
            class="grid grid-cols-1 md:grid-cols-4 gap-3 border p-3 rounded-lg items-center"
          >
            <div class="relative">

              <input
                :value="buscaProduto[index] || ''"
                @input="(e) => {
                  buscaProduto[index] = e.target.value
                  buscarProduto(index, e.target.value)
                }"
                placeholder="Buscar Produto"
                class="border rounded-lg px-2 py-1 text-sm text-center w-full"
              />

              <div
                v-if="produtosEncontrados[index]?.length"
                class="absolute z-20 w-full bg-white border rounded-lg mt-1 max-h-48 overflow-y-auto shadow"
              >
                <div
                  v-for="p in produtosEncontrados[index]"
                  :key="p.id"
                  @click="selecionarProduto(index, p)"
                  class="px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm"
                >
                  {{ p.nome }} (Estoque: {{ p.estoque }})
                </div>
              </div>

            </div>

            <input
                :value="item.quantidade"
                @input="(e) => limitarQuantidade(e, item)"
                inputmode="numeric"
                class="border rounded-lg px-2 py-1 text-sm text-center"
            />

           <input
                :value="item.preco ? item.preco.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) : ''"
                @input="(e) => limitarPreco(e, item)"
                inputmode="numeric"
                class="border rounded-lg px-2 py-1 text-sm text-center"
            />

            <div class="text-center">
              <button @click="removerProduto(index)" class="text-red-600 text-sm hover:underline">
                Remover
              </button>
            </div>
          </div>
        </div>

        <!-- Total -->
        <div class="text-right font-semibold text-lg">
          Total: {{ formatarmoedaBR(valorTotal) }}
        </div>

        <div v-if="mensagemErro" class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded-lg text-sm">
          {{ mensagemErro }}
        </div>

        <button @click="salvar" class="px-4 py-2 bg-black text-white rounded-lg">
          {{ modoEdicao ? 'Atualizar venda' : 'Salvar venda' }}
        </button>
      </div>
    </div>

    <!-- LISTA -->
    <div v-else class="bg-white border border-gray-200 rounded-xl shadow-sm">
      <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl flex justify-between items-center">
        <h3 class="font-semibold text-gray-700">Vendas cadastradas</h3>

        <input v-model="busca" placeholder="Buscar cliente..." class="border rounded-lg px-3 py-1 text-sm w-72" />
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
            <tr>
              <th class="px-4 py-3">Cliente</th>
              <th class="px-4 py-3">Data</th>
              <th class="px-4 py-3">Total</th>
              <th class="px-4 py-3">Ações</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-100">
            <tr v-for="v in dadosPaginados" :key="v.id" class="hover:bg-gray-50">
              <td class="px-4 py-3">{{ v.cliente?.nome }}</td>
              <td class="px-4 py-3">{{ formatarDataBR(v.data_venda) }}</td>
              <td class="px-4 py-3"> {{ formatarmoedaBR(v.valor_total) }}</td>
              <td class="px-4 py-3">
                <button @click="abrirEdicao(v)" class="text-blue-600 hover:underline">Editar</button>
                <button
                    @click="excluir(v)"
                    class="ml-3 hover:underline text-red-600"
                    >
                    {{ confirmandoId === v.id ? 'Confirma' : 'Excluir' }}
                </button>
              </td>
            </tr>

            <tr v-if="!dadosPaginados.length">
              <td colspan="4" class="px-4 py-6 text-gray-400">Nenhuma venda encontrada</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginação -->
      <div class="flex items-center justify-between px-4 py-3 border-t bg-gray-50 rounded-b-xl">
        <span class="text-sm text-gray-600">Página {{ paginaAtual }} de {{ totalPaginas }}</span>

        <div class="flex gap-1">
          <button
            @click="paginaAtual--"
            :disabled="paginaAtual === 1"
            class="px-3 py-1 border rounded-lg bg-white"
          >
            Anterior
          </button>

          <button
            v-for="n in totalPaginas"
            :key="n"
            @click="paginaAtual = n"
            class="px-3 py-1 border rounded-lg"
            :class="{
              'bg-blue-600 text-white border-blue-600': paginaAtual === n,
              'bg-white hover:bg-gray-100': paginaAtual !== n
            }"
          >
            {{ n }}
          </button>

          <button
            @click="paginaAtual++"
            :disabled="paginaAtual === totalPaginas"
            class="px-3 py-1 border rounded-lg bg-white"
          >
            Próxima
          </button>
        </div>
      </div>
    </div>
  </div>

  <ClienteModal
  :aberto="modalPessoaAberto"
  :clientes="pessoas"
  @fechar="modalPessoaAberto = false"
  @selecionar="selecionarPessoa"
  @atualizado="(novoCliente) => pessoas.push(novoCliente)"
/>
</template>