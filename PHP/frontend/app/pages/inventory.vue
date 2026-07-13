<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { useApi } from '~/composables/useApi'

definePageMeta({
  middleware: 'auth'
})

const toast = useToast()
const { data: inventory, refresh: refreshInventory, pending: inventoryPending } = await useApi<any>('/api/inventory/index')
const { data: borrowsData, refresh: refreshBorrows, pending: borrowsPending } = await useApi<any>('/api/borrows/index')

const pending = computed(() => inventoryPending.value || borrowsPending.value)

const equipments = computed(() => inventory.value?.data?.equipment || inventory.value?.equipment || [])
const borrows = computed(() => borrowsData.value?.data?.borrows || borrowsData.value?.borrows || [])

const selectedItem = ref<any>(null)
const isModalOpen = ref(false)
const borrowForm = ref({
  quantity: 1,
  pickupDate: '',
  returnDate: '',
  reason: ''
})
const isSubmitting = ref(false)

const isCalendarModalOpen = ref(false)
const calendarEventInfo = ref({
  title: '',
  description: '',
  date: ''
})

const generateGoogleCalendarLink = () => {
  const { title, description, date } = calendarEventInfo.value
  const d = new Date(date)
  const start = d.toISOString().replace(/-|:|\.\d\d\d/g, "")
  const end = start
  const url = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(title)}&details=${encodeURIComponent(description)}&dates=${start}/${end}`
  window.open(url, '_blank')
}

const generateICSFile = () => {
  const { title, description, date } = calendarEventInfo.value
  const d = new Date(date)
  const start = d.toISOString().replace(/-|:|\.\d\d\d/g, "")
  
  const icsContent = `BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
DTSTART:${start}
DTEND:${start}
SUMMARY:${title}
DESCRIPTION:${description}
END:VEVENT
END:VCALENDAR`
  
  const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = 'return_equipment.ics'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const openBorrowModal = (item: any) => {
  selectedItem.value = item
  borrowForm.value = {
    quantity: 1,
    pickupDate: minDate.value,
    returnDate: '',
    reason: ''
  }
  isModalOpen.value = true
}

const submitBorrow = async () => {
  if (!selectedItem.value) return
  
  if (borrowForm.value.quantity > selectedItem.value.available) {
    toast.add({
      title: 'ข้อมูลไม่ถูกต้อง',
      description: 'จำนวนที่ยืมเกินกว่าคงเหลือ',
      color: 'error'
    })
    return
  }

  if (!borrowForm.value.pickupDate) {
    toast.add({ title: 'ข้อมูลไม่ครบ', description: 'กรุณาระบุวันที่ต้องการมารับอุปกรณ์', color: 'error' })
    return
  }

  if (!borrowForm.value.returnDate) {
    toast.add({ title: 'ข้อมูลไม่ครบ', description: 'กรุณาระบุวันที่คืนอุปกรณ์', color: 'error' })
    return
  }

  // Validate return date is within 7 days of pickup date
  const pickupD = new Date(borrowForm.value.pickupDate)
  const returnD = new Date(borrowForm.value.returnDate)
  const diffMs = returnD.getTime() - pickupD.getTime()
  const diffDays = diffMs / (1000 * 60 * 60 * 24)

  if (diffDays < 0 || diffDays > 7) {
    toast.add({
      title: 'ข้อมูลไม่ถูกต้อง',
      description: 'วันที่คืนต้องอยู่ภายใน 7 วันนับจากวันที่มารับ',
      color: 'error'
    })
    return
  }

  isSubmitting.value = true
  try {
    const baseUrl = useBaseUrl()
    await $fetch(`${baseUrl}/api/borrows/create`, {
      method: 'POST',
      body: {
        equipment_id: selectedItem.value.id,
        quantity: borrowForm.value.quantity,
        borrow_date: borrowForm.value.pickupDate,
        return_date: borrowForm.value.returnDate,
        reason: borrowForm.value.reason
      },
      credentials: 'include'
    })
    isModalOpen.value = false
    await refreshInventory()
    await refreshBorrows()
    toast.add({
      title: 'ส่งคำขอยืมสำเร็จ!',
      description: `ยืม "${selectedItem.value.name}" จำนวน ${borrowForm.value.quantity} ชิ้น กรุณารอเจ้าหน้าที่อนุมัติ`,
      color: 'success',
      icon: 'i-heroicons-check-circle'
    })
    
    calendarEventInfo.value = {
      title: `กำหนดคืน: ${selectedItem.value.name}`,
      description: `กรุณานำอุปกรณ์ ${selectedItem.value.name} (จำนวน ${borrowForm.value.quantity}) ไปคืนที่แผนก IT`,
      date: borrowForm.value.returnDate
    }
    isCalendarModalOpen.value = true

  } catch (err: any) {
    console.error(err)
    toast.add({
      title: 'เกิดข้อผิดพลาด',
      description: err?.data?.error || 'ไม่สามารถส่งคำขอยืมได้ กรุณาลองใหม่',
      color: 'error',
      icon: 'i-heroicons-exclamation-circle'
    })
  } finally {
    isSubmitting.value = false
  }
}

const borrowColumns = [
  { accessorKey: 'id', header: 'รหัส' },
  { accessorKey: 'equipment_name', header: 'อุปกรณ์' },
  { accessorKey: 'quantity', header: 'จำนวน' },
  { accessorKey: 'borrow_date', header: 'วันที่ยืม' },
  { accessorKey: 'return_date', header: 'กำหนดคืน' },
  { accessorKey: 'status', header: 'สถานะ' },
  { accessorKey: 'actions', header: '' }
]

const isOverdue = (row: any) => {
  if (row.status !== 'approved') return false
  if (!row.return_date) return false
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const returnDate = new Date(row.return_date)
  returnDate.setHours(0, 0, 0, 0)
  return returnDate < today
}

const daysOverdue = (row: any) => {
  if (!row.return_date) return 0
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const returnDate = new Date(row.return_date)
  returnDate.setHours(0, 0, 0, 0)
  return Math.floor((today.getTime() - returnDate.getTime()) / (1000 * 60 * 60 * 24))
}

const isDueSoon = (row: any) => {
  if (row.status !== 'approved') return false
  if (!row.return_date) return false
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const returnDate = new Date(row.return_date)
  returnDate.setHours(0, 0, 0, 0)
  const diffDays = Math.floor((returnDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24))
  return diffDays >= 0 && diffDays <= 1
}

const daysUntilDue = (row: any) => {
  if (!row.return_date) return 0
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const returnDate = new Date(row.return_date)
  returnDate.setHours(0, 0, 0, 0)
  return Math.floor((returnDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24))
}

const minDate = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

const maxPickupDate = computed(() => {
  const today = new Date()
  today.setDate(today.getDate() + 30)
  return today.toISOString().split('T')[0]
})

// return date min = pickup date, max = pickup date + 7
const minReturnDate = computed(() => {
  return borrowForm.value.pickupDate || minDate.value
})

const maxReturnDate = computed(() => {
  if (!borrowForm.value.pickupDate) return minDate.value
  const pickup = new Date(borrowForm.value.pickupDate)
  pickup.setDate(pickup.getDate() + 7)
  return pickup.toISOString().split('T')[0]
})

// reset return date when pickup date changes
watch(() => borrowForm.value.pickupDate, () => {
  borrowForm.value.returnDate = ''
})

const isReturning = ref(false)

const requestReturn = async (borrowId: number) => {
  isReturning.value = true
  try {
    const formData = new FormData()
    formData.append('id', borrowId.toString())
    formData.append('status', 'pending_return')

    const baseUrl = useBaseUrl()
    await $fetch(`${baseUrl}/api/borrows/update`, {
      method: 'POST',
      body: formData,
      credentials: 'include'
    })
    await refreshBorrows()
    toast.add({
      title: 'แจ้งคืนอุปกรณ์สำเร็จ!',
      description: 'กรุณารอเจ้าหน้าที่ตรวจสอบและยืนยันการรับคืน',
      color: 'success',
      icon: 'i-heroicons-check-circle'
    })
  } catch (err: any) {
    console.error(err)
    toast.add({
      title: 'เกิดข้อผิดพลาด',
      description: err?.data?.error || 'ไม่สามารถแจ้งคืนได้',
      color: 'error',
      icon: 'i-heroicons-exclamation-circle'
    })
  } finally {
    isReturning.value = false
  }
}

// Pseudo-realtime background polling
const fetchSilentUpdates = async () => {
  if (isModalOpen.value || isCalendarModalOpen.value) return;
  
  try {
    const baseUrl = useBaseUrl();
    const [equipData, borrowsRes] = await Promise.all([
      $fetch<any>('/api/inventory/index', { baseURL: baseUrl, credentials: 'include' }),
      $fetch<any>('/api/borrows/index', { baseURL: baseUrl, credentials: 'include' })
    ]);
    if (equipData?.data?.equipment || equipData?.equipment) inventory.value = equipData;
    if (borrowsRes?.data?.borrows || borrowsRes?.borrows) borrowsData.value = borrowsRes;
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
  <div class="space-y-12 pb-10">
    <div>
      <div class="flex justify-between items-center mb-6" v-motion-fade>
        <h1 class="text-2xl font-bold font-kanit text-gray-900 dark:text-white">ยืม-คืนอุปกรณ์ (Inventory)</h1>
        <UButton icon="i-heroicons-arrow-path" variant="ghost" color="gray" @click="refreshInventory(); refreshBorrows()" />
      </div>

      <div v-if="equipments.length === 0" class="text-center py-12 glass rounded-xl border border-gray-200 dark:border-gray-800">
        <UIcon name="i-heroicons-inbox" class="w-16 h-16 mx-auto text-gray-400 mb-4" />
        <p class="text-gray-500 font-kanit">ไม่พบรายการอุปกรณ์ในคลัง</p>
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <UCard v-for="(item, index) in equipments" :key="item.id" v-motion-slide-visible-bottom :delay="index * 100" class="glass-card flex flex-col h-full hover:shadow-lg transition-all duration-300 group overflow-hidden border-transparent hover:border-coral-200 dark:hover:border-coral-900/50" :ui="{ body: { padding: 'sm:p-4' } }">
          <div class="aspect-video bg-gray-50/50 dark:bg-gray-800/50 -mx-4 -mt-4 mb-4 flex items-center justify-center relative overflow-hidden group-hover:bg-gray-100/50 dark:group-hover:bg-gray-700/50 transition-colors backdrop-blur-sm">
            <img v-if="item.image_path" :src="`${useBaseUrl()}${item.image_path}`" class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-500" />
            <UIcon v-else name="i-heroicons-cube" class="w-16 h-16 text-gray-400 group-hover:scale-110 transition-transform duration-500" />
            
              <div class="absolute top-3 right-3 flex gap-2">
                <UBadge v-if="item.available === 0" color="red" variant="solid" class="shadow-sm">หมด</UBadge>
                <UBadge v-else-if="item.available <= 3" color="orange" variant="solid" class="shadow-sm text-white">ใกล้หมด</UBadge>
                <UBadge v-else color="success" variant="solid" class="shadow-sm">พร้อมให้ยืม</UBadge>
              </div>
          </div>
          
          <div class="flex-1">
            <h3 class="font-bold text-lg font-kanit text-gray-900 dark:text-white line-clamp-1" :title="item.name">{{ item.name }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2 mt-1 min-h-[2.5rem]">{{ item.description || 'ไม่มีรายละเอียดเพิ่มเติม' }}</p>
          </div>
          
          <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <div class="flex flex-col">
              <span class="text-xs text-gray-500 font-kanit">คงเหลือ</span>
              <span class="text-lg font-bold font-inter" :class="item.available > 0 ? 'text-coral-600 dark:text-coral-400' : 'text-red-500'">{{ item.available }}</span>
            </div>
            <UButton color="primary" @click="openBorrowModal(item)" :disabled="item.available <= 0" class="px-6 font-kanit shadow-sm hover:shadow-md transition-shadow !bg-coral-500 hover:!bg-coral-600 !text-white rounded-lg">
              ยืมอุปกรณ์
            </UButton>
          </div>
        </UCard>
      </div>
    </div>

    <!-- History Section -->
    <div v-motion-slide-visible-bottom>
      <h2 class="text-xl font-bold font-kanit mb-4 text-gray-900 dark:text-white">ประวัติการยืมของคุณ</h2>
      <UCard :ui="{ body: { padding: '!p-0' } }">
        <!-- Desktop View -->
        <div class="hidden md:block overflow-x-auto w-full">
          <UTable :data="borrows" :columns="borrowColumns" class="font-kanit min-w-[800px]">
            <!-- Return Date Cell with overdue/due-soon highlight -->
            <template #return_date-cell="{ row }">
              <div class="flex items-center gap-2">
                <span :class="isOverdue(row.original) ? 'text-red-600 font-bold' : isDueSoon(row.original) ? 'text-orange-500 font-semibold' : ''">
                  {{ row.original.return_date || '-' }}
                </span>
                <UBadge v-if="isOverdue(row.original)" color="red" variant="solid" size="xs" class="animate-pulse flex items-center gap-1">
                  <UIcon name="i-heroicons-exclamation-triangle" class="w-4 h-4" /> เกินกำหนด! {{ daysOverdue(row.original) }} วัน
                </UBadge>
                <UBadge v-else-if="isDueSoon(row.original)" color="orange" variant="subtle" size="xs" class="flex items-center gap-1">
                  <UIcon name="i-heroicons-bell-alert" class="w-4 h-4" /> {{ daysUntilDue(row.original) === 0 ? 'ครบกำหนดวันนี้!' : 'คืนพรุ่งนี้!' }}
                </UBadge>
              </div>
            </template>

            <template #status-cell="{ row }">
              <div class="flex items-center gap-2">
                <UBadge 
                  :color="isOverdue(row.original) ? 'red' : isDueSoon(row.original) ? 'orange' : row.original.status === 'approved' ? 'success' : row.original.status === 'returned' ? 'gray' : row.original.status === 'rejected' ? 'error' : row.original.status === 'pending_return' ? 'info' : 'warning'" 
                  variant="subtle"
                >
                  {{ isOverdue(row.original) ? 'เกินกำหนด!' : isDueSoon(row.original) ? (daysUntilDue(row.original) === 0 ? 'ครบกำหนดวันนี้' : 'ใกล้ครบกำหนด') : row.original.status === 'approved' ? 'กำลังใช้งาน' : row.original.status === 'returned' ? 'คืนแล้ว' : row.original.status === 'rejected' ? 'ไม่อนุมัติ' : row.original.status === 'pending_return' ? 'รอตรวจสอบการคืน' : 'รออนุมัติ' }}
                </UBadge>
                <UTooltip v-if="row.original.admin_note" :text="row.original.admin_note" :ui="{ width: 'max-w-[250px]' }">
                  <UIcon name="i-heroicons-information-circle" class="w-4 h-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors cursor-help" />
                </UTooltip>
              </div>
            </template>
            
            <template #actions-cell="{ row }">
              <UButton v-if="row.original.status === 'approved'" size="xs" :color="isOverdue(row.original) ? 'red' : isDueSoon(row.original) ? 'orange' : 'primary'" variant="soft" icon="i-heroicons-arrow-uturn-left" :loading="isReturning" @click="requestReturn(row.original.id)">
                {{ isOverdue(row.original) ? 'คืนด่วน!' : isDueSoon(row.original) ? 'คืนเร็วๆนะ!' : 'แจ้งคืนอุปกรณ์' }}
              </UButton>
              <span v-else-if="row.original.status === 'pending_return'" class="text-xs text-blue-500 font-medium">รอเจ้าหน้าที่ตรวจสอบ</span>
            </template>
          </UTable>
        </div>

        <!-- Mobile View -->
        <div class="md:hidden flex flex-col divide-y divide-gray-100 dark:divide-gray-800 w-full font-kanit">
          <div v-if="pending" class="p-8 text-center text-gray-500">กำลังโหลด...</div>
          <div v-else-if="borrows.length === 0" class="p-8 text-center text-gray-500 flex flex-col items-center">
             <UIcon name="i-heroicons-inbox" class="w-12 h-12 mb-2 text-gray-200 dark:text-gray-700" />
             ยังไม่มีประวัติการยืม
          </div>
          <div v-else v-for="borrow in borrows" :key="borrow.id" class="p-4 flex flex-col gap-3">
            <div class="flex justify-between items-start gap-2">
              <div class="min-w-0 flex-1">
                <div class="font-bold text-gray-900 dark:text-white truncate text-base">{{ borrow.equipment_name }} <span class="text-sm font-normal text-gray-500">(x{{ borrow.quantity }})</span></div>
                <div v-if="borrow.admin_note" class="text-xs text-amber-600 dark:text-amber-400 mt-0.5 flex items-start gap-1">
                  <UIcon name="i-heroicons-information-circle" class="w-3.5 h-3.5 shrink-0 mt-px" />
                  <span class="line-clamp-1">{{ borrow.admin_note }}</span>
                </div>
              </div>
              <UBadge 
                :color="isOverdue(borrow) ? 'red' : isDueSoon(borrow) ? 'orange' : borrow.status === 'approved' ? 'success' : borrow.status === 'returned' ? 'gray' : borrow.status === 'rejected' ? 'error' : borrow.status === 'pending_return' ? 'info' : 'warning'" 
                variant="subtle" size="xs" class="shrink-0"
              >
                {{ isOverdue(borrow) ? 'เกินกำหนด!' : isDueSoon(borrow) ? (daysUntilDue(borrow) === 0 ? 'ครบกำหนดวันนี้' : 'ใกล้ครบกำหนด') : borrow.status === 'approved' ? 'กำลังใช้งาน' : borrow.status === 'returned' ? 'คืนแล้ว' : borrow.status === 'rejected' ? 'ไม่อนุมัติ' : borrow.status === 'pending_return' ? 'รอตรวจสอบการคืน' : 'รออนุมัติ' }}
              </UBadge>
            </div>
            
            <div class="grid grid-cols-2 gap-2 text-sm bg-gray-50 dark:bg-gray-800/50 p-2.5 rounded-lg border border-gray-100 dark:border-gray-800">
              <div>
                <div class="text-xs text-gray-500 mb-0.5 flex items-center gap-1"><UIcon name="i-heroicons-calendar-days" class="w-3.5 h-3.5" />มารับอุปกรณ์</div>
                <div class="font-medium text-gray-900 dark:text-gray-200 pl-4.5">{{ borrow.borrow_date }}</div>
              </div>
              <div>
                <div class="text-xs text-gray-500 mb-0.5 flex items-center gap-1"><UIcon name="i-heroicons-arrow-uturn-left" class="w-3.5 h-3.5" />กำหนดคืน</div>
                <div :class="isOverdue(borrow) ? 'font-bold text-red-600 pl-4.5' : isDueSoon(borrow) ? 'font-semibold text-orange-500 pl-4.5' : 'font-medium text-gray-900 dark:text-gray-200 pl-4.5'">
                  {{ borrow.return_date || '-' }}
                </div>
              </div>
            </div>

            <div v-if="isOverdue(borrow)" class="text-xs text-red-700 dark:text-red-400 flex items-center gap-1.5 font-medium bg-red-50 dark:bg-red-900/20 p-2 rounded-lg border border-red-100 dark:border-red-900/50">
              <UIcon name="i-heroicons-exclamation-triangle" class="w-4 h-4 shrink-0" /> เกินกำหนด {{ daysOverdue(borrow) }} วัน!
            </div>
            <div v-else-if="isDueSoon(borrow)" class="text-xs text-orange-700 dark:text-orange-400 flex items-center gap-1.5 font-medium bg-orange-50 dark:bg-orange-900/20 p-2 rounded-lg border border-orange-100 dark:border-orange-900/50">
              <UIcon name="i-heroicons-bell-alert" class="w-4 h-4 shrink-0" /> {{ daysUntilDue(borrow) === 0 ? 'ครบกำหนดวันนี้!' : 'คืนพรุ่งนี้!' }}
            </div>

            <UButton v-if="borrow.status === 'approved'" block size="sm" :color="isOverdue(borrow) ? 'red' : isDueSoon(borrow) ? 'orange' : 'primary'" variant="soft" icon="i-heroicons-arrow-uturn-left" :loading="isReturning" @click="requestReturn(borrow.id)">
              {{ isOverdue(borrow) ? 'คืนด่วน!' : isDueSoon(borrow) ? 'คืนเร็วๆนะ!' : 'แจ้งคืนอุปกรณ์' }}
            </UButton>
            <div v-else-if="borrow.status === 'pending_return'" class="text-xs text-blue-500 font-medium text-center bg-blue-50 dark:bg-blue-900/20 py-2 rounded-lg border border-blue-100 dark:border-blue-900/50">
              รอเจ้าหน้าที่ตรวจสอบการคืนอุปกรณ์
            </div>
          </div>
        </div>
      </UCard>
    </div>

    <!-- Borrow Modal -->
    <UModal v-model:open="isModalOpen">
      <template #content>
        <UCard v-if="selectedItem" class="font-kanit w-full">
          <template #header>
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-bold text-gray-900 dark:text-white">ยืนยันการยืมอุปกรณ์</h3>
              <UButton color="gray" variant="ghost" icon="i-heroicons-x-mark" class="-my-1" @click="isModalOpen = false" />
            </div>
          </template>
          
          <div class="max-h-[70vh] overflow-y-auto px-1">
            <form @submit.prevent="submitBorrow" class="space-y-6 py-2">
              <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg flex items-center gap-4">
                <div class="w-12 h-12 bg-white dark:bg-gray-700 rounded-md flex items-center justify-center flex-shrink-0">
                  <UIcon name="i-heroicons-cube" class="w-6 h-6 text-coral-500" />
                </div>
                <div>
                  <p class="font-bold text-gray-900 dark:text-white">{{ selectedItem.name }}</p>
                  <p class="text-sm text-gray-500">คงเหลือในระบบ: <span class="font-bold text-coral-600">{{ selectedItem.available }}</span> รายการ</p>
                </div>
              </div>

              <div class="space-y-4">
                <UFormField label="จำนวนที่ยืม" name="quantity">
                  <UInput type="number" v-model.number="borrowForm.quantity" min="1" :max="selectedItem.available" class="w-full text-lg text-center" size="lg" required />
                </UFormField>

                <div class="grid grid-cols-2 gap-4">
                  <UFormField label="วันที่ต้องการมารับอุปกรณ์" name="pickupDate">
                    <UInput type="date" icon="i-heroicons-calendar-days" v-model="borrowForm.pickupDate" :min="minDate" :max="maxPickupDate" class="w-full" size="lg" required />
                    <p class="text-xs text-gray-400 mt-1">จองล่วงหน้าได้สูงสุด 30 วัน</p>
                  </UFormField>
                  <UFormField label="กำหนดคืน (ไม่เกิน 7 วันนับจากมารับ)" name="returnDate">
                    <UInput type="date" icon="i-heroicons-arrow-uturn-left" v-model="borrowForm.returnDate" :min="minReturnDate" :max="maxReturnDate" :disabled="!borrowForm.pickupDate" class="w-full" size="lg" required />
                    <p class="text-xs text-gray-400 mt-1">ระบุวันมารับก่อน</p>
                  </UFormField>
                </div>
              </div>

              <UFormField label="เหตุผลที่ยืม / วัตถุประสงค์" name="reason">
                <UTextarea v-model="borrowForm.reason" placeholder="ระบุเหตุผลเพื่อประกอบการอนุมัติ..." :rows="3" required />
              </UFormField>

              <div class="bg-blue-50 dark:bg-blue-900/30 p-3 rounded-lg flex gap-3 text-blue-700 dark:text-blue-300 text-sm">
                <UIcon name="i-heroicons-information-circle" class="w-5 h-5 flex-shrink-0" />
                <div>
                  <p>หลังจากส่งคำขอแล้ว กรุณารอเจ้าหน้าที่อนุมัติก่อนมารับอุปกรณ์ที่ห้อง IT</p>
                  <p v-if="borrowForm.pickupDate !== minDate" class="mt-1 font-semibold text-indigo-600 dark:text-indigo-400 flex items-center gap-1.5">
                    <UIcon name="i-heroicons-calendar-days" class="w-5 h-5 flex-shrink-0" /> จองล่วงหน้า: มารับวันที่ {{ borrowForm.pickupDate ? new Date(borrowForm.pickupDate).toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' }) : '' }}
                  </p>
                </div>
              </div>

              <div class="flex justify-end gap-3 w-full pt-4 border-t border-gray-100 dark:border-gray-800">
                <UButton variant="ghost" color="gray" @click="isModalOpen = false" class="px-6">ยกเลิก</UButton>
                <UButton type="submit" color="primary" :loading="isSubmitting" class="px-6 font-semibold shadow-sm !bg-coral-500 hover:!bg-coral-600 !text-white">ส่งคำขอยืม</UButton>
              </div>
            </form>
          </div>
        </UCard>
      </template>
    </UModal>

    <!-- Calendar Sync Modal -->
    <UModal v-model:open="isCalendarModalOpen">
      <template #content>
        <UCard class="font-kanit text-center glass">
          <div class="py-6 flex flex-col items-center">
            <div class="w-16 h-16 bg-green-100 text-green-500 rounded-full flex items-center justify-center mb-4 shadow-sm shadow-green-500/20">
              <UIcon name="i-heroicons-check" class="w-8 h-8" />
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ส่งคำขอยืมสำเร็จ!</h3>
            <p class="text-sm text-gray-500 mb-6">อย่าลืมเพิ่มกำหนดวันคืนอุปกรณ์ลงในปฏิทินของคุณ เพื่อป้องกันการลืมคืน</p>
            
            <div class="flex flex-col gap-3 w-full max-w-xs">
              <UButton color="primary" variant="soft" icon="i-heroicons-calendar" block @click="generateGoogleCalendarLink" class="justify-center font-medium">
                เพิ่มลง Google Calendar
              </UButton>
              <UButton color="gray" variant="soft" icon="i-heroicons-arrow-down-tray" block @click="generateICSFile" class="justify-center font-medium">
                ดาวน์โหลด .ics (Apple/Outlook)
              </UButton>
              <UButton color="gray" variant="ghost" block class="mt-2 justify-center" @click="isCalendarModalOpen = false">
                ปิดหน้าต่าง
              </UButton>
            </div>
          </div>
        </UCard>
      </template>
    </UModal>
  </div>
</template>
