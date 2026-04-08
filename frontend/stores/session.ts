import { defineStore } from 'pinia'
import { ref } from 'vue'

interface Session {
  id: number
  event_id: number
  date: string
  time: string
  total_seats: number
  available_seats: number
}

interface Event {
  id: number
  title: string
  description: string
  image: string
  type: 'movie' | 'concert'
  duration: number
  genre: string
  rating: string
  venue: string
  sessions: Session[]
}

export const useSessionStore = defineStore('session', () => {
  const event = ref<Event | null>(null)
  const selectedSession = ref<Session | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const inWaitingRoom = ref(false)
  const position = ref<number | null>(null)

  async function fetchEvent(id: number) {
    const config = useRuntimeConfig()
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${config.public.apiBase}/events/${id}`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      event.value = data.data || data
    } catch (e) {
      error.value = 'Error carregar esdeveniment'
    } finally {
      loading.value = false
    }
  }

  function selectSession(session: Session) {
    selectedSession.value = session
  }

  function setWaitingRoom(value: boolean) {
    inWaitingRoom.value = value
  }

  function setPosition(pos: number) {
    position.value = pos
  }

  return {
    event, selectedSession, loading, error, inWaitingRoom, position,
    fetchEvent, selectSession, setWaitingRoom, setPosition
  }
})