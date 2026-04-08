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
  return zonesStore.selectedZones.length > 0
})

const totalPrice = computed(() => {
  if (props.mode === 'seats') return seatsStore.totalPrice
  return zonesStore.totalPrice
})

const selectionSummary = computed(() => {
  if (props.mode === 'seats') {
    return seatsStore.selectedSeats.map(s => `F${s.row}${s.number}`).join(', ')
  }
  return zonesStore.selectedZones.map(z => `${z.name} (${z.quantity})`).join(', ')
})

function handleBook() {
  if (hasSelection.value) {
    router.push(`/events/${props.eventId}/checkout/${props.sessionId}`)
  }
}
</script>

<template>
  <div class="fixed bottom-0 left-0 right-0 border-t-2 border-black bg-white p-4 shadow-[0_-4px_0_0_#000000]">
    <div class="mx-auto flex max-w-7xl items-center justify-between">
      <div class="flex-1">
        <p class="text-sm text-gray-500">Seleccionat</p>
        <p class="font-medium">{{ selectionSummary || 'Cap selecció' }}</p>
      </div>
      <div class="flex items-center gap-6">
        <div class="text-right">
          <p class="text-sm text-gray-500">Total</p>
          <p class="text-2xl font-bold">{{ totalPrice }}€</p>
        </div>
        <button
          class="btn-brutal-secondary text-lg"
          :disabled="!hasSelection"
          @click="handleBook"
        >
          Reservar Ara
        </button>
      </div>
    </div>
  </div>
</template>