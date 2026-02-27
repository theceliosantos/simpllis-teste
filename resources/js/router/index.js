import { createRouter, createWebHistory } from 'vue-router'

import Dashboard from '../pages/Dashboard.vue'
import Relatorios from '../pages/Relatorios.vue'
import Cadastros from '../pages/Cadastros.vue'

import PessoaCadastro from '../components/cadastros/PessoaCadastro.vue'
import ProdutoCadastro from '../components/cadastros/ProdutoCadastro.vue'
import CompraCadastro from '../components/cadastros/CompraCadastro.vue'
import VendaCadastro from '../components/cadastros/VendaCadastro.vue'

const routes = [
  {
    path: '/',
    name: 'dashboard',
    component: Dashboard
  },

  {
    path: '/relatorios',
    name: 'relatorios',
    component: Relatorios
  },

  {
    path: '/cadastros',
    name: 'cadastros',
    component: Cadastros
  },

  {
    path: '/cadastros/pessoas',
    name: 'cadastros.pessoas',
    component: PessoaCadastro
  },

  {
    path: '/cadastros/produtos',
    name: 'cadastros.produtos',
    component: ProdutoCadastro
  },

  {
    path: '/cadastros/compras',
    name: 'cadastros.compras',
    component: CompraCadastro
  },

  {
    path: '/cadastros/vendas',
    name: 'cadastros.vendas',
    component: VendaCadastro
  },
]

export default createRouter({
  history: createWebHistory(),
  routes,
})