<script setup lang="ts">
import { useSessionStore } from '~/stores/session'
import { useQueue } from '~/composables/useQueue'
import { useClientIdentifier } from '~/composables/useClientIdentifier'

const route = useRoute()
const router = useRouter()
const eventId = parseInt(route.params.id as string)
const sessionId = parseInt(route.params.sessionId as string)

const sessionStore = useSessionStore()
const { position, isAdmitted, isProcessing, connected, init, registerQueueSocket, cleanup } = useQueue(sessionId, eventId)
const { post } = useApi()

const isJoiningQueue = ref(false)
const isMovie = computed(() => sessionStore.event?.type === 'movie')
const canSelect = computed(() => !isMovie.value || isAdmitted.value)

onMounted(async () => {
  await sessionStore.fetchEvent(eventId)
  const currentSession = sessionStore.event?.sessions?.find((session) => session.id === sessionId)
  if (currentSession) {
    sessionStore.selectSession(currentSession)
  }

  if (sessionStore.event?.type === 'movie') {
    init()
  }
})

onUnmounted(() => {
  if (isMovie.value) {
    cleanup()
  }
})

async function joinQueue() {
  const { getIdentifier } = useClientIdentifier()
  const identifier = getIdentifier()

  isJoiningQueue.value = true
  registerQueueSocket()

  try {
      const res: any = await post(`/sessions/${sessionId}/queue/join`, { identifier })
      
      if (res && res.active) {
          isAdmitted.value = true
      } else if (res && res.position > 0) {
          position.value = res.position
      }
  } catch (e) {
      console.error(e)
  } finally {
      isJoiningQueue.value = false
  }
}

function handleBook() {
  router.push(`/events/${eventId}/checkout/${sessionId}`)
}
</script>

<template>
  <div class="min-h-screen bg-background pb-36">
    <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
      <WaitingRoom
        v-if="isMovie && position && position > 0 && !isAdmitted"
        :position="position"
        :processing="isProcessing"
        :connected="connected"
      />

      <!-- Seat Map -->
      <template v-if="sessionStore.event">
        <div class="card-brutal mb-5 bg-white p-4 sm:p-5">
          <NuxtLink :to="`/events/${eventId}`" class="mb-3 inline-flex items-center gap-2 text-sm font-medium hover:underline">
            ← Tornar
          </NuxtLink>

          <div class="mb-4">
            <h1 class="text-xl font-extrabold leading-tight sm:text-2xl">{{ sessionStore.event.title }}</h1>
            <p class="mt-1 text-sm text-gray-700 sm:text-base">{{ sessionStore.event.venue }} · {{ sessionStore.selectedSession?.date }} {{ sessionStore.selectedSession?.time }}</p>
          </div>

          <div class="flex flex-wrap gap-2">
            <span class="inline-flex items-center border-2 border-black bg-secondary px-2.5 py-1 text-xs font-bold uppercase">{{ sessionStore.event.type === 'movie' ? '🎬 Cine' : '🎤 Concert' }}</span>
            <span class="inline-flex items-center border-2 border-black bg-white px-2.5 py-1 text-xs font-bold">Tria els teus llocs</span>
            <span class="inline-flex items-center border-2 border-black bg-white px-2.5 py-1 text-xs font-bold">Reserva durant la compra</span>
          </div>
        </div>

        <SeatMap
          v-if="sessionStore.event?.type === 'movie'"
          :session-id="sessionId"
          :layout="{ rows: ['A', 'B', 'C', 'D', 'E'], seatsPerRow: 10 }"
          :readonly="!canSelect"
        />
        <ZoneMap
          v-else
          :session-id="sessionId"
          :readonly="!canSelect"
        />

        <div class="mb-4" />
      </template>
    </main>

    <div
      v-if="sessionStore.event && isMovie && !isAdmitted"
      class="fixed bottom-0 left-0 right-0 border-t-2 border-black bg-white/95 p-3 shadow-[0_-4px_0_0_#000000] backdrop-blur sm:p-4"
    >
      <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-3">
        <p class="hidden text-sm font-medium text-gray-700 sm:block">Quan estigui preparat, podràs triar els teus llocs.</p>
        <button
          @click="joinQueue"
          :disabled="isJoiningQueue || (position !== null && position > 0)"
          class="btn-brutal w-full bg-primary px-5 py-2 text-sm text-white disabled:opacity-50 sm:w-auto"
        >
          {{ position && position > 0 ? 'Preparando acceso...' : (isJoiningQueue ? 'Preparando acceso...' : 'Empezar selección') }}
        </button>
      </div>
    </div>

    <BookingFooter
      v-if="sessionStore.event && canSelect"
      :mode="sessionStore.event?.type === 'movie' ? 'seats' : 'zones'"
      :event-id="eventId"
      :session-id="sessionId"
      @book="handleBook"
    />
  </div>
</template>
