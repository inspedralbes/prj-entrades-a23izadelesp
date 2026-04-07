<script setup lang="ts">
import { useEventsStore } from '~/stores/events'

const eventsStore = useEventsStore()

onMounted(() => {
  eventsStore.fetchEvents()
})
</script>

<template>
  <div>
    <TopBar />
    <main class="mx-auto max-w-7xl px-4 py-8">
      <div class="mb-8 flex gap-4">
        <button
          v-for="f in ['all', 'cine', 'concierto']"
          :key="f"
          class="btn-brutal capitalize"
          :class="eventsStore.filter === f ? 'bg-primary' : 'bg-white'"
          @click="eventsStore.setFilter(f as 'all' | 'cine' | 'concierto')"
        >
          {{ f === 'all' ? 'Todos' : f === 'cine' ? '🎬 Cine' : '🎤 Conciertos' }}
        </button>
      </div>

      <div v-if="eventsStore.loading" class="py-12 text-center text-lg font-medium">
        Carregant esdeveniments...
      </div>

      <div v-else-if="eventsStore.error" class="py-12 text-center text-lg font-medium text-accent">
        {{ eventsStore.error }}
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
        v-if="!eventsStore.loading && eventsStore.filteredEvents.length === 0"
        class="py-12 text-center text-lg text-gray-500"
      >
        No hi ha esdeveniments disponibles
      </div>
    </main>
  </div>
</template>