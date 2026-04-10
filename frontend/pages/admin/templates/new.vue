<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '../../../composables/useApi'

const router = useRouter()
const { post, loading, error } = useApi()

const name = ref('')
const slug = ref('')
const description = ref('')
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

async function saveTemplate() {
  try {
    const zones = JSON.parse(zonesJson.value)
    const res = await post('/venue-templates', {
      name: name.value,
      slug: slug.value || undefined,
      description: description.value || undefined,
      zones,
    })

    if ((res as any)?.data?.id) {
      await router.push('/admin/templates')
    }
  } catch {
    alert('JSON de zones no vàlid')
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

      <label class="block text-sm font-semibold">Zones (JSON)</label>
      <textarea v-model="zonesJson" class="input-brutal w-full min-h-[300px] font-mono text-xs" />

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
