<script setup lang="ts">
import { computed } from 'vue'

const { user } = useAuth()
const isOpen = useSidebar()

const links = computed(() => {
  if (user.value?.role === 'admin') {
    return [
      { label: 'ภาพรวมระบบ', icon: 'i-heroicons-chart-pie', to: '/admin', id: 'admin-menu-dashboard' },
      { label: 'จัดการปัญหา', icon: 'i-heroicons-clipboard-document-list', to: '/admin/issues', id: 'admin-menu-issues' },
      { label: 'คลังอุปกรณ์', icon: 'i-heroicons-cube', to: '/admin/inventory', id: 'admin-menu-inventory' },
      { label: 'จัดการผู้ใช้งาน', icon: 'i-heroicons-users', to: '/admin/users', id: 'admin-menu-users' },
      { label: 'รีเซ็ตรหัสผ่าน (Tickets)', icon: 'i-heroicons-key', to: '/admin/tickets', id: 'admin-menu-tickets' }
    ]
  }
  
  return [
    { label: 'หน้าหลัก', icon: 'i-heroicons-home', to: '/dashboard', id: 'menu-dashboard' },
    { label: 'แจ้งปัญหา', icon: 'i-heroicons-exclamation-circle', to: '/report-issue', id: 'menu-report-issue' },
    { label: 'ยืมคืนอุปกรณ์', icon: 'i-heroicons-cube', to: '/inventory', id: 'menu-inventory' },
    { label: 'ประวัติการใช้งาน', icon: 'i-heroicons-clock', to: '/history', id: 'menu-history' }
  ]
})
</script>
<template>
  <div class="flex flex-col h-full bg-white dark:bg-gray-950">
    <div class="p-4 flex items-center justify-between border-b border-gray-100 dark:border-gray-800">
      <h2 class="text-lg font-bold text-coral-500 font-kanit">เมนู</h2>
      <UButton icon="i-heroicons-x-mark" color="gray" variant="ghost" @click="isOpen = false" />
    </div>
    <nav class="p-4 flex flex-col gap-2 flex-1 overflow-y-auto">
      <NuxtLink v-for="link in links" :key="link.to" :to="link.to" :id="link.id" @click="isOpen = false" class="group flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-coral-50 dark:hover:bg-coral-950 text-gray-700 dark:text-gray-300 hover:text-coral-600 dark:hover:text-coral-400 transition-colors font-kanit">
        <UIcon :name="link.icon" class="w-5 h-5 transition-transform duration-200 group-hover:rotate-12 group-hover:scale-110" />
        <span>{{ link.label }}</span>
      </NuxtLink>
    </nav>
  </div>
</template>
