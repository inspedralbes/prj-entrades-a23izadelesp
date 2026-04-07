<script setup lang="ts">
const props = defineProps<{
  modelValue: number
  max?: number
}>()

const emit = defineEmits<{
  'update:modelValue': [value: number]
}>()

const maxQuantity = props.max || 10

function decrement() {
  if (props.modelValue > 1) {
    emit('update:modelValue', props.modelValue - 1)
  }
}

function increment() {
  if (props.modelValue < maxQuantity) {
    emit('update:modelValue', props.modelValue + 1)
  }
}
</script>

<template>
  <div class="flex items-center gap-3">
    <button
      class="btn-brutal h-10 w-10"
      :disabled="modelValue <= 1"
      @click="decrement"
    >
      -
    </button>
    <span class="w-8 text-center text-xl font-bold">{{ modelValue }}</span>
    <button
      class="btn-brutal h-10 w-10"
      :disabled="modelValue >= maxQuantity"
      @click="increment"
    >
      +
    </button>
  </div>
</template>