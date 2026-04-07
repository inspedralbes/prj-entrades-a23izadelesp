export function useApi() {
  const config = useRuntimeConfig()
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function get<T>(path: string): Promise<T | null> {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${config.public.apiBase}${path}`)
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      return await res.json()
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Error'
      return null
    } finally {
      loading.value = false
    }
  }

  async function post<T>(path: string, body: unknown): Promise<T | null> {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${config.public.apiBase}${path}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      })
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      return await res.json()
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Error'
      return null
    } finally {
      loading.value = false
    }
  }

  return { get, post, loading, error }
}