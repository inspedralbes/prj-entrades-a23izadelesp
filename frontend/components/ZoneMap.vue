<script setup lang="ts">
import { useZonesStore } from '~/stores/zones'
import { useSocket } from '~/composables/useSocket'

const props = defineProps<{
  sessionId: number
  readonly?: boolean
}>()

const zonesStore = useZonesStore()
const { connect, on, off, disconnect } = useSocket()

function toggleZone(zoneId: number) {
  if (props.readonly) return
  
  const status = getZoneStatus(zoneId)
  if (status === 'unavailable') return
  
  zonesStore.toggleZone(props.sessionId, zoneId)
}

function getZoneStatus(zoneId: number) {
  const zone = zonesStore.zones.find(z => z.id === zoneId)
  if (!zone) return 'unavailable'
  if (zone.selected_by_session_id === props.sessionId) return 'selected'
  return zone.available ? 'available' : 'unavailable'
}

onMounted(async () => {
  await zonesStore.fetchZones(props.sessionId)
  
  const config = useRuntimeConfig()
  connect(config.public.socketUrl)
  
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
  disconnect()
})
</script>

<template>
  <div class="grid grid-cols-1 gap-4 py-4 sm:grid-cols-2 lg:grid-cols-3">
    <div v-if="zonesStore.loading" class="col-span-3 py-12 text-center text-lg font-medium">
      Carregant zones...
    </div>
    <template v-else>
      <ZoneSelector
        v-for="zone in zonesStore.zones"
        :key="zone.id"
        :zone="zone"
        :class="[
          getZoneStatus(zone.id) === 'unavailable' ? 'opacity-50 grayscale' : '',
          getZoneStatus(zone.id) === 'selected' ? 'bg-primary text-white border-white scale-[1.02]' : 'hover:-translate-y-1',
          props.readonly ? 'cursor-default pointer-events-none' : 'cursor-pointer'
        ]"
        @click="toggleZone(zone.id)"
      />
    </template>
  </div>
</template>
