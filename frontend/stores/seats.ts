import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export interface Seat {
  id: number
  row: string
  number: number
  apiRow: number
  apiCol: number
  status: 'available' | 'occupied' | 'selected' | 'blocked'
  price: number
}

export interface SeatSelection {
  seatId: number
  row: string
  number: number
  apiRow: number
  apiCol: number
  price: number
}

export const useSeatsStore = defineStore('seats', () => {
  const seats = ref<Seat[]>([])
  const selectedSeats = ref<SeatSelection[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const sessionId = ref<number | null>(null)

  const totalPrice = computed(() => {
    return selectedSeats.value.reduce((sum, s) => sum + s.price, 0)
  })

  async function fetchSeats(sessionIdNum: number) {
    const config = useRuntimeConfig()
    sessionId.value = sessionIdNum
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${config.public.apiBase}/sessions/${sessionIdNum}/seats`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      const payload = data.data || data

      if (payload?.type === 'grid' && Array.isArray(payload.grid)) {
        const parsedSeats: Seat[] = []

        payload.grid.forEach((rowData: any[], rowIndex: number) => {
          let rowSeatNumber = 0

          rowData.forEach((cell: any, colIndex: number) => {
            if (!cell) return

            rowSeatNumber += 1

            parsedSeats.push({
              id: rowIndex * 1000 + colIndex + 1,
              row: String.fromCharCode(65 + rowIndex),
              number: rowSeatNumber,
              apiRow: rowIndex,
              apiCol: colIndex,
              status: cell.status === 'occupied' ? 'occupied' : 'available',
              price: 10
            })
          })
        })

        seats.value = parsedSeats
      } else {
        seats.value = payload
      }
    } catch (e) {
      error.value = 'Error carregar seats'
    } finally {
      loading.value = false
    }
  }

  async function lockSeat(seatId: number, row: string, number: number, price: number) {
    if (!sessionId.value) return
    const config = useRuntimeConfig()
    const identifier = localStorage.getItem('auth-token') 
        ? `user_${localStorage.getItem('auth-token')?.split('|')[0]}` 
        : localStorage.getItem('guest-identifier') || `guest_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`
    
    // Guardar el identifier si somos guest
    if (!localStorage.getItem('auth-token')) localStorage.setItem('guest-identifier', identifier)

    const seat = seats.value.find(s => s.id === seatId)
    if (!seat) return

    if (seat.status === 'occupied' || seat.status === 'blocked') {
      error.value = 'Seat no disponible'
      return
    }

    if (selectedSeats.value.some((selectedSeat) => selectedSeat.seatId === seatId)) {
      return
    }

    try {
      const res = await fetch(`${config.public.apiBase}/sessions/${sessionId.value}/seats/lock`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ row: seat.apiRow, col: seat.apiCol, identifier })
      })
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      selectedSeats.value.push({ seatId, row, number, apiRow: seat.apiRow, apiCol: seat.apiCol, price })
    } catch (e) {
      error.value = 'Error bloquejar seat'
    }
  }

  async function unlockSeat(seatId: number) {
    if (!sessionId.value) return
    const config = useRuntimeConfig()
    
    const identifier = localStorage.getItem('auth-token') 
        ? `user_${localStorage.getItem('auth-token')?.split('|')[0]}` 
        : localStorage.getItem('guest-identifier')

    try {
      const seat = selectedSeats.value.find(s => s.seatId === seatId)
      if (!seat) return

      await fetch(`${config.public.apiBase}/sessions/${sessionId.value}/seats/unlock`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ row: seat.apiRow, col: seat.apiCol, identifier })
      })
      selectedSeats.value = selectedSeats.value.filter(s => s.seatId !== seatId)
    } catch (e) {
      error.value = 'Error desbloquejar seat'
    }
  }

  function updateSeatStatus(seatId: number, status: Seat['status']) {
    const seat = seats.value.find(s => s.id === seatId)
    if (seat) seat.status = status
  }

  function updateSeatStatusByGrid(apiRow: number, apiCol: number, status: Seat['status']) {
    const seat = seats.value.find(s => s.apiRow === apiRow && s.apiCol === apiCol)
    if (seat) seat.status = status
  }

  function clearSelection() {
    selectedSeats.value = []
  }

  return {
    seats, selectedSeats, loading, error, sessionId,
    totalPrice, fetchSeats, lockSeat, unlockSeat, updateSeatStatus, updateSeatStatusByGrid, clearSelection
  }
})