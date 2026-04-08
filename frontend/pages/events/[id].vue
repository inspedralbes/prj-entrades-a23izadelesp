<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
import { useApi } from '~/composables/useApi'
import { useSocket } from '~/composables/useSocket'

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()
const sessionStore = useSessionStore()
const { post } = useApi()
const { connect, on, emit, disconnect } = useSocket()

const eventId = parseInt(route.params.id as string)

onMounted(async () => {
  await sessionStore.fetchEvent(eventId)
})

const isJoiningQueue = ref(false)

async function joinQueue() {
  const session = sessionStore.selectedSession
  if (!session) return

  router.push(`/events/${eventId}/seats/${session.id}`)
}

onUnmounted(() => {
  disconnect()
})

function formatDuration(minutes: number) {
  const h = Math.floor(minutes / 60)
  const m = minutes % 60
  return h > 0 ? `${h}h ${m}min` : `${m}min`
}
</script>

<template>
  <div>
    <div v-if="sessionStore.loading" class="py-12 text-center text-lg font-medium">
      Carregant...
    </div>
    <div v-else-if="sessionStore.error" class="py-12 text-center text-lg text-accent">
      {{ sessionStore.error }}
    </div>
    <div v-else-if="sessionStore.event" class="min-h-screen bg-background">
      <div class="relative aspect-[16/9] w-full overflow-hidden border-b-2 border-black">
        <img
          :src="sessionStore.event.image"
          :alt="sessionStore.event.title"
          class="h-full w-full object-cover"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent" />
        <div class="absolute bottom-4 left-4 right-4">
          <span
            class="mb-2 inline-block border-2 border-black px-2 py-0.5 text-xs font-semibold"
            :class="sessionStore.event.type === 'movie' ? 'bg-secondary' : 'bg-primary'"
          >
            {{ sessionStore.event.type === 'movie' ? '🎬 Cine' : '🎤 Concierto' }}
          </span>
          <h1 class="text-3xl font-bold text-white">{{ sessionStore.event.title }}</h1>
        </div>
      </div>

      <div class="mx-auto max-w-7xl px-4 py-6">
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
          <div class="card-brutal p-4">
            <p class="text-sm text-gray-500">Durada</p>
            <p class="text-lg font-bold">{{ formatDuration(sessionStore.event.duration) }}</p>
          </div>
          <div class="card-brutal p-4">
            <p class="text-sm text-gray-500">Gènere</p>
            <p class="text-lg font-bold">{{ sessionStore.event.genre }}</p>
          </div>
          <div class="card-brutal p-4">
            <p class="text-sm text-gray-500">Calificació</p>
            <p class="text-lg font-bold">{{ sessionStore.event.rating }}</p>
          </div>
        </div>

        <div class="mb-6">
          <h2 class="mb-3 text-lg font-bold">Sessions</h2>
          <SessionSelector :sessions="sessionStore.event.sessions" />
        </div>

        <div class="mb-6">
          <h2 class="mb-3 text-lg font-bold">Descripció</h2>
          <div class="card-brutal p-4">
            <p class="text-gray-700">{{ sessionStore.event.description }}</p>
          </div>
        </div>

        <div v-if="sessionStore.selectedSession" class="mt-6">
          <button
            class="btn-brutal w-full bg-primary text-white"
            @click="joinQueue"
          >
            Veure seients
          </button>
        </div>
      </div>
    </div>
  </div>
</template>