<script setup lang="ts">
import { computed } from 'vue'
import { useApi } from '~/composables/useApi'

definePageMeta({
  middleware: 'auth'
})

const { data, pending } = await useApi<any>('/api/auth/history')

const historyItems = computed(() => data.value?.data?.history || data.value?.history || [])

const issues = computed(() => {
  const res = data.value
  if (!res) return []
  if (Array.isArray(res.data)) return res.data
  return res.data?.issues || res.issues || []
})

const getStatusColor = (status: string) => {
  const map: Record<string, string> = {
    pending: 'warning',
    approved: 'success',
    pending_return: 'info',
    returned: 'gray',
    rejected: 'error',
    in_progress: 'primary',
    resolved: 'success',
    closed: 'error',
    cancelled: 'gray'
  }
  return map[status] || 'gray'
}

const getStatusLabel = (status: string) => {
  const map: Record<string, string> = {
    pending: 'รอตรวจสอบ',
    approved: 'กำลังใช้งาน',
    pending_return: 'รอตรวจสอบการคืน',
    returned: 'คืนแล้ว',
    rejected: 'ไม่อนุมัติ',
    in_progress: 'กำลังดำเนินการ',
    resolved: 'เสร็จสิ้น',
    closed: 'ปิดงาน/ยกเลิก',
    cancelled: 'ยกเลิก'
  }
  return map[status] || status
}

const addToCalendar = (item: any) => {
  if (!item.return_date) {
    useToast().add({ title: 'ไม่มีกำหนดคืนระบุไว้', color: 'error' })
    return
  }
  const dateStr = item.return_date.replace(/-/g, '')
  const title = encodeURIComponent(`กำหนดคืน: ${item.title}`)
  const details = encodeURIComponent(`อย่าลืมนำอุปกรณ์ ${item.title} จำนวน ${item.quantity} ชิ้น ไปคืนที่ห้อง IT`)
  const url = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${title}&dates=${dateStr}T020000Z/${dateStr}T100000Z&details=${details}`
  window.open(url, '_blank')
}

const goToDetails = (item: any) => {
  if (item.type === 'issue') {
    useRouter().push(`/issue/${item.id}`)
  } else {
    useRouter().push(`/inventory`)
  }
}
</script>

<template>
  <div class="space-y-6 pb-10 font-kanit">
    <div class="flex items-center justify-between" v-motion-fade>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ประวัติการใช้งาน</h1>
    </div>

    <UCard>
      <div v-if="pending" class="flex justify-center p-8">
        <UIcon name="i-heroicons-arrow-path" class="w-8 h-8 text-primary animate-spin" />
      </div>
      
      <div v-else-if="historyItems.length === 0" class="text-center p-12 bg-gray-50/50 dark:bg-gray-900/50 rounded-lg">
        <UIcon name="i-heroicons-clock" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-700 mb-4" />
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">ยังไม่มีประวัติการใช้งาน</h3>
        <p class="text-gray-500 mt-2">คุณยังไม่เคยแจ้งซ่อมหรือยืมอุปกรณ์ใดๆ</p>
      </div>
      
      <div v-else class="space-y-4">
        <div v-for="item in historyItems" :key="`${item.type}-${item.id}`" 
             @click="goToDetails(item)"
             class="flex items-start gap-4 p-4 rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-950 hover:shadow-md transition-shadow cursor-pointer">
          <div :class="[
            'w-12 h-12 rounded-full flex items-center justify-center shrink-0',
            item.type === 'issue' ? 'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400'
          ]">
            <UIcon :name="item.type === 'issue' ? 'i-heroicons-wrench' : 'i-heroicons-cube'" class="w-6 h-6" />
          </div>
          
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2 mb-1">
              <h4 class="text-base font-semibold text-gray-900 dark:text-white truncate">
                <span class="text-xs text-gray-500 dark:text-gray-400 mr-2 uppercase tracking-wider font-bold">
                  {{ item.type === 'issue' ? 'แจ้งซ่อม' : 'ยืมอุปกรณ์' }} #{{ item.id }}
                </span>
                <br class="sm:hidden" />
                {{ item.title }}
              </h4>
              <UBadge :color="getStatusColor(item.status)" variant="subtle" size="sm" class="shrink-0">
                {{ getStatusLabel(item.status) }}
              </UBadge>
            </div>
            
            <p v-if="item.type === 'issue' && item.category" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
              <UIcon name="i-heroicons-tag" class="w-4 h-4 inline-block mr-1 align-text-bottom" />
              {{ item.category }}
            </p>
            <p v-if="item.type === 'borrow' && item.quantity" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
              <UIcon name="i-heroicons-bars-3-bottom-left" class="w-4 h-4 inline-block mr-1 align-text-bottom" />
              จำนวน {{ item.quantity }} ชิ้น
            </p>
            
            <div class="flex items-center gap-4 mt-3 text-xs text-gray-500">
              <span>
                <UIcon name="i-heroicons-calendar" class="w-4 h-4 inline-block mr-1 align-text-bottom" />
                {{ new Date(item.created_at).toLocaleString('th-TH') }}
              </span>
              
              <UButton 
                v-if="item.type === 'borrow' && (item.status === 'approved' || item.status === 'pending_return')" 
                size="xs" 
                color="primary" 
                variant="soft" 
                icon="i-heroicons-calendar-days" 
                @click.stop="addToCalendar(item)"
              >
                เพิ่มลงปฏิทิน (แจ้งเตือนคืนของ)
              </UButton>
            </div>
          </div>
        </div>
      </div>
    </UCard>
  </div>
</template>
