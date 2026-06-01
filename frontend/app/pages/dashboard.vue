<script setup lang="ts">
import { useApi } from '~/composables/useApi'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useAuth } from '~/composables/useAuth'
import { useTour } from '~/composables/useTour'

definePageMeta({
  middleware: 'auth'
})

const { user } = useAuth()
const { startUserTour } = useTour()



const { data: recentIssues, pending } = await useApi<any>('/api/issues/index.php', { query: { limit: 5 } })

import { useBreakpoints, breakpointsTailwind } from '@vueuse/core'
const breakpoints = useBreakpoints(breakpointsTailwind)
const isSm = breakpoints.greaterOrEqual('sm')

const columns = computed(() => {
  const cols = [
    { accessorKey: 'id', header: 'รหัส' },
    { accessorKey: 'title', header: 'หัวข้อปัญหา' },
    { accessorKey: 'status', header: 'สถานะ' }
  ]
  if (isSm.value) cols.push({ accessorKey: 'created_at', header: 'วันที่แจ้ง' })
  cols.push({ accessorKey: 'actions', header: 'รายละเอียด' })
  return cols
})

const selectedIssue = ref<any>(null)
const isSlideoverOpen = ref(false)

const openIssue = (issue: any) => {
  selectedIssue.value = issue
  isSlideoverOpen.value = true
}

// Pseudo-realtime background polling
const fetchSilentUpdates = async () => {
  if (isSlideoverOpen.value) return;
  
  try {
    const baseUrl = useBaseUrl();
    const data = await $fetch<any>('/api/issues/index.php?limit=5', { baseURL: baseUrl, credentials: 'include' });
    if (data?.issues) recentIssues.value = data;
  } catch (e) {
    // Silent fail
  }
}

let pollInterval: any = null
onMounted(() => {
  pollInterval = setInterval(fetchSilentUpdates, 5000)
  
  // Start tour after a short delay to ensure UI is fully rendered
  setTimeout(() => {
    startUserTour()
  }, 500)
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})
</script>

<template>
  <div class="space-y-6 pb-10" v-motion-fade>
    <!-- Hidden element to force Tailwind to generate red badge classes -->
    <UBadge color="red" variant="subtle" class="hidden" />
    <div class="flex items-center justify-between" v-motion-fade>
      <div>
        <h1 class="text-2xl font-bold font-kanit text-gray-900 dark:text-white">หน้าหลัก (Home)</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1 font-kanit">ยินดีต้อนรับเข้าสู่ระบบแจ้งซ่อมและเบิกอุปกรณ์</p>
      </div>
    </div>
    
    <!-- User View: Quick Actions -->
    <div v-if="user?.role !== 'admin'" class="grid grid-cols-1 sm:grid-cols-2 gap-6" v-motion-slide-visible-bottom>
      <NuxtLink to="/report-issue" class="block h-full">
        <UCard class="h-full glass-card hover:border-coral-400 hover:-translate-y-1 hover:shadow-md cursor-pointer group">
          <div class="flex flex-col items-center text-center gap-4 p-6">
            <div class="bg-coral-500/90 p-4 rounded-full text-white shadow-md shadow-coral-500/30 group-hover:scale-110 transition-transform">
              <UIcon name="i-heroicons-exclamation-triangle" class="w-10 h-10" />
            </div>
            <div>
              <h3 class="text-xl font-bold font-kanit text-gray-900 dark:text-white">แจ้งปัญหาใหม่</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 font-kanit">ส่งเรื่องแจ้งซ่อม หรือรายงานปัญหาการใช้งานอุปกรณ์ต่างๆ</p>
            </div>
          </div>
        </UCard>
      </NuxtLink>

      <NuxtLink to="/inventory" class="block h-full">
        <UCard class="h-full glass-card hover:border-blue-400 hover:-translate-y-1 hover:shadow-md cursor-pointer group">
          <div class="flex flex-col items-center text-center gap-4 p-6">
            <div class="bg-blue-500/90 p-4 rounded-full text-white shadow-md shadow-blue-500/30 group-hover:scale-110 transition-transform">
              <UIcon name="i-heroicons-cube" class="w-10 h-10" />
            </div>
            <div>
              <h3 class="text-xl font-bold font-kanit text-gray-900 dark:text-white">ยืมอุปกรณ์</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 font-kanit">ตรวจสอบอุปกรณ์คงเหลือ เบิกหรือยืมอุปกรณ์จากคลังส่วนกลาง</p>
            </div>
          </div>
        </UCard>
      </NuxtLink>
    </div>

    <div class="mt-8" v-motion-fade :delay="400">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold font-kanit text-gray-900 dark:text-white">ประวัติการแจ้งปัญหาล่าสุดของคุณ</h2>
      </div>
      <UCard class="glass" :ui="{ body: { padding: '!p-0' } }">
        <UTable :data="recentIssues?.issues || []" :columns="columns" class="font-kanit" :loading="pending">
          <template #status-cell="{ row }">
            <UBadge :color="row.original.status === 'resolved' ? 'success' : (row.original.status === 'closed' || row.original.status === 'cancelled') ? 'red' : row.original.status === 'in_progress' ? 'primary' : 'warning'" variant="subtle">
              {{ row.original.status === 'resolved' ? 'เสร็จสิ้น' : (row.original.status === 'closed' || row.original.status === 'cancelled') ? 'ยกเลิก' : row.original.status === 'in_progress' ? 'กำลังดำเนินการ' : 'รอดำเนินการ' }}
            </UBadge>
          </template>
          
          <template #actions-cell="{ row }">
            <UButton size="xs" color="gray" variant="ghost" icon="i-heroicons-eye" @click="openIssue(row.original)">
              ดูรายละเอียด
            </UButton>
          </template>
        </UTable>
      </UCard>
    </div>

    <!-- Issue Details Slideover -->
    <USlideover v-model:open="isSlideoverOpen" :ui="{ content: 'w-screen max-w-md', overlay: { background: 'bg-gray-900/50 backdrop-blur-sm' } }">
      <template #content>
        <div class="flex flex-col h-full bg-white dark:bg-gray-950 font-kanit">
          <div class="p-4 flex items-center justify-between border-b border-gray-100 dark:border-gray-800">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">รายละเอียดปัญหา #{{ selectedIssue?.id }}</h2>
            <UButton icon="i-heroicons-x-mark" color="gray" variant="ghost" @click="isSlideoverOpen = false" />
          </div>
          
          <div class="flex-1 overflow-y-auto p-6 space-y-6" v-if="selectedIssue">
            <div>
              <p class="text-sm text-gray-500">หัวข้อปัญหา</p>
              <p class="font-medium text-lg">{{ selectedIssue.title }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4 mt-4">
              <div>
                <p class="text-sm text-gray-500">สถานะ</p>
                <UBadge :color="selectedIssue.status === 'resolved' ? 'success' : (selectedIssue.status === 'closed' || selectedIssue.status === 'cancelled') ? 'red' : selectedIssue.status === 'in_progress' ? 'primary' : 'warning'" variant="subtle" class="mt-1">
                  {{ selectedIssue.status === 'resolved' ? 'เสร็จสิ้น' : (selectedIssue.status === 'closed' || selectedIssue.status === 'cancelled') ? 'ยกเลิก' : selectedIssue.status === 'in_progress' ? 'กำลังดำเนินการ' : 'รอดำเนินการ' }}
                </UBadge>
              </div>
              <div>
                <p class="text-sm text-gray-500">สถานที่</p>
                <p class="font-medium">{{ selectedIssue.location || '-' }}</p>
              </div>
            </div>

            <div class="mt-4">
              <p class="text-sm text-gray-500">รายละเอียด</p>
              <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded-lg mt-1 whitespace-pre-wrap text-sm border border-gray-100 dark:border-gray-800">
                {{ selectedIssue.description }}
              </div>
            </div>

            <div v-if="selectedIssue.image_path" class="mt-4">
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปภาพประกอบ</p>
              <img :src="`${useBaseUrl()}${selectedIssue.image_path}`" class="w-full rounded-lg border border-gray-200 shadow-sm" alt="My Attachment" />
            </div>

            <!-- Admin Reply / Resolution Section -->
            <div v-if="selectedIssue.admin_note || selectedIssue.admin_image_path || selectedIssue.status === 'resolved' || selectedIssue.status === 'closed'" class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-800">
              <h3 class="font-bold text-primary-600 dark:text-primary-400 mb-4 flex items-center gap-2">
                <UIcon name="i-heroicons-chat-bubble-left-ellipsis-solid" class="w-5 h-5" />
                รายงานจากผู้ดูแลระบบ
              </h3>
              
              <div class="bg-primary-50 dark:bg-primary-900/10 p-4 rounded-xl border border-primary-100 dark:border-primary-900/30">
                <div v-if="selectedIssue.admin_note">
                  <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หมายเหตุ / ผลการดำเนินการ:</p>
                  <p class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ selectedIssue.admin_note }}</p>
                </div>
                <div v-else>
                  <p class="text-sm text-gray-500 italic">ไม่มีข้อความเพิ่มเติมจากผู้ดูแลระบบ</p>
                </div>

                <div v-if="selectedIssue.admin_image_path" class="mt-4">
                  <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปภาพหลังซ่อมแซม</p>
                  <img :src="`${useBaseUrl()}${selectedIssue.admin_image_path}`" class="w-full rounded-lg border border-primary-200 dark:border-primary-800 shadow-md" alt="Admin Report" />
                </div>
              </div>
            </div>
          </div>
          
          <div class="p-4 border-t border-gray-100 dark:border-gray-800 flex bg-gray-50 dark:bg-gray-900">
            <UButton color="gray" variant="solid" class="w-full justify-center" @click="isSlideoverOpen = false">ปิดหน้าต่าง</UButton>
          </div>
        </div>
      </template>
    </USlideover>
  </div>
</template>
