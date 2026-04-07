import { ref } from 'vue'
import { useSocket } from '~/composables/useSocket'
import { useSessionStore } from '~/stores/session'

export function useQueue(sessionId: number, eventId: number) {
  const position = ref<number | null>(null)
  const isAdmitted = ref(false)
  const isProcessing = ref(true)
  const queueLength = ref(0)
  
  const { connected, on, off, connect, disconnect } = useSocket()
  const sessionStore = useSessionStore()

  function init() {
    const config = useRuntimeConfig()
    connect(config.public.socketUrl)

    on('queue:position', (data: any) => {
      if (data.session_id === sessionId) {
        position.value = data.position
        isProcessing.value = true
        sessionStore.setPosition(data.position)
      }
    })

    on('queue:admitted', (data: any) => {
      if (data.session_id === sessionId) {
        isAdmitted.value = true
        isProcessing.value = false
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