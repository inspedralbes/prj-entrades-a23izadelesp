<script setup lang="ts">
import { useSeatsStore } from '~/stores/seats'
import { useZonesStore } from '~/stores/zones'
import { useRouter } from 'vue-router'

const props = defineProps<{
  mode: 'seats' | 'zones'
  eventId: number
  sessionId: number
}>()

const emit = defineEmits<{
  book: []
}>()

const router = useRouter()
const seatsStore = useSeatsStore()
const zonesStore = useZonesStore()

const hasSelection = computed(() => {
  if (props.mode === 'seats') return seatsStore.selectedSeats.length > 0
  return zonesStore.selectedZones.length > 0 || zonesStore.selectedZoneSeats.length > 0
})

const totalPrice = computed(() => {
  if (props.mode === 'seats') return seatsStore.totalPrice
  return zonesStore.totalPrice
})

const selectionSummary = computed(() => {
  if (props.mode === 'seats') {
    return seatsStore.selectedSeats.map(s => `F${s.row}${s.number}`).join(', ')
  }
  const general = zonesStore.selectedZones.map(z => `${z.name} (${z.lockIds.length})`)
  const seated = zonesStore.selectedZoneSeats.map(s => `${s.zoneName} ${s.label}`)
  return [...general, ...seated].join(', ')
})

function handleBook() {
  if (hasSelection.value) {
    router.push(`/events/${props.eventId}/checkout/${props.sessionId}`)
  }
}
</script>

<template>
  <div class="fixed bottom-0 left-0 right-0 border-t-2 border-black bg-white/95 p-3 shadow-[0_-4px_0_0_#000000] backdrop-blur sm:p-4">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="min-w-0 flex-1">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Seleccionat</p>
        <p class="truncate pr-1 text-sm font-medium sm:text-base">{{ selectionSummary || 'Cap selecció' }}</p>
      </div>
      <div class="flex items-center justify-between gap-3 sm:justify-end sm:gap-6">
        <div class="text-left sm:text-right">
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total</p>
          <p class="text-xl font-extrabold sm:text-2xl">{{ totalPrice }}€</p>
        </div>
        <button
          class="btn-brutal-secondary min-w-[150px] px-4 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-50 sm:min-w-[180px] sm:text-base"
          :disabled="!hasSelection"
          @click="handleBook"
        >
          {{ hasSelection ? 'Comprar' : 'Selecciona sitios' }}
        </button>
      </div>
    </div>
  </div>
</template>