<script setup lang="ts">
import { useProfileStore } from '~/stores/profile'
import { Listbox, ListboxButton, ListboxOption, ListboxOptions } from '@headlessui/vue'

const profileStore = useProfileStore()
const selectedStatus = ref('all')

const statusOptions = [
  { value: 'all', label: 'Todos los estados' },
  { value: 'confirmed', label: 'Confirmadas' },
  { value: 'pending', label: 'Pendientes' },
  { value: 'failed', label: 'Fallidas' }
]

onMounted(() => {
  profileStore.fetchTickets()
})

const filteredTickets = computed(() => {
  if (selectedStatus.value === 'all') return profileStore.tickets
  return profileStore.tickets.filter(ticket => ticket.status === selectedStatus.value)
})

function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString('ca-ES', {
    day: 'numeric', month: 'short', year: 'numeric'
  })
}

function statusLabel(status: string) {
  return statusOptions.find(option => option.value === status)?.label || status
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
        <div class="card-brutal bg-white p-4 sm:p-5">
          <div class="mb-2 text-sm font-semibold">Filtrar por estado</div>
          <Listbox v-model="selectedStatus">
            <div class="relative max-w-sm">
              <ListboxButton class="input-brutal w-full text-left">
                {{ statusLabel(selectedStatus) }}
              </ListboxButton>
              <ListboxOptions class="absolute z-10 mt-2 max-h-56 w-full overflow-auto border-2 border-black bg-white shadow-brutal">
                <ListboxOption
                  v-for="option in statusOptions"
                  :key="option.value"
                  :value="option.value"
                  class="cursor-pointer px-3 py-2 hover:bg-gray-100"
                >
                  {{ option.label }}
                </ListboxOption>
              </ListboxOptions>
            </div>
          </Listbox>
        </div>

        <TicketsStatusChart :tickets="filteredTickets" />

        <TicketCard
          v-for="ticket in filteredTickets"
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