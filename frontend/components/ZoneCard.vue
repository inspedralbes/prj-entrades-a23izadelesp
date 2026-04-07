<script setup lang="ts">
import { useZonesStore } from '~/stores/zones'

const props = defineProps<{
  id: number
  name: string
  price: number
  capacity: number
  available: number
  status: string
  color: string
}>()

const emit = defineEmits<{
  select: [id: number, name: string, quantity: number, price: number]
}>()

const zonesStore = useZonesStore()

const zoneSelection = computed(() => {
  return zonesStore.selectedZones.find(z => z.zoneId === props.id)
})

function handleClick() {
  if (props.available === 0) return
  emit('select', props.id, props.name, 1, props.price)
}

const statusClasses = computed(() => {
  if (zoneSelection.value) return 'ring-4 ring-primary ring-offset-2'
  if (props.available === 0) return 'opacity-50 cursor-not-allowed'
  return 'hover:shadow-brutal'
})
</script>

<template>
  <button
    class="card-brutal flex w-full flex-col items-start p-4 text-left transition-all"
    :class="statusClasses"
    :disabled="available === 0"
    @click="handleClick"
  >
    <div class="mb-2 h-4 w-full" :style="{ backgroundColor: color }" />
    <h3 class="text-lg font-bold">{{ name }}</h3>
    <p class="text-2xl font-bold text-primary">{{ price }}€</p>
    <p class="mt-2 text-sm text-gray-500">
      {{ available }} de {{ capacity }} disponibles
    </p>
    <div v-if="zoneSelection" class="mt-2 w-full border-t-2 border-black pt-2">
      <p class="font-bold">Seleccionades: {{ zoneSelection.quantity }}</p>
    </div>
  </button>
</template>