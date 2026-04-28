<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '../../../composables/useApi'

const route = useRoute()
const router = useRouter()
const templateId = Number(route.params.id)
const { get, put, loading, error } = useApi()

const name = ref('')
const slug = ref('')
const description = ref('')
const templateType = ref<'movie' | 'concert'>('concert')
const movieLayoutJson = ref('[]')
const zonesJson = ref('[]')

function parseMovieLayout() {
  const layout = JSON.parse(movieLayoutJson.value)

  if (!Array.isArray(layout) || layout.length === 0) {
    throw new Error('La matriu de cine ha de tenir almenys una fila')
  }

  const width = Array.isArray(layout[0]) ? layout[0].length : 0
  if (width === 0) {
    throw new Error('La matriu de cine ha de tenir almenys una columna')
  }

  for (const row of layout) {
    if (!Array.isArray(row) || row.length !== width) {
      throw new Error('Totes les files de la matriu han de tenir la mateixa longitud')
    }

    for (const cell of row) {
      if (cell !== 0 && cell !== 1) {
        throw new Error('La matriu només pot contenir valors 0 o 1')
      }
    }
  }

  return layout
}

async function loadTemplate() {
  const res: any = await get(`/venue-templates/${templateId}`)
  const tpl = res?.data
  if (!tpl) return

  name.value = tpl.name || ''
  slug.value = tpl.slug || ''
  description.value = tpl.description || ''
  templateType.value = tpl.template_type === 'movie' ? 'movie' : 'concert'
  movieLayoutJson.value = JSON.stringify(tpl?.metadata?.layout || [], null, 2)
  zonesJson.value = JSON.stringify(tpl.zones || [], null, 2)
}

async function saveTemplate() {
  try {
    const payload: any = {
      name: name.value,
      slug: slug.value,
      description: description.value,
      template_type: templateType.value,
    }

    if (templateType.value === 'movie') {
      payload.metadata = {
        layout: parseMovieLayout(),
      }
      payload.zones = []
    } else {
      payload.zones = JSON.parse(zonesJson.value)
    }

    const res = await put(`/venue-templates/${templateId}`, payload)

    if ((res as any)?.data?.id) {
      await router.push('/admin/templates')
    }
  } catch (exception: any) {
    alert(exception?.message || 'Dades de plantilla no vàlides')
  }
}

onMounted(loadTemplate)
</script>

<template>
  <main class="mx-auto max-w-4xl px-4 py-8">
    <h1 class="mb-6 text-2xl font-extrabold">Editar plantilla</h1>

    <div class="space-y-4 card-brutal bg-white p-5">
      <label class="block text-sm font-semibold">Nom</label>
      <input v-model="name" class="input-brutal w-full" />

      <label class="block text-sm font-semibold">Slug</label>
      <input v-model="slug" class="input-brutal w-full" />

      <label class="block text-sm font-semibold">Descripció</label>
      <textarea v-model="description" class="input-brutal w-full min-h-[80px]" />

      <label class="block text-sm font-semibold">Tipus de plantilla</label>
      <select v-model="templateType" class="input-brutal w-full">
        <option value="concert">Concert</option>
        <option value="movie">Cine</option>
      </select>

      <div v-if="templateType === 'movie'" class="space-y-2">
        <label class="block text-sm font-semibold">Matriu de seients (JSON)</label>
        <p class="text-xs text-gray-600">Usa 1 per seient i 0 per passadís/espai buit.</p>
        <textarea v-model="movieLayoutJson" class="input-brutal w-full min-h-[220px] font-mono text-xs" />
      </div>

      <div v-else class="space-y-2">
        <label class="block text-sm font-semibold">Zones (JSON)</label>
        <p class="text-xs text-gray-600">Per zones `seated`, defineix `seat_layout.layout` amb matriu 0/1.</p>
        <textarea v-model="zonesJson" class="input-brutal w-full min-h-[300px] font-mono text-xs" />
      </div>

      <p v-if="error" class="border-2 border-accent bg-white p-3 text-accent">{{ error }}</p>

      <div class="flex gap-3">
        <NuxtLink to="/admin/templates" class="btn-brutal-secondary px-4 py-2 text-sm">Cancel·lar</NuxtLink>
        <button :disabled="loading" class="btn-brutal px-4 py-2 text-sm" @click="saveTemplate">
          {{ loading ? 'Guardant...' : 'Guardar canvis' }}
        </button>
      </div>
    </div>
  </main>
</template>
