<script setup lang="ts">
import { ref, computed } from 'vue'
import { useApi } from '~/composables/useApi'

definePageMeta({
  middleware: 'admin'
})

const toast = useToast()
const { user: currentUser } = useAuth()

// Fetch users
const { data: usersData, pending, refresh } = await useApi<any>('/api/users/index.php')
const users = computed(() => usersData.value?.users || [])

const columns = [
  { accessorKey: 'id', header: 'รหัส' },
  { accessorKey: 'email', header: 'อีเมลผู้ใช้' },
  { accessorKey: 'role', header: 'สิทธิ์' },
  { accessorKey: 'stats', header: 'ประวัติ (ซ่อม/ยืม)' },
  { accessorKey: 'created_at', header: 'วันที่สร้าง' },
  { accessorKey: 'actions', header: 'จัดการ' }
]

// --- Modals State ---
const isDeleteModalOpen = ref(false)
const isSubmitting = ref(false)

const selectedUser = ref<any>(null)

// --- Delete User ---
const openDeleteModal = (user: any) => {
  selectedUser.value = user
  isDeleteModalOpen.value = true
}

const submitDeleteUser = async () => {
  isSubmitting.value = true
  try {
    const formData = new FormData()
    formData.append('id', selectedUser.value.id.toString())

    await $fetch('/api/users/delete.php', {
      method: 'POST',
      baseURL: useBaseUrl(),
      credentials: 'include',
      body: formData
    })
    
    toast.add({ title: 'ลบผู้ใช้งานสำเร็จ', color: 'success', icon: 'i-heroicons-check-circle' })
    isDeleteModalOpen.value = false
    await refresh()
  } catch (err: any) {
    toast.add({ title: 'ลบไม่สำเร็จ', description: err.data?.error || 'ไม่สามารถลบผู้ใช้นี้ได้', color: 'error', icon: 'i-heroicons-exclamation-circle' })
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="space-y-6 pb-10">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4" v-motion-fade>
      <h1 class="text-2xl font-bold font-kanit text-gray-900 dark:text-white">จัดการผู้ใช้งาน (Users)</h1>
    </div>

    <UCard :ui="{ body: { padding: '!p-0' } }" v-motion-fade :delay="100">
      <div id="admin-users-table">
        <UTable :data="users" :columns="columns" :loading="pending" class="font-kanit whitespace-nowrap">
          <template #role-cell="{ row }">
            <UBadge :color="row.original.role === 'admin' ? 'primary' : 'gray'" variant="subtle">
              {{ row.original.role === 'admin' ? 'Admin' : 'User' }}
            </UBadge>
          </template>
          
          <template #stats-cell="{ row }">
            <div class="flex items-center gap-2">
              <UTooltip text="จำนวนการแจ้งซ่อม">
              <span class="flex items-center gap-1 text-sm bg-coral-50 dark:bg-coral-900/30 text-coral-600 px-2 py-0.5 rounded-full">
                <UIcon name="i-heroicons-wrench" class="w-3.5 h-3.5" /> {{ row.original.issues_count }}
              </span>
            </UTooltip>
            <UTooltip text="จำนวนการยืมอุปกรณ์">
              <span class="flex items-center gap-1 text-sm bg-blue-50 dark:bg-blue-900/30 text-blue-600 px-2 py-0.5 rounded-full">
                <UIcon name="i-heroicons-cube" class="w-3.5 h-3.5" /> {{ row.original.borrows_count }}
              </span>
            </UTooltip>
          </div>
        </template>

        <template #created_at-cell="{ row }">
          <span class="text-sm text-gray-500">{{ new Date(row.original.created_at).toLocaleDateString('th-TH') }}</span>
        </template>

        <template #actions-cell="{ row }">
          <div class="flex items-center gap-2">
            <UButton v-if="row.original.id !== currentUser?.id" size="xs" color="red" variant="ghost" icon="i-heroicons-trash" @click="openDeleteModal(row.original)">ลบ</UButton>
            <UButton v-else size="xs" color="gray" variant="ghost" icon="i-heroicons-check" disabled>คุณ</UButton>
          </div>
        </template>
      </UTable>
      </div>
    </UCard>

    <!-- Delete Confirmation Modal -->
    <UModal v-model:open="isDeleteModalOpen">
      <template #content>
        <UCard :ui="{ ring: '', divide: 'divide-y divide-gray-100 dark:divide-gray-800' }">
          <div class="text-center py-4 font-kanit">
            <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
              <UIcon name="i-heroicons-exclamation-triangle" class="w-8 h-8" />
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยืนยันการลบผู้ใช้งาน?</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6 text-sm">
              คุณแน่ใจหรือไม่ที่จะลบ <span class="font-bold text-gray-900 dark:text-white">{{ selectedUser?.email }}</span> ?<br>
              <span class="text-red-500 font-bold">ข้อมูลประวัติการแจ้งซ่อมและการยืมอุปกรณ์ทั้งหมดของผู้ใช้นี้จะถูกลบทิ้งอย่างถาวร (Cascade Delete)</span>
            </p>
            
            <div class="flex justify-center gap-3">
              <UButton color="gray" variant="soft" @click="isDeleteModalOpen = false" class="px-6">ยกเลิก</UButton>
              <UButton color="red" @click="submitDeleteUser" :loading="isSubmitting" class="px-6">ยืนยันการลบ</UButton>
            </div>
          </div>
        </UCard>
      </template>
    </UModal>
  </div>
</template>
