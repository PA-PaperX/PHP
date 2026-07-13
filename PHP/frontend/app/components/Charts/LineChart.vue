<script setup lang="ts">
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

const props = defineProps<{
  labels: string[]
  data: number[]
}>()

const chartData = computed(() => ({
  labels: props.labels.map(date => {
    // Format date to short format like "20 May"
    const d = new Date(date)
    return d.toLocaleDateString('th-TH', { day: 'numeric', month: 'short' })
  }),
  datasets: [
    {
      label: 'รายการแจ้งซ่อม',
      data: props.data,
      borderColor: '#F97316', // Coral 500
      backgroundColor: 'rgba(249, 115, 22, 0.1)', // Coral with opacity for fill
      borderWidth: 3,
      tension: 0.4, // Smooth curve
      fill: true,
      pointBackgroundColor: '#FFFFFF',
      pointBorderColor: '#F97316',
      pointBorderWidth: 2,
      pointRadius: 4,
      pointHoverRadius: 6
    }
  ]
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      backgroundColor: 'rgba(17, 24, 39, 0.9)',
      titleFont: { family: 'Kanit' },
      bodyFont: { family: 'Kanit' },
      padding: 12,
      cornerRadius: 8,
      displayColors: false
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: {
        color: 'rgba(0, 0, 0, 0.05)',
        drawBorder: false,
      },
      ticks: {
        font: { family: 'Kanit' },
        stepSize: 1
      }
    },
    x: {
      grid: {
        display: false,
        drawBorder: false,
      },
      ticks: {
        font: { family: 'Kanit' }
      }
    }
  },
  interaction: {
    intersect: false,
    mode: 'index' as const,
  },
}
</script>

<template>
  <div class="h-64 w-full">
    <Line :data="chartData" :options="chartOptions" />
  </div>
</template>
