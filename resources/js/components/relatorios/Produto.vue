<script setup>
import { ref, onMounted, computed, watch, nextTick } from 'vue'
import { Chart } from 'chart.js/auto'

const relatorioSelecionado = ref('ativos')
const ativos = ref([])
const grupos = ref([])
const grupoSelecionado = ref(null)
const produtosPorGrupo = ref([])
const marcas = ref([])
const marcaSelecionada = ref(null)
const produtosPorMarca = ref([])
const chartRef = ref(null)
let chartInstance = null

function montarGraficoAtivos() {

    if (!chartRef.value) return

    const ativosCount =
        ativos.value.find(p => p.ativo == 1)?.total || 0

    const inativosCount =
        ativos.value.find(p => p.ativo == 0)?.total || 0

    if (chartInstance) {
        chartInstance.destroy()
    }

    chartInstance = new Chart(chartRef.value, {
        type: 'doughnut', 

        data: {
            labels: ['Ativos', 'Inativos'],
            datasets: [
                {
                    data: [ativosCount, inativosCount],
                    backgroundColor: [
                        '#5500de',
                        '#0068de'
                    ]
                }
            ]
        },

        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    })
}

async function fetchJson(url) {
    const res = await fetch(url)
    if (!res.ok) throw new Error(`Erro ${res.status} em ${url}`)
    return res.json()
}

async function carregarProdutosPorGrupo() {
    if (!grupoSelecionado.value) return

    produtosPorGrupo.value = await fetchJson(
        `/api/relatorios/produtos/por-grupo/${grupoSelecionado.value}`
    )
}

async function carregarProdutosPorMarca() {
    if (!marcaSelecionada.value) return

    produtosPorMarca.value = await fetchJson(
        `/api/relatorios/produtos/por-marca/${marcaSelecionada.value}`
    )
}

onMounted(async () => {

    ativos.value =
        await fetchJson('/api/relatorios/produtos/ativos-inativos')

    grupos.value = await fetchJson('/api/grupos')
    marcas.value = await fetchJson('/api/marcas')

    await nextTick()
    montarGraficoAtivos()

})

watch(grupoSelecionado, async () => {
    paginaAtual.value = 1

    if (relatorioSelecionado.value === 'grupo') {
        await carregarProdutosPorGrupo()
    }
})

watch(marcaSelecionada, async () => {
    paginaAtual.value = 1

    if (relatorioSelecionado.value === 'marca') {
        await carregarProdutosPorMarca()
    }
})

watch(relatorioSelecionado, async (novo) => {

    paginaAtual.value = 1

    if (novo === 'grupo' && grupoSelecionado.value) {
        await carregarProdutosPorGrupo()
    }

    if (novo === 'marca' && marcaSelecionada.value) {
        await carregarProdutosPorMarca()
    }

    if (novo === 'ativos') {
        await nextTick()
        montarGraficoAtivos()
    }

})


const paginaAtual = ref(1)
const itensPorPagina = 10

const dadosAtuais = computed(() => {

    if (relatorioSelecionado.value === 'grupo')
        return produtosPorGrupo.value

    if (relatorioSelecionado.value === 'marca')
        return produtosPorMarca.value

    return []

})

const totalPaginas = computed(() => {
    return Math.ceil(dadosAtuais.value.length / itensPorPagina) || 1
})

const dadosPaginados = computed(() => {
    const inicio = (paginaAtual.value - 1) * itensPorPagina
    return dadosAtuais.value.slice(inicio, inicio + itensPorPagina)
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

        <h2 class="text-2xl font-bold">
            Relatórios de Produtos
        </h2>

        <div class="flex gap-2 flex-wrap">

            <button @click="relatorioSelecionado = 'ativos'" :class="btnClass('ativos')">
                Ativos / Inativos
            </button>

            <button @click="relatorioSelecionado = 'grupo'" :class="btnClass('grupo')">
                Por Grupo
            </button>

            <button @click="relatorioSelecionado = 'marca'" :class="btnClass('marca')">
                Por Marca
            </button>

        </div>

        <div v-if="relatorioSelecionado === 'ativos'" class="bg-white border border-gray-200 rounded-xl shadow-sm">

            <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl">
                <h3 class="font-semibold text-gray-700">
                    Produtos ativos / inativos
                </h3>
            </div>

            <div class="p-6 flex justify-center">
                <div class="w-100 h-100">
                    <canvas ref="chartRef"></canvas>
                </div>
            </div>

        </div>

        <div v-if="relatorioSelecionado === 'grupo'" class="bg-white border border-gray-200 rounded-xl shadow-sm">

            <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl flex justify-between items-center">

                <h3 class="font-semibold text-gray-700">
                    Produtos por grupo
                </h3>

                <select v-model="grupoSelecionado" class="border rounded-lg px-3 py-1 text-sm w-48">
                    <option :value="null">
                        Selecione o grupo
                    </option>

                    <option v-for="g in grupos" :key="g.id" :value="g.id">
                        {{ g.nome }}
                    </option>

                </select>

            </div>

            <!-- Tabela -->
            <div class="overflow-x-auto">

                <table class="w-full text-sm text-left">

                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Nome</th>
                            <th class="px-4 py-3">Grupo</th>
                            <th class="px-4 py-3">Marca</th>
                            <th class="px-4 py-3 text-right">Preço Compra</th>
                            <th class="px-4 py-3 text-right">Preço Venda</th>
                            <th class="px-4 py-3 text-center">Estoque</th>
                        </tr>

                    </thead>

                    <tbody class="divide-y divide-gray-100">

                        <tr v-for="p in dadosPaginados" :key="p.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800">
                                {{ p.nome }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ p.grupo?.nome }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ p.marca?.nome }}
                            </td>

                            <td class="px-4 py-3 text-right text-gray-700">
                                R$ {{ Number(p.preco_compra).toFixed(2) }}
                            </td>

                            <td class="px-4 py-3 text-right text-gray-700">
                                R$ {{ Number(p.preco_venda).toFixed(2) }}
                            </td>

                            <td class="px-4 py-3 text-center text-gray-700">
                                {{ p.estoque }}
                            </td>
                        </tr>

                        <tr v-if="!dadosPaginados.length">
                            <td colspan="3" class="px-4 py-6 text-center text-gray-400">
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

        <div v-if="relatorioSelecionado === 'marca'" class="bg-white border border-gray-200 rounded-xl shadow-sm">

            <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl flex justify-between items-center">

                <h3 class="font-semibold text-gray-700">
                    Produtos por marca
                </h3>

                <select v-model="marcaSelecionada" class="border rounded-lg px-3 py-1 text-sm w-48">
                    <option :value="null">
                        Selecione a marca
                    </option>

                    <option v-for="m in marcas" :key="m.id" :value="m.id">
                        {{ m.nome }}
                    </option>

                </select>

            </div>

            <div class="overflow-x-auto">

                <table class="w-full text-sm text-left">

                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Nome</th>
                            <th class="px-4 py-3">Grupo</th>
                            <th class="px-4 py-3">Marca</th>
                            <th class="px-4 py-3 text-right">Preço Compra</th>
                            <th class="px-4 py-3 text-right">Preço Venda</th>
                            <th class="px-4 py-3 text-center">Estoque</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">

                        <tr v-for="p in dadosPaginados" :key="p.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800">
                                {{ p.nome }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ p.grupo?.nome }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ p.marca?.nome }}
                            </td>

                            <td class="px-4 py-3 text-right text-gray-700">
                                R$ {{ Number(p.preco_compra).toFixed(2) }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-700">
                                R$ {{ Number(p.preco_venda).toFixed(2) }}
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700">
                                {{ p.estoque }}
                            </td>
                        </tr>

                        <tr v-if="!dadosPaginados.length">
                            <td colspan="3" class="px-4 py-6 text-center text-gray-400">
                                Nenhum produto encontrado
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
    </div>
</template>