<script setup lang="ts">
import { ref, computed } from 'vue'
import { format, startOfMonth, endOfMonth, eachDayOfInterval } from 'date-fns'

export interface CalendarEvent {
  id: string
  title: string
  date: string // ISO string
}

const props = defineProps<{
  events: CalendarEvent[]
}>()

const emit = defineEmits<{
  (e: 'add-event', event: CalendarEvent): void
  (e: 'remove-event', id: string): void
}>()

const selectedDate = ref<Date>(new Date())
const newTitle = ref("")

const handleAddEvent = () => {
  if (!selectedDate.value || !newTitle.value.trim()) return
  emit('add-event', {
    id: crypto.randomUUID(), // using native instead of uuid
    title: newTitle.value.trim(),
    date: selectedDate.value.toISOString(),
  })
  newTitle.value = ""
}

// Only days in current month based on selected date
const daysInMonth = computed(() => {
  return eachDayOfInterval({
    start: startOfMonth(selectedDate.value),
    end: endOfMonth(selectedDate.value),
  })
})

const eventsCount = (date: Date) => {
  return props.events.filter(
    (e) => format(new Date(e.date), "yyyy-MM-dd") === format(date, "yyyy-MM-dd")
  )
}

const getIntensityColor = (count: number) => {
  if (count === 0) return "bg-gray-100 dark:bg-gray-800 text-gray-500"
  if (count === 1) return "bg-primary-200 dark:bg-primary-900/50 text-primary-800 dark:text-primary-200"
  if (count === 2) return "bg-primary-400 dark:bg-primary-700 text-white"
  if (count >= 3) return "bg-primary-600 dark:bg-primary-500 text-white shadow-sm"
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h2 class="text-xl font-bold font-kanit text-gray-900 dark:text-white capitalize">
        {{ format(selectedDate, "MMMM yyyy") }}
      </h2>
    </div>

    <!-- Heatmap grid -->
    <div class="grid grid-cols-7 gap-1.5 sm:gap-2 mt-4">
      <template v-for="day in daysInMonth" :key="day.toISOString()">
        <UPopover mode="hover">
          <div
            :class="[
              'w-10 h-10 sm:w-12 sm:h-12 rounded-xl cursor-pointer flex items-center justify-center transition-all duration-200 hover:scale-105 font-medium font-kanit',
              getIntensityColor(eventsCount(day).length),
              format(day, 'yyyy-MM-dd') === format(selectedDate, 'yyyy-MM-dd') ? 'ring-2 ring-primary-500 ring-offset-2 dark:ring-offset-gray-950' : ''
            ]"
            @click="selectedDate = day"
          >
            <span class="text-sm sm:text-base">{{ day.getDate() }}</span>
          </div>

          <template #panel>
            <div class="p-4 w-60 font-kanit">
              <h3 class="font-semibold text-sm mb-3 text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-800 pb-2">
                {{ format(day, "d MMMM yyyy") }}
              </h3>
              
              <div v-if="eventsCount(day).length === 0" class="text-xs text-gray-500 dark:text-gray-400 py-3 italic text-center">
                ไม่มีกิจกรรม
              </div>
              
              <div v-else class="space-y-2 max-h-[200px] overflow-y-auto pr-1">
                <div
                  v-for="event in eventsCount(day)"
                  :key="event.id"
                  class="flex justify-between items-center text-sm bg-gray-50 dark:bg-gray-800/50 p-2.5 rounded-lg border border-gray-100 dark:border-gray-700/50"
                >
                  <span class="truncate pr-2 text-gray-700 dark:text-gray-300">{{ event.title }}</span>
                  <UButton
                    color="red"
                    variant="ghost"
                    icon="i-heroicons-trash"
                    size="2xs"
                    :padded="false"
                    class="opacity-60 hover:opacity-100 shrink-0"
                    @click="emit('remove-event', event.id)"
                  />
                </div>
              </div>
            </div>
          </template>
        </UPopover>
      </template>
    </div>

    <!-- Add new event -->
    <div v-if="selectedDate" class="flex gap-2 mt-6 items-center bg-gray-50 dark:bg-gray-900 p-3 rounded-xl border border-gray-100 dark:border-gray-800">
      <div class="flex flex-col items-center justify-center w-12 shrink-0">
        <span class="text-xs text-gray-500 font-medium">{{ format(selectedDate, 'MMM') }}</span>
        <span class="text-lg font-bold text-primary-600 dark:text-primary-400 leading-tight">{{ format(selectedDate, "d") }}</span>
      </div>
      <div class="h-8 w-px bg-gray-200 dark:bg-gray-700 mx-1"></div>
      <UInput
        v-model="newTitle"
        placeholder="เพิ่มกิจกรรมใหม่..."
        class="flex-1 font-kanit"
        size="md"
        @keyup.enter="handleAddEvent"
      />
      <UButton color="primary" @click="handleAddEvent" icon="i-heroicons-plus" size="md">
        เพิ่ม
      </UButton>
    </div>
  </div>
</template>
