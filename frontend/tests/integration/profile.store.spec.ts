import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useProfileStore } from '~/stores/profile'

describe('profile store integration', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.restoreAllMocks()

    ;(globalThis as any).useRuntimeConfig = () => ({
      public: {
        apiBase: 'http://api.test'
      }
    })
  })

  it('fetchTickets loads data into pinia store', async () => {
    localStorage.setItem('auth-token', '1|token')

    const mockedPayload = {
      data: [
        {
          id: 7,
          event: { id: 1, title: 'Concert', image: 'img.jpg', venue: 'Hall' },
          session: { id: 2, date: '2026-04-14', time: '20:00' },
          status: 'confirmed',
          total: 20,
          ticket_count: 2,
          created_at: '2026-04-14T12:00:00Z'
        }
      ]
    }

    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({
        ok: true,
        json: async () => mockedPayload
      })
    )

    const store = useProfileStore()
    await store.fetchTickets()

    expect(store.error).toBeNull()
    expect(store.loading).toBe(false)
    expect(store.tickets).toHaveLength(1)
    expect(store.tickets[0]?.status).toBe('confirmed')
  })
})
