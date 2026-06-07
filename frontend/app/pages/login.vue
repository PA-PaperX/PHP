<template>
  <div class="relative isolate w-full pb-10">
    <DottedSurface />
    <div class="pointer-events-none fixed inset-0 z-[1] bg-[radial-gradient(circle_at_center,rgba(248,85,85,0.045),transparent_58%)]"></div>

    <div v-motion :initial="{ opacity: 0, y: 20 }" :enter="{ opacity: 1, y: 0, transition: { duration: 500 } }" class="relative z-10 w-full">
      <div class="w-full bg-white/95 backdrop-blur-sm rounded-3xl p-6 sm:p-10 max-w-md mx-auto relative shadow-2xl shadow-slate-950/10 ring-1 ring-slate-950/5">
      <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-gray-900 font-['Kanit'] mb-1">ยินดีต้อนรับกลับ!!</h2>
        <p class="text-xs text-gray-400 font-['Kanit']">กรุณาเข้าสู่บัญชี</p>
      </div>

      <form @submit.prevent="onSubmit" class="space-y-5 font-['Kanit']">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
          <UInput 
            v-model="form.username" 
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
          <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <UInput 
            v-model="form.password" 
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

        <div class="flex items-center justify-between mt-4">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="form.remember" class="rounded-sm border-gray-300 text-primary-400 focus:ring-primary-400 focus:ring-offset-0 w-4 h-4" />
            <span class="text-xs text-gray-500 font-medium">จดจำฉัน</span>
          </label>
          <NuxtLink to="/forgot-password" class="text-xs text-primary-400 hover:text-primary-500 transition-colors font-medium">ลืมรหัสผ่าน?</NuxtLink>
        </div>

        <div class="pt-4">
          <button 
            type="submit" 
            class="relative w-full flex items-center justify-center h-[52px] rounded-2xl font-medium text-base shadow-lg shadow-black/10 overflow-hidden group transition-all duration-200"
            :class="isHovered ? 'border border-black/10 bg-white text-black' : 'border border-black/10 bg-black text-white active:scale-[0.98]'"
            :disabled="isLoading"
            @mousemove="handleMouseMove"
            @mouseenter="handleMouseEnter"
            @mouseleave="handleMouseLeave"
            ref="loginBtn"
          >
            <!-- Default Background for mobile / non-hover -->
            <div v-if="!isHovered" class="absolute inset-0 bg-black pointer-events-none"></div>

            <!-- Desktop Hover Background -->
            <div 
              class="absolute inset-0 bg-white transition-opacity duration-300 pointer-events-none"
              :class="isHovered ? 'opacity-100' : 'opacity-0'"
            ></div>
            
            <!-- Mouse Tracking Shadow/Gradient Effect -->
            <div 
              class="absolute bg-black transition-opacity duration-300 pointer-events-none"
              :class="isHovered ? 'opacity-100' : 'opacity-0'"
              :style="{
                left: `${mouseX}px`,
                top: `${mouseY}px`,
                width: '180px',
                height: '180px',
                transform: 'translate(-50%, -50%)',
                borderRadius: '50%',
                filter: 'blur(30px)'
              }"
            ></div>

            <!-- Content -->
            <div class="relative z-10 flex items-center text-white mix-blend-difference">
              <UIcon v-if="isLoading" name="i-heroicons-arrow-path" class="w-5 h-5 animate-spin mr-2" />
              ล็อคอิน
            </div>
          </button>
        </div>
      </form>

      <div class="mt-8 text-center">
        <div class="flex items-center justify-center gap-4 mb-6">
          <div class="h-px bg-gray-200 flex-1"></div>
          <span class="text-xs text-gray-400">OR</span>
          <div class="h-px bg-gray-200 flex-1"></div>
        </div>
        
        <div class="text-xs font-['Kanit'] text-gray-500">
          ยังไม่มีบัญชีหรอ? 
          <NuxtLink to="/register" class="text-primary-400 hover:text-primary-500 font-medium transition-colors">
            สมัครสมาชิก
          </NuxtLink>
        </div>
      </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive } from 'vue'
import DottedSurface from '~/components/ui/DottedSurface.client.vue'
import { useAuth } from '~/composables/useAuth'

definePageMeta({
  layout: 'auth'
})

const { login, isLoading, error } = useAuth()

const form = reactive({
  username: '',
  password: '',
  remember: false
})

const showPassword = ref(false)

const toast = useToast()

const isHovered = ref(false)
const mouseX = ref(0)
const mouseY = ref(0)
const loginBtn = ref<HTMLElement | null>(null)

const handleMouseMove = (e: MouseEvent) => {
  // Only apply on devices with actual hover capability (desktop)
  if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) return
  
  if (!loginBtn.value) return
  const rect = loginBtn.value.getBoundingClientRect()
  mouseX.value = e.clientX - rect.left
  mouseY.value = e.clientY - rect.top
}

const handleMouseEnter = () => {
  if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) return
  isHovered.value = true
}

const handleMouseLeave = () => {
  isHovered.value = false
}

const onSubmit = async () => {
  const success = await login({
    username: form.username,
    password: form.password
  })
  
  if (!success && error.value) {
    toast.add({
      title: 'เข้าสู่ระบบล้มเหลว',
      description: error.value,
      color: 'red',
      icon: 'i-heroicons-exclamation-circle'
    })
  }
}
</script>
