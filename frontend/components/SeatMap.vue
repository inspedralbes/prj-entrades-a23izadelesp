<script setup lang="ts">
import { useSeatsStore } from '~/stores/seats'
import { useSocket } from '~/composables/useSocket'

const props = defineProps<{
  sessionId: number
  layout: { rows: string[], seatsPerRow: number }
  readonly?: boolean
}>()

const seatsStore = useSeatsStore()
const { connect, emit, on, off, disconnect } = useSocket()

onMounted(async () => {
  await seatsStore.fetchSeats(props.sessionId)
  
  const config = useRuntimeConfig()
  connect(config.public.socketUrl)
  emit('join:session', props.sessionId)
  
  on('seat:locked', (data: any) => {
    if (data.session_id === props.sessionId) {
      seatsStore.updateSeatStatusByGrid(data.row, data.col, 'blocked')
    }
  })
  
  on('seat:released', (data: any) => {
    if (data.session_id === props.sessionId) {
      seatsStore.updateSeatStatusByGrid(data.row, data.col, 'available')
    }
  })
})

onUnmounted(() => {
  off('seat:locked')
  off('seat:released')
  disconnect()
})

function handleSelect(seatId: number, row: string, number: number, price: number) {
  seatsStore.lockSeat(seatId, row, number, price)
}

function handleDeselect(seatId: number) {
  seatsStore.unlockSeat(seatId)
}

function getRowSeats(row: string) {
  return seatsStore.seats
    .filter((seat) => seat.row === row)
    .sort((a, b) => a.apiCol - b.apiCol)
}
</script>

<template>
  <div class="card-brutal bg-white p-3 sm:p-5">
    <div v-if="seatsStore.loading" class="py-12 text-center text-lg font-medium">
      Carregant seats...
    </div>
    <div v-else class="overflow-x-auto pb-1">
      <div class="flex w-full justify-center">
        <div class="inline-block min-w-max">
        <div class="mb-1 text-center text-xs font-extrabold tracking-[0.2em] text-gray-600 sm:text-sm">PANTALLA</div>
        <div class="mb-5 flex justify-center sm:mb-6">
          <div class="h-3 w-48 rounded-t-full border-2 border-black border-b-0 bg-gray-100 sm:w-72" />
        </div>

        <div v-for="row in layout.rows" :key="row" class="mb-2 flex items-center gap-2">
          <span class="w-7 text-center text-sm font-extrabold sm:w-8 sm:text-base">{{ row }}</span>
          <div class="flex gap-1.5 sm:gap-2">
            <SeatCell
              v-for="seat in getRowSeats(row)"
              :key="seat.id"
              :id="seat.id"
              :row="seat.row"
              :number="seat.number"
              :status="seat.status"
              :price="seat.price"
              @select="handleSelect"
              @deselect="handleDeselect"
            />
          </div>
        </div>
      </div>
      </div>

      <div class="mt-5 grid grid-cols-1 gap-2 text-sm sm:mt-6 sm:grid-cols-4 sm:gap-4">
        <div class="flex items-center justify-center gap-2 border-2 border-black bg-white px-3 py-2">
          <div class="h-4 w-4 border-2 border-black bg-white" />
          <span class="font-medium">Lliure</span>
        </div>
        <div class="flex items-center justify-center gap-2 border-2 border-black bg-white px-3 py-2">
          <div class="h-4 w-4 border-2 border-black bg-black" />
          <span class="font-medium">Ocupat</span>
        </div>
        <div class="flex items-center justify-center gap-2 border-2 border-black bg-white px-3 py-2">
          <div class="h-4 w-4 border-2 border-black bg-secondary" />
          <span class="font-medium">Reservat</span>
        </div>
        <div class="flex items-center justify-center gap-2 border-2 border-black bg-white px-3 py-2">
          <div class="h-4 w-4 border-2 border-black bg-primary" />
          <span class="font-medium">Seleccionat</span>
        </div>
      </div>
    </div>
  </div>
</template>
