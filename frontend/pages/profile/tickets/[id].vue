<script setup lang="ts">
import { useProfileStore } from '~/stores/profile'

const route = useRoute()
const profileStore = useProfileStore()
const bookingId = parseInt(route.params.id as string)

onMounted(() => {
  profileStore.fetchBooking(bookingId)
})

function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString('ca-ES', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
  })
}

function downloadQR(qrCode: string) {
  const link = document.createElement('a')
  link.href = qrCode
  link.download = `entrada-${bookingId}.png`
  link.click()
}
</script>

<template>
  <div>
    <main class="mx-auto max-w-3xl px-4 py-8">
      <NuxtLink to="/profile/tickets" class="mb-4 inline-flex items-center gap-2 text-sm font-medium hover:underline">
        ← Tornar
      </NuxtLink>

      <div v-if="profileStore.loading" class="py-12 text-center text-lg font-medium">
        Carregant...
      </div>

      <div v-else-if="profileStore.error" class="py-12 text-center text-lg text-accent">
        {{ profileStore.error }}
      </div>

      <div v-else-if="profileStore.currentBooking" class="space-y-6">
        <div class="card-brutal p-6">
          <div class="mb-4 flex items-center justify-between">
            <h1 class="text-2xl font-bold">{{ profileStore.currentBooking.event.title }}</h1>
            <span
              class="border-2 border-black px-3 py-1 font-semibold"
              :class="{
                'bg-primary': profileStore.currentBooking.status === 'confirmed',
                'bg-secondary': profileStore.currentBooking.status === 'pending',
                'bg-accent': profileStore.currentBooking.status === 'failed'
              }"
            >
              {{ profileStore.currentBooking.status === 'confirmed' ? 'Confirmada' : 'Pendent' }}
            </span>
          </div>
          <div class="space-y-2 text-gray-600">
            <p>📍 {{ profileStore.currentBooking.event.venue }}</p>
            <p>📅 {{ formatDate(profileStore.currentBooking.session.date) }} · {{ profileStore.currentBooking.session.time }}</p>
          </div>
        </div>

        <div class="card-brutal p-6">
          <h2 class="mb-4 text-lg font-bold">Entrades</h2>
          <div class="space-y-4">
            <div
              v-for="ticket in profileStore.currentBooking.tickets"
              :key="ticket.id"
              class="flex items-center justify-between border-2 border-black p-4"
            >
              <div>
                <p class="font-bold">
                  {{ ticket.seat ? `Fila ${ticket.seat.row}, Seat ${ticket.seat.number}` : ticket.zone?.name }}
                </p>
                <p class="text-sm text-gray-500">Entrada #{{ ticket.id }}</p>
              </div>
              <button
                class="btn-brutal text-sm"
                @click="downloadQR(ticket.qr_code)"
              >
                Descarregar QR
              </button>
            </div>
          </div>
        </div>

        <div class="card-brutal flex justify-between p-6">
          <span class="font-bold">Total</span>
          <span class="text-xl font-bold">{{ profileStore.currentBooking.total }}€</span>
        </div>
      </div>
    </main>
  </div>
</template>