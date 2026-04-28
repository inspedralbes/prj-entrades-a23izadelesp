<script setup lang="ts">
import { ref } from 'vue'

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirm = ref('')
const loading = ref(false)
const error = ref('')
const success = ref(false)

const router = useRouter()
const { post } = useApi()

const register = async () => {
  if (password.value !== passwordConfirm.value) {
    error.value = 'Las contraseñas no coinciden'
    return
  }

  try {
    loading.value = true
    error.value = ''

    const response = await post('/auth/register', {
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirm.value
    })

    if (response && (response as any).token) {
      success.value = true
      localStorage.setItem('auth-token', (response as any).token)
      if ((response as any).user?.name) {
        localStorage.setItem('user-name', (response as any).user.name)
      }
      setTimeout(() => {
        router.push('/')
      }, 2000)
    }
  } catch (err: any) {
    error.value = err instanceof Error ? err.message : 'Error al registrarse'
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
        <p class="text-sm text-gray-600">Crea tu cuenta para comenzar</p>
      </div>

      <!-- Success Message -->
      <div
        v-if="success"
        class="mb-6 border-2 border-primary bg-white p-3 text-primary font-medium"
      >
        ¡Registro exitoso! Redirigiendo...
      </div>

      <!-- Error Message -->
      <div
        v-if="error"
        class="mb-6 border-2 border-accent bg-white p-3 text-accent font-medium"
      >
        {{ error }}
      </div>

      <!-- Register Form -->
      <form class="space-y-4 mb-6" @submit.prevent="register">
        <!-- Name Input -->
        <div>
          <label for="name" class="block text-sm font-semibold mb-2">Nombre completo</label>
          <input
            id="name"
            v-model="name"
            type="text"
            class="input-brutal w-full"
            placeholder="Tu nombre"
            required
            :disabled="loading"
          />
        </div>

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

        <!-- Confirm Password Input -->
        <div>
          <label for="passwordConfirm" class="block text-sm font-semibold mb-2">Confirmar contraseña</label>
          <input
            id="passwordConfirm"
            v-model="passwordConfirm"
            type="password"
            class="input-brutal w-full"
            placeholder="••••••••"
            required
            :disabled="loading"
          />
        </div>

        <!-- Register Button -->
        <button
          type="submit"
          class="btn-brutal w-full bg-black text-white font-bold disabled:opacity-50"
          :disabled="loading"
        >
          {{ loading ? 'Registrando...' : 'Registrarse' }}
        </button>
      </form>

      <!-- Links -->
      <div class="text-center space-y-2 text-sm">
        <p>
          ¿Ya tienes cuenta?
          <NuxtLink to="/login" class="font-bold text-primary hover:underline">
            Inicia sesión aquí
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
