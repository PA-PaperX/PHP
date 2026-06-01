<script setup lang="ts">
import { computed } from 'vue'
import { Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend
} from 'chart.js'

ChartJS.register(ArcElement, Tooltip, Legend)

const props = defineProps<{
  labels: string[]
  data: number[]
  empty?: boolean
}>()

const chartData = computed(() => {
  if (props.empty) {
    return {
      labels: ['ไม่มีข้อมูล'],
      datasets: [{
        data: [1],
        backgroundColor: ['#E5E7EB'], // Gray 200
        borderWidth: 0,
      }]
    }
  }
  
  return {
    labels: props.labels,
    datasets: [
      {
        data: props.data,
        backgroundColor: [
          '#F97316', // Coral
          '#3B82F6', // Blue
          '#10B981', // Emerald
          '#8B5CF6', // Violet
          '#F59E0B', // Amber
          '#EC4899', // Pink
          '#06B6D4', // Cyan
        ],
        borderWidth: 2,
        borderColor: '#ffffff',
        hoverOffset: 4
      }
    ]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '70%', // Make it a donut
  plugins: {
    legend: {
      position: 'right' as const,
      labels: {
        font: { family: 'Kanit' },
        usePointStyle: true,
        padding: 20
      }
    },
    tooltip: {
      enabled: !props.empty,
      backgroundColor: 'rgba(17, 24, 39, 0.9)',
      titleFont: { family: 'Kanit' },
      bodyFont: { family: 'Kanit' },
      padding: 12,
      cornerRadius: 8,
    }
  }
}
</script>

<template>
  <div class="h-64 w-full flex items-center justify-center relative">
    <Doughnut :data="chartData" :options="chartOptions" />
    <div v-if="empty" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
      <UIcon name="i-heroicons-inbox" class="w-8 h-8 text-gray-400 mb-1" />
      <span class="text-sm text-gray-500 font-kanit">ยังไม่มีการยืม</span>
    </div>
  </div>
</template>
