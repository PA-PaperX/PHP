<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useApi } from '~/composables/useApi'

definePageMeta({
  middleware: 'auth'
})

const route = useRoute()
const issueId = route.params.id as string

const { data, pending, error, refresh } = await useApi<any>(`/api/issues/show.php?id=${issueId}`)

let pollInterval: any = null
onMounted(() => {
  pollInterval = setInterval(() => {
    if (issue.value && issue.value.status !== 'closed' && issue.value.status !== 'resolved') {
      refresh()
    }
  }, 5000)
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})

const issue = computed(() => data.value?.issue)

const statusSteps = [
  { value: 'pending', label: 'รอรับเรื่อง', icon: 'i-heroicons-clock' },
  { value: 'in_progress', label: 'กำลังดำเนินการ', icon: 'i-heroicons-wrench-screwdriver' },
  { value: 'resolved', label: 'เสร็จสิ้น', icon: 'i-heroicons-check-circle' }
]

const currentStatusIndex = computed(() => {
  if (!issue.value) return 0
  return statusSteps.findIndex(s => s.value === issue.value.status)
})

const getStatusColor = (index: number) => {
  if (index < currentStatusIndex.value) return 'text-primary-500 dark:text-primary-400'
  if (index === currentStatusIndex.value) return 'text-coral-500 dark:text-coral-400 font-bold scale-110'
  return 'text-gray-300 dark:text-gray-700'
}

const getLineColor = (stepIndex: number) => {
  if (currentStatusIndex.value > stepIndex) return 'bg-primary-500' // Passed
  if (currentStatusIndex.value === stepIndex) {
    if (issue.value?.status === 'closed') return 'bg-red-500' // Cancelled
    return 'bg-coral-500 progress-line-animated' // Current - Animated
  }
  return 'bg-gray-200 dark:bg-gray-800'
}
</script>

<template>
  <div class="max-w-4xl mx-auto space-y-6 pb-10">
    <div v-if="pending" class="flex justify-center py-20">
      <UIcon name="i-heroicons-arrow-path" class="w-10 h-10 animate-spin text-gray-400" />
    </div>

    <div v-else-if="error || !issue" class="text-center py-20 font-kanit">
      <UIcon name="i-heroicons-exclamation-circle" class="w-16 h-16 text-red-500 mx-auto mb-4" />
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ไม่พบข้อมูล</h2>
      <p class="text-gray-500 mt-2">คุณไม่มีสิทธิ์ดูข้อมูลนี้ หรือข้อมูลอาจถูกลบไปแล้ว</p>
      <UButton to="/dashboard" class="mt-6" variant="soft">กลับไปหน้าแรก</UButton>
    </div>

    <div v-else class="space-y-6 font-kanit" v-motion-fade>
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <UButton icon="i-heroicons-arrow-left" color="gray" variant="ghost" to="/dashboard" />
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">รายละเอียดการแจ้งปัญหา #{{ issue.id }}</h1>
        </div>
      </div>

      <!-- Cancelled State -->
      <UCard v-if="issue.status === 'closed'" class="bg-red-50 dark:bg-red-900/10 border-red-100 dark:border-red-900">
        <div class="text-center py-6">
           <UIcon name="i-heroicons-x-circle" class="w-16 h-16 text-red-500 mx-auto mb-4" />
           <h3 class="text-xl font-bold text-red-600 dark:text-red-400">ยกเลิกการแจ้งปัญหา</h3>
           <p class="text-red-500 mt-2">รายการนี้ถูกยกเลิกโดยแอดมินแล้ว</p>
        </div>
      </UCard>

      <!-- Timeline Tracking -->
      <UCard v-else :ui="{ body: { padding: 'p-6 sm:p-10' } }">
        <h3 class="text-lg font-bold mb-8 text-center text-gray-800 dark:text-gray-200">สถานะการดำเนินการ</h3>
        
        <div class="relative flex items-center justify-between w-full max-w-2xl mx-auto">
          <!-- Connecting Lines -->
          <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 flex">
            <div v-for="i in 2" :key="`line-${i}`" class="flex-1 h-full transition-colors duration-500" :class="getLineColor(i-1)"></div>
          </div>
          
          <!-- Steps -->
          <div v-for="(step, index) in statusSteps" :key="step.value" class="relative z-10 flex flex-col items-center gap-3 bg-white dark:bg-gray-900 px-2 transition-all duration-300" :class="getStatusColor(index)">
            <div class="w-12 h-12 rounded-full flex items-center justify-center bg-white dark:bg-gray-900 border-4 transition-colors duration-500" :class="index <= currentStatusIndex ? (index === currentStatusIndex ? 'border-coral-500 shadow-lg shadow-coral-500/30' : 'border-primary-500') : 'border-gray-200 dark:border-gray-800'">
              <UIcon :name="step.icon" class="w-6 h-6" />
            </div>
            <span class="text-sm sm:text-base whitespace-nowrap">{{ step.label }}</span>
          </div>
        </div>

        <!-- Admin info if accepted -->
        <div v-if="issue.status !== 'pending'" class="mt-10 p-5 rounded-2xl border transition-all" :class="issue.status === 'resolved' || issue.status === 'closed' ? 'bg-green-50/50 border-green-100 dark:bg-green-900/10 dark:border-green-800' : 'bg-blue-50/50 border-blue-100 dark:bg-blue-900/10 dark:border-blue-800'">
          <div class="flex items-start gap-4">
            <UAvatar :alt="issue.admin_email || 'Admin'" size="lg" :ui="{ background: 'bg-gray-200 dark:bg-gray-800' }" />
            <div class="flex-1">
              <p class="font-semibold text-gray-900 dark:text-white">รับเรื่องโดย: <span class="text-primary-600 dark:text-primary-400">{{ issue.admin_email || 'แอดมินระบบ' }}</span></p>
              
              <div v-if="issue.admin_note" class="mt-2">
                <p class="text-sm text-gray-500 mb-1">ข้อความจากแอดมิน:</p>
                <div class="bg-white dark:bg-gray-800 p-3 rounded-lg text-sm text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700">
                  {{ issue.admin_note }}
                </div>
              </div>
              
              <div v-if="issue.admin_image_path" class="mt-4">
                <p class="text-sm text-gray-500 mb-2">ภาพประกอบจากแอดมิน:</p>
                <img :src="`http://localhost:8080${issue.admin_image_path}`" alt="Admin photo" class="max-w-xs rounded-xl shadow-sm border border-gray-200 dark:border-gray-700" />
              </div>
            </div>
          </div>
        </div>
      </UCard>

      <!-- Original Request Details -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <UCard>
          <template #header>
            <h3 class="font-bold flex items-center gap-2">
              <UIcon name="i-heroicons-document-text" class="text-primary-500" /> ข้อมูลที่แจ้ง
            </h3>
          </template>
          
          <div class="space-y-4">
            <div>
              <p class="text-sm text-gray-500">หัวข้อปัญหา</p>
              <p class="font-semibold text-lg">{{ issue.title }}</p>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-500">หมวดหมู่</p>
                <UBadge color="gray" variant="solid" class="mt-1 uppercase text-xs">{{ issue.category }}</UBadge>
              </div>
              <div>
                <p class="text-sm text-gray-500">วันที่แจ้ง</p>
                <p class="text-sm mt-1">{{ new Date(issue.created_at).toLocaleString('th-TH') }}</p>
              </div>
            </div>
            
            <USeparator />
            
            <div>
              <p class="text-sm text-gray-500">รายละเอียด</p>
              <p class="mt-1 text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ issue.description || '-' }}</p>
            </div>
            
            <div v-if="issue.location">
              <p class="text-sm text-gray-500">สถานที่</p>
              <p class="mt-1 flex items-center gap-1 text-gray-700 dark:text-gray-300">
                <UIcon name="i-heroicons-map-pin" class="text-coral-500" /> {{ issue.location }}
              </p>
            </div>
          </div>
        </UCard>

        <!-- Attached Image -->
        <UCard v-if="issue.image_path">
          <template #header>
            <h3 class="font-bold flex items-center gap-2">
              <UIcon name="i-heroicons-photo" class="text-primary-500" /> ภาพประกอบที่คุณส่ง
            </h3>
          </template>
          <div class="flex justify-center">
            <img :src="`http://localhost:8080${issue.image_path}`" alt="Issue image" class="rounded-xl max-h-80 object-cover shadow-sm" />
          </div>
        </UCard>
      </div>

    </div>
  </div>
</template>

<style scoped>
.progress-line-animated {
  background-image: linear-gradient(
    -45deg,
    rgba(255, 255, 255, 0.5) 25%,
    transparent 25%,
    transparent 50%,
    rgba(255, 255, 255, 0.5) 50%,
    rgba(255, 255, 255, 0.5) 75%,
    transparent 75%,
    transparent
  );
  background-size: 30px 30px;
  animation: move-stripes 1s linear infinite;
}

@keyframes move-stripes {
  0% {
    background-position: 0 0;
  }
  100% {
    background-position: 30px 0;
  }
}
</style>
