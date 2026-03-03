<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import axios from 'axios'

const BASE_URL = '/api/pessoas'
const abaSelecionada = ref('lista')
const carregando = ref(false)
const todas = ref([])
const busca = ref('')
const paginaAtual = ref(1)
const itensPorPagina = 10
const mensagemErro = ref('')
const confirmandoId = ref(null)

const form = reactive({
    id: null,
    nome: '',
    email: '',
    telefone: '',
    sexo: '',
    data_nascimento: '',
    tipo: 'cliente',
    ativo: true,
})

const modoEdicao = computed(() => form.id !== null)

const resetForm = () => {
    form.id = null
    form.nome = ''
    form.email = ''
    form.telefone = ''
    form.sexo = ''
    form.data_nascimento = ''
    form.tipo = 'cliente'
    form.ativo = true
    mensagemErro.value = ''
}

const carregar = async () => {
    carregando.value = true
    try {
        const { data } = await axios.get(BASE_URL)
        todas.value = Array.isArray(data) ? data : (data?.data ?? [])
    } finally {
        carregando.value = false
    }
}

onMounted(carregar)

const filtradas = computed(() => {
    const q = busca.value.trim().toLowerCase()
    if (!q) return todas.value

    return todas.value.filter((p) =>
        String(p.nome ?? '').toLowerCase().includes(q) ||
        String(p.email ?? '').toLowerCase().includes(q) ||
        String(p.tipo ?? '').toLowerCase().includes(q)
    )
})

watch(busca, () => paginaAtual.value = 1)
watch(abaSelecionada, () => paginaAtual.value = 1)

const totalPaginas = computed(() =>
    Math.ceil(filtradas.value.length / itensPorPagina) || 1
)

const dadosPaginados = computed(() => {
    const inicio = (paginaAtual.value - 1) * itensPorPagina
    return filtradas.value.slice(inicio, inicio + itensPorPagina)
})

function btnClass(tipo) {
    return [
        'px-3 py-1 text-sm border rounded-lg',
        abaSelecionada.value === tipo
            ? 'bg-blue-600 text-white border-blue-600'
            : 'bg-white hover:bg-gray-100',
    ]
}

const abrirNovo = () => {
    resetForm()
    abaSelecionada.value = 'novo'
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

const abrirLista = () => {
    abaSelecionada.value = 'lista'
}

const abrirEdicao = (p) => {
    resetForm()
    form.id = p.id
    form.nome = p.nome ?? ''
    form.email = p.email ?? ''
    form.telefone = p.telefone ?? ''
    form.sexo = p.sexo ?? ''
    form.data_nascimento = p.data_nascimento ?? ''
    form.tipo = p.tipo ?? 'cliente'
    form.ativo = !!p.ativo

    abaSelecionada.value = 'novo'
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

const salvar = async () => {

    mensagemErro.value = ''

    if (!form.nome.trim()) {
        mensagemErro.value = 'Informe o nome da pessoa.'
        return
    }

    if (!form.tipo) {
        mensagemErro.value = 'Selecione o tipo da pessoa.'
        return
    }

    const payload = {
        nome: form.nome,
        email: form.email || null,
        telefone: form.telefone || null,
        sexo: form.sexo || null,
        data_nascimento: form.data_nascimento || null,
        tipo: form.tipo,
        ativo: !!form.ativo,
    }

    try {
        if (modoEdicao.value) {
            await axios.put(`${BASE_URL}/${form.id}`, payload)
        } else {
            await axios.post(BASE_URL, payload)
        }

        await carregar()
        resetForm()
        abaSelecionada.value = 'lista'

    } catch (err) {

        if (err.response?.status === 422) {
            mensagemErro.value = err.response.data.message ?? 'Dados inválidos.'
        } else {
            mensagemErro.value = 'Erro ao salvar pessoa.'
        }

        setTimeout(() => {
            mensagemErro.value = ''
        }, 3000)
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
        await carregar()
    } catch {
        mensagemErro.value = 'Erro ao excluir pessoa.'
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

</script>

<template>
    <div class="pt-15 space-y-8">

        <h2 class="text-2xl font-bold">Cadastro de Pessoas</h2>

        <!-- Abas -->
        <div class="flex gap-2 flex-wrap">
            <button @click="abrirNovo" :class="btnClass('novo')">
                Novo cadastro
            </button>

            <button @click="abrirLista" :class="btnClass('lista')">
                Pessoas cadastradas
            </button>
        </div>

        <!-- FORM -->
        <div v-if="abaSelecionada === 'novo'"
            class="bg-white border border-gray-200 rounded-xl shadow-sm">

            <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl flex justify-between items-center">
                <h3 class="font-semibold text-gray-700">
                    {{ modoEdicao ? 'Editar pessoa' : 'Nova pessoa' }}
                </h3>

                <button v-if="modoEdicao"
                    type="button"
                    class="px-3 py-1 text-sm border rounded-lg bg-white hover:bg-gray-100"
                    @click="resetForm">
                    Limpar edição
                </button>
            </div>

            <div class="p-4">

                <!-- 🔴 Mensagem de erro padrão -->
                <div v-if="mensagemErro"
                    class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded-lg text-sm">
                    {{ mensagemErro }}
                </div>

                <form class="grid grid-cols-1 md:grid-cols-3 gap-4"
                    @submit.prevent="salvar">

                    <div class="md:col-span-2">
                        <label class="text-sm text-gray-600">Nome *</label>
                        <input v-model="form.nome"
                            class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" />
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Tipo *</label>
                        <select v-model="form.tipo"
                            class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="cliente">Cliente</option>
                            <option value="fornecedor">Fornecedor</option>
                            <option value="funcionario">Funcionário</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm text-gray-600 ">Email</label>
                        <input v-model="form.email"
                            type="email"
                            class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" />
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Telefone</label>
                        <input v-model="form.telefone"
                            class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" />
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Sexo</label>
                        <select v-model="form.sexo"
                            class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="">—</option>
                            <option value="M">M</option>
                            <option value="F">F</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Data de nascimento</label>
                        <input v-model="form.data_nascimento"
                            type="date"
                            class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" />
                    </div>

                    <div class="md:col-span-3 flex items-center gap-2">
                        <input id="ativo"
                            type="checkbox"
                            v-model="form.ativo"
                            class="h-4 w-4" />
                        <label for="ativo"
                            class="text-sm text-gray-700">
                            Ativo
                        </label>
                    </div>

                    <div class="md:col-span-3 flex gap-2">
                        <button type="submit"
                            class="px-3 py-1 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            {{ modoEdicao ? 'Salvar' : 'Cadastrar' }}
                        </button>

                        <button type="button"
                            class="px-3 py-1 text-sm border rounded-lg bg-white hover:bg-gray-100"
                            @click="resetForm">
                            Limpar
                        </button>

                        <button type="button"
                            class="px-3 py-1 text-sm border rounded-lg bg-white hover:bg-gray-100"
                            @click="abrirLista">
                            Voltar para lista
                        </button>
                    </div>

                </form>
            </div>
        </div>

        <!-- LISTA -->
        <div v-else
            class="bg-white border border-gray-200 rounded-xl shadow-sm">

            <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl flex justify-between items-center gap-3 flex-wrap">
                <h3 class="font-semibold text-gray-700">
                    Pessoas cadastradas
                </h3>

                <div class="flex gap-2 items-center flex-wrap">
                    <input v-model="busca"
                        class="border rounded-lg px-3 py-1 text-sm w-72"
                        placeholder="Buscar por nome, email ou tipo..." />

                    <button @click="abrirNovo"
                        class="px-3 py-1 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Novo
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
                            <th class="px-4 py-3">Ativo</th>
                            <th class="px-4 py-3">Ações</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">

                        <tr v-for="p in dadosPaginados"
                            :key="p.id"
                            class="hover:bg-gray-50">

                            <td class="px-4 py-3 font-medium text-gray-800">
                                {{ p.nome }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ p.tipo }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ p.email ?? '-' }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                <span :class="p.ativo ? 'text-green-600' : 'text-gray-400'">
                                    {{ p.ativo ? 'Sim' : 'Não' }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <button class="text-blue-600 hover:underline"
                                    @click="abrirEdicao(p)">
                                    Editar
                                </button>

                                <button
                                    @click="excluir(p)"
                                    class="ml-3 hover:underline"
                                    :class="confirmandoId === p.id ? 'text-red-600' : 'text-red-600'"
                                >
                                    {{ confirmandoId === p.id ? 'Confirma' : 'Excluir' }}
                                </button>
                            </td>
                        </tr>

                        <tr v-if="carregando">
                            <td colspan="5"
                                class="px-4 py-6 text-center text-gray-400">
                                Carregando...
                            </td>
                        </tr>

                        <tr v-if="!carregando && !dadosPaginados.length">
                            <td colspan="5"
                                class="px-4 py-6 text-center text-gray-400">
                                Nenhuma pessoa encontrada
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
    </div>
</template>