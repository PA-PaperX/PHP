<template>
  <div v-motion :initial="{ opacity: 0, y: 20 }" :enter="{ opacity: 1, y: 0, transition: { duration: 500 } }" class="w-full pb-10">
    <div class="w-full bg-white rounded-3xl p-6 sm:p-10 max-w-md mx-auto z-10 relative">
      <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-gray-900 font-['Kanit'] mb-1">ตั้งรหัสผ่านใหม่</h2>
        <p class="text-xs text-gray-400 font-['Kanit']">กรุณากรอกรหัสผ่านใหม่ที่คุณต้องการ</p>
      </div>

      <form @submit.prevent="onSubmit" class="space-y-5 font-['Kanit']">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
          <UInput 
            v-model="password" 
            :type="showPassword ? 'text' : 'password'" 
            placeholder="รหัสผ่านใหม่อย่างน้อย 6 ตัวอักษร" 
            icon="i-heroicons-lock-closed"
            class="w-full"
            color="white"
            variant="outline"
            size="lg"
            :ui="{ rounded: 'rounded-xl', color: { white: { outline: 'border-gray-200 focus:border-primary-400 ring-0 focus:ring-0 shadow-none' } } }"
            required
          >
            <template #trailing>
              <UButton color="gray" variant="link" :icon="showPassword ? 'i-heroicons-eye-slash' : 'i-heroicons-eye'" :padded="false" @click="showPassword = !showPassword" />
            </template>
          </UInput>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
          <UInput 
            v-model="confirmPassword" 
            :type="showPassword ? 'text' : 'password'" 
            placeholder="ยืนยันรหัสผ่านใหม่อีกครั้ง" 
            icon="i-heroicons-lock-closed"
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
            บันทึกรหัสผ่านใหม่
          </UButton>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

definePageMeta({
  layout: 'auth'
})

const route = useRoute()
const router = useRouter()
const baseUrl = useBaseUrl()
const toast = useToast()

const token = route.query.token as string
const password = ref('')
const confirmPassword = ref('')
const showPassword = ref(false)
const isLoading = ref(false)

if (!token) {
  router.push('/login')
}

const onSubmit = async () => {
  if (password.value !== confirmPassword.value) {
    toast.add({ title: 'รหัสผ่านไม่ตรงกัน', color: 'red' })
    return
  }
  
  if (password.value.length < 6) {
    toast.add({ title: 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร', color: 'red' })
    return
  }
  
  isLoading.value = true
  try {
    await $fetch(`${baseUrl}/api/auth/reset_password`, {
      method: 'POST',
      body: { token, password: password.value }
    })
    
    toast.add({
      title: 'ตั้งรหัสผ่านสำเร็จ!',
      description: 'กรุณาล็อกอินด้วยรหัสผ่านใหม่ของคุณ',
      color: 'green'
    })
    
    router.push('/login')
  } catch (e: any) {
    toast.add({
      title: 'ไม่สามารถเปลี่ยนรหัสผ่านได้',
      description: e.data?.error || 'Token อาจหมดอายุหรือไม่ถูกต้อง',
      color: 'red',
      icon: 'i-heroicons-exclamation-circle'
    })
  } finally {
    isLoading.value = false
  }
}
</script>
