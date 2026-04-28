<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '../../../composables/useApi'

const router = useRouter()
const { post, loading, error } = useApi()

const name = ref('')
const slug = ref('')
const description = ref('')
const templateType = ref<'movie' | 'concert'>('concert')

const movieLayoutJson = ref(`[
  [1,1,1,1,1,1,1,1],
  [1,1,1,1,1,1,1,1],
  [1,1,1,0,0,1,1,1],
  [1,1,1,1,1,1,1,1]
]`)

const zonesJson = ref(`[
  {
    "key": "pista",
    "name": "Pista",
    "zone_type": "general_admission",
    "capacity": 800,
    "price": 45,
    "color": "#22c55e"
  },
  {
    "key": "grada_a",
    "name": "Grada A",
    "zone_type": "seated",
    "capacity": 0,
    "price": 75,
    "color": "#60a5fa",
    "seat_layout": {
      "layout": [[1,1,1,1,1],[1,1,1,1,1],[1,1,0,1,1]]
    }
  }
]`)

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

async function saveTemplate() {
  try {
    const payload: any = {
      name: name.value,
      slug: slug.value || undefined,
      description: description.value || undefined,
      template_type: templateType.value,
    }

    if (templateType.value === 'movie') {
      payload.metadata = {
        layout: parseMovieLayout(),
      }
    } else {
      payload.zones = JSON.parse(zonesJson.value)
    }

    const res = await post('/venue-templates', payload)

    if ((res as any)?.data?.id) {
      await router.push('/admin/templates')
    }
  } catch (exception: any) {
    alert(exception?.message || 'Dades de plantilla no vàlides')
  }
}
</script>

<template>
  <main class="mx-auto max-w-4xl px-4 py-8">
    <h1 class="mb-6 text-2xl font-extrabold">Nova plantilla</h1>

    <div class="space-y-4 card-brutal bg-white p-5">
      <label class="block text-sm font-semibold">Nom</label>
      <input v-model="name" class="input-brutal w-full" placeholder="Palau Sant Jordi - Config A" />

      <label class="block text-sm font-semibold">Slug (opcional)</label>
      <input v-model="slug" class="input-brutal w-full" placeholder="palau-sant-jordi-a" />

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
          {{ loading ? 'Guardant...' : 'Guardar plantilla' }}
        </button>
      </div>
    </div>
  </main>
</template>
