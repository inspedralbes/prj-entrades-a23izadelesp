<script setup lang="ts">
import { useSocket } from '~/composables/useSocket'
import { useSessionStore } from '~/stores/session'

const props = defineProps<{
  sessionId: number
  eventId: number
}>()

const router = useRouter()
const sessionStore = useSessionStore()
const { connected, on, off, disconnect } = useSocket()

const emit = defineEmits(['admitted'])

const processing = ref(true)

onMounted(() => {
  const config = useRuntimeConfig()
  useSocket().connect(config.public.socketUrl)

  const identifier = localStorage.getItem('auth-token') 
    ? `user_${localStorage.getItem('auth-token')?.split('|')[0]}` 
    : localStorage.getItem('guest-identifier') || ''

  on('queue:position', (data: any) => {
    if (data.identifier === identifier) {
      sessionStore.setPosition(data.position)
      processing.value = true
    }
  })

  on('queue:admitted', (data: any) => {
    if (data.identifier === identifier) {
      sessionStore.setWaitingRoom(false)
      emit('admitted')
    }
  })

  on('queue:remaining', () => {
    processing.value = true
  })
})

onUnmounted(() => {
  disconnect()
})

function getTimeEstimate(position: number) {
  const minutes = position * 2
  if (minutes < 1) return 'Menys d\'1 min'
  if (minutes === 1) return '1 min'
  if (minutes < 60) return `${minutes} min`
  const h = Math.floor(minutes / 60)
  const m = minutes % 60
  return m > 0 ? `${h}h ${m}min` : `${h}h`
}
</script>

<template>
  <div class="fixed bottom-6 left-1/2 z-50 flex w-full max-w-sm -translate-x-1/2 flex-col items-center gap-4 rounded-xl border-4 border-black bg-white p-4 shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
    <div class="flex w-full items-center justify-between">
      <div>
        <h2 class="text-lg font-bold">Sala d'Espera</h2>
        <p class="text-sm text-gray-600">Posició #{{ sessionStore.position || 0 }}</p>
      </div>
      <div class="rounded-full border-2 border-black bg-yellow-400 px-3 py-1 font-bold">
        {{ getTimeEstimate(sessionStore.position || 0) }}
      </div>
    </div>
    
    <div v-if="processing" class="w-full text-center">
      <p class="animate-pulse text-xs font-medium text-gray-500">Processant posició...</p>
    </div>

    <div class="w-full text-center text-xs">
      <span v-if="connected" class="inline-flex items-center gap-1 text-green-600">
        <span class="h-2 w-2 animate-pulse rounded-full bg-green-600" />
        Connectat per socket
      </span>
      <span v-else class="text-red-600">Desconnectat...</span>
    </div>
  </div>
</template>