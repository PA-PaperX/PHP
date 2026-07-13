<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '~/composables/useApi'
import { useToast } from '#imports'

definePageMeta({
  middleware: 'admin'
})

const route = useRoute()
const router = useRouter()
const toast = useToast()
const issueId = route.params.id as string

const { data, pending, error, refresh } = await useApi<any>(`/api/issues/show.php?id=${issueId}`)
const issue = computed(() => data.value?.issue)

const isLoadingLocation = ref(true)
const adminLocation = ref<{ lat: number, lng: number } | null>(null)
const userLocation = computed(() => {
  if (issue.value && issue.value.lat && issue.value.lng) {
    return { lat: parseFloat(issue.value.lat), lng: parseFloat(issue.value.lng) }
  }
  return null
})

// Management States
const isUpdating = ref(false)
const editStatus = ref('')
const adminNote = ref('')
const adminImage = ref<File | null>(null)
const paymentStatus = ref('unpaid')
const isCancelModalOpen = ref(false)

const statusOptions = [
  { name: 'กำลังดำเนินการ', value: 'in_progress' },
  { name: 'เสร็จสิ้น', value: 'resolved' },
  { name: 'ยกเลิก', value: 'closed' }
]

import { watch } from 'vue'

watch(issue, (newVal) => {
  if (newVal && !editStatus.value) {
    editStatus.value = newVal.status === 'pending' ? 'in_progress' : newVal.status
    adminNote.value = newVal.admin_note || ''
    paymentStatus.value = newVal.payment_status || 'unpaid'
  }
}, { immediate: true })

const handleAdminImageChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    adminImage.value = target.files[0]
  }
}

const updateIssue = async (goBack = false) => {
  if (!issue.value) return
  isUpdating.value = true
  try {
    const formData = new FormData()
    formData.append('id', issue.value.id.toString())
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
    
    toast.add({ title: 'อัปเดตข้อมูลเรียบร้อยแล้ว', color: 'success' })
    await refresh()
    if (goBack) {
      router.push('/admin/issues')
    }
  } catch (e) {
    console.error(e)
    toast.add({ title: 'เกิดข้อผิดพลาดในการอัปเดต', color: 'error' })
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
    toast.add({ title: 'กรุณาระบุสาเหตุ', color: 'error' })
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

// Calculate straight line distance (Haversine)
const calculateDistance = (lat1: number, lon1: number, lat2: number, lon2: number) => {
  const R = 6371 // Radius of the earth in km
  const dLat = (lat2 - lat1) * Math.PI / 180
  const dLon = (lon2 - lon1) * Math.PI / 180
  const a = 
    Math.sin(dLat/2) * Math.sin(dLat/2) +
    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
    Math.sin(dLon/2) * Math.sin(dLon/2)
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)) 
  const d = R * c // Distance in km
  return d
}

const distance = computed(() => {
  if (adminLocation.value && userLocation.value) {
    const dist = calculateDistance(adminLocation.value.lat, adminLocation.value.lng, userLocation.value.lat, userLocation.value.lng)
    return dist.toFixed(2)
  }
  return null
})

onMounted(() => {
  // Simulate an animation delay to show the "Globe" effect, then fetch real GPS
  setTimeout(() => {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          adminLocation.value = {
            lat: position.coords.latitude,
            lng: position.coords.longitude
          }
          isLoadingLocation.value = false
        },
        (error) => {
          console.error("Error getting location", error)
          toast.add({ title: 'ไม่สามารถระบุตำแหน่งได้', description: 'กรุณาอนุญาตการเข้าถึง GPS', color: 'error' })
          isLoadingLocation.value = false
        },
        { enableHighAccuracy: true, timeout: 10000 }
      )
    } else {
      toast.add({ title: 'เบราว์เซอร์ไม่รองรับ GPS', color: 'error' })
      isLoadingLocation.value = false
    }
  }, 2000) // 2 seconds globe animation
})
</script>

<template>
  <div class="max-w-5xl mx-auto space-y-6 pb-10">
    <!-- Header -->
    <div class="flex items-center justify-between" v-motion-fade>
      <div class="flex items-center gap-3">
        <UButton icon="i-heroicons-arrow-left" color="gray" variant="ghost" to="/admin/issues" />
        <h1 class="text-2xl font-bold font-kanit text-gray-900 dark:text-white">รับเรื่องและเดินทาง (Issue #{{ issue?.id }})</h1>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error || (!pending && !issue)" class="text-center py-20 font-kanit">
      <UIcon name="i-heroicons-exclamation-circle" class="w-16 h-16 text-red-500 mx-auto mb-4" />
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ไม่พบข้อมูลปัญหา</h2>
    </div>

    <template v-else-if="issue">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 font-kanit">
        <!-- Main Map Area -->
        <UCard class="lg:col-span-2 shadow-md" :ui="{ body: { padding: '!p-0' } }" v-motion-slide-visible-bottom>
          <div class="relative">
            <!-- Cobe Globe while locating -->
            <CobeGlobe
              v-if="isLoadingLocation"
              :userLocation="userLocation"
              :adminLocation="adminLocation"
              height="500px"
            />

            <!-- Real Map after location found -->
            <IssueMap 
              v-else
              :modelValue="userLocation"
              :adminLocation="adminLocation"
              :readonly="true"
              height="500px" 
            />
            
            <!-- Distance Overlay (Only show when not loading) -->
            <div v-if="distance && !isLoadingLocation" class="absolute top-4 right-4 bg-white/95 dark:bg-gray-900/95 backdrop-blur shadow-lg rounded-xl p-4 border border-gray-200 dark:border-gray-800">
              <p class="text-sm text-gray-500 mb-1">ระยะทางโดยประมาณ</p>
              <div class="flex items-baseline gap-2">
                <span class="text-3xl font-black text-primary-600 dark:text-primary-400">{{ distance }}</span>
                <span class="text-gray-600 dark:text-gray-400 font-bold">กม.</span>
              </div>
            </div>
          </div>
        </UCard>

        <!-- Issue Details Sidebar -->
        <div class="space-y-6" v-motion-fade :delay="200">
          <UCard class="shadow-sm">
            <template #header>
              <h3 class="font-bold flex items-center gap-2">
                <UIcon name="i-heroicons-document-text" class="text-primary-500" /> ข้อมูลการแจ้งซ่อม
              </h3>
            </template>
            
            <div class="space-y-4">
              <div>
                <p class="text-sm text-gray-500">ผู้แจ้ง</p>
                <p class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                  <UAvatar :alt="issue.user_email" size="xs" /> {{ issue.user_email }}
                </p>
              </div>
              <div>
                <p class="text-sm text-gray-500">หัวข้อปัญหา</p>
                <p class="font-semibold">{{ issue.title }}</p>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-gray-500">หมวดหมู่</p>
                  <UBadge color="gray" variant="solid" class="mt-1 uppercase text-xs">{{ issue.category }}</UBadge>
                </div>
                <div>
                  <p class="text-sm text-gray-500">เวลาที่แจ้ง</p>
                  <p class="text-sm mt-1">{{ new Date(issue.created_at).toLocaleTimeString('th-TH') }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-500">ชำระเงิน</p>
                  <UBadge :color="paymentStatus === 'paid' ? 'success' : 'warning'" variant="subtle" class="mt-1">
                    {{ paymentStatus === 'paid' ? 'ชำระแล้ว' : 'รอชำระ' }}
                  </UBadge>
                </div>
              </div>
              
              <USeparator />
              
              <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                  <UIcon name="i-heroicons-map" class="w-5 h-5 text-blue-500" />
                  <span class="font-semibold text-gray-900 dark:text-white">พิกัดบนแผนที่</span>
                </div>
                <div class="flex items-center gap-2">
                  <UButton :to="`https://www.google.com/maps/search/?api=1&query=${issue.lat},${issue.lng}`" target="_blank" size="xs" color="gray" variant="soft" icon="i-heroicons-arrow-top-right-on-square">
                    เปิดใน Google Maps
                  </UButton>
                  <UBadge v-if="distanceText" color="blue" variant="subtle" class="font-kanit animate-pulse">
                    <UIcon name="i-heroicons-map-pin" class="w-4 h-4 mr-1" />
                    ห่างจากคุณ: {{ distanceText }}
                  </UBadge>
                </div>
              </div>
              
              <div>
                <p class="text-sm text-gray-500">สถานที่</p>
                <p class="mt-1 flex items-center gap-1 text-gray-700 dark:text-gray-300 font-medium">
                  <UIcon name="i-heroicons-map-pin" class="text-coral-500" /> {{ issue.location }}
                </p>
              </div>
              
              <div>
                <p class="text-sm text-gray-500">รายละเอียด</p>
                <p class="mt-1 text-gray-700 dark:text-gray-300 text-sm whitespace-pre-wrap">{{ issue.description || '-' }}</p>
              </div>
            </div>
          </UCard>

          <UCard v-if="issue.image_path" class="shadow-sm">
            <template #header>
              <h3 class="font-bold flex items-center gap-2">
                <UIcon name="i-heroicons-photo" class="text-primary-500" /> ภาพประกอบ
              </h3>
            </template>
            <img :src="`http://localhost:8080${issue.image_path}`" alt="Issue image" class="rounded-xl w-full object-cover" />
          </UCard>

          <!-- Admin Management Actions -->
          <UCard class="shadow-sm border-t-4 border-t-primary-500">
            <template #header>
              <h3 class="font-bold flex items-center gap-2 text-gray-900 dark:text-white">
                <UIcon name="i-heroicons-wrench-screwdriver" class="text-primary-500" /> จัดการสถานะ (Admin)
              </h3>
            </template>
            
            <div v-if="issue.status === 'pending'" class="text-center p-4 border border-dashed border-gray-300 dark:border-gray-700 rounded-xl bg-gray-50/50 dark:bg-gray-900/30">
              <h4 class="font-medium text-gray-900 dark:text-white mb-2">รับเรื่องดำเนินการ</h4>
              <p class="text-sm text-gray-500 mb-4">คุณต้องการรับเรื่องนี้เพื่อดำเนินการต่อหรือไม่?</p>
              <label class="flex items-center justify-center gap-2 p-3 mb-4 rounded-lg bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-sm font-medium text-gray-700 dark:text-gray-200">
                <input v-model="paymentConfirmed" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                ยืนยันว่าชำระเงินแล้ว
              </label>
              <div class="flex gap-2">
                <UButton color="red" variant="soft" class="flex-1 justify-center" @click="openRejectModal">ปฏิเสธ</UButton>
                <UButton color="primary" class="flex-1 justify-center" :loading="isUpdating" :disabled="paymentStatus !== 'paid'" @click="handleAction('in_progress')">รับเรื่อง</UButton>
              </div>
            </div>

            <div v-else-if="issue.status === 'in_progress'" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">อัปเดตสถานะ</label>
                <select v-model="editStatus" class="w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 font-kanit">
                  <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.name }}</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หมายเหตุ</label>
                <UTextarea v-model="adminNote" placeholder="ระบุรายละเอียดเพิ่มเติม..." :rows="3" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">แนบรูปภาพหลังดำเนินการ</label>
                <input type="file" @change="handleAdminImageChange" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100" accept="image/*" />
              </div>

              <UButton color="primary" block class="mt-4 justify-center" :loading="isUpdating" @click="handleSaveClick">บันทึกข้อมูล</UButton>
            </div>
            
            <div v-else class="space-y-4">
              <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg flex items-center gap-2">
                <UIcon :name="issue.status === 'resolved' ? 'i-heroicons-check-circle' : 'i-heroicons-x-circle'" 
                       :class="issue.status === 'resolved' ? 'text-success' : 'text-gray-500'" class="w-5 h-5" />
                <span class="font-medium text-sm">{{ issue.status === 'resolved' ? 'รายการนี้แก้ไขเสร็จสิ้นแล้ว' : 'รายการนี้ถูกยกเลิกแล้ว' }}</span>
              </div>
              <div v-if="issue.admin_note">
                <p class="text-xs text-gray-500">หมายเหตุจากแอดมิน</p>
                <p class="text-sm mt-1 whitespace-pre-wrap">{{ issue.admin_note }}</p>
              </div>
              <div v-if="issue.admin_image_path">
                <p class="text-xs text-gray-500 mb-1">ภาพหลักฐาน</p>
                <img :src="`${useBaseUrl()}${issue.admin_image_path}`" class="rounded-lg w-full" />
              </div>
            </div>
          </UCard>
        </div>
      </div>

      <!-- Cancel Confirmation Modal -->
      <UModal v-model:open="isCancelModalOpen">
        <template #content>
          <UCard class="font-kanit border border-red-100 dark:border-red-900 shadow-xl shadow-red-900/10">
            <template #header>
              <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-red-600 dark:text-red-500 flex items-center gap-2">
                  <UIcon name="i-heroicons-exclamation-triangle-solid" class="w-5 h-5" /> ยืนยันการยกเลิกรายการนี้?
                </h3>
                <UButton color="gray" variant="ghost" icon="i-heroicons-x-mark" @click="isCancelModalOpen = false" />
              </div>
            </template>
            <div class="py-4">
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">โปรดระบุสาเหตุของการยกเลิก:</p>
              <UTextarea v-model="adminNote" placeholder="เช่น ซ้ำซ้อน, ยกเลิกโดยผู้ใช้..." :rows="3" />
            </div>
            <template #footer>
              <div class="flex justify-end gap-3 w-full">
                <UButton variant="soft" color="gray" @click="isCancelModalOpen = false">กลับไปแก้ไข</UButton>
                <UButton color="red" variant="solid" @click="confirmCancel" :loading="isUpdating">ยืนยันการยกเลิก</UButton>
              </div>
            </template>
          </UCard>
        </template>
      </UModal>
    </template>
  </div>
</template>
