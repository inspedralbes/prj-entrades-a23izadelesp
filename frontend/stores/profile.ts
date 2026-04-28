import { defineStore } from 'pinia'
import { ref } from 'vue'

interface Event {
  id: number
  title: string
  image: string
  venue: string
}

interface Session {
  id: number
  date: string
  time: string
}

interface TicketListItem {
  id: number
  event: Event
  session: Session
  status: string
  total: number
  ticket_count: number
  created_at: string
}

interface TicketItem {
  id: number
  seat: { row: string; number: number } | null
  zone: { name: string } | null
  qr_code: string
}

interface BookingDetail {
  id: number
  status: string
  total: number
  event: Event
  session: Session
  tickets: TicketItem[]
  created_at: string
}

export const useProfileStore = defineStore('profile', () => {
  const tickets = ref<TicketListItem[]>([])
  const currentBooking = ref<BookingDetail | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchTickets() {
    const config = useRuntimeConfig()
    loading.value = true
    error.value = null
    try {
      const token = localStorage.getItem('auth-token')
      const res = await fetch(`${config.public.apiBase}/profile/tickets`, {
        headers: { Authorization: `Bearer ${token}` }
      })
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      tickets.value = data.data || data
    } catch (e) {
      error.value = 'Error carregar entrades'
    } finally {
      loading.value = false
    }
  }

  async function fetchBooking(id: number) {
    const config = useRuntimeConfig()
    loading.value = true
    error.value = null
    try {
      const token = localStorage.getItem('auth-token')
      const res = await fetch(`${config.public.apiBase}/profile/tickets/${id}`, {
        headers: { Authorization: `Bearer ${token}` }
      })
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      currentBooking.value = data.data || data
    } catch (e) {
      error.value = 'Error carregar reserva'
    } finally {
      loading.value = false
    }
  }

  return { tickets, currentBooking, loading, error, fetchTickets, fetchBooking }
})