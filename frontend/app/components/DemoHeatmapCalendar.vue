<script setup lang="ts">
import { ref } from 'vue'
import type { CalendarEvent } from './HeatmapCalendar.vue'

const events = ref<CalendarEvent[]>([
  { id: '1', title: 'Meeting', date: new Date().toISOString() },
  { id: '2', title: 'Design Review', date: new Date().toISOString() },
  { id: '3', title: 'Deploy', date: new Date().toISOString() },
])

const handleAddEvent = (event: CalendarEvent) => {
  events.value.push(event)
}

const handleRemoveEvent = (id: string) => {
  events.value = events.value.filter(e => e.id !== id)
}
</script>

<template>
  <div class="p-6 space-y-6 max-w-3xl mx-auto bg-white dark:bg-gray-950 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800">
    <h1 class="text-2xl font-bold font-kanit text-gray-900 dark:text-white">ระบบปฏิทินแบบ Heatmap</h1>
    <HeatmapCalendar
      :events="events"
      @add-event="handleAddEvent"
      @remove-event="handleRemoveEvent"
    />
  </div>
</template>
