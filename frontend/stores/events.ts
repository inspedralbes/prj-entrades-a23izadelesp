import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

interface Event {
  id: number
  title: string
  image: string
  type: 'cine' | 'concierto'
  date: string
  venue: string
}

export const useEventsStore = defineStore('events', () => {
  const events = ref<Event[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const filter = ref<'all' | 'cine' | 'concierto'>('all')

  const filteredEvents = computed(() => {
    if (filter.value === 'all') return events.value
    return events.value.filter(e => e.type === filter.value)
  })

  async function fetchEvents() {
    const config = useRuntimeConfig()
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${config.public.apiBase}/events`)
      const data = await res.json()
      events.value = data.data || data
    } catch (e) {
      error.value = 'Error carregar esdeveniments'
    } finally {
      loading.value = false
    }
  }

  function setFilter(newFilter: 'all' | 'cine' | 'concierto') {
    filter.value = newFilter
  }

  return { events, loading, error, filter, filteredEvents, fetchEvents, setFilter }
})