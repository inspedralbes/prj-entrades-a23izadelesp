<script setup lang="ts">
import { useApi } from '~/composables/useApi'

const route = useRoute()
const bookingId = parseInt(route.params.id as string)
const { get, loading, error } = useApi()

const booking = ref<any>(null)

onMounted(async () => {
  booking.value = await get(`/bookings/${bookingId}`)
})

function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString('ca-ES', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
  })
}
</script>

<template>
  <div>
    <TopBar />
    <main class="mx-auto max-w-2xl px-4 py-8">
      <div v-if="loading" class="py-12 text-center text-lg font-medium">
        Carregant...
      </div>
      <div v-else-if="error" class="py-12 text-center text-lg text-accent">
        {{ error }}
      </div>
      <div v-else-if="booking" class="space-y-6">
        <div class="text-center">
          <div class="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-primary">
            <span class="text-3xl">✓</span>
          </div>
          <h1 class="text-2xl font-bold">Reserva Confirmada!</h1>
          <p class="text-gray-600">La teva entrada ha estat reservada</p>
        </div>

        <div class="card-brutal p-6">
          <h2 class="mb-4 text-lg font-bold">{{ booking.session?.event?.title }}</h2>
          <div class="space-y-2 text-gray-600">
            <p>📅 {{ formatDate(booking.session?.date) }} · {{ booking.session?.time }}</p>
            <p>📍 {{ booking.session?.event?.venue }}</p>
          </div>
        </div>

        <div class="border-2 border-black bg-gray-50 p-4">
          <div v-for="ticket in booking.tickets" :key="ticket.id" class="flex justify-between border-b border-dotted border-black py-2 last:border-b-0">
            <span>{{ ticket.seat ? `Fila ${ticket.seat.row}, Seat ${ticket.seat.number}` : ticket.zone?.name }}</span>
          </div>
        </div>

        <div class="flex justify-between border-2 border-black bg-secondary p-4">
          <span class="font-bold">Total pagat</span>
          <span class="text-xl font-bold">{{ booking.total }}€</span>
        </div>

        <div class="mt-8 text-center">
          <NuxtLink to="/" class="btn-brutal">
            Tornar a Inici
          </NuxtLink>
        </div>
      </div>
    </main>
  </div>
</template>