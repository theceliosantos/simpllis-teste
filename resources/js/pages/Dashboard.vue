<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { Chart } from 'chart.js/auto'

const loading = ref(true)
const erro = ref(null)

const filtro = ref({
  start: new Date(new Date().setDate(new Date().getDate() - 30))
    .toISOString().slice(0, 10),
  end: new Date().toISOString().slice(0, 10),
})

const vendasPorDia = ref([])
const produtosAtivosInativos = ref([])
const produtosSemVendas = ref([])
const vendasPeriodo = ref([])

const chartVendasBarRef = ref(null)
const chartVendasLineRef = ref(null)
const chartProdutosRef = ref(null)

let chartVendasBar = null
let chartVendasLine = null
let chartProdutos = null

const fetchJson = async (url) => {
  const res = await fetch(url, {
    headers: { Accept: 'application/json' }
  })

  const text = await res.text()

  if (!res.ok || text.startsWith('<'))
    throw new Error(`Erro na rota ${url}`)

  return JSON.parse(text)
}

const qsPeriodo = () =>
  new URLSearchParams({
    start: filtro.value.start,
    end: filtro.value.end
  }).toString()

const formatarMoeda = (v) =>
  new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL'
  }).format(Number(v || 0))

const formatarData = (d) =>
  new Intl.DateTimeFormat('pt-BR').format(new Date(d))

const destruir = (chart) => {
  if (chart) chart.destroy()
  return null
}

const renderizarGraficos = () => {
  chartVendasBar = destruir(chartVendasBar)
  chartVendasLine = destruir(chartVendasLine)
  chartProdutos = destruir(chartProdutos)

  if (chartVendasBarRef.value && vendasPorDia.value.length) {
    chartVendasBar = new Chart(chartVendasBarRef.value, {
      type: 'bar',
      data: {
        labels: vendasPorDia.value.map(x => formatarData(x.dia)),
        datasets: [{
          label: 'Valor vendido',
          data: vendasPorDia.value.map(x => Number(x.total_vendas))
        }]
      }
    })
  }

  if (chartVendasLineRef.value && vendasPeriodo.value.length) {
    chartVendasLine = new Chart(chartVendasLineRef.value, {
      type: 'line',
      data: {
        labels: vendasPeriodo.value.map(x => formatarData(x.data_venda)),
        datasets: [{
          label: 'Venda',
          data: vendasPeriodo.value.map(x => Number(x.valor_total)),
          tension: 0.3
        }]
      }
    })
  }

  if (chartProdutosRef.value && produtosAtivosInativos.value.length) {
    chartProdutos = new Chart(chartProdutosRef.value, {
      type: 'doughnut',
      data: {
        labels: produtosAtivosInativos.value.map(x =>
          x.ativo ? 'Ativos' : 'Inativos'
        ),
        datasets: [{
          data: produtosAtivosInativos.value.map(x => Number(x.total))
        }]
      }
    })
  }
}

const carregarDashboard = async () => {
  loading.value = true
  erro.value = null

  try {
    const [vendasDia, produtos, semVenda, vendas] = await Promise.all([
      fetchJson(`/api/relatorios/vendas/por-dia?${qsPeriodo()}`),
      fetchJson('/api/relatorios/produtos/ativos-inativos'),
      fetchJson('/api/relatorios/vendas/produtos-sem-venda'),
      fetchJson(`/api/relatorios/vendas/periodo?${qsPeriodo()}`)
    ])

    vendasPorDia.value = vendasDia
    produtosAtivosInativos.value = produtos
    produtosSemVendas.value = semVenda
    vendasPeriodo.value = vendas

  } catch (e) {
    erro.value = e.message
  } finally {
    loading.value = false
    await nextTick()
    renderizarGraficos()
  }
}

const kpiTotalVendas = computed(() =>
  vendasPorDia.value.reduce((t, x) => t + Number(x.total_vendas), 0)
)

const kpiQtdVendas = computed(() =>
  vendasPorDia.value.reduce((t, x) => t + Number(x.qtd_vendas), 0)
)

const kpiProdutosSemVenda = computed(() =>
  produtosSemVendas.value.length
)

const kpiProdutosAtivos = computed(() =>
  produtosAtivosInativos.value
    .filter(x => x.ativo)
    .reduce((t, x) => t + Number(x.total), 0)
)

onMounted(carregarDashboard)

watch(() => ({ ...filtro.value }), () => {
  setTimeout(carregarDashboard, 400)
})
</script>

<template>
  <div class="pt-15 space-y-8 bg-gray-50 p-6 min-h-screen">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6">
      <div>
        <h2 class="text-2xl font-semibold text-gray-800">Dashboard</h2>
        <p class="text-sm text-gray-500 mt-1">
          Visão geral do desempenho do sistema
        </p>
      </div>

      <!-- Filtro -->
      <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 flex gap-3 items-end">
        <div class="flex flex-col">
          <label class="text-xs text-gray-500 mb-1">Início</label>
          <input
            type="date"
            v-model="filtro.start"
            class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black"
          />
        </div>

        <div class="flex flex-col">
          <label class="text-xs text-gray-500 mb-1">Fim</label>
          <input
            type="date"
            v-model="filtro.end"
            class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black"
          />
        </div>

        <button
          @click="carregarDashboard"
          class="bg-black text-white px-5 py-2 rounded-lg text-sm hover:opacity-90 transition"
        >
          Atualizar
        </button>
      </div>
    </div>

    <!-- Estados -->
    <div v-if="loading" class="bg-white rounded-xl shadow-sm p-10 text-center text-gray-500">
      Carregando dados...
    </div>

    <div v-else-if="erro" class="bg-white rounded-xl shadow-sm p-10 text-center text-red-600">
      {{ erro }}
    </div>

    <template v-else>

      <!-- KPIs -->
      <section class="grid md:grid-cols-2 lg:grid-cols-4 gap-5">

        <div class="bg-white border border-gray-100 rounded-xl shadow-sm p-5">
          <p class="text-xs uppercase tracking-wide text-gray-500">Total vendido</p>
          <p class="text-2xl font-semibold text-gray-800 mt-2">
            {{ formatarMoeda(kpiTotalVendas) }}
          </p>
        </div>

        <div class="bg-white border border-gray-100 rounded-xl shadow-sm p-5">
          <p class="text-xs uppercase tracking-wide text-gray-500">Qtd. vendas</p>
          <p class="text-2xl font-semibold text-gray-800 mt-2">
            {{ kpiQtdVendas }}
          </p>
        </div>

        <div class="bg-white border border-gray-100 rounded-xl shadow-sm p-5">
          <p class="text-xs uppercase tracking-wide text-gray-500">Produtos ativos</p>
          <p class="text-2xl font-semibold text-gray-800 mt-2">
            {{ kpiProdutosAtivos }}
          </p>
        </div>

        <div class="bg-white border border-gray-100 rounded-xl shadow-sm p-5">
          <p class="text-xs uppercase tracking-wide text-gray-500">Sem venda</p>
          <p class="text-2xl font-semibold text-gray-800 mt-2">
            {{ kpiProdutosSemVenda }}
          </p>
        </div>

      </section>

      <!-- Gráficos -->
      <section class="grid lg:grid-cols-3 gap-5">

        <div class="bg-white border border-gray-100 rounded-xl shadow-sm p-5">
          <h3 class="text-sm font-medium text-gray-700 mb-4">
            Vendas por dia
          </h3>
          <canvas ref="chartVendasBarRef"></canvas>
        </div>

        <div class="bg-white border border-gray-100 rounded-xl shadow-sm p-5">
          <h3 class="text-sm font-medium text-gray-700 mb-4">
            Evolução das vendas
          </h3>
          <canvas ref="chartVendasLineRef"></canvas>
        </div>

        <div class="bg-white border border-gray-100 rounded-xl shadow-sm p-5">
          <h3 class="text-sm font-medium text-gray-700 mb-4">
            Produtos ativos vs inativos
          </h3>
          <canvas ref="chartProdutosRef"></canvas>
        </div>

      </section>

    </template>

  </div>
</template>