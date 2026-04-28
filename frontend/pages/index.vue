<script setup lang="ts">
import { useEventsStore } from '~/stores/events'
import { watch } from 'vue'

const eventsStore = useEventsStore()

const { data: eventsData, pending, error } = await useFetch('/api/events', {
  key: 'events-home'
})

// Poblar el store con los datos
watch(eventsData, (newData) => {
  if (newData && (newData as any).data) {
    eventsStore.events = (newData as any).data
  }
}, { immediate: true })
</script>

<template>
  <main class="mx-auto max-w-7xl px-4 py-8">
    <div class="mb-8 flex gap-4">
      <button
        v-for="f in ['all', 'movie', 'concert']"
        :key="f"
        class="btn-brutal capitalize"
        :class="eventsStore.filter === f ? 'bg-primary' : 'bg-white'"
        @click="eventsStore.setFilter(f as 'all' | 'movie' | 'concert')"
      >
        {{ f === 'all' ? 'Todos' : f === 'movie' ? '🎬 Cine' : '🎤 Conciertos' }}
      </button>
    </div>

    <div v-if="pending" class="py-12 text-center text-lg font-medium">
      Carregant acontecimientos...
    </div>

    <div v-else-if="error" class="py-12 text-center text-lg font-medium text-accent">
      Error: {{ error }}
    </div>

    <div
      v-else
      class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3"
    >
      <EventCard
        v-for="event in eventsStore.filteredEvents"
        :key="event.id"
        :id="event.id"
        :title="event.title"
        :image="event.image"
        :type="event.type"
        :date="event.date"
        :venue="event.venue"
      />
    </div>

    <div
      v-if="!pending && eventsStore.filteredEvents.length === 0"
      class="py-12 text-center text-lg text-gray-500"
    >
      No hi ha esdeveniments disponibles
    </div>
  </main>
</template>