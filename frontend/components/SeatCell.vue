<script setup lang="ts">
import { useSeatsStore } from '~/stores/seats'

const props = defineProps<{
  id: number
  row: string
  number: number
  status: string
  price: number
}>()

const emit = defineEmits<{
  select: [id: number, row: string, number: number, price: number]
  deselect: [id: number]
}>()

const seatsStore = useSeatsStore()

const isSelected = computed(() => {
  return seatsStore.selectedSeats.some(s => s.seatId === props.id)
})

function handleClick() {
  if (props.status === 'occupied') return
  if (isSelected.value) {
    emit('deselect', props.id)
  } else {
    emit('select', props.id, props.number, props.price)
  }
}

const statusClasses = computed(() => {
  if (isSelected.value) return 'bg-primary border-black'
  switch (props.status) {
    case 'occupied': return 'bg-black border-black cursor-not-allowed'
    case 'blocked': return 'bg-gray-400 border-black'
    default: return 'bg-white border-black hover:bg-gray-100'
  }
})
</script>

<template>
  <button
    class="flex h-8 w-8 items-center justify-center border-2 text-xs font-medium transition-all"
    :class="statusClasses"
    :disabled="status === 'occupied'"
    @click="handleClick"
  >
    {{ number }}
  </button>
</template>