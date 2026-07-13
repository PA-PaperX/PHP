<template>
  <div v-motion :initial="{ opacity: 0, y: 20 }" :enter="{ opacity: 1, y: 0, transition: { duration: 500 } }" class="w-full pb-10">
    <div class="w-full bg-white rounded-3xl p-6 sm:p-10 max-w-md mx-auto z-10 relative">
      <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-gray-900 font-['Kanit'] mb-1">สมัครสมาชิก</h2>
      </div>

      <form @submit.prevent="onSubmit" class="space-y-5 font-['Kanit']">
        <div>
          <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
          <UInput id="username" v-model="form.username" 
            type="text" 
            placeholder="demo_user" 
            icon="i-heroicons-user"
            class="w-full"
            color="white"
            variant="outline"
            size="lg"
            :ui="{ rounded: 'rounded-xl', color: { white: { outline: 'border-gray-200 focus:border-primary-400 ring-0 focus:ring-0 shadow-none' } } }"
            required
          />
        </div>

        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <UInput id="email" v-model="form.email" 
            type="email" 
            placeholder="demo@email.com" 
            icon="i-heroicons-envelope"
            class="w-full"
            color="white"
            variant="outline"
            size="lg"
            :ui="{ rounded: 'rounded-xl', color: { white: { outline: 'border-gray-200 focus:border-primary-400 ring-0 focus:ring-0 shadow-none' } } }"
            required
          />
        </div>

        <div>
          <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <UInput id="password" v-model="form.password" 
            :type="showPassword ? 'text' : 'password'" 
            placeholder="enter your password" 
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
          <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
          <UInput id="confirmPassword" v-model="form.confirmPassword" 
            :type="showConfirmPassword ? 'text' : 'password'" 
            placeholder="Confirm your password" 
            icon="i-heroicons-lock-closed"
            class="w-full"
            color="white"
            variant="outline"
            size="lg"
            :ui="{ rounded: 'rounded-xl', color: { white: { outline: 'border-gray-200 focus:border-primary-400 ring-0 focus:ring-0 shadow-none' } } }"
            required
          >
            <template #trailing>
              <UButton color="gray" variant="link" :icon="showConfirmPassword ? 'i-heroicons-eye-slash' : 'i-heroicons-eye'" :padded="false" @click="showConfirmPassword = !showConfirmPassword" />
            </template>
          </UInput>
        </div>
        
        <div v-if="passwordMismatch" class="text-primary-500 text-xs mt-1">
          รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน
        </div>

        <div class="pt-4">
          <UButton 
            type="submit" 
            block 
            size="xl" 
            class="rounded-2xl font-medium text-base shadow-lg shadow-black/10 !bg-black !text-white hover:!bg-gray-800"
            :loading="isLoading"
            :disabled="passwordMismatch"
          >
            สมัครสมาชิก
          </UButton>
        </div>
      </form>

      <div class="mt-8 text-center">
        <div class="flex items-center justify-center gap-4 mb-6">
          <div class="h-px bg-gray-200 flex-1"></div>
          <span class="text-xs text-gray-400">OR</span>
          <div class="h-px bg-gray-200 flex-1"></div>
        </div>
        
        <div class="text-xs font-['Kanit'] text-gray-500">
          มีบัญชีอยู่แล้ว? 
          <NuxtLink to="/login" class="text-primary-400 hover:text-primary-500 font-medium transition-colors">
            ล็อคอิน
          </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, computed } from 'vue'
import { useAuth } from '~/composables/useAuth'

definePageMeta({
  layout: 'auth'
})

const { register, isLoading, error } = useAuth()
const toast = useToast()

const form = reactive({
  username: '',
  email: '',
  password: '',
  confirmPassword: ''
})

const showPassword = ref(false)
const showConfirmPassword = ref(false)

const passwordMismatch = computed(() => {
  if (!form.password || !form.confirmPassword) return false
  return form.password !== form.confirmPassword
})

const onSubmit = async () => {
  if (passwordMismatch.value) return
  
  const success = await register({
    username: form.username,
    email: form.email,
    password: form.password
  })
  
  if (!success && error.value) {
    toast.add({
      title: 'สมัครสมาชิกไม่สำเร็จ',
      description: error.value,
      color: 'red',
      icon: 'i-heroicons-exclamation-circle'
    })
  } else if (success) {
    toast.add({
      title: 'สมัครสมาชิกสำเร็จ',
      description: 'กรุณาเข้าสู่ระบบ',
      color: 'green',
      icon: 'i-heroicons-check-circle'
    })
  }
}
</script>
