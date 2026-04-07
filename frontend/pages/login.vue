<script setup lang="ts">
import { ref } from 'vue'

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

const router = useRouter()
const { post } = useApi()

const loginWithEmail = async () => {
  try {
    loading.value = true
    error.value = ''

    const response = await post('/auth/login', {
      email: email.value,
      password: password.value
    })

    if (response && (response as any).token) {
      // Guardar token
      localStorage.setItem('auth-token', (response as any).token)
      // Redirigir a home
      await router.push('/')
    }
  } catch (err: any) {
    error.value = err instanceof Error ? err.message : 'Error al iniciar sesión'
  } finally {
    loading.value = false
  }
}

const loginWithGoogle = async () => {
  try {
    loading.value = true

    const response = await post('/auth/google', {})

    // Redirigir a Google
    if (response && (response as any).url) {
      window.location.href = (response as any).url
    }
  } catch (err: any) {
    error.value = 'Error al conectar con Google'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-[#FDFDFC] px-4 py-8">
    <div class="w-full max-w-sm card-brutal p-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2">Queue<span class="text-primary">Ly</span></h1>
        <p class="text-sm text-gray-600">Accede a tu cuenta para continuar</p>
      </div>

      <!-- Error Message -->
      <div
        v-if="error"
        class="mb-6 border-2 border-accent bg-white p-3 text-accent font-medium"
      >
        {{ error }}
      </div>

      <!-- Login Form -->
      <form class="space-y-4 mb-6" @submit.prevent="loginWithEmail">
        <!-- Email Input -->
        <div>
          <label for="email" class="block text-sm font-semibold mb-2">Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            class="input-brutal w-full"
            placeholder="tu@example.com"
            required
            :disabled="loading"
          />
        </div>

        <!-- Password Input -->
        <div>
          <label for="password" class="block text-sm font-semibold mb-2">Contraseña</label>
          <input
            id="password"
            v-model="password"
            type="password"
            class="input-brutal w-full"
            placeholder="••••••••"
            required
            :disabled="loading"
          />
        </div>

        <!-- Login Button -->
        <button
          type="submit"
          class="btn-brutal w-full bg-black text-white font-bold disabled:opacity-50"
          :disabled="loading"
        >
          {{ loading ? 'Iniciando...' : 'Iniciar sesión' }}
        </button>
      </form>

      <!-- Divider -->
      <div class="relative mb-6">
        <div class="border-t-2 border-black"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-[#FDFDFC] px-2 text-xs font-semibold">
          O CONTINÚA CON
        </div>
      </div>

      <!-- Google OAuth Button -->
      <button
        @click="loginWithGoogle"
        class="btn-brutal w-full bg-white mb-6 flex items-center justify-center gap-2 disabled:opacity-50"
        :disabled="loading"
      >
        <svg class="w-5 h-5" viewBox="0 0 24 24">
          <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
          <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
          <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
          <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
        </svg>
        Google
      </button>

      <!-- Links -->
      <div class="text-center space-y-2 text-sm">
        <p>
          ¿No tienes cuenta?
          <NuxtLink to="/register" class="font-bold text-primary hover:underline">
            Regístrate aquí
          </NuxtLink>
        </p>
        <p>
          <NuxtLink to="/" class="font-bold text-primary hover:underline">
            Volver al inicio
          </NuxtLink>
        </p>
      </div>
    </div>
  </div>
</template>
