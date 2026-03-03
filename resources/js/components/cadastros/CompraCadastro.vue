<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import ProdutoModal from '@/components/cadastros/modal/ProdutoModal.vue'

const BASE_URL = '/api/compras'
const abaSelecionada = ref('lista')
const compras = ref([])
const fornecedores = ref([])
const carregando = ref(false)
const busca = ref('')
const paginaAtual = ref(1)
const itensPorPagina = 10
const mensagemErro = ref('')
const confirmandoId = ref(null)
const modalProdutoAberto = ref(false)
const produtosDisponiveis = ref([])
const produtoIndexAtual = ref(null)

const form = reactive({
    id: null,
    fornecedor_id: '',
    data_compra: '',
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

async function carregarDados() {
    carregando.value = true
    try {
        const [c, f] = await Promise.all([
            axios.get(BASE_URL),
            axios.get('/api/fornecedores')
        ])

        compras.value = c.data
        fornecedores.value = f.data

    } finally {
        carregando.value = false
    }
}

onMounted(() => {
    carregarDados()
    resetForm()
})


function adicionarProduto() {
    form.produtos.push({
        produto_id: null,
        nome_produto: '',
        marca_id: null,
        marca_nome: '',
        grupo_id: null,
        grupo_nome: '',
        quantidade: 1,
        preco_compra: 0,
        preco_compra_formatado: '',
        preco_venda: 0,
        preco_venda_formatado: ''
    })
}

function removerProduto(index) {
    form.produtos.splice(index, 1)
}

const valorTotal = computed(() =>
    form.produtos.reduce((total, item) =>
        total + (item.quantidade * item.preco_compra), 0)
)

async function salvar() {

    if (!form.fornecedor_id || !form.data_compra) {
        mensagemErro.value = 'Preencha fornecedor e data.'
        return
    }

    if (!form.produtos.length) {
        mensagemErro.value = 'Adicione pelo menos um produto.'
        return
    }

    for (const item of form.produtos) {
        if (!item.nome_produto?.trim()) {
            mensagemErro.value = 'Informe o nome do produto.'
            return
        }
    }

    const payload = {
        fornecedor_id: form.fornecedor_id,
        data_compra: form.data_compra,
        produtos: form.produtos.map(p => ({
            produto_id: p.produto_id || null,
            nome_produto: p.nome_produto,
            marca_nome: p.marca_nome || null,
            grupo_nome: p.grupo_nome || null,
            quantidade: Number(p.quantidade),
            preco_compra: parseFloat(String(p.preco_compra).replace(',', '.')) || 0,
            preco_venda: parseFloat(String(p.preco_venda).replace(',', '.')) || 0,
        })),
    }

    try {

        if (modoEdicao.value) {
            await axios.put(`${BASE_URL}/${form.id}`, payload)
        } else {
            await axios.post(BASE_URL, payload)
        }

        resetForm()
        await carregarDados()
        abaSelecionada.value = 'lista'

    } catch (err) {
        mensagemErro.value = 'Erro ao salvar compra.'
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
        await carregarDados()
    } catch (err) {
        mensagemErro.value = 'Erro ao excluir compra.'
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

function abrirEdicao(c) {

    form.id = c.id
    form.fornecedor_id = c.fornecedor_id
    form.data_compra = c.data_compra

    form.produtos = c.produtos.map(p => {

        const precoCompra = Number(p.pivot.preco ?? 0)
        const precoVenda  = Number(p.preco_venda ?? 0)

        return {
            produto_id: p.id,
            nome_produto: p.nome,

            marca_id: p.marca?.id ?? null,
            marca_nome: p.marca?.nome ?? '',

            grupo_id: p.grupo?.id ?? null,
            grupo_nome: p.grupo?.nome ?? '',

            quantidade: Number(p.pivot.quantidade),

            preco_compra: precoCompra,
            preco_compra_formatado: precoCompra.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }),

            preco_venda: precoVenda,
            preco_venda_formatado: precoVenda.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }),
        }
    })

    abaSelecionada.value = 'novo'
}

function resetForm() {
    form.id = null
    form.fornecedor_id = ''
    form.data_compra = dataHoje()
    form.produtos = []
    adicionarProduto()
}


const comprasFiltradas = computed(() => {
    const q = busca.value.toLowerCase()
    if (!q) return compras.value

    return compras.value.filter(c =>
        c.fornecedor?.nome?.toLowerCase().includes(q)
    )
})

watch(busca, () => paginaAtual.value = 1)

const totalPaginas = computed(() =>
    Math.ceil(comprasFiltradas.value.length / itensPorPagina) || 1
)

const dadosPaginados = computed(() => {
    const inicio = (paginaAtual.value - 1) * itensPorPagina
    return comprasFiltradas.value.slice(inicio, inicio + itensPorPagina)
})

function btnClass(tipo) {
    return [
        'px-3 py-1 text-sm border rounded-lg',
        abaSelecionada.value === tipo
            ? 'bg-blue-600 text-white border-blue-600'
            : 'bg-white hover:bg-gray-100'
    ]
}

async function abrirModalProduto(index) {
    produtoIndexAtual.value = index
    modalProdutoAberto.value = true

    const { data } = await axios.get('/api/produtos')
    produtosDisponiveis.value = data
}
function selecionarProduto(produto) {

    if (produtoIndexAtual.value === null) return

    const item = form.produtos[produtoIndexAtual.value]

    item.produto_id = produto.id
    item.nome_produto = produto.nome

    item.marca_id = produto.marca?.id ?? null
    item.marca_nome = produto.marca?.nome ?? ''

    item.grupo_id = produto.grupo?.id ?? null
    item.grupo_nome = produto.grupo?.nome ?? ''

    item.preco_compra = Number(produto.preco_compra ?? 0)
    item.preco_compra_formatado = item.preco_compra.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })

    item.preco_venda = Number(produto.preco_venda ?? 0)
    item.preco_venda_formatado = item.preco_venda.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })

    modalProdutoAberto.value = false
    produtoIndexAtual.value = null
}

const limitarDigitos = (event) => {
    if (event.target.value.length > 5) {
        event.target.value = event.target.value.slice(0, 5);
        codigo.value = event.target.value;
    }
};
const limitarPreco = (event, item, campo) => {
    const raw = String(event.target.value ?? '').replace(/\D/g, '').slice(0, 8) 

    const numero = raw ? Number(raw) / 100 : 0

    item[campo] = numero
    item[`${campo}_formatado`] = numero.toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    })

    event.target.value = item[`${campo}_formatado`]
}

const formatarDataBR = (data) => {
  if (!data) return ''

  return new Date(data).toLocaleDateString('pt-BR')
}

</script>

<template>
    <div class="pt-15 space-y-8">

        <h2 class="text-2xl font-bold">Cadastro de Compras</h2>

        <!-- Abas -->
        <div class="flex gap-2 flex-wrap">
            <button @click="resetForm(); abaSelecionada = 'novo'" :class="btnClass('novo')">
                Nova compra
            </button>

            <button @click="abaSelecionada = 'lista'" :class="btnClass('lista')">
                Compras cadastradas
            </button>
        </div>

        <!-- FORM -->
        <div v-if="abaSelecionada === 'novo'" class="bg-white border border-gray-200 rounded-xl shadow-sm">

            <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl">
                <h3 class="font-semibold text-gray-700">
                    {{ modoEdicao ? 'Editar compra' : 'Nova compra' }}
                </h3>
            </div>

            <div class="p-4 space-y-6">

                <!-- Fornecedor + Data -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div>
                        <label class="text-sm text-gray-600">Fornecedor *</label>
                        <select v-model="form.fornecedor_id" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="">Selecione</option>
                            <option v-for="f in fornecedores" :key="f.id" :value="f.id">
                                {{ f.nome }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Data *</label>
                        <input type="date" v-model="form.data_compra"
                            class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" />
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

                    <!-- Linhas -->
                    <div v-for="(item, index) in form.produtos" :key="index"
                        class="border rounded-xl p-4 space-y-3 bg-gray-50">

                        <!-- Linha 1 -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

                            <div @click="abrirModalProduto(index)" class="w-full border rounded-lg px-3 py-2 text-sm ">
                                {{ item.nome_produto || 'Selecione um produto' }}
                            </div>

                            <div>
                                <label class="text-xs text-gray-500">Marca</label>
                                <input v-model="item.marca_nome" class="w-full border rounded-lg px-3 py-2 text-sm" />
                            </div>

                        </div>

                        <!-- Linha 2 -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">

                            <div>
                                <label class="text-xs text-gray-500">Grupo</label>
                                <input v-model="item.grupo_nome" class="w-full border rounded-lg px-3 py-2 text-sm" />
                            </div>

                            <div>
                                <label class="text-xs text-gray-500">Quantidade</label>
                                <input type="number" v-model="item.quantidade" min="1" @input="limitarDigitos"
                                    class="w-full border rounded-lg px-3 py-2 text-sm" />
                            </div>

                            <div>
                                <label class="text-xs text-gray-500">Preço Compra</label>
                                <input type="text" :value="item.preco_compra_formatado"
                                    @input="(e) => limitarPreco(e, item, 'preco_compra')" maxlength="12"
                                    class="w-full border rounded-lg px-3 py-2 text-sm" />
                            </div>

                        </div>

                        <!-- Linha 3 -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

                            <div>
                                <label class="text-xs text-gray-500">Preço Venda</label>
                                <input type="text" :value="item.preco_venda_formatado"
                                    @input="(e) => limitarPreco(e, item, 'preco_venda')" maxlength="12"
                                    class="w-full border rounded-lg px-3 py-2 text-sm" />
                            </div>

                            <div class="flex items-end justify-end">
                                <button @click="removerProduto(index)" class="text-red-600 text-sm hover:underline">
                                    Remover
                                </button>
                            </div>

                        </div>

                    </div>

                </div>

                <!-- Total -->
                <div class="text-right font-semibold text-lg">
                    Total: R$ {{ valorTotal.toFixed(2) }}
                </div>

                <div v-if="mensagemErro"
                    class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded-lg text-sm">
                    {{ mensagemErro }}
                </div>

                <button @click="salvar" class="px-4 py-2 bg-black text-white rounded-lg">
                    {{ modoEdicao ? 'Atualizar compra' : 'Salvar compra' }}
                </button>

            </div>
        </div>

        <!-- LISTA -->
        <div v-else class="bg-white border border-gray-200 rounded-xl shadow-sm">

            <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl flex justify-between items-center">
                <h3 class="font-semibold text-gray-700">Compras cadastradas</h3>

                <input v-model="busca" placeholder="Buscar fornecedor..."
                    class="border rounded-lg px-3 py-1 text-sm w-72" />
            </div>

            <div class="overflow-x-auto">
                <table class="w-full ">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs text-left ">
                        <tr>
                            <th class="px-4 py-3">Fornecedor</th>
                            <th class="px-4 py-3">Data</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Ações</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="c in dadosPaginados" :key="c.id" class="hover:bg-gray-50">

                            <td class="px-4 py-3">{{ c.fornecedor?.nome }}</td>
                            <td class="px-4 py-3">{{ formatarDataBR(c.data_compra) }}</td>
                            <td class="px-4 py-3">
                                R$ {{ Number(c.valor_total).toFixed(2) }}
                            </td>
                            <td class="px-4 py-3">
                                <button @click="abrirEdicao(c)" class="text-blue-600 hover:underline mr-3">
                                    Editar
                                </button>

                                <button @click="excluir(c)" class="ml-3 hover:underline"
                                    :class="confirmandoId === c.id ? 'text-red-600' : 'text-red-600'">
                                    {{ confirmandoId === c.id ? 'Confirma' : 'Excluir' }}
                                </button>

                            </td>

                        </tr>

                        <tr v-if="!dadosPaginados.length">
                            <td colspan="4" class="px-4 py-6 text-center text-gray-400">
                                Nenhuma compra encontrada
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
                    <button @click="paginaAtual--" :disabled="paginaAtual === 1" class="px-3 py-1 border rounded-lg">
                        Anterior
                    </button>

                    <button v-for="n in totalPaginas" :key="n" @click="paginaAtual = n"
                        class="px-3 py-1 border rounded-lg" :class="{
                            'bg-blue-600 text-white': paginaAtual === n
                        }">
                        {{ n }}
                    </button>

                    <button @click="paginaAtual++" :disabled="paginaAtual === totalPaginas"
                        class="px-3 py-1 border rounded-lg">
                        Próxima
                    </button>
                </div>
            </div>

        </div>

    </div>
    <ProdutoModal :aberto="modalProdutoAberto" :produtos="produtosDisponiveis" @fechar="modalProdutoAberto = false"
        @selecionar="selecionarProduto" @atualizado="produtosDisponiveis.push($event)" />

</template>