<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useZonesStore } from '~/stores/zones'
import { useSocket } from '~/composables/useSocket'

const props = defineProps<{
  sessionId: number
  readonly?: boolean
}>()

const zonesStore = useZonesStore()
const { connect, on, off, emit, disconnect } = useSocket()

const isSeatLoading = ref(false)

async function handleZoneAction(zoneId: number) {
  if (props.readonly) return

  const zone = zonesStore.zones.find((z) => z.id === zoneId)
  if (!zone || zone.available <= 0) return

  if (zone.zone_type === 'general_admission') return

  zonesStore.setActiveZone(zoneId)
  if (!zonesStore.zoneSeats[zoneId]) {
    isSeatLoading.value = true
    await zonesStore.fetchZoneSeats(zoneId)
    isSeatLoading.value = false
  }
}

async function addGeneralTicket(zoneId: number) {
  if (props.readonly) return
  await zonesStore.lockGeneralZone(zoneId)
}

async function removeGeneralTicket(zoneId: number) {
  if (props.readonly) return
  await zonesStore.unlockGeneralZone(zoneId)
}

async function toggleZoneSeat(zoneId: number, row: number, col: number) {
  if (props.readonly) return
  await zonesStore.toggleZoneSeat(zoneId, row, col)
}

function getZoneStatus(zoneId: number): 'selected' | 'available' | 'unavailable' {
  const zone = zonesStore.zones.find(z => z.id === zoneId)
  if (!zone) return 'unavailable'

  if (zone.zone_type === 'general_admission' && zonesStore.selectedZones.some((z) => z.zoneId === zoneId)) {
    return 'selected'
  }

  if (zone.zone_type === 'seated' && zonesStore.activeZoneId === zoneId) {
    return 'selected'
  }

  return zone.available > 0 ? 'available' : 'unavailable'
}

onMounted(async () => {
  await zonesStore.fetchZones(props.sessionId)

  const config = useRuntimeConfig()
  connect(config.public.socketUrl)
  const identifier = zonesStore.getIdentifier()

  on('connect', () => {
    emit('join:session', props.sessionId)
  })

  emit('join:session', props.sessionId)
  
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

  on('zone-seat:locked', (data: any) => {
    if (data.session_id !== props.sessionId) return

    if (zonesStore.isSeatSelected(data.zone_id, data.row, data.col) && data.identifier === identifier) {
      zonesStore.updateZoneSeatStatus(data.zone_id, data.row, data.col, 'selected')
      return
    }

    zonesStore.updateZoneSeatStatus(data.zone_id, data.row, data.col, 'blocked')
  })

  on('zone-seat:released', (data: any) => {
    if (data.session_id !== props.sessionId) return
    if (zonesStore.isSeatSelected(data.zone_id, data.row, data.col)) {
      zonesStore.updateZoneSeatStatus(data.zone_id, data.row, data.col, 'selected')
      return
    }
    zonesStore.updateZoneSeatStatus(data.zone_id, data.row, data.col, 'free')
  })
})

onUnmounted(() => {
  off('zone:locked')
  off('zone:released')
  off('zone-seat:locked')
  off('zone-seat:released')
  disconnect()
})
</script>

<template>
  <div class="space-y-6 py-4">
    <div v-if="zonesStore.loading" class="py-12 text-center text-lg font-medium">
      Carregant zones...
    </div>

    <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="zone in zonesStore.zones"
        :key="zone.id"
        :class="[
          'card-brutal p-4 transition-all',
          getZoneStatus(zone.id) === 'unavailable' ? 'opacity-60' : '',
          getZoneStatus(zone.id) === 'selected' ? 'bg-primary text-white' : 'bg-white',
        ]"
      >
        <div class="mb-3 flex items-start justify-between gap-2">
          <div>
            <h3 class="text-base font-bold">{{ zone.name }}</h3>
            <p class="text-xs opacity-80">
              {{ zone.zone_type === 'seated' ? 'Zona amb seients numerats' : 'Zona general' }}
            </p>
          </div>
          <span class="rounded border-2 border-black px-2 py-1 text-xs font-bold" :style="`background:${zone.color}`">
            {{ zone.price }}€
          </span>
        </div>

        <p class="mb-4 text-sm font-semibold">
          Disponibles: {{ zone.available }} / {{ zone.capacity }}
        </p>

        <button
          v-if="zone.zone_type === 'seated'"
          class="btn-brutal-secondary w-full px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="props.readonly || zone.available <= 0"
          @click="handleZoneAction(zone.id)"
        >
          {{ zonesStore.activeZoneId === zone.id ? 'Zona activa' : 'Seleccionar asientos' }}
        </button>

        <div v-else class="flex items-center justify-between gap-3">
          <button
            class="btn-brutal-secondary h-10 w-10 px-0 py-0 text-lg leading-none disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="props.readonly || zonesStore.getGeneralZoneQuantity(zone.id) <= 0"
            @click="removeGeneralTicket(zone.id)"
          >
            -
          </button>

          <div class="flex-1 text-center">
            <p class="text-xs uppercase tracking-wide opacity-80">Seleccionades</p>
            <p class="text-xl font-extrabold">{{ zonesStore.getGeneralZoneQuantity(zone.id) }}</p>
          </div>

          <button
            class="btn-brutal h-10 w-10 px-0 py-0 text-lg leading-none text-white disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="props.readonly || zone.available <= 0"
            @click="addGeneralTicket(zone.id)"
          >
            +
          </button>
        </div>
      </div>
    </div>

    <div v-if="zonesStore.activeZoneId" class="card-brutal bg-white p-4 sm:p-6">
      <h3 class="mb-3 text-lg font-bold">
        Seients de la zona {{ zonesStore.zones.find((z) => z.id === zonesStore.activeZoneId)?.name }}
      </h3>

      <p v-if="isSeatLoading" class="py-6 text-center text-sm">Carregant seients...</p>

      <div v-else class="overflow-x-auto">
        <div class="mx-auto w-fit space-y-2">
          <div
            v-for="(row, rowIndex) in zonesStore.activeZoneSeats"
            :key="`row-${rowIndex}`"
            class="flex items-center gap-2"
          >
            <span class="w-5 text-xs font-bold text-gray-500">{{ String.fromCharCode(65 + rowIndex) }}</span>

            <template v-for="(seat, colIndex) in row" :key="seat ? `${seat.row}-${seat.col}` : `empty-${rowIndex}-${colIndex}`">
              <div v-if="seat === null" class="h-7 w-7" />
              <button
                v-else
                class="h-7 w-7 rounded border-2 text-[10px] font-bold"
                :class="[
                  seat.status === 'selected' ? 'border-black bg-primary text-white' : '',
                  seat.status === 'free' ? 'border-black bg-green-300 text-black' : '',
                  seat.status === 'blocked' ? 'border-black bg-yellow-300 text-black' : '',
                  seat.status === 'occupied' ? 'border-black bg-gray-400 text-white cursor-not-allowed' : ''
                ]"
                :disabled="props.readonly || seat.status === 'blocked' || seat.status === 'occupied'"
                @click="toggleZoneSeat(zonesStore.activeZoneId!, seat.row, seat.col)"
              >
                {{ seat.col + 1 }}
              </button>
            </template>
          </div>
        </div>
      </div>

      <div class="mt-4 flex flex-wrap gap-3 text-xs font-semibold">
        <span class="inline-flex items-center gap-1"><i class="h-3 w-3 border border-black bg-green-300"></i> Lliure</span>
        <span class="inline-flex items-center gap-1"><i class="h-3 w-3 border border-black bg-primary"></i> Teu</span>
        <span class="inline-flex items-center gap-1"><i class="h-3 w-3 border border-black bg-yellow-300"></i> Reservat</span>
        <span class="inline-flex items-center gap-1"><i class="h-3 w-3 border border-black bg-gray-400"></i> Ocupat</span>
      </div>
    </div>
  </div>
</template>
