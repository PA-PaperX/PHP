<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useApi } from '~/composables/useApi'

definePageMeta({
  middleware: 'admin'
})

// Tabs
const items = [{
  label: 'รายการอุปกรณ์ในคลัง',
  icon: 'i-heroicons-cube',
  slot: 'equipment'
}, {
  label: 'คำขอยืม-คืน',
  icon: 'i-heroicons-clipboard-document-list',
  slot: 'borrows'
}]

const categories = [
  { label: 'ฮาร์ดแวร์ (Hardware)', value: 'ฮาร์ดแวร์ (Hardware)' },
  { label: 'ซอฟต์แวร์ (Software)', value: 'ซอฟต์แวร์ (Software)' },
  { label: 'เครือข่าย (Network)', value: 'เครือข่าย (Network)' },
  { label: 'อื่นๆ (Other)', value: 'อื่นๆ (Other)' }
]

// Data fetching
const { data: equipResponse, pending: equipPending, refresh: refreshEquip } = await useApi<any>('/api/inventory/index')
const { data: borrowsResponse, pending: borrowsPending, refresh: refreshBorrows } = await useApi<any>('/api/borrows/index')

const equipments = computed(() => equipResponse.value?.data?.equipment || equipResponse.value?.equipment || [])
const borrows = computed(() => borrowsResponse.value?.data?.borrows || borrowsResponse.value?.borrows || [])

// Equipment Table
const equipColumns = [
  { accessorKey: 'id', header: 'รหัส' },
  { accessorKey: 'image', header: 'รูปภาพ' },
  { accessorKey: 'name', header: 'ชื่ออุปกรณ์' },
  { accessorKey: 'category', header: 'หมวดหมู่' },
  { accessorKey: 'quantity', header: 'จำนวนทั้งหมด' },
  { accessorKey: 'available', header: 'ว่างให้ยืม' },
  { accessorKey: 'actions', header: 'จัดการ' }
]

// Borrows Table
const borrowColumns = [
  { accessorKey: 'id', header: 'รหัส' },
  { accessorKey: 'user_email', header: 'ผู้ยืม' },
  { accessorKey: 'equipment_name', header: 'อุปกรณ์' },
  { accessorKey: 'quantity', header: 'จำนวน' },
  { accessorKey: 'borrow_date', header: 'วันที่มารับ' },
  { accessorKey: 'return_date', header: 'กำหนดคืน' },
  { accessorKey: 'status', header: 'สถานะ' },
  { accessorKey: 'actions', header: 'จัดการ' }
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

// Modals & States
const isEquipModalOpen = ref(false)
const form = ref({
  id: null as number | null,
  name: '',
  category: '',
  description: '',
  quantity: 1,
  image: null as File | null,
  image_path: null as string | null
})
const isSaving = ref(false)

const openAddModal = () => {
  form.value = { id: null, name: '', category: '', description: '', quantity: 1, image: null, image_path: null }
  isEquipModalOpen.value = true
}

const openEditModal = (item: any) => {
  form.value = { 
    id: item.id, 
    name: item.name, 
    category: item.category, 
    description: item.description, 
    quantity: item.quantity, 
    image: null,
    image_path: item.image_path || null
  }
  isEquipModalOpen.value = true
}

const saveEquipment = async () => {
  isSaving.value = true;
  try {
    const formData = new FormData();
    if (form.value.id) formData.append('id', form.value.id.toString());
    formData.append('name', form.value.name || '');
    formData.append('category', form.value.category || '');
    formData.append('description', form.value.description || '');
    formData.append('quantity', form.value.quantity ? form.value.quantity.toString() : '0');
    if (form.value.image) {
      formData.append('image', form.value.image);
    }

    const endpoint = form.value.id ? '/api/inventory/update' : '/api/inventory/create';
    const baseUrl = useBaseUrl();
    await $fetch(`${baseUrl}${endpoint}`, {
      method: 'POST',
      body: formData,
      credentials: 'include'
    });
    await refreshEquip();
    isEquipModalOpen.value = false;
    toast.add({ title: 'บันทึกข้อมูลสำเร็จ', color: 'success', icon: 'i-heroicons-check-circle' });
  } catch (e: any) {
    console.error(e);
    toast.add({ title: 'ไม่สามารถบันทึกได้', description: e?.data?.error || e.message, color: 'error', icon: 'i-heroicons-exclamation-circle' });
  } finally {
    isSaving.value = false;
  }
}

const isDeleteModalOpen = ref(false)
const itemToDelete = ref<any>(null)
const isDeleting = ref(false)

const openDeleteModal = (item: any) => {
  itemToDelete.value = item
  isDeleteModalOpen.value = true
}

const confirmDelete = async () => {
  if (!itemToDelete.value) return;
  isDeleting.value = true;
  try {
    const baseUrl = useBaseUrl();
    await $fetch(`${baseUrl}/api/inventory/delete`, {
      method: 'POST',
      body: { id: itemToDelete.value.id },
      credentials: 'include'
    });
    await refreshEquip();
    isDeleteModalOpen.value = false;
    itemToDelete.value = null;
    toast.add({ title: 'ลบข้อมูลสำเร็จ', color: 'success', icon: 'i-heroicons-check-circle' });
  } catch (e: any) {
    console.error(e);
    toast.add({ title: 'ไม่สามารถลบข้อมูลได้', description: e?.data?.error || e.message, color: 'error', icon: 'i-heroicons-exclamation-circle' });
  } finally {
    isDeleting.value = false;
  }
}

const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    form.value.image = target.files[0]
  }
}

const isDragging = ref(false)

const handleDrop = (e: DragEvent) => {
  isDragging.value = false
  if (e.dataTransfer?.files && e.dataTransfer.files.length > 0) {
    const file = e.dataTransfer.files[0]
    if (file.type.startsWith('image/')) {
      form.value.image = file
    } else {
      console.error('กรุณาอัปโหลดเฉพาะไฟล์รูปภาพ')
    }
  }
}

// Borrow Slideover
const selectedBorrow = ref<any>(null)
const isBorrowSlideoverOpen = ref(false)
const borrowEditStatus = ref('')
const borrowAdminNote = ref('')
const isBorrowUpdating = ref(false)

const openBorrowDetail = (borrow: any) => {
  selectedBorrow.value = borrow
  borrowEditStatus.value = borrow.status
  borrowAdminNote.value = ''
  isBorrowSlideoverOpen.value = true
}

const borrowStatusOptions = [
  { name: 'อนุมัติ / กำลังใช้งาน', value: 'approved' },
  { name: 'ปฏิเสธ', value: 'rejected' },
  { name: 'คืนอุปกรณ์แล้ว', value: 'returned' }
]

// Update Borrow Status
const toast = useToast()
const updateBorrowStatus = async (id: number, status: string, closeOnSuccess = true) => {
  isBorrowUpdating.value = true;
  try {
    const formData = new FormData();
    formData.append('id', id.toString());
    formData.append('status', status);
    formData.append('admin_note', borrowAdminNote.value || '');

    const baseUrl = useBaseUrl();
    await $fetch(`${baseUrl}/api/borrows/update`, {
      method: 'POST',
      body: formData,
      credentials: 'include'
    });
    await refreshBorrows();
    await refreshEquip();
    if (closeOnSuccess) {
      isBorrowSlideoverOpen.value = false;
    } else {
      selectedBorrow.value = borrows.value.find((b: any) => b.id === id);
    }
    const statusText = status === 'approved' ? 'อนุมัติ' : status === 'returned' ? 'คืนแล้ว' : 'ปฏิเสธ';
    toast.add({ title: `อัปเดตสถานะเป็น "${statusText}" สำเร็จ`, color: 'success', icon: 'i-heroicons-check-circle' });
  } catch (e: any) {
    console.error(e);
    toast.add({ title: 'เกิดข้อผิดพลาด', description: e?.data?.error || 'ไม่สามารถอัปเดตสถานะได้', color: 'error', icon: 'i-heroicons-exclamation-circle' });
  } finally {
    isBorrowUpdating.value = false;
  }
}

const handleBorrowAction = async (newStatus: string) => {
  if (!selectedBorrow.value) return
  borrowEditStatus.value = newStatus
  await updateBorrowStatus(selectedBorrow.value.id, newStatus, false)
}

const isBorrowRejectModalOpen = ref(false)

const handleBorrowSaveClick = () => {
  if (borrowEditStatus.value === 'rejected') {
    isBorrowRejectModalOpen.value = true
  } else {
    updateBorrowStatus(selectedBorrow.value.id, borrowEditStatus.value, true)
  }
}

const confirmBorrowReject = () => {
  if (!borrowAdminNote.value && borrowEditStatus.value === 'rejected') {
    useToast().add({ title: 'กรุณาระบุสาเหตุ', color: 'error' })
    return
  }
  isBorrowRejectModalOpen.value = false
  updateBorrowStatus(selectedBorrow.value.id, 'rejected', true)
}

// Pseudo-realtime background polling
const fetchSilentUpdates = async () => {
  if (isEquipModalOpen.value || isDeleteModalOpen.value || isBorrowSlideoverOpen.value || isBorrowRejectModalOpen.value) return;
  
  try {
    const baseUrl = useBaseUrl();
    const [equipData, borrowsData] = await Promise.all([
      $fetch<any>('/api/inventory/index', { baseURL: baseUrl, credentials: 'include' }),
      $fetch<any>('/api/borrows/index', { baseURL: baseUrl, credentials: 'include' })
    ]);
    if (equipData?.data?.equipment || equipData?.equipment) equipResponse.value = equipData;
    if (borrowsData?.data?.borrows || borrowsData?.borrows) borrowsResponse.value = borrowsData;
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
    <div class="flex items-center justify-between" v-motion-fade>
      <h1 class="text-2xl font-bold font-kanit text-gray-900 dark:text-white">จัดการคลังอุปกรณ์</h1>
    </div>

    <UTabs :items="items" class="w-full font-kanit">
      <!-- Equipment Tab -->
      <template #equipment>
        <UCard class="mt-4" :ui="{ body: { padding: '!p-0' } }">
          <template #header>
            <div class="flex justify-between items-center px-4 py-3">
              <h3 class="font-bold text-gray-900 dark:text-white">รายการอุปกรณ์</h3>
              <UButton icon="i-heroicons-plus" color="primary" @click="openAddModal">เพิ่มอุปกรณ์</UButton>
            </div>
          </template>
          
          <!-- Desktop View -->
          <div class="hidden md:block overflow-x-auto w-full">
            <UTable :data="equipments" :columns="equipColumns" :loading="equipPending" class="min-w-[800px]">
              <template #image-cell="{ row }">
                <div class="w-16 h-16 shrink-0 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                  <img v-if="row.original.image_path" :src="`${useBaseUrl()}${row.original.image_path}`" class="object-cover w-full h-full" />
                  <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                    <UIcon name="i-heroicons-cube" class="w-6 h-6" />
                  </div>
                </div>
              </template>
              <template #available-cell="{ row }">
                <div class="flex items-center gap-2">
                  <span :class="row.original.available > 0 ? 'text-emerald-600 font-bold' : 'text-red-500 font-bold'">
                    {{ row.original.available }}
                  </span>
                  <UBadge v-if="row.original.available === 0" color="red" variant="solid" size="xs">
                    หมด
                  </UBadge>
                  <UBadge v-else-if="row.original.available <= 3" color="orange" variant="soft" size="xs">
                    ใกล้หมด
                  </UBadge>
                </div>
              </template>
              <template #actions-cell="{ row }">
                <div class="flex items-center gap-2">
                  <UButton size="xs" color="gray" variant="ghost" icon="i-heroicons-pencil-square" @click="openEditModal(row.original)">แก้ไข</UButton>
                  <UButton size="xs" color="red" variant="ghost" icon="i-heroicons-trash" @click="openDeleteModal(row.original)">ลบ</UButton>
                </div>
              </template>
            </UTable>
          </div>

          <!-- Mobile View -->
          <div class="md:hidden flex flex-col divide-y divide-gray-100 dark:divide-gray-800 w-full">
            <div v-if="equipPending" class="p-8 text-center text-gray-500">กำลังโหลด...</div>
            <div v-else-if="equipments.length === 0" class="p-8 text-center text-gray-500">ไม่มีข้อมูลอุปกรณ์</div>
            <div v-else v-for="equip in equipments" :key="equip.id" class="p-4 flex gap-4 items-center">
              <div class="w-16 h-16 shrink-0 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                <img v-if="equip.image_path" :src="`${useBaseUrl()}${equip.image_path}`" class="object-cover w-full h-full" />
                <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                  <UIcon name="i-heroicons-cube" class="w-6 h-6" />
                </div>
              </div>
              
              <div class="flex-1 min-w-0">
                <h4 class="font-bold text-gray-900 dark:text-white truncate text-base">{{ equip.name }}</h4>
                <p class="text-xs text-gray-500 truncate">{{ equip.category }}</p>
                <div class="mt-1 flex items-center gap-2 text-sm">
                  <span class="text-gray-500">ว่าง:</span>
                  <span :class="equip.available > 0 ? 'text-emerald-600 font-bold' : 'text-red-500 font-bold'">
                    {{ equip.available }}
                  </span>
                  <span class="text-gray-400 text-xs">/ {{ equip.quantity }}</span>
                  
                  <UBadge v-if="equip.available === 0" color="red" variant="solid" size="xs" class="ml-1">หมด</UBadge>
                  <UBadge v-else-if="equip.available <= 3" color="orange" variant="soft" size="xs" class="ml-1">ใกล้หมด</UBadge>
                </div>
              </div>

              <div class="flex flex-col gap-2 shrink-0">
                <UButton size="sm" color="gray" variant="soft" icon="i-heroicons-pencil-square" @click="openEditModal(equip)" />
                <UButton size="sm" color="red" variant="soft" icon="i-heroicons-trash" @click="openDeleteModal(equip)" />
              </div>
            </div>
          </div>
        </UCard>
      </template>

      <!-- Borrows Tab -->
      <template #borrows>
        <UCard class="mt-4" :ui="{ body: { padding: '!p-0' } }">
           <template #header>
            <div class="px-4 py-3">
              <h3 class="font-bold text-gray-900 dark:text-white">คำขอยืม-คืนอุปกรณ์</h3>
            </div>
          </template>
          
          <!-- Desktop View -->
          <div class="hidden md:block overflow-x-auto w-full">
            <UTable :data="borrows" :columns="borrowColumns" :loading="borrowsPending" class="min-w-[900px]">
              <!-- Return Date with overdue/due-soon badge -->
              <template #return_date-cell="{ row }">
                <div class="flex items-center gap-2">
                  <span :class="isOverdue(row.original) ? 'text-red-600 font-bold' : isDueSoon(row.original) ? 'text-orange-500 font-semibold' : ''">
                    {{ row.original.return_date || '-' }}
                  </span>
                  <UBadge v-if="isOverdue(row.original)" color="red" variant="solid" size="xs" class="animate-pulse flex items-center gap-1">
                    <UIcon name="i-heroicons-exclamation-triangle" class="w-4 h-4" /> เกิน {{ daysOverdue(row.original) }} วัน!
                  </UBadge>
                  <UBadge v-else-if="isDueSoon(row.original)" color="orange" variant="subtle" size="xs" class="flex items-center gap-1">
                    <UIcon name="i-heroicons-bell-alert" class="w-4 h-4" /> {{ daysUntilDue(row.original) === 0 ? 'ครบกำหนดวันนี้!' : 'คืนพรุ่งนี้!' }}
                  </UBadge>
                </div>
              </template>

              <template #status-cell="{ row }">
                <UBadge 
                  :color="isOverdue(row.original) ? 'red' : isDueSoon(row.original) ? 'orange' : row.original.status === 'approved' ? 'success' : row.original.status === 'returned' ? 'gray' : row.original.status === 'rejected' ? 'error' : row.original.status === 'pending_return' ? 'info' : 'warning'" 
                  variant="subtle"
                >
                  {{ isOverdue(row.original) ? 'เกินกำหนด!' : row.original.status === 'approved' ? 'กำลังใช้งาน' : row.original.status === 'returned' ? 'คืนแล้ว' : row.original.status === 'rejected' ? 'ไม่อนุมัติ' : row.original.status === 'pending_return' ? 'รอตรวจสอบการคืน' : 'รออนุมัติ' }}
                </UBadge>
              </template>

              <template #actions-cell="{ row }">
                <UButton size="xs" :color="isOverdue(row.original) ? 'red' : isDueSoon(row.original) ? 'orange' : 'gray'" variant="ghost" :icon="isOverdue(row.original) ? 'i-heroicons-exclamation-triangle' : isDueSoon(row.original) ? 'i-heroicons-bell-alert' : 'i-heroicons-eye'" @click="openBorrowDetail(row.original)">
                  {{ isOverdue(row.original) ? 'ด่วน!' : isDueSoon(row.original) ? 'ใกล้ครบกำหนด' : 'ดู / จัดการ' }}
                </UButton>
              </template>
            </UTable>
          </div>

          <!-- Mobile View -->
          <div class="md:hidden flex flex-col divide-y divide-gray-100 dark:divide-gray-800 w-full">
            <div v-if="borrowsPending" class="p-8 text-center text-gray-500">กำลังโหลด...</div>
            <div v-else-if="borrows.length === 0" class="p-8 text-center text-gray-500 flex flex-col items-center">
               <UIcon name="i-heroicons-inbox" class="w-12 h-12 mb-2 text-gray-200 dark:text-gray-700" />
               ไม่มีข้อมูลคำขอ
            </div>
            <div v-else v-for="borrow in borrows" :key="borrow.id" class="p-4 flex flex-col gap-3">
              <div class="flex justify-between items-start gap-2">
                <div class="min-w-0 flex-1">
                  <div class="font-bold text-gray-900 dark:text-white truncate text-base">{{ borrow.equipment_name }} <span class="text-sm font-normal text-gray-500">(x{{ borrow.quantity }})</span></div>
                  <div class="text-sm text-gray-500 mt-0.5 truncate">{{ borrow.user_email }}</div>
                </div>
                <UBadge 
                  :color="isOverdue(borrow) ? 'red' : borrow.status === 'approved' ? 'success' : borrow.status === 'returned' ? 'gray' : borrow.status === 'rejected' ? 'error' : borrow.status === 'pending_return' ? 'info' : 'warning'" 
                  variant="subtle" size="xs" class="shrink-0"
                >
                  {{ isOverdue(borrow) ? 'เกินกำหนด!' : borrow.status === 'approved' ? 'กำลังใช้งาน' : borrow.status === 'returned' ? 'คืนแล้ว' : borrow.status === 'rejected' ? 'ไม่อนุมัติ' : borrow.status === 'pending_return' ? 'รอตรวจสอบการคืน' : 'รออนุมัติ' }}
                </UBadge>
              </div>
              
              <div class="grid grid-cols-2 gap-2 text-sm bg-gray-50 dark:bg-gray-800/50 p-2.5 rounded-lg border border-gray-100 dark:border-gray-800">
                <div>
                  <div class="text-xs text-gray-500 mb-0.5">มารับอุปกรณ์</div>
                  <div class="font-medium text-gray-900 dark:text-gray-200">{{ borrow.borrow_date }}</div>
                </div>
                <div>
                  <div class="text-xs text-gray-500 mb-0.5">กำหนดคืน</div>
                  <div :class="isOverdue(borrow) ? 'font-bold text-red-600' : isDueSoon(borrow) ? 'font-semibold text-orange-500' : 'font-medium text-gray-900 dark:text-gray-200'">
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

              <UButton block size="sm" :color="isOverdue(borrow) ? 'red' : isDueSoon(borrow) ? 'orange' : 'gray'" variant="soft" :icon="isOverdue(borrow) ? 'i-heroicons-exclamation-triangle' : isDueSoon(borrow) ? 'i-heroicons-bell-alert' : 'i-heroicons-eye'" @click="openBorrowDetail(borrow)">
                {{ isOverdue(borrow) ? 'จัดการด่วน!' : isDueSoon(borrow) ? 'จัดการ (ใกล้ครบกำหนด)' : 'ดูรายละเอียด / จัดการ' }}
              </UButton>
            </div>
          </div>
        </UCard>
      </template>
    </UTabs>

    <!-- Borrow Detail Slideover -->
    <USlideover v-model:open="isBorrowSlideoverOpen" :ui="{ content: 'w-screen max-w-md', overlay: { background: 'bg-gray-900/50 backdrop-blur-sm' } }">
      <template #content>
        <div class="flex flex-col h-[100dvh] overflow-hidden bg-white dark:bg-gray-950 font-kanit">
          <div class="shrink-0 p-4 flex items-center justify-between border-b border-gray-100 dark:border-gray-800">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">รายละเอียดการยืม #{{ selectedBorrow?.id }}</h2>
            <UButton icon="i-heroicons-x-mark" color="gray" variant="ghost" @click="isBorrowSlideoverOpen = false" />
          </div>
          
          <div class="flex-1 overflow-y-auto p-6 space-y-6" v-if="selectedBorrow">
            <div>
              <p class="text-sm text-gray-500">ผู้ยืม</p>
              <p class="font-medium">{{ selectedBorrow.user_email }}</p>
            </div>
            
            <div class="mt-4">
              <p class="text-sm text-gray-500">อุปกรณ์ที่ยืม</p>
              <p class="font-medium text-lg">{{ selectedBorrow.equipment_name }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4 mt-4">
              <div>
                <p class="text-sm text-gray-500">จำนวน</p>
                <p class="font-bold text-lg text-coral-600">{{ selectedBorrow.quantity }} ชิ้น</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">สถานะ</p>
                <UBadge :color="selectedBorrow.status === 'approved' ? 'success' : selectedBorrow.status === 'returned' ? 'gray' : selectedBorrow.status === 'rejected' ? 'error' : 'warning'" variant="subtle" class="mt-1">
                  {{ selectedBorrow.status === 'approved' ? 'กำลังใช้งาน' : selectedBorrow.status === 'returned' ? 'คืนแล้ว' : selectedBorrow.status === 'rejected' ? 'ไม่อนุมัติ' : 'รออนุมัติ' }}
                </UBadge>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mt-4">
              <div>
                <p class="text-sm text-gray-500 flex items-center gap-1"><UIcon name="i-heroicons-calendar-days" class="w-4 h-4" /> วันที่ต้องการมารับ</p>
                <p class="font-medium">{{ selectedBorrow.borrow_date }}</p>
                <UBadge 
                  v-if="selectedBorrow.borrow_date > new Date().toISOString().split('T')[0]"
                  color="indigo" variant="subtle" size="xs" class="mt-1"
                >
                  จองล่วงหน้า
                </UBadge>
              </div>
              <div>
                <p class="text-sm text-gray-500 flex items-center gap-1"><UIcon name="i-heroicons-arrow-uturn-left" class="w-4 h-4" /> กำหนดคืน</p>
                <p :class="isOverdue(selectedBorrow) ? 'font-bold text-red-600' : isDueSoon(selectedBorrow) ? 'font-semibold text-orange-500' : 'font-medium'">
                  {{ selectedBorrow.return_date || '-' }}
                </p>
                <UBadge v-if="isOverdue(selectedBorrow)" color="red" variant="solid" size="xs" class="mt-1 animate-pulse flex items-center gap-1">
                  <UIcon name="i-heroicons-exclamation-triangle" class="w-4 h-4" /> เกินกำหนด! {{ daysOverdue(selectedBorrow) }} วัน
                </UBadge>
                <UBadge v-else-if="isDueSoon(selectedBorrow)" color="orange" variant="subtle" size="xs" class="mt-1 flex items-center gap-1">
                  <UIcon name="i-heroicons-bell-alert" class="w-4 h-4" /> {{ daysUntilDue(selectedBorrow) === 0 ? 'ครบกำหนดวันนี้!' : 'คืนพรุ่งนี้!' }}
                </UBadge>
              </div>
            </div>

            <!-- Overdue Alert Box -->
            <div v-if="isOverdue(selectedBorrow)" class="mt-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 flex gap-3">
              <UIcon name="i-heroicons-exclamation-triangle" class="w-6 h-6 text-red-500 flex-shrink-0 mt-0.5" />
              <div>
                <p class="font-bold text-red-700 dark:text-red-400">เกินกำหนดคืน! {{ daysOverdue(selectedBorrow) }} วัน</p>
                <p class="text-sm text-red-600 dark:text-red-300 mt-1">ผู้ยืมยังไม่คืนอุปกรณ์ตามกำหนด กรุณาติดต่อผู้ยืมเพื่อตามอุปกรณ์คืน</p>
              </div>
            </div>

            <!-- Due Soon Warning Box -->
            <div v-else-if="isDueSoon(selectedBorrow)" class="mt-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl p-4 flex gap-3">
              <UIcon name="i-heroicons-bell-alert" class="w-6 h-6 text-orange-500 flex-shrink-0 mt-0.5" />
              <div>
                <p class="font-bold text-orange-700 dark:text-orange-400">
                  {{ daysUntilDue(selectedBorrow) === 0 ? 'ครบกำหนดคืนวันนี้!' : 'ใกล้ครบกำหนดคืน - พรุ่งนี้!' }}
                </p>
                <p class="text-sm text-orange-600 dark:text-orange-300 mt-1">แจ้งเตือนผู้ยืมให้นำอุปกรณ์มาคืนตามกำหนด</p>
              </div>
            </div>

            <div v-if="selectedBorrow.reason" class="mt-4">
              <p class="text-sm text-gray-500">เหตุผล / วัตถุประสงค์ (ผู้ใช้)</p>
              <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded-lg mt-1 whitespace-pre-wrap text-sm border border-gray-100 dark:border-gray-800">
                {{ selectedBorrow.reason }}
              </div>
            </div>

            <div v-if="selectedBorrow.admin_note" class="mt-4">
              <p class="text-sm text-gray-500 font-medium text-primary">หมายเหตุจากแอดมิน</p>
              <div class="bg-primary-50 dark:bg-primary-900/20 p-3 rounded-lg mt-1 whitespace-pre-wrap text-sm border border-primary-100 dark:border-primary-800">
                {{ selectedBorrow.admin_note }}
              </div>
            </div>

            <USeparator class="my-6" />
            
            <h3 class="font-bold text-gray-900 dark:text-white mb-4">ส่วนจัดการของ Admin</h3>
            
            <!-- Pending: Accept/Reject -->
            <div v-if="selectedBorrow.status === 'pending'" class="text-center p-6 border border-dashed border-gray-300 dark:border-gray-700 rounded-xl bg-gray-50/50 dark:bg-gray-900/30">
              <UIcon name="i-heroicons-inbox-arrow-down" class="w-12 h-12 text-gray-400 mb-2" />
              <h4 class="font-medium text-gray-900 dark:text-white text-lg">มีคำขอยืมอุปกรณ์ใหม่</h4>
              <p class="text-sm text-gray-500 mt-1 mb-4">คุณต้องการอนุมัติคำขอยืมนี้หรือไม่?</p>
              <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1 text-left">หมายเหตุจากแอดมิน (ถ้ามี)</label>
                <UTextarea v-model="borrowAdminNote" placeholder="ระบุเหตุผลหากปฏิเสธ หรือหมายเหตุหากอนุมัติ..." :rows="2" />
              </div>
            </div>

            <!-- Pending Return: User says returned, admin confirms -->
            <div v-else-if="selectedBorrow.status === 'pending_return'" class="text-center p-6 border border-dashed border-blue-300 dark:border-blue-700 rounded-xl bg-blue-50/50 dark:bg-blue-900/20">
              <UIcon name="i-heroicons-arrow-uturn-left" class="w-12 h-12 text-blue-500 mb-2" />
              <h4 class="font-medium text-gray-900 dark:text-white text-lg">ผู้ใช้แจ้งคืนอุปกรณ์แล้ว</h4>
              <p class="text-sm text-gray-500 mt-1 mb-4">กรุณาตรวจสอบอุปกรณ์และยืนยันการรับคืน</p>
            </div>

            <!-- Not Pending: Read-only Status -->
            <div v-else class="space-y-4 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-800">
              <h4 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <UIcon name="i-heroicons-check-circle" class="w-5 h-5 text-gray-500" v-if="selectedBorrow.status === 'returned'" />
                <UIcon name="i-heroicons-x-circle" class="w-5 h-5 text-red-500" v-else-if="selectedBorrow.status === 'rejected'" />
                <UIcon name="i-heroicons-arrow-path" class="w-5 h-5 text-green-500" v-else />
                {{ selectedBorrow.status === 'returned' ? 'รายการนี้รับคืนเสร็จสิ้นแล้ว' : selectedBorrow.status === 'rejected' ? 'รายการนี้ถูกปฏิเสธไปแล้ว' : 'ผู้ใช้กำลังใช้งานอุปกรณ์นี้อยู่ (รอการแจ้งคืน)' }}
              </h4>
            </div>
          </div>
          
          <!-- Footer: Pending -->
          <div v-if="selectedBorrow?.status === 'pending'" class="shrink-0 p-4 border-t border-gray-100 dark:border-gray-800 flex gap-3 bg-white dark:bg-gray-950 pb-safe">
            <UButton color="red" variant="soft" class="flex-1 justify-center" :loading="isBorrowUpdating" @click="handleBorrowSaveClick">ปฏิเสธคำขอ</UButton>
            <UButton color="primary" class="flex-1 justify-center" :loading="isBorrowUpdating" @click="handleBorrowAction('approved')">อนุมัติการยืม</UButton>
          </div>
          <!-- Footer: Pending Return -->
          <div v-else-if="selectedBorrow?.status === 'pending_return'" class="shrink-0 p-4 border-t border-gray-100 dark:border-gray-800 flex gap-3 bg-white dark:bg-gray-950 pb-safe">
            <UButton color="gray" variant="ghost" class="flex-1 justify-center" @click="isBorrowSlideoverOpen = false">ปิดหน้าต่าง</UButton>
            <UButton color="success" class="flex-1 justify-center" :loading="isBorrowUpdating" @click="handleBorrowAction('returned')">ยืนยันรับคืนอุปกรณ์</UButton>
          </div>
          <!-- Footer: Other -->
          <div v-else class="shrink-0 p-4 border-t border-gray-100 dark:border-gray-800 flex justify-end bg-white dark:bg-gray-950 pb-safe">
            <UButton color="gray" variant="ghost" class="px-6" @click="isBorrowSlideoverOpen = false">ปิดหน้าต่าง</UButton>
          </div>
        </div>
      </template>
    </USlideover>

    <!-- Borrow Reject Confirmation Modal -->
    <UModal v-model:open="isBorrowRejectModalOpen">
      <template #content>
        <UCard class="font-kanit border border-red-100 dark:border-red-900 shadow-xl shadow-red-900/10" :ui="{ ring: 'ring-1 ring-red-500 dark:ring-red-400' }">
          <template #header>
            <div class="flex items-center justify-between">
              <h3 class="text-base font-bold text-red-600 dark:text-red-500 flex items-center gap-2">
                <UIcon name="i-heroicons-exclamation-triangle-solid" class="w-5 h-5" />
                ยืนยันการปฏิเสธคำขอยืม?
              </h3>
              <UButton color="gray" variant="ghost" icon="i-heroicons-x-mark" class="-my-1" @click="isBorrowRejectModalOpen = false" />
            </div>
          </template>
          
          <div class="py-4">
            <p class="text-gray-800 dark:text-gray-200 mb-4 text-center">
              คุณกำลังจะเปลี่ยนสถานะเป็น <span class="font-bold text-red-600 dark:text-red-400">"ปฏิเสธ"</span>
            </p>
            <p class="text-sm text-gray-500 italic text-center mb-4">อุปกรณ์จะถูกคืนกลับเข้าคลังอัตโนมัติ</p>
            <div class="text-left">
              <label class="block text-sm text-gray-600 dark:text-gray-400 mb-2">โปรดระบุสาเหตุของการปฏิเสธให้ผู้ใช้ทราบ:</label>
              <UTextarea v-model="borrowAdminNote" placeholder="เช่น อุปกรณ์ถูกยืมหมดแล้ว, ไม่พบอุปกรณ์..." :rows="3" autofocus required class="w-full" />
            </div>
          </div>

          <template #footer>
            <div class="flex justify-end gap-3 w-full">
              <UButton variant="soft" color="gray" @click="isBorrowRejectModalOpen = false" class="px-6 font-medium">กลับไปแก้ไข</UButton>
              <UButton color="red" variant="solid" @click="confirmBorrowReject" :loading="isBorrowUpdating" class="px-6 font-bold shadow-lg shadow-red-500/30 !bg-red-600 !text-white hover:!bg-red-700 transition-all">ยืนยันปฏิเสธ</UButton>
            </div>
          </template>
        </UCard>
      </template>
    </UModal>

    <!-- Modal Add/Edit Equipment -->
    <UModal v-model:open="isEquipModalOpen">
      <template #content>
        <UCard :ui="{ ring: '', divide: 'divide-y divide-gray-100 dark:divide-gray-800' }" class="font-kanit">
          <template #header>
            <div class="flex items-center justify-between">
              <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ form.id ? 'แก้ไขข้อมูลอุปกรณ์' : 'เพิ่มอุปกรณ์ใหม่ในคลัง' }}</h3>
              <UButton color="gray" variant="ghost" icon="i-heroicons-x-mark" class="-my-1" @click="isEquipModalOpen = false" />
            </div>
          </template>
          
          <div class="max-h-[70vh] overflow-y-auto px-1">
            <form @submit.prevent="saveEquipment" class="space-y-4">
              <UFormField label="ชื่ออุปกรณ์" name="name">
                <UInput v-model="form.name" required placeholder="เช่น โปรเจคเตอร์ EPSON" />
              </UFormField>
              
              <UFormField label="หมวดหมู่" name="category">
                <USelect v-model="form.category" :items="categories" :options="categories" required placeholder="เลือกหมวดหมู่อุปกรณ์" />
              </UFormField>

              <UFormField label="จำนวนทั้งหมด" name="quantity">
                <UInput v-model.number="form.quantity" type="number" :min="1" required />
              </UFormField>
              
              <UFormField label="รูปภาพประกอบ (ถ้ามี)" name="image">
                <div v-if="form.image_path && !form.image" class="mb-3 relative rounded-xl overflow-hidden border border-gray-200 dark:border-gray-800 inline-block w-full">
                  <img :src="`${useBaseUrl()}${form.image_path}`" class="w-full h-48 object-cover" alt="Current image" />
                  <div class="absolute inset-0 bg-black/50 opacity-0 hover:opacity-100 transition-opacity flex items-center justify-center">
                    <span class="text-white text-sm font-medium">อัปโหลดใหม่เพื่อเปลี่ยนรูป</span>
                  </div>
                </div>
                <label 
                  for="image-upload"
                  class="flex justify-center px-6 pt-8 pb-8 border-2 border-dashed rounded-xl transition-all cursor-pointer group"
                  :class="[isDragging ? 'border-coral-500 bg-coral-50 dark:bg-coral-900/20 scale-[1.01]' : 'border-gray-300 dark:border-gray-700 hover:border-coral-400 hover:bg-gray-50 dark:hover:bg-gray-800', form.image_path && !form.image ? 'mt-0' : 'mt-1']"
                  @dragover.prevent="isDragging = true"
                  @dragleave.prevent="isDragging = false"
                  @drop.prevent="handleDrop"
                >
                  <div class="space-y-2 text-center pointer-events-none">
                    <div :class="isDragging ? 'text-coral-500 animate-bounce' : 'text-gray-400 group-hover:text-coral-500 transition-colors'">
                      <UIcon name="i-heroicons-cloud-arrow-up" class="mx-auto h-14 w-14" />
                    </div>
                    <div class="flex text-sm text-gray-600 dark:text-gray-400 justify-center">
                      <span class="relative bg-transparent rounded-md font-medium text-coral-600 group-hover:text-coral-500">
                        {{ form.image_path && !form.image ? 'เปลี่ยนรูปภาพ' : 'อัปโหลดรูปภาพ' }}
                      </span>
                      <p class="pl-1">หรือลากและวางไฟล์มาที่นี่</p>
                    </div>
                    <p class="text-xs text-gray-500">
                      PNG, JPG, GIF ขนาดไม่เกิน 10MB
                    </p>
                    <p v-if="form.image" class="text-sm font-bold text-coral-600 mt-3 bg-coral-100 dark:bg-coral-900/30 py-2 px-4 rounded-full inline-block">
                      ไฟล์ใหม่: {{ form.image.name }}
                    </p>
                  </div>
                  <input id="image-upload" name="image-upload" type="file" class="sr-only" @change="handleFileChange" accept="image/*" />
                </label>
              </UFormField>

              <UFormField label="รายละเอียด" name="description">
                <UTextarea v-model="form.description" placeholder="คุณสมบัติเพิ่มเติม..." :rows="3" />
              </UFormField>
              
              <div class="pt-4 flex justify-end gap-3">
                <UButton color="gray" variant="ghost" @click="isEquipModalOpen = false">ยกเลิก</UButton>
                <UButton type="submit" color="primary" :loading="isSaving">บันทึกข้อมูล</UButton>
              </div>
            </form>
          </div>
        </UCard>
      </template>
    </UModal>

    <!-- Delete Confirmation Modal -->
    <UModal v-model:open="isDeleteModalOpen">
      <template #content>
        <UCard class="font-kanit border border-red-100 dark:border-red-900 shadow-xl shadow-red-900/10" :ui="{ ring: 'ring-1 ring-red-500 dark:ring-red-400' }">
          <template #header>
            <div class="flex items-center justify-between">
              <h3 class="text-base font-bold text-red-600 dark:text-red-500 flex items-center gap-2">
                <UIcon name="i-heroicons-exclamation-triangle-solid" class="w-5 h-5" />
                ยืนยันการลบข้อมูล
              </h3>
              <UButton color="gray" variant="ghost" icon="i-heroicons-x-mark" class="-my-1" @click="isDeleteModalOpen = false" />
            </div>
          </template>
          
          <div class="py-6 flex flex-col items-center justify-center text-center">
            <div class="w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center mb-4 ring-8 ring-red-50 dark:ring-red-900/20">
              <UIcon name="i-heroicons-trash" class="w-8 h-8 text-red-600 dark:text-red-400" />
            </div>
            <p class="text-gray-800 dark:text-gray-200 text-lg">
              คุณแน่ใจหรือไม่ที่จะลบอุปกรณ์ <span class="font-bold text-red-600 dark:text-red-400">"{{ itemToDelete?.name }}"</span> ?
            </p>
            <p class="text-sm font-medium text-red-500 bg-red-50 dark:bg-red-900/20 px-4 py-2 rounded-lg mt-4 w-full">
              การดำเนินการนี้ลบข้อมูลถาวร และไม่สามารถกู้คืนได้
            </p>
          </div>

          <template #footer>
            <div class="flex justify-end gap-3 w-full">
              <UButton variant="soft" color="gray" @click="isDeleteModalOpen = false" class="px-6 font-medium">ยกเลิก</UButton>
              <UButton color="red" variant="solid" @click="confirmDelete" :loading="isDeleting" class="px-6 font-bold shadow-lg shadow-red-500/30 !bg-red-600 !text-white hover:!bg-red-700 transition-all">ลบข้อมูลถาวร</UButton>
            </div>
          </template>
        </UCard>
      </template>
    </UModal>
  </div>
</template>
