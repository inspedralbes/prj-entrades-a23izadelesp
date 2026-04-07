<script setup lang="ts">
import { useSeatsStore } from '~/stores/seats'
import { useZonesStore } from '~/stores/zones'
import { useSessionStore } from '~/stores/session'

const route = useRoute()
const router = useRouter()
const eventId = parseInt(route.params.id as string)
const sessionId = parseInt(route.params.sessionId as string)

const sessionStore = useSessionStore()
const seatsStore = useSeatsStore()
const zonesStore = useZonesStore()

onMounted(async () => {
  await sessionStore.fetchEvent(eventId)
})

function handleBook() {
  router.push(`/events/${eventId}/checkout/${sessionId}`)
}
</script>

<template>
  <div>
    <TopBar />
    <main class="mx-auto max-w-4xl px-4 py-8">
      <NuxtLink :to="`/events/${eventId}`" class="mb-4 inline-flex items-center gap-2 text-sm font-medium hover:underline">
        ← Tornar
      </NuxtLink>

      <div v-if="sessionStore.event" class="mb-6">
        <h1 class="text-2xl font-bold">{{ sessionStore.event.title }}</h1>
        <p class="text-gray-600">{{ sessionStore.event.venue }} · {{ sessionStore.selectedSession?.date }} {{ sessionStore.selectedSession?.time }}</p>
      </div>

      <SeatMap
        v-if="sessionStore.event?.type === 'cine'"
        :session-id="sessionId"
        :layout="{ rows: ['A', 'B', 'C', 'D', 'E'], seatsPerRow: 10 }"
      />
      <ZoneMap
        v-else
        :session-id="sessionId"
      />

      <div class="mb-20" />
    </main>

    <BookingFooter
      v-if="sessionStore.event?.type === 'cine'"
      mode="seats"
      :event-id="eventId"
      :session-id="sessionId"
      @book="handleBook"
    />
    <BookingFooter
      v-else
      mode="zones"
      :event-id="eventId"
      :session-id="sessionId"
      @book="handleBook"
    />
  </div>
</template>