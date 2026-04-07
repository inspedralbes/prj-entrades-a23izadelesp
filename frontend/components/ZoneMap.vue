<script setup lang="ts">
import { useZonesStore } from '~/stores/zones'
import { useSocket } from '~/composables/useSocket'

const props = defineProps<{
  sessionId: number
}>()

const zonesStore = useZonesStore()
const { on, off } = useSocket()

onMounted(async () => {
  await zonesStore.fetchZones(props.sessionId)
  
  on('zone:locked', (data: any) => {
    if (data.session_id === props.sessionId) {
      zonesStore.updateZoneAvailability(data.zone_id, data.available)
    }
  })
  
  on('zone:released', (data: any) => {
    if (data.session_id === props.sessionId) {
      zonesStore.updateZoneAvailability(data.zone_id, data.available)
    }
  })
})

onUnmounted(() => {
  off('zone:locked')
  off('zone:released')
})

function handleSelect(zoneId: number, name: string, quantity: number, price: number) {
  zonesStore.lockZone(zoneId, name, quantity, price)
}

function handleDeselect(zoneId: number) {
  zonesStore.unlockZone(zoneId)
}
</script>

<template>
  <div class="grid grid-cols-1 gap-4 py-4 sm:grid-cols-2">
    <div v-if="zonesStore.loading" class="col-span-2 py-12 text-center text-lg font-medium">
      Carregant zones...
    </div>
    <template v-else>
      <ZoneCard
        v-for="zone in zonesStore.zones"
        :key="zone.id"
        :id="zone.id"
        :name="zone.name"
        :price="zone.price"
        :capacity="zone.capacity"
        :available="zone.available"
        :status="zone.status"
        :color="zone.color"
        @select="handleSelect"
      />
    </template>
  </div>
</template>