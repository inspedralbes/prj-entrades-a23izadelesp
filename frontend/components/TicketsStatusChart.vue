<script setup lang="ts">
import { computed } from 'vue'
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

const props = defineProps<{
  tickets: Array<{ status: string }>
}>()

const chartData = computed(() => {
  const statusMap: Record<string, number> = {
    confirmed: 0,
    pending: 0,
    failed: 0
  }

  props.tickets.forEach((ticket: { status: string }) => {
    if (statusMap[ticket.status] !== undefined) {
      statusMap[ticket.status] += 1
    }
  })

  return {
    labels: ['Confirmadas', 'Pendientes', 'Fallidas'],
    datasets: [
      {
        label: 'Reservas',
        data: [statusMap.confirmed, statusMap.pending, statusMap.failed],
        backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
        borderColor: '#111827',
        borderWidth: 1
      }
    ]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        precision: 0
      }
    }
  }
}
</script>

<template>
  <div class="card-brutal bg-white p-4 sm:p-5">
    <h2 class="mb-3 text-base font-bold">Estado de mis reservas</h2>
    <div class="h-56">
      <Bar :data="chartData" :options="chartOptions" />
    </div>
  </div>
</template>
