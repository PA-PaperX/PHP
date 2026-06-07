<template>
  <div class="space-y-8 pb-10" v-motion-fade>
    <!-- Hidden element to force Tailwind to generate red badge classes -->
    <UBadge color="red" variant="subtle" class="hidden" />
    <div class="dashboard-hero flex items-center justify-between">
      <div>
        <h1 id="admin-tour-start" class="text-3xl font-bold text-gray-900 dark:text-white font-kanit">ภาพรวมระบบ (Admin Dashboard)</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-2 font-kanit">สรุปข้อมูลสถิติและการดำเนินการทั้งหมด</p>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <UCard v-for="(stat, index) in stats" :key="index" v-motion-slide-visible-bottom :delay="index * 100" class="dashboard-surface dashboard-surface-hover">
        <div class="flex items-center gap-4">
          <div :class="[stat.color, 'dashboard-icon-pill p-3 text-white shadow-sm']">
            <UIcon :name="stat.icon" class="w-7 h-7" />
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400 font-kanit">{{ stat.label }}</p>
            <p class="text-3xl font-bold font-inter text-gray-900 dark:text-white">{{ stat.value }}</p>
          </div>
        </div>
      </UCard>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <UCard class="dashboard-surface dashboard-surface-hover">
        <div class="mb-4">
          <h2 class="text-xl font-bold font-kanit text-gray-900 dark:text-white flex items-center gap-2">
            <UIcon name="i-heroicons-chart-bar" class="w-6 h-6 text-coral-500" />
            สถิติการแจ้งซ่อม (7 วันย้อนหลัง)
          </h2>
          <p class="text-sm text-gray-500 font-kanit mt-1">แนวโน้มจำนวนการแจ้งปัญหาใหม่ในแต่ละวัน</p>
        </div>
        <div v-if="pendingStats" class="h-64 flex items-center justify-center">
          <UIcon name="i-heroicons-arrow-path" class="w-8 h-8 text-primary animate-spin" />
        </div>
        <ClientOnly v-else-if="statsData?.issues_trend">
          <ChartsLineChart 
            :labels="statsData.issues_trend.labels" 
            :data="statsData.issues_trend.data" 
          />
        </ClientOnly>
      </UCard>

      <UCard class="dashboard-surface dashboard-surface-hover">
        <div class="mb-4">
          <h2 class="text-xl font-bold font-kanit text-gray-900 dark:text-white flex items-center gap-2">
            <UIcon name="i-heroicons-chart-pie" class="w-6 h-6 text-blue-500" />
            สัดส่วนการยืมอุปกรณ์
          </h2>
          <p class="text-sm text-gray-500 font-kanit mt-1">แยกตามหมวดหมู่อุปกรณ์ที่มีการยืมทั้งหมด</p>
        </div>
        <div v-if="pendingStats" class="h-64 flex items-center justify-center">
          <UIcon name="i-heroicons-arrow-path" class="w-8 h-8 text-primary animate-spin" />
        </div>
        <ClientOnly v-else-if="statsData?.borrows_by_category">
          <ChartsDonutChart 
            :labels="statsData.borrows_by_category.labels" 
            :data="statsData.borrows_by_category.data" 
            :empty="statsData.borrows_by_category.empty"
          />
        </ClientOnly>
      </UCard>
    </div>

    <!-- Quick Links -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6" v-motion-slide-visible-bottom :delay="300">
      <UCard class="dashboard-surface dashboard-surface-hover group">
        <div class="flex flex-col h-full">
          <div class="flex items-center gap-3 mb-4">
            <div class="bg-coral-100 dark:bg-coral-900/30 p-2 rounded-lg text-coral-600">
              <UIcon name="i-heroicons-ticket" class="w-6 h-6" />
            </div>
            <h2 class="text-lg font-bold font-kanit">จัดการปัญหา (Issues)</h2>
          </div>
          <p class="text-gray-500 dark:text-gray-400 font-kanit text-sm flex-1 mb-6">
            ตรวจสอบและอัปเดตสถานะการแจ้งซ่อมและปัญหาต่างๆ ที่ผู้ใช้แจ้งเข้ามาในระบบ
          </p>
          <UButton to="/admin/issues" color="primary" variant="solid" class="font-kanit w-full justify-center !bg-coral-500 hover:!bg-coral-600 !text-white shadow-sm">
            จัดการปัญหาทั้งหมด
          </UButton>
        </div>
      </UCard>

      <UCard class="dashboard-surface dashboard-surface-hover group">
        <div class="flex flex-col h-full">
          <div class="flex items-center gap-3 mb-4">
            <div class="bg-slate-100 dark:bg-slate-800 p-2 rounded-lg text-slate-700 dark:text-slate-200">
              <UIcon name="i-heroicons-computer-desktop" class="w-6 h-6" />
            </div>
            <h2 class="text-lg font-bold font-kanit">จัดการอุปกรณ์ (Inventory)</h2>
          </div>
          <p class="text-gray-500 dark:text-gray-400 font-kanit text-sm flex-1 mb-6">
            เพิ่ม แก้ไข และลบข้อมูลอุปกรณ์ในระบบ รวมถึงตรวจสอบสถานะคำขอยืม-คืน
          </p>
          <UButton to="/admin/inventory" color="primary" variant="solid" class="font-kanit w-full justify-center !bg-slate-900 hover:!bg-slate-800 !text-white shadow-sm dark:!bg-slate-100 dark:!text-slate-950 dark:hover:!bg-white">
            จัดการคลังอุปกรณ์
          </UButton>
        </div>
      </UCard>
    </div>

    <!-- Recent Issues Table -->
    <div class="mt-8" v-motion-fade :delay="400">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold font-kanit text-gray-900 dark:text-white">ปัญหาที่แจ้งเข้ามาล่าสุด</h2>
        <UButton to="/admin/issues" color="gray" variant="ghost" icon="i-heroicons-arrow-right" trailing class="font-kanit text-sm">
          ดูทั้งหมด
        </UButton>
      </div>
      
      <UCard class="dashboard-surface overflow-hidden" :ui="{ body: { padding: '!p-0' } }">
        <UTable :data="activeRecentIssues" :columns="columns" class="font-kanit" :loading="pendingIssues">
          <template #status-cell="{ row }">
            <UBadge 
              :color="row.original.status === 'resolved' ? 'success' : row.original.status === 'pending' ? 'warning' : (row.original.status === 'closed' || row.original.status === 'cancelled') ? 'red' : 'primary'" 
              variant="subtle"
            >
              {{ row.original.status === 'resolved' ? 'เสร็จสิ้น' : row.original.status === 'pending' ? 'รอดำเนินการ' : (row.original.status === 'closed' || row.original.status === 'cancelled') ? 'ยกเลิก' : 'กำลังดำเนินการ' }}
            </UBadge>
          </template>
        </UTable>
      </UCard>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue'
import { useApi } from '~/composables/useApi'
import { useTour } from '~/composables/useTour'

definePageMeta({
  middleware: 'admin'
})

const { startAdminTour } = useTour()



const { data: statsData, pending: pendingStats } = await useApi<any>('/api/dashboard/stats.php')
const { data: recentIssues, pending: pendingIssues } = await useApi<any>('/api/issues/index.php', { query: { limit: 5 } })

const stats = computed(() => [
  { label: 'ปัญหาทั้งหมด', value: statsData.value?.issues?.total || 0, icon: 'i-heroicons-document-text', color: 'bg-slate-900 dark:bg-slate-100 dark:text-slate-950' },
  { label: 'รอดำเนินการ', value: statsData.value?.issues?.pending || 0, icon: 'i-heroicons-clock', color: 'bg-amber-500' },
  { label: 'กำลังแก้ไข', value: statsData.value?.issues?.in_progress || 0, icon: 'i-heroicons-wrench', color: 'bg-coral-500' },
  { label: 'เสร็จสิ้น', value: statsData.value?.issues?.resolved || 0, icon: 'i-heroicons-check-circle', color: 'bg-emerald-500' }
])

const activeRecentIssues = computed(() => {
  if (!recentIssues.value?.issues) return []
  return recentIssues.value.issues
    .filter((issue: any) => issue.status !== 'closed' && issue.status !== 'cancelled')
    .slice(0, 5)
})

import { useBreakpoints, breakpointsTailwind } from '@vueuse/core'
const breakpoints = useBreakpoints(breakpointsTailwind)
const isSm = breakpoints.greaterOrEqual('sm')
const isMd = breakpoints.greaterOrEqual('md')

const columns = computed(() => {
  const cols = [
    { accessorKey: 'id', header: 'รหัส' },
    { accessorKey: 'title', header: 'หัวข้อปัญหา' }
  ]
  if (isSm.value) cols.push({ accessorKey: 'user_email', header: 'ผู้แจ้ง' })
  cols.push({ accessorKey: 'status', header: 'สถานะ' })
  if (isMd.value) cols.push({ accessorKey: 'created_at', header: 'วันที่แจ้ง' })
  return cols
})

// Pseudo-realtime background polling
const fetchSilentUpdates = async () => {
  try {
    const baseUrl = useBaseUrl();
    const [statsRes, issuesRes] = await Promise.all([
      $fetch<any>('/api/dashboard/stats.php', { baseURL: baseUrl, credentials: 'include' }),
      $fetch<any>('/api/issues/index.php?limit=5', { baseURL: baseUrl, credentials: 'include' })
    ]);
    
    if (statsRes) statsData.value = statsRes;
    if (issuesRes?.issues) recentIssues.value = issuesRes;
  } catch (e) {
    // Silent fail
  }
}

let pollInterval: any = null
onMounted(() => {
  pollInterval = setInterval(fetchSilentUpdates, 10000) // 10s for dashboard stats
  
  setTimeout(() => {
    startAdminTour()
  }, 500)
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})
</script>
