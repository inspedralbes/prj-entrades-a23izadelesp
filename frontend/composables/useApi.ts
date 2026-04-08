export function useApi() {
  const config = useRuntimeConfig()
  const loading = ref(false)
  const error = ref<string | null>(null)

  function getHeaders(): Record<string, string> {
    const headers: Record<string, string> = { 'Content-Type': 'application/json' }
    const token = localStorage.getItem('auth-token')
    if (token) {
      headers['Authorization'] = `Bearer ${token}`
    }
    return headers
  }

  async function get<T>(path: string): Promise<T | null> {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${config.public.apiBase}${path}`, {
        headers: getHeaders()
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

  async function post<T>(path: string, body: unknown): Promise<T | null> {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${config.public.apiBase}${path}`, {
        method: 'POST',
        headers: getHeaders(),
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

  async function put<T>(path: string, body: unknown): Promise<T | null> {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${config.public.apiBase}${path}`, {
        method: 'PUT',
        headers: getHeaders(),
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

  async function del<T>(path: string): Promise<T | null> {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${config.public.apiBase}${path}`, {
        method: 'DELETE',
        headers: getHeaders()
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

  return { get, post, put, del, loading, error }
}