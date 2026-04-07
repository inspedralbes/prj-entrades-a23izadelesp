import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

interface Event {
  id: number
  title: string
  image?: string
  description?: string
  type: string
  date: string
  venue: string
  price?: number
}

export const useEventsStore = defineStore('events', () => {
  const events = ref<Event[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const filter = ref<'all' | 'movie' | 'concert'>('all')

  const filteredEvents = computed(() => {
    if (filter.value === 'all') return events.value
    return events.value.filter(e => {
      if (filter.value === 'movie') return e.type === 'movie'
      if (filter.value === 'concert') return e.type === 'concert'
      return true
    })
  })

  async function fetchEvents() {
    loading.value = true
    error.value = null
    try {
      const { data } = await useFetch('/api/events', {
        baseURL: 'http://localhost:8080'
      })
      if (data.value) {
        events.value = (data.value as any).data || (data.value as any) || []
      }
    } catch (e) {
      error.value = 'Error carregar esdeveniments'
      console.error('Fetch events error:', e)
    } finally {
      loading.value = false
    }
  }

  function setFilter(newFilter: 'all' | 'movie' | 'concert') {
    filter.value = newFilter
  }

  return { events, loading, error, filter, filteredEvents, fetchEvents, setFilter }
})