<script setup lang="ts">
import { useSeatsStore } from '~/stores/seats'
import { useZonesStore } from '~/stores/zones'
import { useSessionStore } from '~/stores/session'
import { useQueue } from '~/composables/useQueue'

const route = useRoute()
const router = useRouter()
const eventId = parseInt(route.params.id as string)
const sessionId = parseInt(route.params.sessionId as string)

const sessionStore = useSessionStore()
const seatsStore = useSeatsStore()
const zonesStore = useZonesStore()
const { position, isAdmitted, isProcessing, init, cleanup } = useQueue(sessionId, eventId)
const { connect, emit } = useSocket()
const { post } = useApi()

const isJoiningQueue = ref(false)

onMounted(async () => {
  await sessionStore.fetchEvent(eventId)
  init()
})

onUnmounted(() => {
  cleanup()
})

async function joinQueue() {
  const config = useRuntimeConfig()
  
  const identifier = localStorage.getItem('auth-token') 
    ? `user_${localStorage.getItem('auth-token')?.split('|')[0]}` 
    : localStorage.getItem('guest-identifier') || `guest_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`
  
  if (!localStorage.getItem('auth-token')) {
    localStorage.setItem('guest-identifier', identifier)
  }

  isJoiningQueue.value = true

  connect(config.public.socketUrl)
  emit('register:queue', { session_id: sessionId, identifier })

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
  <div>
    <main class="mx-auto max-w-4xl px-4 py-8">
      <!-- Waiting Room -->
      <!-- Queue Context / Banners -->
      <div v-if="!isAdmitted" class="mb-4 rounded-lg bg-yellow-100 p-4 font-medium text-yellow-800">
        <p>Observant l'estat en temps real. Pots unir-te a la cua quan vulguis participar.</p>
        <button 
          @click="joinQueue" 
          :disabled="isJoiningQueue || (position !== null && position > 0)"
          class="btn-brutal mt-2 bg-yellow-400 text-sm px-6 py-2"
        >
          {{ position && position > 0 ? 'Fent la cua...' : (isJoiningQueue ? 'Processant...' : 'Unir-se a la cua') }}
        </button>
      </div>

      <WaitingRoom
        v-if="position && position > 0 && !isAdmitted"
        :session-id="sessionId"
        :event-id="eventId"
        @admitted="isAdmitted = true"
      />

      <!-- Seat Map -->
      <template v-if="sessionStore.event">
        <NuxtLink :to="`/events/${eventId}`" class="mb-4 inline-flex items-center gap-2 text-sm font-medium hover:underline">
          ← Tornar
        </NuxtLink>

        <div class="mb-6">
          <h1 class="text-2xl font-bold">{{ sessionStore.event.title }}</h1>
          <p class="text-gray-600">{{ sessionStore.event.venue }} · {{ sessionStore.selectedSession?.date }} {{ sessionStore.selectedSession?.time }}</p>
        </div>

        <SeatMap
          v-if="sessionStore.event?.type === 'movie'"
          :session-id="sessionId"
          :layout="{ rows: ['A', 'B', 'C', 'D', 'E'], seatsPerRow: 10 }"
          :readonly="!isAdmitted"
        />
        <ZoneMap
          v-else
          :session-id="sessionId"
          :readonly="!isAdmitted"
        />

        <div class="mb-20" />
      </template>
    </main>

    <BookingFooter
      v-if="sessionStore.event && isAdmitted"
      :mode="sessionStore.event?.type === 'movie' ? 'seats' : 'zones'"
      :event-id="eventId"
      :session-id="sessionId"
      @book="handleBook"
    />
  </div>
</template>
