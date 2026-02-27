<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { Chart } from 'chart.js/auto'

const relatorioSelecionado = ref('periodo')
const start = ref(null)
const end = ref(null)
const vendasPeriodo = ref([])
const vendasPorCliente = ref([])
const produtosVendidos = ref([])
const gruposMarcas = ref([])
const produtosSemVendas = ref([])
const mediaTempo = ref([])
const vendasPorDia = ref([])

const usaPeriodo = computed(() => {
    return [
        'periodo',
        'cliente',
        'produtos',
        'gruposMarcas',
        'dia'
    ].includes(relatorioSelecionado.value)
})

const chartDiaRef = ref(null)
let chartDia = null
const chartClienteRef = ref(null)
let chartCliente = null
const paginaAtual = ref(1)
const itensPorPagina = 10

const dadosAtuais = computed(() => {
    if (relatorioSelecionado.value === 'periodo') return vendasPeriodo.value
    if (relatorioSelecionado.value === 'cliente') return vendasPorCliente.value
    if (relatorioSelecionado.value === 'produtos') return produtosVendidos.value
    if (relatorioSelecionado.value === 'gruposMarcas') return gruposMarcas.value
    if (relatorioSelecionado.value === 'semVendas') return produtosSemVendas.value
    if (relatorioSelecionado.value === 'mediaTempo') return mediaTempo.value
    if (relatorioSelecionado.value === 'dia') return vendasPorDia.value
    return []
})

const totalPaginas = computed(() => Math.ceil(dadosAtuais.value.length / itensPorPagina) || 1)

const dadosPaginados = computed(() => {
    const ini = (paginaAtual.value - 1) * itensPorPagina
    return dadosAtuais.value.slice(ini, ini + itensPorPagina)
})

async function fetchJson(url) {
    const res = await fetch(url)
    if (!res.ok) throw new Error(`Erro ${res.status} em ${url}`)
    return res.json()
}

function periodoQuery() {
    if (!start.value || !end.value) return ''
    const qs = new URLSearchParams({ start: start.value, end: end.value })
    return `?${qs.toString()}`
}

function formatMoney(v) {
    const n = Number(v || 0)
    return n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatDateBr(iso) {
    if (!iso) return ''
    const [y, m, d] = String(iso).slice(0, 10).split('-')
    if (!y || !m || !d) return String(iso)
    return `${d}/${m}/${y}`
}

async function carregarVendasPeriodo() {
    vendasPeriodo.value = await fetchJson(`/api/relatorios/vendas/periodo${periodoQuery()}`)
}

async function carregarVendasPorCliente() {
    vendasPorCliente.value = await fetchJson(`/api/relatorios/vendas/por-cliente${periodoQuery()}`)
}

async function carregarProdutosVendidos() {
    produtosVendidos.value = await fetchJson(`/api/relatorios/vendas/produtos-vendidos${periodoQuery()}`)
}

async function carregarGruposMarcas() {
    gruposMarcas.value = await fetchJson(`/api/relatorios/vendas/grupos-marcas${periodoQuery()}`)
}

async function carregarSemVendas() {
    produtosSemVendas.value = await fetchJson(`/api/relatorios/vendas/produtos-sem-venda`)
}

async function carregarMediaTempo() {
    mediaTempo.value = await fetchJson(`/api/relatorios/vendas/media-tempo-por-produto`)
}

async function carregarVendasPorDia() {
    vendasPorDia.value = await fetchJson(`/api/relatorios/vendas/por-dia${periodoQuery()}`)
    await nextTick()
    montarGraficoDia()
}

function montarGraficoDia() {
    if (!chartDiaRef.value) return

    const labels = vendasPorDia.value.map(d => formatDateBr(d.dia))
    const valores = vendasPorDia.value.map(d => Number(d.total_vendas || 0))

    if (chartDia) chartDia.destroy()

    chartDia = new Chart(chartDiaRef.value, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Faturamento por dia',
                data: valores,
                backgroundColor: '#2563eb'
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } }
        }
    })
}

function montarGraficoCliente() {
    if (!chartClienteRef.value) return

    const top = vendasPorCliente.value.slice(0, 10)

    const labels = top.map(c => c.cliente?.nome ?? `Cliente #${c.cliente_id}`)
    const valores = top.map(c => Number(c.total_vendas || 0))

    if (chartCliente) chartCliente.destroy()

    chartCliente = new Chart(chartClienteRef.value, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Total por cliente (Top 10)',
                data: valores,
                backgroundColor: '#16a34a'
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } }
        }
    })
}

async function buscarAtual() {
    paginaAtual.value = 1

    if (relatorioSelecionado.value === 'periodo') return carregarVendasPeriodo()
    if (relatorioSelecionado.value === 'cliente') {
        await carregarVendasPorCliente()
        await nextTick()
        montarGraficoCliente()
        return
    }
    if (relatorioSelecionado.value === 'produtos') return carregarProdutosVendidos()
    if (relatorioSelecionado.value === 'gruposMarcas') return carregarGruposMarcas()
    if (relatorioSelecionado.value === 'dia') return carregarVendasPorDia()
}

watch(relatorioSelecionado, async (novo) => {
    paginaAtual.value = 1

    if (novo === 'periodo') {
        if (vendasPeriodo.value.length === 0)
            await carregarVendasPeriodo()
    }

    if (novo === 'cliente') {
        if (vendasPorCliente.value.length === 0)
            await carregarVendasPorCliente()

        await nextTick()
        montarGraficoCliente()
    }

    if (novo === 'produtos') {
        if (produtosVendidos.value.length === 0)
            await carregarProdutosVendidos()
    }

    if (novo === 'gruposMarcas') {
        if (gruposMarcas.value.length === 0)
            await carregarGruposMarcas()
    }

    if (novo === 'semVendas') {
        if (produtosSemVendas.value.length === 0)
            await carregarSemVendas()
    }

    if (novo === 'mediaTempo') {
        if (mediaTempo.value.length === 0)
            await carregarMediaTempo()
    }

    if (novo === 'dia') {
        if (vendasPorDia.value.length === 0)
            await carregarVendasPorDia()

        await nextTick()
        montarGraficoDia()
    }
})

watch([start, end], async () => {
    if (['periodo', 'cliente', 'produtos', 'gruposMarcas', 'dia'].includes(relatorioSelecionado.value)) {
        await buscarAtual()
    }
})


onMounted(async () => {
    await carregarVendasPeriodo()
})

function btnClass(tipo) {
    return [
        'px-3 py-1 text-sm border rounded-lg',
        relatorioSelecionado.value === tipo
            ? 'bg-blue-600 text-white border-blue-600'
            : 'bg-white hover:bg-gray-100'
    ]
}
</script>

<template>
    <div class="pt-15 space-y-8">

        <h2 class="text-2xl font-bold">Relatórios de Vendas</h2>

        <div class="flex gap-2 flex-wrap">
            <button @click="relatorioSelecionado = 'periodo'" :class="btnClass('periodo')">Período (todas)</button>
            <button @click="relatorioSelecionado = 'cliente'" :class="btnClass('cliente')">Por cliente</button>
            <button @click="relatorioSelecionado = 'produtos'" :class="btnClass('produtos')">Produtos vendidos</button>
            <button @click="relatorioSelecionado = 'gruposMarcas'" :class="btnClass('gruposMarcas')">Grupos /
                marcas</button>
            <button @click="relatorioSelecionado = 'semVendas'" :class="btnClass('semVendas')">Produtos sem
                vendas</button>
            <button @click="relatorioSelecionado = 'mediaTempo'" :class="btnClass('mediaTempo')">Média tempo
                venda</button>
            <button @click="relatorioSelecionado = 'dia'" :class="btnClass('dia')">Vendas por dia</button>
        </div>

        <div v-if="usaPeriodo" class="bg-white border border-gray-200 rounded-xl shadow-sm">
            <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl">
                <h3 class="font-semibold text-gray-700">
                    Filtro de período
                </h3>
            </div>
            <div class="p-4 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="text-xs text-gray-600">
                        Início
                    </label>
                    <input type="date" v-model="start" class="border rounded-lg px-3 py-1 text-sm block">
                </div>
                <div>
                    <label class="text-xs text-gray-600">
                        Fim
                    </label>
                    <input type="date" v-model="end" class="border rounded-lg px-3 py-1 text-sm block">
                </div>
                <button @click="buscarAtual" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">
                    Buscar
                </button>
            </div>
        </div>

        <div v-if="relatorioSelecionado === 'periodo'" class="bg-white border border-gray-200 rounded-xl shadow-sm">

            <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl">
                <h3 class="font-semibold text-gray-700">
                    Vendas do período
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Data</th>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3 text-right">Valor</th>
                            <th class="px-4 py-3 text-center">Itens</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="v in dadosPaginados" :key="v.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-700">{{ formatDateBr(v.data_venda) }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ v.cliente?.nome ?? '-' }}</td>
                            <td class="px-4 py-3 text-right text-gray-800 font-medium">{{ formatMoney(v.valor_total) }}
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ v.produtos?.length ?? 0 }}</td>
                        </tr>

                        <tr v-if="!dadosPaginados.length">
                            <td colspan="4" class="px-4 py-6 text-center text-gray-400">
                                Nenhuma venda encontrada
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
                    <button @click="paginaAtual--" :disabled="paginaAtual === 1"
                        class="px-3 py-1 text-sm border rounded-lg bg-white hover:bg-gray-100 disabled:opacity-50">
                        Anterior
                    </button>

                    <button v-for="n in totalPaginas" :key="n" @click="paginaAtual = n"
                        class="px-3 py-1 text-sm border rounded-lg" :class="{
                            'bg-blue-600 text-white border-blue-600': paginaAtual === n,
                            'bg-white hover:bg-gray-100': paginaAtual !== n
                        }">
                        {{ n }}
                    </button>

                    <button @click="paginaAtual++" :disabled="paginaAtual === totalPaginas"
                        class="px-3 py-1 text-sm border rounded-lg bg-white hover:bg-gray-100 disabled:opacity-50">
                        Próxima
                    </button>
                </div>
            </div>

        </div>

        <div v-if="relatorioSelecionado === 'cliente'" class="bg-white border border-gray-200 rounded-xl shadow-sm">

            <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl">
                <h3 class="font-semibold text-gray-700">
                    Vendas agrupadas por cliente
                </h3>
            </div>

            <!-- Gráfico Top 10 -->
            <div class="p-6 border-b">
                <canvas ref="chartClienteRef" height="120"></canvas>
            </div>

            <!-- Tabela -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3 text-center">Qtd. vendas</th>
                            <th class="px-4 py-3 text-center">Total</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="c in dadosPaginados" :key="c.cliente_id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-700">{{ c.cliente?.nome ?? `Cliente #${c.cliente_id}` }}</td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ c.qtd_vendas }}</td>
                            <td class="px-4 py-3 text-center text-gray-800 font-medium">{{ formatMoney(c.total_vendas) }}</td>
                        </tr>

                        <tr v-if="!dadosPaginados.length">
                            <td colspan="3" class="px-4 py-6 text-center text-gray-400">
                                Nenhum dado encontrado
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
                    <button @click="paginaAtual--" :disabled="paginaAtual === 1"
                        class="px-3 py-1 text-sm border rounded-lg bg-white hover:bg-gray-100 disabled:opacity-50">
                        Anterior
                    </button>

                    <button v-for="n in totalPaginas" :key="n" @click="paginaAtual = n"
                        class="px-3 py-1 text-sm border rounded-lg" :class="{
                            'bg-blue-600 text-white border-blue-600': paginaAtual === n,
                            'bg-white hover:bg-gray-100': paginaAtual !== n
                        }">
                        {{ n }}
                    </button>

                    <button @click="paginaAtual++" :disabled="paginaAtual === totalPaginas"
                        class="px-3 py-1 text-sm border rounded-lg bg-white hover:bg-gray-100 disabled:opacity-50">
                        Próxima
                    </button>
                </div>
            </div>

        </div>

        <div v-if="relatorioSelecionado === 'produtos'" class="bg-white border border-gray-200 rounded-xl shadow-sm">

            <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl">
                <h3 class="font-semibold text-gray-700">
                    Produtos vendidos no período
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Produto</th>
                            <th class="px-4 py-3 text-center">Qtd. vendida</th>
                            <th class="px-4 py-3 text-right">Valor total</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="p in dadosPaginados" :key="p.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-700">{{ p.nome }}</td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ p.qtd_vendida }}</td>
                            <td class="px-4 py-3 text-right text-gray-800 font-medium">{{ formatMoney(p.valor_total) }}
                            </td>
                        </tr>

                        <tr v-if="!dadosPaginados.length">
                            <td colspan="3" class="px-4 py-6 text-center text-gray-400">
                                Nenhum produto vendido encontrado
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
                    <button @click="paginaAtual--" :disabled="paginaAtual === 1"
                        class="px-3 py-1 text-sm border rounded-lg bg-white hover:bg-gray-100 disabled:opacity-50">
                        Anterior
                    </button>

                    <button v-for="n in totalPaginas" :key="n" @click="paginaAtual = n"
                        class="px-3 py-1 text-sm border rounded-lg" :class="{
                            'bg-blue-600 text-white border-blue-600': paginaAtual === n,
                            'bg-white hover:bg-gray-100': paginaAtual !== n
                        }">
                        {{ n }}
                    </button>

                    <button @click="paginaAtual++" :disabled="paginaAtual === totalPaginas"
                        class="px-3 py-1 text-sm border rounded-lg bg-white hover:bg-gray-100 disabled:opacity-50">
                        Próxima
                    </button>
                </div>
            </div>

        </div>

        <div v-if="relatorioSelecionado === 'gruposMarcas'"
            class="bg-white border border-gray-200 rounded-xl shadow-sm">

            <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl">
                <h3 class="font-semibold text-gray-700">
                    Grupos e marcas vendidos no período
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Grupo</th>
                            <th class="px-4 py-3">Marca</th>
                            <th class="px-4 py-3 text-center">Quantidade</th>
                            <th class="px-4 py-3 text-center">Valor Total</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="gm in dadosPaginados" :key="`${gm.grupo}-${gm.marca}`" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-700">{{ gm.grupo }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ gm.marca }}</td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ gm.qtd ?? gm.qtd_vendida ?? '-' }}</td>
                            <td class="px-4 py-3 text-center text-gray-800 font-medium">{{(gm.valor_total) }}</td>
                        </tr>

                        <tr v-if="!dadosPaginados.length">
                            <td colspan="3" class="px-4 py-6 text-center text-gray-400">
                                Nenhum dado encontrado
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
                    <button @click="paginaAtual--" :disabled="paginaAtual === 1"
                        class="px-3 py-1 text-sm border rounded-lg bg-white hover:bg-gray-100 disabled:opacity-50">
                        Anterior
                    </button>

                    <button v-for="n in totalPaginas" :key="n" @click="paginaAtual = n"
                        class="px-3 py-1 text-sm border rounded-lg" :class="{
                            'bg-blue-600 text-white border-blue-600': paginaAtual === n,
                            'bg-white hover:bg-gray-100': paginaAtual !== n
                        }">
                        {{ n }}
                    </button>

                    <button @click="paginaAtual++" :disabled="paginaAtual === totalPaginas"
                        class="px-3 py-1 text-sm border rounded-lg bg-white hover:bg-gray-100 disabled:opacity-50">
                        Próxima
                    </button>
                </div>
            </div>

        </div>

        <div v-if="relatorioSelecionado === 'semVendas'" class="bg-white border border-gray-200 rounded-xl shadow-sm">

            <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl">
                <h3 class="font-semibold text-gray-700">
                    Produtos sem vendas
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Produto</th>
                            <th class="px-4 py-3">Grupo</th>
                            <th class="px-4 py-3">Marca</th>
                            <th class="px-4 py-3 text-center">Estoque</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="p in dadosPaginados" :key="p.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-700">{{ p.nome }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ p.grupo?.nome }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ p.marca?.nome }}</td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ p.estoque ?? '-' }}</td>
                            
                        </tr>

                        <tr v-if="!dadosPaginados.length">
                            <td colspan="4" class="px-4 py-6 text-center text-gray-400">
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
                    <button @click="paginaAtual--" :disabled="paginaAtual === 1"
                        class="px-3 py-1 text-sm border rounded-lg bg-white hover:bg-gray-100 disabled:opacity-50">
                        Anterior
                    </button>

                    <button v-for="n in totalPaginas" :key="n" @click="paginaAtual = n"
                        class="px-3 py-1 text-sm border rounded-lg" :class="{
                            'bg-blue-600 text-white border-blue-600': paginaAtual === n,
                            'bg-white hover:bg-gray-100': paginaAtual !== n
                        }">
                        {{ n }}
                    </button>

                    <button @click="paginaAtual++" :disabled="paginaAtual === totalPaginas"
                        class="px-3 py-1 text-sm border rounded-lg bg-white hover:bg-gray-100 disabled:opacity-50">
                        Próxima
                    </button>
                </div>
            </div>

        </div>

        <div v-if="relatorioSelecionado === 'mediaTempo'" class="bg-white border border-gray-200 rounded-xl shadow-sm">

            <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl">
                <h3 class="font-semibold text-gray-700">
                    Média de tempo de venda por produto
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Produto</th>
                            <th class="px-4 py-3">Primeira compra</th>
                            <th class="px-4 py-3">Primeira venda</th>
                            <th class="px-4 py-3 text-center">Dias</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="m in dadosPaginados" :key="m.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-700">{{ m.nome }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ formatDateBr(m.primeira_compra) }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ formatDateBr(m.primeira_venda) }}</td>
                            <td class="px-4 py-3 text-center text-gray-800 font-medium">{{ m.media_dias }}</td>
                        </tr>

                        <tr v-if="!dadosPaginados.length">
                            <td colspan="4" class="px-4 py-6 text-center text-gray-400">
                                Nenhum dado encontrado
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>


            <div class="flex items-center justify-between px-4 py-3 border-t bg-gray-50 rounded-b-xl">
                <span class="text-sm text-gray-600">
                    Página {{ paginaAtual }} de {{ totalPaginas }}
                </span>

                <div class="flex gap-1">
                    <button @click="paginaAtual--" :disabled="paginaAtual === 1"
                        class="px-3 py-1 text-sm border rounded-lg bg-white hover:bg-gray-100 disabled:opacity-50">
                        Anterior
                    </button>

                    <button v-for="n in totalPaginas" :key="n" @click="paginaAtual = n"
                        class="px-3 py-1 text-sm border rounded-lg" :class="{
                            'bg-blue-600 text-white border-blue-600': paginaAtual === n,
                            'bg-white hover:bg-gray-100': paginaAtual !== n
                        }">
                        {{ n }}
                    </button>

                    <button @click="paginaAtual++" :disabled="paginaAtual === totalPaginas"
                        class="px-3 py-1 text-sm border rounded-lg bg-white hover:bg-gray-100 disabled:opacity-50">
                        Próxima
                    </button>
                </div>
            </div>

        </div>

        <!-- VENDAS POR DIA -->

        <div v-if="relatorioSelecionado === 'dia'" class="bg-white border border-gray-200 rounded-xl shadow-sm">

            <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl">
                <h3 class="font-semibold text-gray-700">
                    Vendas por dia (gráfico + tabela)
                </h3>
            </div>

            <div class="p-6 border-b">
                <canvas ref="chartDiaRef" height="120"></canvas>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Dia</th>
                            <th class="px-4 py-3 text-center">Qtd vendas</th>
                            <th class="px-4 py-3 text-right">Total</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="d in dadosPaginados" :key="d.dia" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-700">{{ formatDateBr(d.dia) }}</td>
                            <td class="px-4 py-3 text-center text-gray-700">{{ d.qtd_vendas }}</td>
                            <td class="px-4 py-3 text-right text-gray-800 font-medium">{{ formatMoney(d.total_vendas) }}
                            </td>
                        </tr>

                        <tr v-if="!dadosPaginados.length">
                            <td colspan="3" class="px-4 py-6 text-center text-gray-400">
                                Nenhum dado encontrado
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
                    <button @click="paginaAtual--" :disabled="paginaAtual === 1"
                        class="px-3 py-1 text-sm border rounded-lg bg-white hover:bg-gray-100 disabled:opacity-50">
                        Anterior
                    </button>

                    <button v-for="n in totalPaginas" :key="n" @click="paginaAtual = n"
                        class="px-3 py-1 text-sm border rounded-lg" :class="{
                            'bg-blue-600 text-white border-blue-600': paginaAtual === n,
                            'bg-white hover:bg-gray-100': paginaAtual !== n
                        }">
                        {{ n }}
                    </button>

                    <button @click="paginaAtual++" :disabled="paginaAtual === totalPaginas"
                        class="px-3 py-1 text-sm border rounded-lg bg-white hover:bg-gray-100 disabled:opacity-50">
                        Próxima
                    </button>
                </div>
            </div>

        </div>

    </div>
</template>