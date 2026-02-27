<script setup>
import Header from '../components/Header.vue'
import Footer from '../components/Footer.vue'
</script>

<template>
  <div class="min-h-screen flex flex-col bg-gray-50">

    <!-- Header -->
    <Header />

    <!-- Conteúdo dinâmico -->
    <main class="flex-1 max-w-7xl mx-auto w-full px-4 py-6">
      <slot />
    </main>

    <!-- Footer -->
    <Footer />

  </div>
</template>