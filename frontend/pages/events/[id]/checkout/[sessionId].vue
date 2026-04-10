<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useSeatsStore } from '../../../../stores/seats'
import { useZonesStore } from '../../../../stores/zones'
import { useSessionStore } from '../../../../stores/session'
import { useApi } from '../../../../composables/useApi'

const router = useRouter()
const route = useRoute()
const seatsStore = useSeatsStore()
const zonesStore = useZonesStore()
const sessionStore = useSessionStore()
const api = useApi()
const { post } = api

const eventId = parseInt(route.params.id as string)
const sessionId = parseInt(route.params.sessionId as string)

const isLoggedIn = ref(false)
const guestEmail = ref('')
const loading = ref(false)
const error = ref('')

onMounted(() => {
  const token = localStorage.getItem('auth-token')
  isLoggedIn.value = !!token
})

const bookingSummary = computed(() => {
  const isCine = sessionStore.event?.type === 'movie'
  if (isCine) {
    return {
      type: 'seats',
      items: seatsStore.selectedSeats.map((s: any) => `Fila ${s.row}, Asiento ${s.number}`),
      total: seatsStore.totalPrice,
      count: seatsStore.selectedSeats.length
    }
  } else {
    const generalItems = zonesStore.selectedZones.map((z: any) => `${z.name} (${z.lockIds.length})`)
    const seatedItems = zonesStore.selectedZoneSeats.map((s: any) => `${s.zoneName} ${s.label}`)

    return {
      type: 'zones',
      items: [...generalItems, ...seatedItems],
      total: zonesStore.totalPrice,
      count: zonesStore.totalQuantity
    }
  }
})

const canCheckout = computed(() => {
  if (sessionStore.event?.type === 'movie') {
    return seatsStore.selectedSeats.length > 0
  }
  return zonesStore.selectedZones.length > 0 || zonesStore.selectedZoneSeats.length > 0
})

async function handleCheckout() {
  if (!canCheckout.value) {
    error.value = 'Selecciona algun elemento antes de continuar'
    return
  }

  try {
    loading.value = true
    error.value = ''

    const seats = sessionStore.event?.type === 'movie' 
      ? seatsStore.selectedSeats.map((s: any) => ({
          row: s.apiRow,
          col: s.apiCol,
          price: s.price
        }))
      : []

    const zones = sessionStore.event?.type !== 'movie'
      ? zonesStore.selectedZones.flatMap((z: any) =>
          (z.lockIds || []).map((lockId: string) => ({
            zone_id: z.zoneId,
            quantity: 1,
            lock_id: lockId
          }))
        )
      : []

    const zoneSeats = sessionStore.event?.type !== 'movie'
      ? zonesStore.selectedZoneSeats.map((s: any) => ({
          zone_id: s.zoneId,
          row: s.row,
          col: s.col
        }))
      : []

    const payload: any = {
      session_id: sessionId,
      identifier: localStorage.getItem('auth-token')
        ? `user_${localStorage.getItem('auth-token')?.split('|')[0]}`
        : localStorage.getItem('guest-identifier') || `guest_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`,
      seats,
      zones,
      zone_seats: zoneSeats
    }

    if (!localStorage.getItem('auth-token')) {
      localStorage.setItem('guest-identifier', payload.identifier)
    }

    if (!isLoggedIn.value) {
      if (!guestEmail.value) {
        error.value = 'Introdueix el teu email per continuar'
        return
      }
      payload.guest_email = guestEmail.value
    }

    const response = await post('/bookings', payload)

    if (!response) {
      error.value = api.error.value || 'No s\'ha pogut completar la reserva. Alguns seients poden no estar disponibles.'
      return
    }

    if ((response as any).data) {
      const bookingId = (response as any).data.id
      await router.push(`/bookings/${bookingId}/confirmed`)
    }
  } catch (err: any) {
    error.value = err instanceof Error ? err.message : 'Error al procesar la reserva'
  } finally {
    loading.value = false
  }
}

function goBack() {
  router.push(`/events/${eventId}/seats/${sessionId}`)
}
</script>

<template>
  <div>
    <main class="mx-auto max-w-2xl px-4 py-8">
      <NuxtLink 
        :to="`/events/${eventId}/seats/${sessionId}`" 
        class="mb-6 inline-flex items-center gap-2 text-sm font-medium hover:underline"
      >
        ← Tornar
      </NuxtLink>

      <h1 class="mb-8 text-3xl font-bold">Checkout</h1>

      <div class="space-y-6">
        <!-- Error Message -->
        <div
          v-if="error"
          class="border-2 border-accent bg-white p-4 text-accent font-medium"
        >
          {{ error }}
        </div>

        <!-- Resumen de Evento -->
        <div class="card-brutal p-6">
          <h2 class="mb-4 text-lg font-bold">Resumen de la Reserva</h2>
          <div class="mb-4 space-y-2 text-gray-600">
            <p><strong>Evento:</strong> {{ sessionStore.event?.title }}</p>
            <p><strong>Lugar:</strong> {{ sessionStore.event?.venue }}</p>
            <p><strong>Fecha:</strong> {{ sessionStore.selectedSession?.date }} · {{ sessionStore.selectedSession?.time }}</p>
          </div>

          <div class="border-t-2 border-black pt-4">
            <p class="mb-3 font-bold">{{ bookingSummary.type === 'seats' ? 'Asientos seleccionados' : 'Zonas seleccionadas' }}</p>
            <div class="space-y-1 text-sm">
              <p v-for="(item, idx) in bookingSummary.items" :key="idx" class="text-gray-700">
                {{ item }}
              </p>
            </div>
          </div>
        </div>

        <!-- Método de Pago / Login -->
        <div class="card-brutal p-6">
          <h2 class="mb-4 text-lg font-bold">Detalles de Pago</h2>
          
          <div v-if="!isLoggedIn" class="space-y-4">
            <p class="text-sm text-gray-600">
              Compra sin registrarte - solo necesitamos tu email para la confirmación
            </p>
            <div>
              <label for="guest-email" class="block text-sm font-semibold mb-2">Email</label>
              <input
                id="guest-email"
                v-model="guestEmail"
                type="email"
                class="input-brutal w-full"
                placeholder="tu@example.com"
                required
                :disabled="loading"
              />
            </div>
          </div>

          <div v-else class="p-4 bg-gray-50 border-2 border-black">
            <p class="text-sm text-gray-600 mb-2">Compra como usuario registrado</p>
            <p class="font-semibold">{{ sessionStore.event?.title }}</p>
          </div>
        </div>

        <!-- Total -->
        <div class="flex justify-between border-2 border-black bg-secondary p-6">
          <span class="text-xl font-bold">Total a pagar</span>
          <span class="text-3xl font-bold">{{ bookingSummary.total }}€</span>
        </div>

        <!-- Botones -->
        <div class="flex gap-4">
          <button
            class="flex-1 border-2 border-black bg-secondary px-4 py-2 font-semibold shadow-brutal transition-all disabled:opacity-50 disabled:cursor-not-allowed"
            @click="goBack"
            :disabled="loading"
          >
            Cancelar
          </button>
          <button
            class="flex-1 btn-brutal disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="!canCheckout || loading"
            @click="handleCheckout"
          >
            {{ loading ? 'Procesando...' : 'Pagar Ahora' }}
          </button>
        </div>

        <!-- Información de Seguridad -->
        <div class="border-2 border-black bg-gray-50 p-4 text-sm text-gray-600">
          <p>✓ Transacción segura · ✓ Datos protegidos · ✓ Confirmación por email</p>
        </div>
      </div>
    </main>
  </div>
</template>
