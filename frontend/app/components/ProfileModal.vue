<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useAuth } from '~/composables/useAuth'

const props = defineProps<{
  modelValue: boolean
}>()

const emit = defineEmits(['update:modelValue'])

const isOpen = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const { user, fetchUser } = useAuth()
const toast = useToast()
const isUploading = ref(false)
const isSaving = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)

const form = ref({
  username: '',
  old_password: '',
  new_password: ''
})

// Reset form when modal opens
watch(isOpen, (val) => {
  if (val && user.value) {
    form.value.username = user.value.username || ''
    form.value.old_password = ''
    form.value.new_password = ''
  }
})

const triggerFileInput = () => {
  if (fileInput.value) {
    fileInput.value.click()
  }
}

const handleFileUpload = async (event: Event) => {
  const target = event.target as HTMLInputElement
  if (!target.files || target.files.length === 0) return
  
  const file = target.files[0]
  if (!file.type.startsWith('image/')) {
    toast.add({ title: 'ไฟล์ไม่รองรับ', description: 'กรุณาอัปโหลดไฟล์รูปภาพเท่านั้น', color: 'error' })
    return
  }

  isUploading.value = true
  try {
    const formData = new FormData()
    formData.append('profile_image', file)
    
    const baseUrl = useBaseUrl()
    await $fetch(`${baseUrl}/api/auth/update_profile.php`, {
      method: 'POST',
      body: formData,
      credentials: 'include'
    })
    
    await fetchUser()
    toast.add({ title: 'อัปโหลดรูปภาพสำเร็จ', color: 'success', icon: 'i-heroicons-check-circle' })
  } catch (err: any) {
    toast.add({ 
      title: 'เกิดข้อผิดพลาด', 
      description: err.data?.error || 'ไม่สามารถอัปโหลดรูปภาพได้', 
      color: 'error' 
    })
  } finally {
    isUploading.value = false
    if (target) target.value = ''
  }
}

const saveProfile = async () => {
  isSaving.value = true
  try {
    const formData = new FormData()
    if (!form.value.username.trim()) {
      toast.add({ title: 'กรุณากรอกชื่อผู้ใช้งาน', color: 'warning' })
      isSaving.value = false
      return
    }

    const usernameRegex = /^[a-zA-Z0-9_]+$/
    if (!usernameRegex.test(form.value.username.trim())) {
      toast.add({ title: 'ชื่อผู้ใช้ไม่ถูกต้อง', description: 'ชื่อผู้ใช้ต้องเป็นภาษาอังกฤษ ตัวเลข หรือขีดล่าง (_) เท่านั้น และห้ามเว้นวรรค', color: 'error' })
      isSaving.value = false
      return
    }

    if (form.value.username.trim() !== user.value?.username) {
      formData.append('username', form.value.username.trim())
    }
    if (form.value.new_password) {
      if (!form.value.old_password) {
        toast.add({ title: 'กรุณากรอกรหัสผ่านเดิม', color: 'warning' })
        isSaving.value = false
        return
      }
      formData.append('old_password', form.value.old_password)
      formData.append('new_password', form.value.new_password)
    }

    // Only send request if something changed
    let hasChanges = false
    for (const [key, value] of formData.entries()) {
      hasChanges = true
    }

    if (!hasChanges) {
      isOpen.value = false
      return
    }

    const baseUrl = useBaseUrl()
    await $fetch(`${baseUrl}/api/auth/update_profile.php`, {
      method: 'POST',
      body: formData,
      credentials: 'include'
    })
    
    await fetchUser()
    toast.add({ title: 'อัปเดตข้อมูลสำเร็จ', color: 'success', icon: 'i-heroicons-check-circle' })
    isOpen.value = false
  } catch (err: any) {
    toast.add({ 
      title: 'เกิดข้อผิดพลาด', 
      description: err.data?.error || 'ไม่สามารถอัปเดตข้อมูลได้', 
      color: 'error' 
    })
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <UModal v-model:open="isOpen">
    <template #content>
      <UCard class="font-kanit" :ui="{ ring: '', divide: 'divide-y divide-gray-100 dark:divide-gray-800' }">
        <template #header>
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">การตั้งค่าโปรไฟล์</h3>
            <UButton color="gray" variant="ghost" icon="i-heroicons-x-mark" class="-my-1" @click="isOpen = false" />
          </div>
        </template>
        
        <div class="py-2 flex flex-col space-y-6">
          <div class="flex flex-col items-center justify-center">
            <div class="relative group cursor-pointer mb-2" @click="triggerFileInput">
              <UAvatar 
                :src="user?.profile_image ? `${useBaseUrl()}${user.profile_image}` : undefined" 
                :alt="user?.username?.charAt(0).toUpperCase() || 'U'" 
                size="3xl" 
                class="w-28 h-28 text-4xl bg-primary-500 text-white font-bold ring-4 ring-white dark:ring-gray-900 shadow-xl"
              />
              <div class="absolute inset-0 bg-black/40 rounded-full flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                <UIcon name="i-heroicons-camera" class="w-8 h-8 text-white mb-1" />
                <span class="text-white text-xs font-medium">เปลี่ยนรูปภาพ</span>
              </div>
              
              <div v-if="isUploading" class="absolute inset-0 bg-black/60 rounded-full flex flex-col items-center justify-center">
                <UIcon name="i-heroicons-arrow-path" class="w-8 h-8 text-white animate-spin" />
              </div>
            </div>
            <input 
              type="file" 
              ref="fileInput" 
              accept="image/*" 
              class="hidden" 
              @change="handleFileUpload"
            />
            <UBadge class="mt-2" :color="user?.role === 'admin' ? 'primary' : 'gray'" variant="soft">
              {{ user?.role === 'admin' ? 'ผู้ดูแลระบบ' : 'ผู้ใช้งานทั่วไป' }}
            </UBadge>
          </div>

          <form @submit.prevent="saveProfile" class="space-y-4 px-2">
            <UFormField label="อีเมล (ไม่สามารถแก้ไขได้)" name="email">
              <UInput :value="user?.email" disabled icon="i-heroicons-envelope" />
            </UFormField>

            <UFormField label="ชื่อผู้ใช้งาน (Username)" name="username">
              <UInput v-model="form.username" icon="i-heroicons-user" />
            </UFormField>

            <USeparator class="my-4" label="เปลี่ยนรหัสผ่าน (เว้นว่างถ้าไม่ต้องการเปลี่ยน)" />

            <div class="grid grid-cols-2 gap-4">
              <UFormField label="รหัสผ่านเดิม" name="old_password">
                <UInput v-model="form.old_password" type="password" placeholder="••••••••" icon="i-heroicons-lock-closed" />
              </UFormField>
              
              <UFormField label="รหัสผ่านใหม่" name="new_password">
                <UInput v-model="form.new_password" type="password" placeholder="••••••••" icon="i-heroicons-key" />
              </UFormField>
            </div>

            <div class="pt-4 flex justify-end gap-3">
              <UButton color="gray" variant="ghost" @click="isOpen = false">ยกเลิก</UButton>
              <UButton type="submit" color="primary" :loading="isSaving">บันทึกข้อมูล</UButton>
            </div>
          </form>
        </div>
      </UCard>
    </template>
  </UModal>
</template>
