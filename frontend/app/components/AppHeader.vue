<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'

const isOpen = useSidebar()
const { logout, user } = useAuth()
const router = useRouter()
const isProfileModalOpen = ref(false)

const notifications = ref<any[]>([])
const notificationCount = ref(0)
const hasViewedNotifications = ref(false)
let pollInterval: any = null

const fetchNotifications = async () => {
  if (!user.value) return
  try {
    const res = await $fetch<any>('/api/notifications/index.php', {
      baseURL: useBaseUrl(),
      credentials: 'include'
    })
    notifications.value = res.notifications || []
    
    if (res.count > notificationCount.value) {
      hasViewedNotifications.value = false
    }
    
    notificationCount.value = res.count || 0
  } catch (e) {
    console.error('Failed to fetch notifications', e)
  }
}

onMounted(() => {
  fetchNotifications()
  pollInterval = setInterval(fetchNotifications, 10000) // Poll every 10 seconds
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})

const markAsRead = async (notif: any) => {
  try {
    await $fetch('/api/notifications/read.php', {
      method: 'POST',
      baseURL: useBaseUrl(),
      credentials: 'include',
      body: { type: notif.type, id: notif.id }
    })
    notificationCount.value = Math.max(0, notificationCount.value - 1)
    notifications.value = notifications.value.filter((n: any) => n.id !== notif.id || n.type !== notif.type)
    
    // Navigate based on type
    if (user.value?.role === 'admin') {
      if (notif.type === 'issue') router.push('/admin/issues')
      else if (notif.type === 'ticket') router.push('/admin/tickets')
      else router.push('/admin/inventory')
    } else {
      if (notif.type === 'issue') router.push('/')
      else router.push('/inventory')
    }
  } catch (e) {
    console.error(e)
  }
}


const userItems = computed(() => [
  [
    { label: 'โปรไฟล์ของฉัน', icon: 'i-heroicons-user', onSelect: () => isProfileModalOpen.value = true }
  ],
  [
    { label: 'ออกจากระบบ', icon: 'i-heroicons-arrow-right-on-rectangle', onSelect: () => logout() }
  ]
])
</script>

<template>
  <header class="h-16 flex items-center justify-between px-6 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 sticky top-0 z-50">
    <div class="flex items-center gap-4">
      <UButton icon="i-heroicons-bars-3" variant="ghost" color="gray" class="lg:hidden" @click="isOpen = true" />
      <UButton icon="i-heroicons-bars-3" variant="ghost" color="gray" class="hidden lg:flex" @click="isOpen = true" />
      <h1 class="text-xl font-bold text-coral-500 font-kanit tracking-wide">ไอย๊าห์ Iya</h1>
    </div>
    <div class="flex items-center gap-4">
      
      <!-- Notifications Popover -->
      <UPopover :popper="{ placement: 'bottom-end' }">
        <UButton 
          variant="ghost" 
          :color="notificationCount > 0 ? 'primary' : 'gray'" 
          :class="[notificationCount > 0 ? 'text-coral-500' : '', 'relative']"
          square
          @click="hasViewedNotifications = true"
        >
          <UIcon 
            name="i-heroicons-bell" 
            class="w-5 h-5" 
            :class="[notificationCount > 0 && user?.role === 'admin' && !hasViewedNotifications ? 'animate-ring' : '']" 
          />
          <UBadge 
            v-if="notificationCount > 0" 
            color="red" 
            size="xs" 
            class="absolute -top-1 -right-1 px-1.5 min-w-[20px] justify-center shadow-sm"
            :ui="{ rounded: 'rounded-full' }"
          >
            {{ notificationCount > 99 ? '99+' : notificationCount }}
          </UBadge>
        </UButton>
        
        <template #panel>
          <div class="p-2 w-80 font-kanit max-h-96 overflow-y-auto bg-white dark:bg-gray-900 rounded-lg shadow-lg ring-1 ring-gray-200 dark:ring-gray-800">
            <h3 class="font-bold text-gray-900 dark:text-white px-2 py-1 border-b mb-2">การแจ้งเตือน</h3>
            <div v-if="notifications.length === 0" class="text-gray-500 text-sm text-center py-4">
              ไม่มีการแจ้งเตือนใหม่
            </div>
            <div v-else class="space-y-1">
              <div 
                v-for="(n, idx) in notifications" 
                :key="idx" 
                @click="markAsRead(n)"
                class="px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer rounded-lg transition-colors border-l-4 border-coral-500 bg-coral-50 dark:bg-coral-950/20"
              >
                <div class="flex gap-3">
                  <UIcon :name="n.type === 'issue' ? 'i-heroicons-wrench' : n.type === 'ticket' ? 'i-heroicons-key' : 'i-heroicons-cube'" class="w-5 h-5 text-coral-500 mt-0.5 shrink-0" />
                  <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                      {{ n.type === 'issue' ? 'แจ้งซ่อม' : n.type === 'ticket' ? 'รีเซ็ตรหัสผ่าน' : 'ยืมอุปกรณ์' }} 
                      <span v-if="user?.role !== 'admin' && n.status" class="text-xs ml-1" :class="n.status === 'resolved' || n.status === 'approved' ? 'text-emerald-500' : 'text-red-500'">
                        ({{ n.status === 'resolved' ? 'เสร็จสิ้น' : n.status === 'approved' ? 'อนุมัติ' : n.status === 'rejected' ? 'ไม่อนุมัติ' : 'ยกเลิก' }})
                      </span>
                    </p>
                    <p class="text-xs text-gray-600 dark:text-gray-300 line-clamp-1">
                      {{ n.title }}
                    </p>
                    <p class="text-[10px] text-gray-400 mt-1">
                      {{ new Date(n.created_at).toLocaleString('th-TH') }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>

        <template #content>
          <div class="p-2 w-80 font-kanit max-h-96 overflow-y-auto bg-white dark:bg-gray-900 rounded-lg shadow-lg ring-1 ring-gray-200 dark:ring-gray-800">
            <h3 class="font-bold text-gray-900 dark:text-white px-2 py-1 border-b mb-2">การแจ้งเตือน</h3>
            <div v-if="notifications.length === 0" class="text-gray-500 text-sm text-center py-4">
              ไม่มีการแจ้งเตือนใหม่
            </div>
            <div v-else class="space-y-1">
              <div 
                v-for="(n, idx) in notifications" 
                :key="idx" 
                @click="markAsRead(n)"
                class="px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer rounded-lg transition-colors border-l-4 border-coral-500 bg-coral-50 dark:bg-coral-950/20"
              >
                <div class="flex gap-3">
                  <UIcon :name="n.type === 'issue' ? 'i-heroicons-wrench' : n.type === 'ticket' ? 'i-heroicons-key' : 'i-heroicons-cube'" class="w-5 h-5 text-coral-500 mt-0.5 shrink-0" />
                  <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                      {{ n.type === 'issue' ? 'แจ้งซ่อม' : n.type === 'ticket' ? 'รีเซ็ตรหัสผ่าน' : 'ยืมอุปกรณ์' }} 
                      <span v-if="user?.role !== 'admin' && n.status" class="text-xs ml-1" :class="n.status === 'resolved' || n.status === 'approved' ? 'text-emerald-500' : 'text-red-500'">
                        ({{ n.status === 'resolved' ? 'เสร็จสิ้น' : n.status === 'approved' ? 'อนุมัติ' : n.status === 'rejected' ? 'ไม่อนุมัติ' : 'ยกเลิก' }})
                      </span>
                    </p>
                    <p class="text-xs text-gray-600 dark:text-gray-300 line-clamp-1">
                      {{ n.title }}
                    </p>
                    <p class="text-[10px] text-gray-400 mt-1">
                      {{ new Date(n.created_at).toLocaleString('th-TH') }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>
      </UPopover>
      
      <UDropdownMenu :items="userItems">
        <div id="profile-menu-button" class="flex items-center gap-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900 py-1 px-2 rounded-lg transition-colors">
          <div class="text-right hidden sm:block">
            <p class="text-sm font-bold font-kanit text-gray-900 dark:text-white">{{ user?.username || 'กำลังโหลด...' }}</p>
            <p class="text-xs font-kanit uppercase" :class="user?.role === 'admin' ? 'text-coral-500 font-bold' : 'text-gray-500'">Role: {{ user?.role || 'user' }}</p>
          </div>
          <UAvatar :src="user?.profile_image ? `${useBaseUrl()}${user.profile_image}` : undefined" :alt="user?.username?.charAt(0).toUpperCase() || 'U'" size="sm" class="bg-primary-500 text-white font-bold" />
        </div>
        
        <template #item="{ item }">
          <div class="flex items-center gap-2 group w-full px-1 py-0.5 font-kanit">
            <UIcon :name="item.icon" class="w-4 h-4 text-gray-400 dark:text-gray-500 transition-transform duration-200 group-hover:rotate-12 group-hover:scale-110 group-hover:text-coral-500" />
            <span class="truncate">{{ item.label }}</span>
          </div>
        </template>
      </UDropdownMenu>
    </div>
    
    <ProfileModal v-model="isProfileModalOpen" />
  </header>
</template>

<style scoped>
@keyframes ring {
  0% { transform: rotate(0); }
  5% { transform: rotate(15deg); }
  10% { transform: rotate(-12deg); }
  15% { transform: rotate(18deg); }
  20% { transform: rotate(-15deg); }
  25% { transform: rotate(12deg); }
  30% { transform: rotate(-10deg); }
  35% { transform: rotate(8deg); }
  40% { transform: rotate(-6deg); }
  45% { transform: rotate(4deg); }
  50% { transform: rotate(0); }
  100% { transform: rotate(0); }
}
.animate-ring {
  animation: ring 2s ease-in-out infinite;
  transform-origin: top center;
}
</style>
