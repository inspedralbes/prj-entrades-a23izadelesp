<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const isLoggedIn = ref(false)
const userName = ref('')

onMounted(() => {
  const token = localStorage.getItem('auth-token')
  isLoggedIn.value = !!token
  const user = localStorage.getItem('user-name')
  if (user) {
    userName.value = user
  }
})

function logout() {
  localStorage.removeItem('auth-token')
  localStorage.removeItem('user-name')
  isLoggedIn.value = false
  router.push('/')
}
</script>

<template>
  <header class="border-b-2 border-black bg-white">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
      <NuxtLink to="/" class="text-2xl font-bold tracking-tight">
        Queue<span class="text-primary">Ly</span>
      </NuxtLink>
      <nav class="flex gap-4 items-center">
        <div v-if="isLoggedIn" class="flex items-center gap-4">
          <span class="text-sm font-medium">{{ userName }}</span>
          <NuxtLink to="/profile/tickets" class="border-2 border-black px-3 py-1 text-sm font-medium hover:bg-gray-100">
            Mis entradas
          </NuxtLink>
          <button
            class="border-2 border-black bg-accent px-3 py-1 text-sm font-medium text-white hover:translate-x-[2px] hover:translate-y-[2px]"
            @click="logout"
          >
            Salir
          </button>
        </div>
        <div v-else class="flex gap-2">
          <NuxtLink to="/login" class="btn-brutal">Iniciar sesión</NuxtLink>
          <NuxtLink to="/register" class="border-2 border-black px-4 py-2 font-semibold hover:bg-gray-100">
            Registrarse
          </NuxtLink>
        </div>
      </nav>
    </div>
  </header>
</template>