<script setup lang="ts">
defineProps<{
  id: number
  title: string
  image: string
  type: string
  date: string
  venue: string
}>()
</script>

<template>
  <NuxtLink
    :to="`/events/${id}`"
    class="card-brutal group block h-full overflow-hidden transition-transform hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-none"
  >
    <div class="aspect-[16/9] w-full overflow-hidden border-b-2 border-black">
      <img
        :src="image"
        :alt="title"
        class="h-full w-full object-cover transition-transform group-hover:scale-105"
      />
    </div>
    <div class="p-4">
      <span
        class="mb-2 inline-block border-2 border-black px-2 py-0.5 text-xs font-semibold"
        :class="type === 'cine' ? 'bg-secondary' : 'bg-primary'"
      >
        {{ type === 'cine' ? '🎬 Cine' : '🎤 Concierto' }}
      </span>
      <h3 class="mb-1 text-lg font-bold leading-tight">{{ title }}</h3>
      <p class="text-sm text-gray-600">{{ date }}</p>
      <p class="text-sm text-gray-600">{{ venue }}</p>
    </div>
  </NuxtLink>
</template>