import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export interface Seat {
  id: number
  row: string
  number: number
  status: 'available' | 'occupied' | 'selected' | 'blocked'
  price: number
}

export interface SeatSelection {
  seatId: number
  row: string
  number: number
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
      seats.value = data.data || data
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

    try {
      const res = await fetch(`${config.public.apiBase}/sessions/${sessionId.value}/seats/lock`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ row: parseInt(row), col: number, identifier })
      })
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      selectedSeats.value.push({ seatId, row, number, price })
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
        body: JSON.stringify({ row: parseInt(seat.row), col: seat.number, identifier })
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

  function clearSelection() {
    selectedSeats.value = []
  }

  return {
    seats, selectedSeats, loading, error, sessionId,
    totalPrice, fetchSeats, lockSeat, unlockSeat, updateSeatStatus, clearSelection
  }
})