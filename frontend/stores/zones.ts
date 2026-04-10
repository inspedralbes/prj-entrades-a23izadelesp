import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useApi } from '../composables/useApi'

export interface Zone {
  id: number
  key?: string
  name: string
  zone_type: 'general_admission' | 'seated'
  price: number
  capacity: number
  available: number
  status: 'available' | 'limited' | 'sold_out'
  color: string
}

export interface ZoneSelection {
  zoneId: number
  name: string
  price: number
  lockIds: string[]
}

export interface ZoneSeatSelection {
  zoneId: number
  zoneName: string
  row: number
  col: number
  label: string
  price: number
}

export interface ZoneSeatCell {
  row: number
  col: number
  label: string
  price: number
  status: 'free' | 'blocked' | 'occupied' | 'selected'
}

export const useZonesStore = defineStore('zones', () => {
  const zones = ref<Zone[]>([])
  const selectedZones = ref<ZoneSelection[]>([])
  const selectedZoneSeats = ref<ZoneSeatSelection[]>([])
  const zoneSeats = ref<Record<number, (ZoneSeatCell | null)[][]>>({})
  const activeZoneId = ref<number | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const sessionId = ref<number | null>(null)

  const totalPrice = computed(() => {
    const generalTotal = selectedZones.value.reduce((sum, z) => sum + (z.price * z.lockIds.length), 0)
    const seatedTotal = selectedZoneSeats.value.reduce((sum, seat) => sum + seat.price, 0)
    return generalTotal + seatedTotal
  })

  const totalQuantity = computed(() => {
    return selectedZones.value.reduce((sum, z) => sum + z.lockIds.length, 0) + selectedZoneSeats.value.length
  })

  const activeZoneSeats = computed(() => {
    if (!activeZoneId.value) return []
    return zoneSeats.value[activeZoneId.value] || []
  })

  async function fetchZones(sessionIdNum: number) {
    const { get } = useApi()
    sessionId.value = sessionIdNum
    loading.value = true
    error.value = null
    try {
      const res: any = await get(`/sessions/${sessionIdNum}/seats`)
      const list = res?.data?.zones || []
      zones.value = list.map((zone: any) => ({
        ...zone,
        status: zone.available <= 0 ? 'sold_out' : zone.available < 10 ? 'limited' : 'available'
      }))
    } catch (e) {
      error.value = 'Error carregar zones'
    } finally {
      loading.value = false
    }
  }

  async function fetchZoneSeats(zoneId: number) {
    if (!sessionId.value) return
    const { get } = useApi()
    loading.value = true
    error.value = null
    try {
      const res: any = await get(`/sessions/${sessionId.value}/zones/${zoneId}/seats`)
      const grid = res?.data?.grid || []
      zoneSeats.value = { ...zoneSeats.value, [zoneId]: grid }
    } catch {
      error.value = 'Error carregar seients de la zona'
    } finally {
      loading.value = false
    }
  }

  function setActiveZone(zoneId: number | null) {
    activeZoneId.value = zoneId
  }

  async function lockGeneralZone(zoneId: number) {
    if (!sessionId.value) return
    const zone = zones.value.find(z => z.id === zoneId)
    if (!zone) return

    const config = useRuntimeConfig()
    const identifier = getIdentifier()

    try {
      const res = await fetch(`${config.public.apiBase}/sessions/${sessionId.value}/zones/lock`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ zone_id: zoneId, quantity: 1, identifier })
      })
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()

      const existing = selectedZones.value.find(z => z.zoneId === zoneId)
      if (existing) {
        existing.lockIds.push(data.lock_id)
      } else {
        selectedZones.value.push({
          zoneId,
          name: zone.name,
          price: zone.price,
          lockIds: [data.lock_id],
        })
      }

      if (typeof data.available === 'number') {
        updateZoneAvailability(zoneId, data.available)
      }
    } catch (e) {
      error.value = 'Error bloquejar zona'
    }
  }

  async function unlockGeneralZone(zoneId: number) {
    if (!sessionId.value) return
    const selection = selectedZones.value.find(z => z.zoneId === zoneId)
    if (!selection || selection.lockIds.length === 0) return

    const config = useRuntimeConfig()
    const identifier = getIdentifier()
    const lockId = selection.lockIds[selection.lockIds.length - 1]

    try {
      const res = await fetch(`${config.public.apiBase}/sessions/${sessionId.value}/zones/unlock`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ zone_id: zoneId, lock_id: lockId, identifier })
      })

      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()

      selection.lockIds.pop()
      if (selection.lockIds.length === 0) {
        selectedZones.value = selectedZones.value.filter(z => z.zoneId !== zoneId)
      }

      if (typeof data.available === 'number') {
        updateZoneAvailability(zoneId, data.available)
      }
    } catch (e) {
      error.value = 'Error desbloquejar zona'
    }
  }

  async function lockZoneSeat(zoneId: number, row: number, col: number) {
    if (!sessionId.value) return false
    const zone = zones.value.find(z => z.id === zoneId)
    if (!zone) return false

    const config = useRuntimeConfig()
    const identifier = getIdentifier()

    try {
      const res = await fetch(`${config.public.apiBase}/sessions/${sessionId.value}/zones/${zoneId}/seats/lock`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ row, col, identifier })
      })

      if (!res.ok) return false

      const target = zoneSeats.value[zoneId]?.[row]?.[col]
      if (!target) return false

      selectedZoneSeats.value.push({
        zoneId,
        zoneName: zone.name,
        row,
        col,
        label: target.label,
        price: target.price,
      })

      updateZoneSeatStatus(zoneId, row, col, 'selected')
      return true
    } catch {
      return false
    }
  }

  async function unlockZoneSeat(zoneId: number, row: number, col: number) {
    if (!sessionId.value) return false

    const config = useRuntimeConfig()
    const identifier = getIdentifier()

    try {
      const res = await fetch(`${config.public.apiBase}/sessions/${sessionId.value}/zones/${zoneId}/seats/unlock`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ row, col, identifier })
      })

      if (!res.ok) return false

      selectedZoneSeats.value = selectedZoneSeats.value.filter(s => !(s.zoneId === zoneId && s.row === row && s.col === col))
      updateZoneSeatStatus(zoneId, row, col, 'free')
      return true
    } catch {
      return false
    }
  }

  async function toggleZoneSeat(zoneId: number, row: number, col: number) {
    const selected = selectedZoneSeats.value.some(s => s.zoneId === zoneId && s.row === row && s.col === col)
    if (selected) {
      return unlockZoneSeat(zoneId, row, col)
    }
    return lockZoneSeat(zoneId, row, col)
  }

  function updateZoneAvailability(zoneId: number, available: number) {
    const zone = zones.value.find(z => z.id === zoneId)
    if (zone) {
      zone.available = available
      zone.status = available === 0 ? 'sold_out' : available < 10 ? 'limited' : 'available'
    }
  }

  function updateZoneSeatStatus(zoneId: number, row: number, col: number, status: ZoneSeatCell['status']) {
    const cell = zoneSeats.value[zoneId]?.[row]?.[col]
    if (!cell) return
    cell.status = status
  }

  function isSeatSelected(zoneId: number, row: number, col: number) {
    return selectedZoneSeats.value.some(s => s.zoneId === zoneId && s.row === row && s.col === col)
  }

  function getGeneralZoneQuantity(zoneId: number) {
    const zone = selectedZones.value.find((z) => z.zoneId === zoneId)
    return zone ? zone.lockIds.length : 0
  }

  function getIdentifier() {
    const token = localStorage.getItem('auth-token')
    if (token) {
      return `user_${token.split('|')[0]}`
    }

    let guestIdentifier = localStorage.getItem('guest-identifier')
    if (!guestIdentifier) {
      guestIdentifier = `guest_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`
      localStorage.setItem('guest-identifier', guestIdentifier)
    }
    return guestIdentifier
  }

  function clearSelection() {
    selectedZones.value = []
    selectedZoneSeats.value = []
    activeZoneId.value = null
  }

  return {
    zones,
    selectedZones,
    selectedZoneSeats,
    zoneSeats,
    activeZoneId,
    activeZoneSeats,
    loading,
    error,
    sessionId,
    totalPrice, totalQuantity,
    fetchZones,
    fetchZoneSeats,
    setActiveZone,
    lockGeneralZone,
    unlockGeneralZone,
    lockZoneSeat,
    unlockZoneSeat,
    toggleZoneSeat,
    updateZoneAvailability,
    updateZoneSeatStatus,
    isSeatSelected,
    getGeneralZoneQuantity,
    getIdentifier,
    clearSelection
  }
})