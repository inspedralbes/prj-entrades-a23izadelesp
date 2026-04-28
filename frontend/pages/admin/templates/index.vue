<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useApi } from '../../../composables/useApi'

const { get, del, loading, error } = useApi()
const templates = ref<any[]>([])

async function loadTemplates() {
  const res: any = await get('/venue-templates')
  templates.value = res?.data || []
}

async function removeTemplate(id: number) {
  const ok = confirm('Vols eliminar aquesta plantilla?')
  if (!ok) return
  await del(`/venue-templates/${id}`)
  await loadTemplates()
}

onMounted(loadTemplates)
</script>

<template>
  <main class="mx-auto max-w-5xl px-4 py-8">
    <div class="mb-6 flex items-center justify-between gap-3">
      <h1 class="text-2xl font-extrabold">Plantilles de recinte</h1>
      <NuxtLink to="/admin/templates/new" class="btn-brutal px-4 py-2 text-sm">Nova plantilla</NuxtLink>
    </div>

    <p v-if="error" class="mb-4 border-2 border-accent bg-white p-3 text-accent">{{ error }}</p>

    <div v-if="loading" class="card-brutal bg-white p-4">Carregant...</div>

    <div v-else-if="templates.length === 0" class="card-brutal bg-white p-4">No hi ha plantilles encara.</div>

    <div v-else class="space-y-3">
      <article v-for="tpl in templates" :key="tpl.id" class="card-brutal bg-white p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="text-lg font-bold">{{ tpl.name }}</h2>
            <p class="text-sm text-gray-600">{{ tpl.slug }}</p>
            <p class="mt-1 text-sm">Tipus: {{ tpl.template_type === 'movie' ? 'Cine' : 'Concert' }}</p>
            <p v-if="tpl.template_type === 'movie'" class="mt-1 text-sm">
              Matriu: {{ tpl.metadata?.layout?.length || 0 }}x{{ tpl.metadata?.layout?.[0]?.length || 0 }}
            </p>
            <p v-else class="mt-1 text-sm">Zones: {{ tpl.zones?.length || 0 }}</p>
          </div>
          <div class="flex gap-2">
            <NuxtLink :to="`/admin/templates/${tpl.id}`" class="btn-brutal-secondary px-3 py-2 text-xs">Editar</NuxtLink>
            <button class="btn-brutal-secondary px-3 py-2 text-xs" @click="removeTemplate(tpl.id)">Eliminar</button>
          </div>
        </div>
      </article>
    </div>
  </main>
</template>
