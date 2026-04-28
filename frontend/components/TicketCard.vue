<script setup lang="ts">
defineProps<{
  id: number
  eventTitle: string
  eventImage: string
  eventVenue: string
  sessionDate: string
  sessionTime: string
  status: string
  total: number
  ticketCount: number
  createdAt: string
}>()
</script>

<template>
  <NuxtLink
    :to="`/profile/tickets/${id}`"
    class="card-brutal block group overflow-hidden transition-all hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-none"
  >
    <div class="flex">
      <div class="w-24 flex-shrink-0 border-r-2 border-black">
        <img :src="eventImage" :alt="eventTitle" class="h-full w-full object-cover" />
      </div>
      <div class="flex flex-1 flex-col justify-between p-4">
        <div>
          <h3 class="mb-1 text-lg font-bold leading-tight">{{ eventTitle }}</h3>
          <p class="text-sm text-gray-600">{{ eventVenue }}</p>
        </div>
        <div class="mt-2 flex items-center justify-between">
          <div class="text-sm">
            <span class="text-gray-500">{{ sessionDate }} · {{ sessionTime }}</span>
          </div>
          <span
            class="border-2 border-black px-2 py-0.5 text-xs font-semibold"
            :class="{
              'bg-primary': status === 'confirmed',
              'bg-secondary': status === 'pending',
              'bg-accent': status === 'failed'
            }"
          >
            {{ status === 'confirmed' ? 'Confirmada' : status === 'pending' ? 'Pendent' : 'Fallida' }}
          </span>
        </div>
      </div>
      <div class="flex w-20 flex-col items-center justify-center border-l-2 border-black bg-gray-50">
        <span class="text-2xl font-bold">{{ ticketCount }}</span>
        <span class="text-xs text-gray-500">entr.</span>
      </div>
    </div>
  </NuxtLink>
</template>