<template>
  <div v-motion :initial="{ opacity: 0, y: 20 }" :enter="{ opacity: 1, y: 0, transition: { duration: 500 } }" class="w-full pb-10">
    <div class="w-full bg-white rounded-3xl p-6 sm:p-10 max-w-md mx-auto z-10 relative">
      <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-gray-900 font-['Kanit'] mb-1">ลืมรหัสผ่าน</h2>
        <p class="text-xs text-gray-400 font-['Kanit']">กรุณากรอกอีเมลของคุณเพื่อติดต่อ Admin</p>
      </div>

      <form @submit.prevent="onSubmit" class="space-y-5 font-['Kanit']">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <UInput 
            v-model="email" 
            type="email" 
            placeholder="example@gmail.com" 
            icon="i-heroicons-envelope"
            class="w-full"
            color="white"
            variant="outline"
            size="lg"
            :ui="{ rounded: 'rounded-xl', color: { white: { outline: 'border-gray-200 focus:border-primary-400 ring-0 focus:ring-0 shadow-none' } } }"
            required
          />
        </div>

        <div class="pt-4">
          <UButton 
            type="submit" 
            block 
            size="xl" 
            class="rounded-2xl font-medium text-base shadow-lg shadow-black/10 !bg-black !text-white hover:!bg-gray-800"
            :loading="isLoading"
          >
            ดำเนินการต่อ
          </UButton>
        </div>
      </form>

      <div class="mt-8 text-center">
        <div class="text-xs font-['Kanit'] text-gray-500">
          <NuxtLink to="/login" class="text-primary-400 hover:text-primary-500 font-medium transition-colors">
            กลับไปหน้าล็อกอิน
          </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

definePageMeta({
  layout: 'auth'
})

const email = ref('')
const isLoading = ref(false)
const toast = useToast()
const router = useRouter()
const baseUrl = useBaseUrl()

const onSubmit = async () => {
  isLoading.value = true
  try {
    const data = await $fetch<any>(`${baseUrl}/api/tickets/create.php`, {
      method: 'POST',
      body: { email: email.value }
    })
    
    // Save access token securely in local storage or pass via URL
    // For simplicity, passing via URL or sessionStorage. Let's use localStorage.
    if (import.meta.client) {
      localStorage.setItem(`ticket_${data.ticket_id}`, data.access_token)
    }
    
    toast.add({
      title: 'สร้าง Ticket สำเร็จ',
      description: 'กำลังพุ่งตรงไปยังห้องสนทนา...',
      color: 'green'
    })
    
    router.push(`/ticket/${data.ticket_id}`)
  } catch (e: any) {
    toast.add({
      title: 'ไม่สามารถดำเนินการได้',
      description: e.data?.error || 'เกิดข้อผิดพลาด กรุณาลองใหม่',
      color: 'red',
      icon: 'i-heroicons-exclamation-circle'
    })
  } finally {
    isLoading.value = false
  }
}
</script>
