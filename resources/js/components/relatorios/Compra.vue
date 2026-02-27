<script setup>
import { ref, onMounted, computed, nextTick, watch } from 'vue'
import { Chart } from 'chart.js/auto'

const relatorioSelecionado = ref('periodo')
const dataInicio = ref(null)
const dataFim = ref(null)
const comprasPeriodo = ref([])
const fornecedores = ref([])
const fornecedorSelecionado = ref(null)
const comprasFornecedor = ref([])
const chartRef = ref(null)
let chartInstance = null

const fetchJson = async (url) => {
  const res = await fetch(url)
  if (!res.ok) throw new Error(`Erro ${res.status} em ${url}`)
  return res.json()
}

const montarGraficoPeriodo = () => {
  if (!chartRef.value) return

  const labels = comprasPeriodo.value.map(c => c.data_compra)
  const valores = comprasPeriodo.value.map(c => Number(c.valor_total))

  chartInstance?.destroy()

  chartInstance = new Chart(chartRef.value, {
    type: 'bar',
    data: {
      labels,
      datasets: [{ label: 'Valor comprado (R$)', data: valores }]
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'bottom' } }
    }
  })
}

const carregarComprasPeriodo = async () => {
  let url = '/api/relatorios/compras/periodo'
  if (dataInicio.value && dataFim.value)
    url += `?start=${dataInicio.value}&end=${dataFim.value}`

  comprasPeriodo.value = await fetchJson(url)
  await nextTick()
  montarGraficoPeriodo()
}

const carregarComprasFornecedor = async () => {
  if (!fornecedorSelecionado.value) {
    comprasFornecedor.value = []
    return
  }
  let url = `/api/relatorios/compras/por-fornecedor/${fornecedorSelecionado.value}`

  if (dataInicio.value && dataFim.value)
    url += `?start=${dataInicio.value}&end=${dataFim.value}`
  comprasFornecedor.value = await fetchJson(url)
}

watch(relatorioSelecionado, async (novo) => {
  paginaAtual.value = 1

  if (novo === 'periodo')
    await carregarComprasPeriodo()

  if (novo === 'fornecedor' && fornecedorSelecionado.value)
    await carregarComprasFornecedor()
})

watch(fornecedorSelecionado, async (novo) => {
  if (!novo) {
    comprasFornecedor.value = []
    return
  }

  if (relatorioSelecionado.value === 'fornecedor') {
    paginaAtual.value = 1
    await carregarComprasFornecedor()
  }
})

const buscarAtual = async () => {
  if (relatorioSelecionado.value === 'periodo')
    await carregarComprasPeriodo()
  else if (relatorioSelecionado.value === 'fornecedor')
    await carregarComprasFornecedor()
}

onMounted(async () => {
  fornecedores.value = await fetchJson('/api/fornecedores')
  await carregarComprasPeriodo()
})

const paginaAtual = ref(1)
const itensPorPagina = 10

const dadosAtuais = computed(() =>
  relatorioSelecionado.value === 'periodo'
    ? comprasPeriodo.value
    : relatorioSelecionado.value === 'fornecedor'
    ? comprasFornecedor.value
    : []
)

const dadosPaginados = computed(() => {
  const inicio = (paginaAtual.value - 1) * itensPorPagina
  return dadosAtuais.value.slice(inicio, inicio + itensPorPagina)
})

const formatarData = (data) =>
  data
    ? new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      }).format(new Date(data))
    : '-'

const formatarMoeda = (valor) =>
  new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL'
  }).format(Number(valor))

const btnClass = (tipo) => [
  'px-3 py-1 text-sm border rounded-lg',
  relatorioSelecionado.value === tipo
    ? 'bg-blue-600 text-white border-blue-600'
    : 'bg-white hover:bg-gray-100'
]
</script>

<template>
    <div class="pt-15 space-y-8">

        <h2 class="text-2xl font-bold">
            Relatórios de Compras
        </h2>

        <!-- Abas -->
        <div class="flex gap-2 flex-wrap">
            <button @click="relatorioSelecionado = 'periodo'" :class="btnClass('periodo')">
                Compras por período
            </button>

            <button @click="relatorioSelecionado = 'fornecedor'" :class="btnClass('fornecedor')">
                Compras por fornecedor
            </button>
        </div>

        <!-- ================= PERÍODO ================= -->

        <!-- Filtro -->
        <div v-if="relatorioSelecionado === 'periodo'"
            class="bg-white border border-gray-200 rounded-xl shadow-sm">

            <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl">
                <h3 class="font-semibold text-gray-700">
                    Filtro de período
                </h3>
            </div>

            <div class="p-4 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="text-xs text-gray-600">Início</label>
                    <input type="date"
                        v-model="dataInicio"
                        class="border rounded-lg px-3 py-1 text-sm block">
                </div>

                <div>
                    <label class="text-xs text-gray-600">Fim</label>
                    <input type="date"
                        v-model="dataFim"
                        class="border rounded-lg px-3 py-1 text-sm block">
                </div>

                <button @click="buscarAtual"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
                    Buscar
                </button>
            </div>
        </div>

        <!-- Resultado por período -->
        <div v-if="relatorioSelecionado === 'periodo'"
            class="bg-white border border-gray-200 rounded-xl shadow-sm">

            <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl">
                <h3 class="font-semibold text-gray-700">
                    Compras por período
                </h3>
            </div>

            <div class="p-6">
                <canvas ref="chartRef" height="110"></canvas>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-center">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Data</th>
                            <th class="px-4 py-3">Fornecedor</th>
                            <th class="px-4 py-3">Itens diferentes</th>
                            <th class="px-4 py-3">Quantidade total</th>
                            <th class="px-4 py-3">Valor</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="c in dadosPaginados"
                            :key="c.id"
                            class="hover:bg-gray-50">

                            <td class="px-4 py-3">
                                {{ formatarData(c.data_compra) }}
                            </td>

                            <td class="px-4 py-3">
                                {{ c.fornecedor?.nome }}
                            </td>

                            <td class="px-4 py-3">
                                {{ c.produtos.length }}
                            </td>

                            <td class="px-4 py-3">
                                {{ c.produtos.reduce((total, p) => total + p.pivot.quantidade, 0) }}
                            </td>

                            <td class="px-4 py-3 font-medium">
                                {{ formatarMoeda(c.valor_total) }}
                            </td>
                        </tr>

                        <tr v-if="!dadosPaginados.length">
                            <td colspan="5"
                                class="px-4 py-6 text-gray-400">
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
                    <button @click="paginaAtual--"
                        :disabled="paginaAtual === 1"
                        class="px-3 py-1 text-sm border rounded-lg bg-white hover:bg-gray-100 disabled:opacity-50">
                        Anterior
                    </button>

                    <button v-for="n in totalPaginas"
                        :key="n"
                        @click="paginaAtual = n"
                        class="px-3 py-1 text-sm border rounded-lg"
                        :class="{
                            'bg-blue-600 text-white border-blue-600': paginaAtual === n,
                            'bg-white hover:bg-gray-100': paginaAtual !== n
                        }">
                        {{ n }}
                    </button>

                    <button @click="paginaAtual++"
                        :disabled="paginaAtual === totalPaginas"
                        class="px-3 py-1 text-sm border rounded-lg bg-white hover:bg-gray-100 disabled:opacity-50">
                        Próxima
                    </button>
                </div>
            </div>
        </div>

        <!-- ================= FORNECEDOR ================= -->

        <div v-if="relatorioSelecionado === 'fornecedor'"
            class="bg-white border border-gray-200 rounded-xl shadow-sm">

            <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl flex justify-between items-center">
                <h3 class="font-semibold text-gray-700">
                    Compras por fornecedor
                </h3>

                <select v-model="fornecedorSelecionado"
                    class="border rounded-lg px-3 py-1 text-sm w-56">
                    <option :value="null">
                        Selecione o fornecedor
                    </option>
                    <option v-for="f in fornecedores"
                        :key="f.id"
                        :value="f.id">
                        {{ f.nome }}
                    </option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-center">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Data</th>
                            <th class="px-4 py-3">Fornecedor</th>
                            <th class="px-4 py-3">Itens diferentes</th>
                            <th class="px-4 py-3">Quantidade total</th>
                            <th class="px-4 py-3">Valor</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="c in dadosPaginados"
                            :key="c.id"
                            class="hover:bg-gray-50">

                            <td class="px-4 py-3">
                                {{ formatarData(c.data_compra) }}
                            </td>

                            <td class="px-4 py-3">
                                {{ c.fornecedor?.nome }}
                            </td>

                            <td class="px-4 py-3">
                                {{ c.produtos.length }}
                            </td>

                            <td class="px-4 py-3">
                                {{ c.produtos.reduce((total, p) => total + p.pivot.quantidade, 0) }}
                            </td>

                            <td class="px-4 py-3 font-medium">
                                {{ formatarMoeda(c.valor_total) }}
                            </td>
                        </tr>

                        <tr v-if="!dadosPaginados.length">
                            <td colspan="5"
                                class="px-4 py-6 text-gray-400">
                                Nenhuma compra encontrada
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

                    <button
                        @click="paginaAtual--"
                        :disabled="paginaAtual === 1"
                        class="px-3 py-1 text-sm border rounded-lg bg-white hover:bg-gray-100 disabled:opacity-50">
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
                        class="px-3 py-1 text-sm border rounded-lg bg-white hover:bg-gray-100 disabled:opacity-50">
                        Próxima
                    </button>

                </div>
            </div>

        </div>

    </div>
</template>