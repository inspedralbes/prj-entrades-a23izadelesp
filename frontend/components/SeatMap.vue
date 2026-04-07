<script setup lang="ts">
import { useSeatsStore } from '~/stores/seats'
import { useSocket } from '~/composables/useSocket'

const props = defineProps<{
  sessionId: number
  layout: { rows: string[], seatsPerRow: number }
}>()

const seatsStore = useSeatsStore()
const { on, off } = useSocket()

onMounted(async () => {
  await seatsStore.fetchSeats(props.sessionId)
  
  on('seat:locked', (data: any) => {
    if (data.session_id === props.sessionId) {
      seatsStore.updateSeatStatus(data.seat_id, 'occupied')
    }
  })
  
  on('seat:released', (data: any) => {
    if (data.session_id === props.sessionId) {
      seatsStore.updateSeatStatus(data.seat_id, 'available')
    }
  })
})

onUnmounted(() => {
  off('seat:locked')
  off('seat:released')
})

function handleSelect(seatId: number, row: string, number: number, price: number) {
  seatsStore.lockSeat(seatId, row, number, price)
}

function handleDeselect(seatId: number) {
  seatsStore.unlockSeat(seatId)
}

function getSeat(row: string, number: number) {
  return seatsStore.seats.find(s => s.row === row && s.number === number)
}
</script>

<template>
  <div class="overflow-x-auto py-4">
    <div v-if="seatsStore.loading" class="py-12 text-center text-lg font-medium">
      Carregant seats...
    </div>
    <div v-else class="inline-block">
      <div class="mb-2 text-center text-sm font-medium text-gray-500">PANTALLA</div>
      <div class="mb-6 flex justify-center">
        <div class="h-2 w-3/4 border-b-2 border-black" />
      </div>
      
      <div v-for="row in layout.rows" :key="row" class="mb-1 flex items-center gap-1">
        <span class="w-6 text-center text-sm font-bold">{{ row }}</span>
        <div class="flex gap-1">
          <SeatCell
            v-for="num in layout.seatsPerRow"
            :key="num"
            :id="getSeat(row, num)?.id || 0"
            :row="row"
            :number="num"
            :status="getSeat(row, num)?.status || 'available'"
            :price="getSeat(row, num)?.price || 0"
            @select="handleSelect"
            @deselect="handleDeselect"
          />
        </div>
      </div>
      
      <div class="mt-6 flex justify-center gap-6 text-sm">
        <div class="flex items-center gap-2">
          <div class="h-4 w-4 border-2 border-black bg-white" />
          <span>Lliure</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="h-4 w-4 border-2 border-black bg-black" />
          <span>Ocupat</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="h-4 w-4 border-2 border-black bg-primary" />
          <span>Seleccionat</span>
        </div>
      </div>
    </div>
  </div>
</template>