<script setup lang="ts">
const route = useRoute()
const bookingId = route.params.id as string
const { get } = useApi()

const isLoggedIn = ref(false)
const qrImage = ref<string | null>(null)
const qrError = ref<string | null>(null)

onMounted(async () => {
  const token = localStorage.getItem('auth-token')
  isLoggedIn.value = !!token

  const response = await get<{ data: { qr: string } }>(`/bookings/${bookingId}/qr`)

  if (response?.data?.qr) {
    qrImage.value = response.data.qr
  } else {
    qrError.value = 'No hem pogut carregar el QR ara mateix.'
  }
})
</script>

<template>
  <div class="min-h-screen bg-background">
    <main class="mx-auto max-w-2xl px-4 py-10">
      <div class="card-brutal bg-white p-6 text-center sm:p-8">
        <div class="mx-auto mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full border-2 border-black bg-secondary text-3xl font-bold">
          ✓
        </div>

        <h1 class="text-2xl font-extrabold sm:text-3xl">¡Pago confirmado!</h1>
        <p class="mt-2 text-sm text-gray-600">Reserva #{{ bookingId }}</p>

        <div class="mt-6 rounded-lg border-2 border-black bg-gray-50 p-4">
          <p class="text-sm font-semibold text-gray-800">El teu QR d'accés</p>
          <img
            v-if="qrImage"
            :src="qrImage"
            alt="QR de la reserva"
            class="mx-auto mt-3 w-52 max-w-full border-2 border-black bg-white p-2"
          >
          <p v-else class="mt-2 text-sm text-gray-600">
            {{ qrError ?? 'Carregant QR...' }}
          </p>
        </div>

        <div v-if="!isLoggedIn" class="mt-6 rounded-lg border-2 border-black bg-gray-50 p-4 text-left">
          <p class="text-base font-medium text-gray-800">
            Te hemos enviado la confirmación por email.
          </p>
          <p class="mt-1 text-sm text-gray-600">
            Revisa tu bandeja de entrada (y spam) para ver tu QR y los detalles de acceso.
          </p>
        </div>

        <div v-else class="mt-6 rounded-lg border-2 border-black bg-gray-50 p-4 text-left">
          <p class="text-base font-medium text-gray-800">
            En tu email tienes el QR de acceso.
          </p>
          <p class="mt-1 text-sm text-gray-600">
            También puedes verlo cuando quieras desde tu perfil, en la sección de entradas.
          </p>
        </div>

        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
          <NuxtLink to="/" class="btn-brutal-secondary inline-flex items-center justify-center px-6 py-2">
            Volver al inicio
          </NuxtLink>

          <NuxtLink
            v-if="isLoggedIn"
            to="/profile/tickets"
            class="btn-brutal inline-flex items-center justify-center px-6 py-2"
          >
            Ver mis entradas
          </NuxtLink>
        </div>
      </div>
    </main>
  </div>
</template>