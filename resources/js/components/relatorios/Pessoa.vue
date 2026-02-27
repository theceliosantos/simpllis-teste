<script setup>
import { ref, onMounted, computed, watch } from 'vue'

const relatorioSelecionado = ref('todas')
const todas = ref([])
const tipoSelecionado = ref('cliente')
const pessoasDoTipo = ref([])
const sexoSelecionado = ref('M')
const pessoasPorSexo = ref([])
const idadeSelecionada = ref(null)
const pessoasPorIdade = ref([])
const carregandoIdade = ref(false)

async function fetchJson(url) {
    const res = await fetch(url)
    if (!res.ok) throw new Error(`Erro ${res.status} ao buscar ${url}`)
    return res.json()
}

async function carregarPessoasPorTipo() {
    pessoasDoTipo.value = await fetchJson(
        `/api/relatorios/pessoas/tipo/${encodeURIComponent(tipoSelecionado.value)}`
    )
}

async function carregarPessoasPorSexo() {
    pessoasPorSexo.value = await fetchJson(
        `/api/relatorios/pessoas/sexo/${sexoSelecionado.value}`
    )
}

async function carregarPessoasPorIdade() {
    if (!idadeSelecionada.value || idadeSelecionada.value <= 0) return

    carregandoIdade.value = true

    try {
        pessoasPorIdade.value = await fetchJson(
            `/api/relatorios/pessoas/idade/${idadeSelecionada.value}`
        )
    } finally {
        carregandoIdade.value = false
    }
}

/* On mount */
onMounted(async () => {
    todas.value = await fetchJson('/api/relatorios/pessoas')

    if (relatorioSelecionado.value === 'tipo') {
        await carregarPessoasPorTipo()
    }
    if (relatorioSelecionado.value === 'sexo') {
        await carregarPessoasPorSexo()
    }
})

watch(tipoSelecionado, async () => {
    paginaAtual.value = 1
    if (relatorioSelecionado.value === 'tipo') {
        await carregarPessoasPorTipo()
    }
})

watch(sexoSelecionado, async () => {
    paginaAtual.value = 1

    if (relatorioSelecionado.value === 'sexo') {
        await carregarPessoasPorSexo()
    }
})

watch(relatorioSelecionado, async (novo) => {
    paginaAtual.value = 1

    if (novo === 'idade' && idadeSelecionada.value) {
        await carregarPessoasPorIdade()
    }
})



watch(relatorioSelecionado, async (novo) => {
    paginaAtual.value = 1

    if (novo === 'tipo' && pessoasDoTipo.value.length === 0) {
        await carregarPessoasPorTipo()
    }
    if (novo === 'sexo' && pessoasPorSexo.value.length === 0) {
        await carregarPessoasPorSexo()
    }
    if (novo === 'idade' && pessoasPorIdade.value.length === 0) {
        await carregarPessoasPorIdade()
    }
})


const paginaAtual = ref(1)
const itensPorPagina = 10


const dadosAtuais = computed(() => {
    if (relatorioSelecionado.value === 'todas') return todas.value
    if (relatorioSelecionado.value === 'tipo') return pessoasDoTipo.value
    if (relatorioSelecionado.value === 'sexo') return pessoasPorSexo.value
    if (relatorioSelecionado.value === 'idade') return pessoasPorIdade.value
    return []
})


const totalPaginas = computed(() => {
    return Math.ceil(dadosAtuais.value.length / itensPorPagina) || 1
})


const dadosPaginados = computed(() => {
    const inicio = (paginaAtual.value - 1) * itensPorPagina
    const fim = inicio + itensPorPagina
    return dadosAtuais.value.slice(inicio, fim)
})

function btnClass(tipo) {
    return [
        'px-3 py-1 text-sm border rounded-lg',
        relatorioSelecionado.value === tipo
            ? 'bg-blue-600 text-white border-blue-600'
            : 'bg-white hover:bg-gray-100'
    ]
}

function formatarTelefone(telefone) {
    if (!telefone) return '-'

    const numeros = String(telefone).replace(/\D/g, '')

    if (numeros.length === 10) {
        return numeros.replace(
            /(\d{2})(\d{4})(\d{4})/,
            '($1) $2-$3'
        )
    }

    if (numeros.length === 11) {
        return numeros.replace(
            /(\d{2})(\d{1})(\d{4})(\d{4})/,
            '($1) $2 $3-$4'
        )
    }

    return telefone
}
</script>

<template>
    <div class="pt-15 space-y-8">

        <h2 class="text-2xl font-bold">Relatórios de Pessoas</h2>

        <div class="flex gap-2 flex-wrap">

            <button @click="relatorioSelecionado = 'todas'" :class="btnClass('todas')">
                Todas
            </button>

            <button @click="relatorioSelecionado = 'tipo'" :class="btnClass('tipo')">
                Por Tipo
            </button>

            <button @click="relatorioSelecionado = 'sexo'" :class="btnClass('sexo')">
                Por Sexo
            </button>

            <button @click="relatorioSelecionado = 'idade'" :class="btnClass('idade')">
                Por Idade
            </button>

        </div>

        <!-- todas as pessoas -->

        <div v-if="relatorioSelecionado === 'todas'" class="bg-white border border-gray-200 rounded-xl shadow-sm">

            <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl">
                <h3 class="font-semibold text-gray-700">Todas as pessoas</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Nome</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Telefone</th>
                            <th class="px-4 py-3">Sexo</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="p in dadosPaginados" :key="p.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ p.nome }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ p.tipo }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ p.email }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ formatarTelefone(p.telefone) }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ p.sexo }}</td>
                        </tr>

                        <tr v-if="!dadosPaginados.length">
                            <td colspan="3" class="px-4 py-6 text-center text-gray-400">
                                Nenhuma pessoa encontrada
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="['todas', 'tipo'].includes(relatorioSelecionado)"
                class="flex items-center justify-between px-4 py-3 border-t bg-gray-50 rounded-b-xl">
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

        <!-- por tipo -->

        <div v-if="relatorioSelecionado === 'tipo'" class="bg-white border border-gray-200 rounded-xl shadow-sm">

            <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl flex justify-between items-center">
                <h3 class="font-semibold text-gray-700">Pessoas por tipo</h3>

                <select v-model="tipoSelecionado" class="border rounded-lg px-3 py-1 text-sm">
                    <option value="funcionario">Funcionário</option>
                    <option value="cliente">Cliente</option>
                    <option value="fornecedor">Fornecedor</option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Nome</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Telefone</th>
                            <th class="px-4 py-3">Sexo</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="p in dadosPaginados" :key="p.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ p.nome }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ p.tipo }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ p.email }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ formatarTelefone(p.telefone) }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ p.sexo }}</td>
                        </tr>

                        <tr v-if="!dadosPaginados.length">
                            <td colspan="3" class="px-4 py-6 text-center text-gray-400">
                                Nenhuma pessoa encontrada
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="['todas', 'tipo'].includes(relatorioSelecionado)"
                class="flex items-center justify-between px-4 py-3 border-t bg-gray-50 rounded-b-xl">
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

        <!-- por sexo -->

        <div v-if="relatorioSelecionado === 'sexo'" class="bg-white border border-gray-200 rounded-xl shadow-sm">

            <!-- Header -->
            <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl flex justify-between items-center">
                <h3 class="font-semibold text-gray-700">
                    Pessoas por sexo
                </h3>

                <select v-model="sexoSelecionado" class="border rounded-lg px-3 py-1 text-sm">
                    <option value="M">Masculino</option>
                    <option value="F">Feminino</option>
                </select>
            </div>

            <!-- Tabela -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">

                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Nome</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Telefone</th>
                            <th class="px-4 py-3">Sexo</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">

                        <tr v-for="p in dadosPaginados" :key="p.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800">
                                {{ p.nome }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ p.tipo }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ p.email }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ formatarTelefone(p.telefone) }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ p.sexo }}
                            </td>
                        </tr>

                        <tr v-if="!dadosPaginados.length">
                            <td colspan="3" class="px-4 py-6 text-center text-gray-400">
                                Nenhuma pessoa encontrada
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
        
        <!-- idade -->

        <div v-if="relatorioSelecionado === 'idade'" class="bg-white border border-gray-200 rounded-xl shadow-sm">
            <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl flex justify-between items-center">
                <h3 class="font-semibold text-gray-700">
                    Pessoas por idade
                </h3>

                <div class="flex items-center gap-2">

                    <input v-model.number="idadeSelecionada" type="number" min="1" placeholder="Digite a idade"
                        class="border rounded-lg px-3 py-1 text-sm w-32" @keyup.enter="carregarPessoasPorIdade" />

                    <button @click="carregarPessoasPorIdade"
                        class="px-3 py-1 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Buscar
                    </button>

                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">

                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Nome</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Telefone</th>
                            <th class="px-4 py-3">Sexo</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">

                        <tr v-for="p in dadosPaginados" :key="p.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800">
                                {{ p.nome }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ p.tipo }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ p.email }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ formatarTelefone(p.telefone) }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ p.sexo }}
                            </td>
                        </tr>

                        <tr v-if="!dadosPaginados.length">
                            <td colspan="3" class="px-4 py-6 text-center text-gray-400">
                                Nenhuma pessoa encontrada
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