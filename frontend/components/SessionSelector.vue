<script setup lang="ts">
import { useSessionStore } from '~/stores/session'

interface Session {
  id: number
  event_id: number
  date: string
  time: string
}

defineProps<{
  sessions: Session[]
}>()

const sessionStore = useSessionStore()

function formatDate(dateStr: string) {
  const d = new Date(dateStr)
  return d.toLocaleDateString('ca-ES', { weekday: 'short', day: 'numeric', month: 'short' })
}

function formatTime(timeStr: string) {
  return timeStr.slice(0, 5)
}

function isSelected(session: Session) {
  return sessionStore.selectedSession?.id === session.id
}
</script>

<template>
  <div class="flex gap-3 overflow-x-auto pb-2">
    <button
      v-for="session in sessions"
      :key="session.id"
      class="flex flex-col items-center justify-center border-2 border-black px-4 py-3 text-sm font-medium transition-all hover:shadow-brutal"
      :class="isSelected(session) ? 'bg-secondary shadow-brutal' : 'bg-white'"
      @click="sessionStore.selectSession(session)"
    >
      <span class="uppercase">{{ formatDate(session.date) }}</span>
      <span class="text-lg font-bold">{{ formatTime(session.time) }}</span>
    </button>
  </div>
</template>