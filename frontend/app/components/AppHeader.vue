<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useWindowScroll } from '@vueuse/core'

const { logout, user } = useAuth()
const router = useRouter()
const route = useRoute()
const isProfileModalOpen = ref(false)
const isOpen = ref(false)

const { y } = useWindowScroll()
const scrolled = computed(() => y.value > 10)

// Navigation links based on user role
const navLinks = computed(() => {
  if (user.value?.role === 'admin') {
    return [
      { label: 'ภาพรวมระบบ', icon: 'i-heroicons-chart-pie', to: '/admin' },
      { label: 'จัดการปัญหา', icon: 'i-heroicons-clipboard-document-list', to: '/admin/issues' },
      { label: 'คลังอุปกรณ์', icon: 'i-heroicons-cube', to: '/admin/inventory' },
      { label: 'จัดการผู้ใช้งาน', icon: 'i-heroicons-users', to: '/admin/users' },
      { label: 'Tickets', icon: 'i-heroicons-key', to: '/admin/tickets' }
    ]
  }
  
  return [
    { label: 'หน้าหลัก', icon: 'i-heroicons-home', to: '/dashboard' },
    { label: 'แจ้งปัญหา', icon: 'i-heroicons-exclamation-circle', to: '/report-issue' },
    { label: 'ยืมคืนอุปกรณ์', icon: 'i-heroicons-cube', to: '/inventory' },
    { label: 'ประวัติ', icon: 'i-heroicons-clock', to: '/history' }
  ]
})

// Close mobile menu on route change
watch(() => route.path, () => {
  isOpen.value = false
})

// Prevent body scroll when mobile menu is open
watch(isOpen, (val) => {
  if (typeof document !== 'undefined') {
    if (val) document.body.style.overflow = 'hidden'
    else document.body.style.overflow = ''
  }
})

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
  pollInterval = setInterval(fetchNotifications, 10000)
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
  if (typeof document !== 'undefined') document.body.style.overflow = ''
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
  <header 
    :class="[
      'flex flex-col w-full transition-all duration-300 ease-out sticky top-0 z-50 border-b',
      scrolled && !isOpen
        ? 'bg-white/70 dark:bg-gray-950/70 backdrop-blur-xl border-gray-200/50 dark:border-gray-800/50 shadow-sm'
        : 'bg-white dark:bg-gray-950 border-gray-100 dark:border-gray-900',
      isOpen ? 'bg-white/95 dark:bg-gray-950/95 backdrop-blur-xl' : ''
    ]"
  >
    <nav :class="['flex items-center justify-between px-4 sm:px-6 transition-all duration-300 w-full mx-auto max-w-7xl', scrolled && !isOpen ? 'h-14' : 'h-16']">
      
      <!-- Left: Logo -->
      <div class="flex items-center gap-3 sm:gap-4 shrink-0">
        <NuxtLink to="/" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
          <h1 class="text-lg sm:text-xl font-bold text-coral-500 font-kanit tracking-wide">ไอย๊าห์ Iya</h1>
        </NuxtLink>
      </div>

      <!-- Center: Desktop Navigation -->
      <div class="hidden lg:flex items-center justify-center gap-1 flex-1 px-4">
        <NuxtLink 
          v-for="link in navLinks" 
          :key="link.to" 
          :to="link.to"
          active-class="bg-coral-50/80 text-coral-600 dark:bg-coral-950/40 dark:text-coral-300 font-medium"
          class="px-3 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white transition-all font-kanit flex items-center gap-1.5 whitespace-nowrap group"
        >
          <UIcon :name="link.icon" class="w-4 h-4 opacity-70 group-hover:opacity-100 transition-opacity" />
          {{ link.label }}
        </NuxtLink>
      </div>

      <!-- Right: User Actions -->
      <div class="flex items-center gap-1 sm:gap-2 shrink-0">
        <!-- Notifications Popover -->
        <UPopover :popper="{ placement: 'bottom-end' }">
          <UButton 
            variant="ghost" 
            :color="notificationCount > 0 ? 'primary' : 'gray'" 
            :class="[notificationCount > 0 ? 'text-coral-500' : 'text-gray-600 dark:text-gray-300', 'relative hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors']"
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
          <div id="profile-menu-button" class="flex items-center gap-3 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 py-1.5 px-2 rounded-xl transition-colors">
            <div class="text-right hidden sm:block">
              <p class="text-sm font-bold font-kanit text-gray-900 dark:text-white leading-none">{{ user?.username || 'กำลังโหลด...' }}</p>
              <p class="text-[10px] font-kanit uppercase tracking-wider mt-1" :class="user?.role === 'admin' ? 'text-coral-500 font-bold' : 'text-gray-500'">{{ user?.role || 'user' }}</p>
            </div>
            <UAvatar :src="user?.profile_image ? `${useBaseUrl()}${user.profile_image}` : undefined" :alt="user?.username?.charAt(0).toUpperCase() || 'U'" size="sm" class="bg-primary-500 text-white font-bold ring-2 ring-white dark:ring-gray-950" />
          </div>
          
          <template #item="{ item }">
            <div class="flex items-center gap-2 group w-full px-1 py-0.5 font-kanit">
              <UIcon :name="item.icon" class="w-4 h-4 text-gray-400 dark:text-gray-500 transition-transform duration-200 group-hover:rotate-12 group-hover:scale-110 group-hover:text-coral-500" />
              <span class="truncate">{{ item.label }}</span>
            </div>
          </template>
        </UDropdownMenu>

        <!-- Mobile Menu Toggle -->
        <UButton variant="ghost" color="gray" class="lg:hidden p-1.5 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors ml-1" @click="isOpen = !isOpen">
          <MenuToggleIcon :open="isOpen" class="w-6 h-6 text-gray-700 dark:text-gray-300" :duration="300" />
        </UButton>
      </div>
    </nav>

    <!-- Mobile Full Screen Menu Overlay -->
    <ClientOnly>
      <Teleport to="body">
        <div
          v-show="isOpen"
          class="lg:hidden fixed top-16 right-0 bottom-0 left-0 z-[100] flex flex-col overflow-y-auto bg-white/95 dark:bg-gray-950/95 supports-[backdrop-filter]:bg-white/90 dark:supports-[backdrop-filter]:bg-gray-950/90 backdrop-blur-2xl border-t border-gray-100 dark:border-gray-800 p-4 animate-in slide-in-from-bottom-2 fade-in duration-200"
        >
          <div class="flex flex-col gap-2 w-full max-w-md mx-auto">
            <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2 font-kanit px-2 mt-2">เมนูหลัก</span>
            
            <NuxtLink
              v-for="link in navLinks"
              :key="link.to"
              :to="link.to"
              active-class="bg-coral-50 dark:bg-coral-950/40 text-coral-600 dark:text-coral-400 font-bold border-coral-200 dark:border-coral-800/50 shadow-sm"
              class="flex items-center gap-3 p-3.5 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-900 border border-transparent transition-all font-kanit group"
              @click="isOpen = false"
            >
              <div class="bg-gray-100 dark:bg-gray-800/50 flex items-center justify-center p-2 rounded-lg group-[.active]:bg-coral-100 dark:group-[.active]:bg-coral-900/40 group-[.active]:text-coral-600 dark:group-[.active]:text-coral-400 transition-colors">
                <UIcon :name="link.icon" class="w-5 h-5" />
              </div>
              <span class="text-base">{{ link.label }}</span>
            </NuxtLink>
          </div>
        </div>
      </Teleport>
    </ClientOnly>

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

.active {
  /* Used as target class for NuxtLink active-class */
}
</style>
