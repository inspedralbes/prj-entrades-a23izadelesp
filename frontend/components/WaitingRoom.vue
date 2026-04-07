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

const processing = ref(true)

onMounted(() => {
  const config = useRuntimeConfig()
  useSocket().connect(config.public.socketUrl)

  on('queue:position', (data: any) => {
    sessionStore.setPosition(data.position)
    processing.value = true
  })

  on('queue:admitted', () => {
    sessionStore.setWaitingRoom(false)
    router.push(`/events/${props.eventId}/seats/${props.sessionId}`)
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
  <div class="flex min-h-[60vh] flex-col items-center justify-center px-4 py-12">
    <div class="w-full max-w-md text-center">
      <div class="mb-8 flex justify-center">
        <div class="relative h-24 w-24">
          <div class="absolute inset-0 animate-ping rounded-full border-4 border-black bg-secondary/30" />
          <div class="absolute inset-0 animate-pulse rounded-full border-4 border-black bg-secondary" />
        </div>
      </div>

      <h2 class="mb-2 text-2xl font-bold">Sala d\'Espera</h2>
      <p class="mb-6 text-gray-600">El teu torn arriba...</p>

      <div class="card-brutal mb-6 p-6">
        <p class="mb-2 text-sm text-gray-500">La teva posició</p>
        <p class="text-6xl font-bold text-primary">#{{ sessionStore.position }}</p>
      </div>

      <div class="card-brutal mb-6 p-4">
        <p class="mb-1 text-sm text-gray-500">Temps estimat</p>
        <p class="text-xl font-bold">{{ getTimeEstimate(sessionStore.position || 0) }}</p>
      </div>

      <div v-if="processing" class="mb-6">
        <p class="animate-pulse text-lg font-medium">Gestionando la teva solicitud...</p>
      </div>

      <div class="mt-8 border-2 border-black bg-gray-100 p-4">
        <p class="text-sm">
          <span v-if="connected" class="flex items-center justify-center gap-2 text-green-600">
            <span class="h-2 w-2 animate-pulse rounded-full bg-green-600" />
            Connexió activa
          </span>
          <span v-else class="text-red-600">Esperant connexió...</span>
        </p>
      </div>
    </div>
  </div>
</template>