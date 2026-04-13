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
  if (isSelected.value) {
    emit('deselect', props.id)
    return
  }

  if (props.status === 'occupied' || props.status === 'blocked') return

  emit('select', props.id, props.row, props.number, props.price)
}

const statusClasses = computed(() => {
  if (isSelected.value) return 'bg-primary border-black'
  switch (props.status) {
    case 'occupied': return 'bg-black border-black cursor-not-allowed'
    case 'blocked': return 'bg-secondary border-black cursor-not-allowed'
    default: return 'bg-white border-black hover:bg-gray-100'
  }
})
</script>

<template>
  <button
    class="flex h-8 w-8 items-center justify-center border-2 text-xs font-semibold transition-all sm:h-10 sm:w-10 sm:text-sm"
    :class="statusClasses"
    :disabled="status === 'occupied' || status === 'blocked'"
    @click="handleClick"
  >
    {{ number }}
  </button>
</template>