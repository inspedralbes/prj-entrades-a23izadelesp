import { ref } from 'vue'
import { useSocket } from '~/composables/useSocket'
import { useSessionStore } from '~/stores/session'
import { useApi } from '~/composables/useApi'
import { useClientIdentifier } from '~/composables/useClientIdentifier'

export function useQueue(sessionId: number, eventId: number) {
  const position = ref<number | null>(null)
  const isAdmitted = ref(false)
  const isProcessing = ref(true)
  const queueLength = ref(0)
  const pollingHandle = ref<ReturnType<typeof setInterval> | null>(null)
  
  const { connected, emit, on, off, connect, disconnect } = useSocket()
  const sessionStore = useSessionStore()

  function init() {
    const config = useRuntimeConfig()
    connect(config.public.socketUrl)
    const { getIdentifier } = useClientIdentifier()
    const identifier = getIdentifier()

    emit('join:session', sessionId)
    emit('register:queue', { session_id: sessionId, identifier })

    const refreshPosition = async () => {
      const res: any = await useApi().get(`/sessions/${sessionId}/queue/position?identifier=${encodeURIComponent(identifier)}`)

      if (res && res.active) {
        isAdmitted.value = true
        isProcessing.value = false
        position.value = 0
        sessionStore.setPosition(0)
        return
      } else if (res && res.position > 0) {
        position.value = res.position
        isProcessing.value = true
        sessionStore.setPosition(res.position)
      } else {
        position.value = null
        isProcessing.value = false
      }
    }

    refreshPosition().catch(console.error)

    if (!pollingHandle.value) {
      pollingHandle.value = setInterval(() => {
        if (!isAdmitted.value) {
          refreshPosition().catch(console.error)
        }
      }, 3000)
    }

    on('queue:position', (data: any) => {
      if (data.session_id === sessionId && data.identifier === identifier) {
        if (data.admitted || data.status === 'admitted' || data.position === 0) {
          isAdmitted.value = true
          isProcessing.value = false
          position.value = 0
          sessionStore.setPosition(0)
          return
        }

        position.value = data.position
        isProcessing.value = true
        sessionStore.setPosition(data.position)
      }
    })

    on('queue:admitted', (data: any) => {
      if (data.session_id === sessionId && data.identifier === identifier) {
        isAdmitted.value = true
        isProcessing.value = false
        position.value = 0
        sessionStore.setPosition(0)
      }
    })

    on('queue:remaining', (data: any) => {
      if (data.session_id === sessionId) {
        queueLength.value = data.count
      }
    })
  }

  function cleanup() {
    off('queue:position')
    off('queue:admitted')
    off('queue:remaining')

    if (pollingHandle.value) {
      clearInterval(pollingHandle.value)
      pollingHandle.value = null
    }

    disconnect()
  }

  function getTimeEstimate(pos: number) {
    const minutes = pos * 2
    if (minutes < 1) return 'Menys d\'1 min'
    if (minutes === 1) return '1 min'
    if (minutes < 60) return `${minutes} min`
    const h = Math.floor(minutes / 60)
    const m = minutes % 60
    return m > 0 ? `${h}h ${m}min` : `${h}h`
  }

  return {
    position,
    isAdmitted,
    isProcessing,
    queueLength,
    connected,
    init,
    cleanup,
    getTimeEstimate
  }
}