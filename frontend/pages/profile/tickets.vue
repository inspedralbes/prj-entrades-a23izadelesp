<script setup lang="ts">
import { useProfileStore } from '~/stores/profile'

const profileStore = useProfileStore()

onMounted(() => {
  profileStore.fetchTickets()
})

function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString('ca-ES', {
    day: 'numeric', month: 'short', year: 'numeric'
  })
}
</script>

<template>
  <div>
    <main class="mx-auto max-w-3xl px-4 py-8">
      <h1 class="mb-6 text-2xl font-bold">Les meves entrades</h1>

      <div v-if="profileStore.loading" class="py-12 text-center text-lg font-medium">
        Carregant...
      </div>

      <div v-else-if="profileStore.error" class="py-12 text-center text-lg text-accent">
        {{ profileStore.error }}
      </div>

      <div v-else-if="profileStore.tickets.length === 0" class="py-12 text-center">
        <div class="mb-4 text-6xl">🎫</div>
        <p class="text-lg text-gray-600">No tens entrades comprades</p>
        <NuxtLink to="/" class="btn-brutal mt-4 inline-block">
          Ver esdeveniments
        </NuxtLink>
      </div>

      <div v-else class="space-y-4">
        <TicketCard
          v-for="ticket in profileStore.tickets"
          :key="ticket.id"
          :id="ticket.id"
          :event-title="ticket.event.title"
          :event-image="ticket.event.image"
          :event-venue="ticket.event.venue"
          :session-date="formatDate(ticket.session.date)"
          :session-time="ticket.session.time"
          :status="ticket.status"
          :total="ticket.total"
          :ticket-count="ticket.ticket_count"
          :created-at="ticket.created_at"
        />
      </div>
    </main>
  </div>
</template>