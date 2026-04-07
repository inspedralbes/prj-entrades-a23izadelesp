import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export interface Zone {
  id: number
  name: string
  price: number
  capacity: number
  available: number
  status: 'available' | 'limited' | 'sold_out'
  color: string
}

export interface ZoneSelection {
  zoneId: number
  name: string
  quantity: number
  price: number
}

export const useZonesStore = defineStore('zones', () => {
  const zones = ref<Zone[]>([])
  const selectedZones = ref<ZoneSelection[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const sessionId = ref<number | null>(null)

  const totalPrice = computed(() => {
    return selectedZones.value.reduce((sum, z) => sum + (z.price * z.quantity), 0)
  })

  const totalQuantity = computed(() => {
    return selectedZones.value.reduce((sum, z) => sum + z.quantity, 0)
  })

  async function fetchZones(sessionIdNum: number) {
    const config = useRuntimeConfig()
    sessionId.value = sessionIdNum
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${config.public.apiBase}/sessions/${sessionIdNum}/zones`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      zones.value = data.data || data
    } catch (e) {
      error.value = 'Error carregar zones'
    } finally {
      loading.value = false
    }
  }

  async function lockZone(zoneId: number, name: string, quantity: number, price: number) {
    if (!sessionId.value) return
    const config = useRuntimeConfig()
    try {
      const res = await fetch(`${config.public.apiBase}/sessions/${sessionId.value}/zones/lock`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ zone_id: zoneId, quantity })
      })
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const existing = selectedZones.value.find(z => z.zoneId === zoneId)
      if (existing) {
        existing.quantity = quantity
      } else {
        selectedZones.value.push({ zoneId, name, quantity, price })
      }
    } catch (e) {
      error.value = 'Error bloquejar zona'
    }
  }

  async function unlockZone(zoneId: number) {
    if (!sessionId.value) return
    const config = useRuntimeConfig()
    try {
      await fetch(`${config.public.apiBase}/sessions/${sessionId.value}/zones/unlock`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ zone_id: zoneId })
      })
      selectedZones.value = selectedZones.value.filter(z => z.zoneId !== zoneId)
    } catch (e) {
      error.value = 'Error desbloquejar zona'
    }
  }

  function updateZoneAvailability(zoneId: number, available: number) {
    const zone = zones.value.find(z => z.id === zoneId)
    if (zone) {
      zone.available = available
      zone.status = available === 0 ? 'sold_out' : available < 10 ? 'limited' : 'available'
    }
  }

  function clearSelection() {
    selectedZones.value = []
  }

  return {
    zones, selectedZones, loading, error, sessionId,
    totalPrice, totalQuantity,
    fetchZones, lockZone, unlockZone, updateZoneAvailability, clearSelection
  }
})