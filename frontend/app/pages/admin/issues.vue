<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useApi } from '~/composables/useApi'

definePageMeta({
  middleware: 'admin'
})

const toast = useToast()
const { data: issuesResponse, pending, refresh } = await useApi<any>('/api/issues/index.php')

const issues = computed(() => issuesResponse.value?.issues || [])
const router = useRouter()

const filterStatus = ref('all')
const statuses = [
  { label: 'ทั้งหมด', value: 'all' },
  { label: 'รอดำเนินการ', value: 'pending' },
  { label: 'กำลังดำเนินการ', value: 'in_progress' },
  { label: 'เสร็จสิ้น', value: 'resolved' },
  { label: 'ยกเลิก', value: 'closed' }
]

const filteredIssues = computed(() => {
  if (filterStatus.value === 'all') return issues.value
  return issues.value.filter((i: any) => i.status === filterStatus.value)
})

import { useBreakpoints, breakpointsTailwind } from '@vueuse/core'
const breakpoints = useBreakpoints(breakpointsTailwind)
const isSm = breakpoints.greaterOrEqual('sm')
const isMd = breakpoints.greaterOrEqual('md')
const isLg = breakpoints.greaterOrEqual('lg')

const columns = computed(() => {
  const cols = [
    { accessorKey: 'id', header: 'รหัส' }
  ]
  
  if (isSm.value) cols.push({ accessorKey: 'user_email', header: 'ผู้แจ้ง' })
  
  cols.push({ accessorKey: 'title', header: 'หัวข้อปัญหา' })
  
  if (isMd.value) cols.push({ accessorKey: 'category', header: 'หมวดหมู่' })
  
  cols.push({ accessorKey: 'status', header: 'สถานะ' })
  cols.push({ accessorKey: 'payment_status', header: 'ชำระเงิน' })
  
  if (isLg.value) cols.push({ accessorKey: 'createdAt', header: 'วันที่แจ้ง' })
  
  cols.push({ accessorKey: 'actions', header: 'จัดการ' })
  
  return cols
})

const selectedIssue = ref<any>(null)
const isSlideoverOpen = ref(false)

const editStatus = ref('')
const adminNote = ref('')
const adminImage = ref<File | null>(null)
const paymentStatus = ref('unpaid')
const isUpdating = ref(false)
const adminLocation = ref<{lat: number, lng: number} | null>(null)
const distanceText = ref<string>('')

function calculateDistance(lat1: number, lon1: number, lat2: number, lon2: number): number {
  const R = 6371 // Radius of the earth in km
  const dLat = (lat2 - lat1) * (Math.PI/180)
  const dLon = (lon2 - lon1) * (Math.PI/180)
  const a = 
    Math.sin(dLat/2) * Math.sin(dLat/2) +
    Math.cos(lat1 * (Math.PI/180)) * Math.cos(lat2 * (Math.PI/180)) * 
    Math.sin(dLon/2) * Math.sin(dLon/2)
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)) 
  return R * c
}

const openIssue = (issue: any) => {
  selectedIssue.value = issue
  editStatus.value = issue.status
  adminNote.value = issue.admin_note || ''
  adminImage.value = null
  paymentStatus.value = issue.payment_status || 'unpaid'
  isSlideoverOpen.value = true
  
  adminLocation.value = null
  distanceText.value = ''
  
  if (issue.lat && issue.lng && navigator.geolocation) {
    navigator.geolocation.getCurrentPosition((pos) => {
      adminLocation.value = { lat: pos.coords.latitude, lng: pos.coords.longitude }
      const dist = calculateDistance(pos.coords.latitude, pos.coords.longitude, Number(issue.lat), Number(issue.lng))
      if (dist < 1) {
        distanceText.value = `${Math.round(dist * 1000)} เมตร`
      } else {
        distanceText.value = `${dist.toFixed(1)} กม.`
      }
    }, (err) => {
      console.warn("ไม่สามารถดึงตำแหน่ง Admin ได้:", err)
    })
  }
}

const handleAdminImageChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    adminImage.value = target.files[0]
  }
}

const updateIssue = async (closeOnSuccess = true) => {
  if (!selectedIssue.value) return
  
  isUpdating.value = true
  try {
    const formData = new FormData()
    formData.append('id', selectedIssue.value.id.toString())
    formData.append('status', editStatus.value)
    formData.append('admin_note', adminNote.value || '')
    formData.append('payment_status', paymentStatus.value)
    if (adminImage.value) {
      formData.append('admin_image', adminImage.value)
    }

    const baseUrl = useBaseUrl()
    await $fetch(`${baseUrl}/api/issues/update.php`, {
      method: 'POST',
      body: formData,
      credentials: 'include'
    })
    
    await refresh()

    // Redirect to Admin Map tracking if status is changed to in_progress (รับเรื่อง)
    if (editStatus.value === 'in_progress') {
       isSlideoverOpen.value = false
       router.push(`/admin/issue/${selectedIssue.value.id}`)
       return
    }
    
    if (closeOnSuccess) {
      isSlideoverOpen.value = false
    } else {
      selectedIssue.value = issues.value.find((i: any) => i.id === selectedIssue.value.id)
    }
  } catch (e) {
    console.error(e)
    toast.add({ title: 'ไม่สามารถอัปเดตได้ กรุณาตรวจสอบการชำระเงินก่อนเริ่มงาน', color: 'error' })
  } finally {
    isUpdating.value = false
  }
}

const handleAction = async (newStatus: string) => {
  if (newStatus === 'in_progress' && paymentStatus.value !== 'paid') {
    toast.add({ title: 'กรุณายืนยันการชำระเงินก่อนรับเรื่อง', color: 'warning' })
    return
  }
  editStatus.value = newStatus
  await updateIssue(false)
}

const paymentConfirmed = computed({
  get: () => paymentStatus.value === 'paid',
  set: (value: boolean) => {
    paymentStatus.value = value ? 'paid' : 'unpaid'
  }
})

const statusOptions = [
  { name: 'กำลังดำเนินการ', value: 'in_progress' },
  { name: 'เสร็จสิ้น', value: 'resolved' },
  { name: 'ยกเลิก', value: 'closed' }
]

const isCancelModalOpen = ref(false)

const handleSaveClick = () => {
  if (editStatus.value === 'closed') {
    isCancelModalOpen.value = true
  } else if (editStatus.value === 'in_progress' && paymentStatus.value !== 'paid') {
    toast.add({ title: 'กรุณายืนยันการชำระเงินก่อนเริ่มงาน', color: 'warning' })
  } else {
    updateIssue(true)
  }
}

const confirmCancel = () => {
  if (!adminNote.value) {
    useToast().add({ title: 'กรุณาระบุสาเหตุ', color: 'error' })
    return
  }
  isCancelModalOpen.value = false
  updateIssue(true)
}

const openRejectModal = () => {
  editStatus.value = 'closed'
  adminNote.value = ''
  isCancelModalOpen.value = true
}

// Pseudo-realtime background polling
const fetchSilentUpdates = async () => {
  if (isSlideoverOpen.value || isCancelModalOpen.value) return;
  
  try {
    const baseUrl = useBaseUrl();
    const data = await $fetch<any>('/api/issues/index.php', { baseURL: baseUrl, credentials: 'include' });
    if (data?.issues) issuesResponse.value = data;
  } catch (e) {
    // Silent fail
  }
}

let pollInterval: any = null
onMounted(() => {
  pollInterval = setInterval(fetchSilentUpdates, 5000)
})
onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})
</script>

<template>
  <div class="space-y-6 pb-10">
    <!-- Hidden element to force Tailwind to generate red badge classes -->
    <UBadge color="red" variant="subtle" class="hidden" />
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4" v-motion-fade>
      <h1 class="text-2xl font-bold font-kanit text-gray-900 dark:text-white">จัดการปัญหา (Manage Issues)</h1>
      
      <div class="flex items-center gap-2">
        <span class="text-sm font-kanit text-gray-500">ตัวกรองสถานะ:</span>
        <select v-model="filterStatus" class="w-40 font-kanit bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm py-1.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
          <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
        </select>
      </div>
    </div>

    <UCard :ui="{ body: { padding: '!p-0' } }" v-motion-fade :delay="100">
      <div id="admin-issues-table">
        <UTable :data="filteredIssues" :columns="columns" class="font-kanit" :loading="pending">
          <template #status-cell="{ row }">
          <UBadge :color="row.original.status === 'resolved' ? 'success' : (row.original.status === 'closed' || row.original.status === 'cancelled') ? 'red' : row.original.status === 'in_progress' ? 'primary' : 'warning'" variant="subtle">
            {{ row.original.status === 'resolved' ? 'เสร็จสิ้น' : (row.original.status === 'closed' || row.original.status === 'cancelled') ? 'ยกเลิก' : row.original.status === 'in_progress' ? 'กำลังดำเนินการ' : 'รอดำเนินการ' }}
          </UBadge>
        </template>

        <template #payment_status-cell="{ row }">
          <UBadge :color="row.original.payment_status === 'paid' ? 'success' : 'warning'" variant="subtle">
            {{ row.original.payment_status === 'paid' ? 'ชำระแล้ว' : 'รอชำระ' }}
          </UBadge>
        </template>
        
        <template #actions-cell="{ row }">
          <UButton size="xs" color="gray" variant="ghost" icon="i-heroicons-eye" @click="openIssue(row.original)">
            ดู / จัดการ
          </UButton>
        </template>
      </UTable>
      </div>
    </UCard>

    <USlideover v-model:open="isSlideoverOpen" :ui="{ content: 'w-screen max-w-md', overlay: { background: 'bg-gray-900/50 backdrop-blur-sm' } }">
      <template #content>
        <div class="flex flex-col h-full bg-white dark:bg-gray-950 font-kanit">
          <div class="p-4 flex items-center justify-between border-b border-gray-100 dark:border-gray-800">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">รายละเอียดปัญหา #{{ selectedIssue?.id }}</h2>
            <div class="flex items-center gap-2">
              <UButton v-if="selectedIssue" :to="`/admin/issue/${selectedIssue.id}`" icon="i-heroicons-arrows-pointing-out" color="primary" variant="soft" size="sm">ดูแบบเต็ม</UButton>
              <UButton icon="i-heroicons-x-mark" color="gray" variant="ghost" @click="isSlideoverOpen = false" />
            </div>
          </div>
          
          <div class="flex-1 overflow-y-auto p-6 space-y-6">
            <div v-if="selectedIssue">
              <div>
                <p class="text-sm text-gray-500">ผู้แจ้ง</p>
                <p class="font-medium">{{ selectedIssue.user_email }}</p>
              </div>
              
              <div class="mt-4">
                <p class="text-sm text-gray-500">หัวข้อปัญหา</p>
                <p class="font-medium text-lg">{{ selectedIssue.title }}</p>
              </div>

              <div class="grid grid-cols-2 gap-4 mt-4">
                <div>
                  <p class="text-sm text-gray-500">หมวดหมู่</p>
                  <UBadge color="gray" class="mt-1">{{ selectedIssue.category }}</UBadge>
                </div>
                <div>
                  <p class="text-sm text-gray-500">สถานที่</p>
                  <p class="font-medium">{{ selectedIssue.location || '-' }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-500">ชำระเงิน</p>
                  <UBadge :color="paymentStatus === 'paid' ? 'success' : 'warning'" variant="subtle" class="mt-1">
                    {{ paymentStatus === 'paid' ? 'ชำระแล้ว' : 'รอชำระ' }}
                  </UBadge>
                </div>
              </div>

              <div v-if="selectedIssue.lat && selectedIssue.lng" class="mt-4">
                <div class="flex items-center justify-between mb-2">
                  <p class="text-sm text-gray-500">พิกัดบนแผนที่</p>
                  <div class="flex items-center gap-2">
                    <UButton :to="`https://www.google.com/maps/search/?api=1&query=${selectedIssue.lat},${selectedIssue.lng}`" target="_blank" size="xs" color="gray" variant="soft" icon="i-heroicons-arrow-top-right-on-square">
                      เปิดใน Google Maps
                    </UButton>
                    <UBadge v-if="distanceText" color="blue" variant="subtle" class="font-kanit animate-pulse">
                      <UIcon name="i-heroicons-map-pin" class="w-4 h-4 mr-1" />
                      ห่างจากคุณ: {{ distanceText }}
                    </UBadge>
                  </div>
                </div>
                <IssueMap 
                  :modelValue="{ lat: Number(selectedIssue.lat), lng: Number(selectedIssue.lng) }" 
                  :adminLocation="adminLocation"
                  :readonly="true" 
                  height="200px" 
                />
              </div>

              <div class="mt-4">
                <p class="text-sm text-gray-500">รายละเอียด</p>
                <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded-lg mt-1 whitespace-pre-wrap text-sm">
                  {{ selectedIssue.description }}
                </div>
              </div>

              <div v-if="selectedIssue.image_path" class="mt-4">
                <p class="text-sm text-gray-500 mb-2">รูปภาพประกอบ</p>
                <img :src="`${useBaseUrl()}${selectedIssue.image_path}`" class="w-full rounded-lg border border-gray-200" alt="Issue Attachment" />
              </div>

              <USeparator class="my-6" />
              
              <h3 class="font-bold text-gray-900 dark:text-white mb-4">ส่วนจัดการของ Admin</h3>
              
              <div v-if="selectedIssue.status === 'pending'" class="text-center p-6 border border-dashed border-gray-300 dark:border-gray-700 rounded-xl bg-gray-50/50 dark:bg-gray-900/30">
                <UIcon name="i-heroicons-inbox-arrow-down" class="w-12 h-12 text-gray-400 mb-2" />
                <h4 class="font-medium text-gray-900 dark:text-white text-lg">มีรายการแจ้งซ่อมใหม่</h4>
                <p class="text-sm text-gray-500 mt-1 mb-4">คุณต้องการรับเรื่องนี้เพื่อดำเนินการซ่อมแซมต่อไปหรือไม่?</p>
                <label class="flex items-center justify-center gap-2 p-3 rounded-lg bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-sm font-medium text-gray-700 dark:text-gray-200">
                  <input v-model="paymentConfirmed" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                  ยืนยันว่าชำระเงินแล้ว
                </label>
                <div class="mt-4">
                  <label class="block text-sm font-medium text-gray-700 mb-1 text-left">หมายเหตุ (ถ้ามี)</label>
                  <UTextarea v-model="adminNote" placeholder="ระบุเหตุผลหากปฏิเสธ หรือหมายเหตุรับเรื่อง..." :rows="2" />
                </div>
              </div>

              <div v-else-if="selectedIssue.status === 'in_progress'" class="space-y-4 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-800">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">อัปเดตสถานะ</label>
                  <select v-model="editStatus" class="w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 font-kanit">
                    <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.name }}</option>
                  </select>
                </div>
                
                <div class="mt-3">
                  <label class="block text-sm font-medium text-gray-700 mb-1">หมายเหตุ (สำหรับแจ้งผู้ใช้)</label>
                  <UTextarea v-model="adminNote" placeholder="ระบุเหตุผล หรือรายละเอียดการซ่อมแซม เช่น ได้เปลี่ยนสายใหม่แล้ว..." :rows="3" />
                </div>
                
                <div class="mt-3">
                  <label class="block text-sm font-medium text-gray-700 mb-1">รูปภาพหลังซ่อมเสร็จ (ถ้ามี)</label>
                  <div class="mt-1 flex items-center gap-3">
                    <label class="relative cursor-pointer bg-white dark:bg-gray-800 py-2 px-4 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                      <span>เลือกไฟล์</span>
                      <input type="file" @change="handleAdminImageChange" accept="image/*" class="sr-only" />
                    </label>
                    <span class="text-sm text-gray-500 truncate max-w-[200px]">{{ adminImage ? adminImage.name : 'ไม่ได้เลือกไฟล์' }}</span>
                  </div>
                </div>
              </div>
              
              <div v-else class="space-y-4 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-800">
                <h4 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                  <UIcon name="i-heroicons-check-circle" class="w-5 h-5 text-success" v-if="selectedIssue.status === 'resolved'" />
                  <UIcon name="i-heroicons-x-circle" class="w-5 h-5 text-gray-500" v-else />
                  {{ selectedIssue.status === 'resolved' ? 'รายการนี้ซ่อมแซมเสร็จสิ้นแล้ว' : 'รายการนี้ถูกปิด/ยกเลิกแล้ว' }}
                </h4>
                <div v-if="selectedIssue.admin_note" class="mt-2">
                  <p class="text-sm text-gray-500 font-medium">หมายเหตุจากเจ้าหน้าที่:</p>
                  <div class="bg-white dark:bg-gray-900 p-3 rounded-lg mt-1 border border-gray-200 dark:border-gray-800 text-sm whitespace-pre-wrap">
                    {{ selectedIssue.admin_note }}
                  </div>
                </div>
                <div v-if="selectedIssue.admin_image_path" class="mt-3">
                  <p class="text-sm text-gray-500 mb-2 font-medium">รูปภาพหลังดำเนินการ:</p>
                  <img :src="`${useBaseUrl()}${selectedIssue.admin_image_path}`" class="w-full max-w-sm rounded-lg border border-gray-200" alt="Admin Attachment" />
                </div>
              </div>
            </div>
          </div>
          
          <div v-if="selectedIssue?.status === 'pending'" class="p-4 border-t border-gray-100 dark:border-gray-800 flex gap-3 bg-white dark:bg-gray-950">
            <UButton color="red" variant="soft" class="flex-1 justify-center" @click="openRejectModal">ปฏิเสธการซ่อม</UButton>
            <UButton color="primary" class="flex-1 justify-center" :loading="isUpdating" :disabled="paymentStatus !== 'paid'" @click="handleAction('in_progress')">รับเรื่องดำเนินการ</UButton>
          </div>
          <div v-else-if="selectedIssue?.status === 'in_progress'" class="p-4 border-t border-gray-100 dark:border-gray-800 flex gap-3 bg-white dark:bg-gray-950">
            <UButton color="gray" variant="ghost" class="flex-1 justify-center" @click="isSlideoverOpen = false">ปิดหน้าต่าง</UButton>
            <UButton color="primary" class="flex-1 justify-center" :loading="isUpdating" @click="handleSaveClick">บันทึกอัปเดตสถานะ</UButton>
          </div>
          <div v-else class="p-4 border-t border-gray-100 dark:border-gray-800 flex gap-3 bg-white dark:bg-gray-950">
            <UButton color="gray" variant="ghost" class="flex-1 justify-center" @click="isSlideoverOpen = false">ปิดหน้าต่าง</UButton>
          </div>
        </div>
      </template>
    </USlideover>

    <!-- Cancel Confirmation Modal -->
    <UModal v-model:open="isCancelModalOpen">
      <template #content>
        <UCard class="font-kanit border border-red-100 dark:border-red-900 shadow-xl shadow-red-900/10" :ui="{ ring: 'ring-1 ring-red-500 dark:ring-red-400' }">
          <template #header>
            <div class="flex items-center justify-between">
              <h3 class="text-base font-bold text-red-600 dark:text-red-500 flex items-center gap-2">
                <UIcon name="i-heroicons-exclamation-triangle-solid" class="w-5 h-5" />
                ยืนยันการยกเลิกรายการนี้?
              </h3>
              <UButton color="gray" variant="ghost" icon="i-heroicons-x-mark" class="-my-1" @click="isCancelModalOpen = false" />
            </div>
          </template>
          
          <div class="py-4">
            <p class="text-gray-800 dark:text-gray-200 mb-4 text-center">
              คุณกำลังจะเปลี่ยนสถานะเป็น <span class="font-bold text-red-600 dark:text-red-400">"ยกเลิก"</span>
            </p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">โปรดระบุสาเหตุของการยกเลิกให้ผู้แจ้งทราบ:</p>
            <UTextarea v-model="adminNote" placeholder="เช่น เครื่องมือไม่พร้อม, ซ้ำซ้อน, ไม่พบอุปกรณ์..." :rows="3" autofocus required class="w-full" />
          </div>

          <template #footer>
            <div class="flex justify-end gap-3 w-full">
              <UButton variant="soft" color="gray" @click="isCancelModalOpen = false" class="px-6 font-medium">กลับไปแก้ไข</UButton>
              <UButton color="red" variant="solid" @click="confirmCancel" :loading="isUpdating" class="px-6 font-bold shadow-lg shadow-red-500/30 !bg-red-600 !text-white hover:!bg-red-700 transition-all">ยืนยันการยกเลิก</UButton>
            </div>
          </template>
        </UCard>
      </template>
    </UModal>
  </div>
</template>
