<template>
  <div class="h-[calc(100vh-64px)] flex font-kanit bg-white relative overflow-hidden">
    <!-- Left Sidebar: Ticket List -->
    <div 
      class="w-full md:w-1/3 border-r border-gray-200 flex flex-col absolute md:relative h-full transition-transform duration-300 bg-white z-10"
      :class="selectedTicket ? '-translate-x-full md:translate-x-0' : 'translate-x-0'"
    >
      <div class="p-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
        <h2 class="font-semibold text-gray-800">คำขอรีเซ็ตรหัสผ่าน</h2>
        <UButton icon="i-heroicons-arrow-path" color="gray" variant="ghost" @click="fetchTickets" :loading="isLoadingList" />
      </div>
      
      <div class="flex-1 overflow-y-auto">
        <div v-if="tickets.length === 0" class="p-8 text-center text-gray-400 text-sm">
          ไม่มีคำขอในขณะนี้
        </div>
        <div 
          v-for="ticket in tickets" 
          :key="ticket.id"
          class="p-4 border-b border-gray-100 cursor-pointer hover:bg-gray-50 transition-colors"
          :class="{ 'bg-primary-50': selectedTicket?.id === ticket.id }"
          @click="selectTicket(ticket)"
        >
          <div class="flex justify-between items-start mb-1">
            <span class="font-medium text-gray-900 truncate flex-1">{{ ticket.email }}</span>
            <UBadge :color="ticket.status === 'resolved' ? 'green' : (ticket.status === 'cancelled' ? 'red' : 'orange')" variant="subtle" size="xs">
              {{ ticket.status }}
            </UBadge>
          </div>
          <div class="text-xs text-gray-400">
            {{ formatDate(ticket.created_at) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Right Sidebar: Chat Interface -->
    <div 
      class="w-full md:w-2/3 flex flex-col bg-gray-50 absolute md:relative h-full transition-transform duration-300 z-20 md:z-0"
      :class="selectedTicket ? 'translate-x-0' : 'translate-x-full md:translate-x-0'"
    >
      <div v-if="!selectedTicket" class="absolute inset-0 flex items-center justify-center text-gray-400 hidden md:flex">
        <div class="text-center">
          <UIcon name="i-heroicons-chat-bubble-left-right" class="w-12 h-12 mx-auto mb-3 opacity-50" />
          <p>เลือก Ticket เพื่อเริ่มการสนทนา</p>
        </div>
      </div>
      
      <template v-else>
        <!-- Chat Header -->
        <div class="p-3 md:p-4 bg-white border-b border-gray-200 flex items-center justify-between shadow-sm z-10">
          <div class="flex items-center gap-2">
            <UButton 
              icon="i-heroicons-arrow-left" 
              color="gray" 
              variant="ghost" 
              class="md:hidden mr-1" 
              @click="selectedTicket = null" 
            />
            <UAvatar src="/images/avatar.jpg" alt="User" size="sm" class="bg-gray-200" />
            <div>
              <h3 class="font-bold text-gray-900 leading-tight">{{ selectedTicket.email }}</h3>
              <p class="text-xs text-gray-500">Ticket #{{ selectedTicket.id }}</p>
            </div>
          </div>
          <div class="flex gap-2">
            <UButton 
              v-if="selectedTicket.status === 'pending'"
              color="red" 
              variant="soft"
              icon="i-heroicons-x-circle"
              @click="rejectTicket"
              :loading="isRejecting"
              class="mr-2"
            >
              ปฏิเสธคำขอ
            </UButton>
            <UButton 
              v-if="selectedTicket.status === 'pending'"
              color="primary" 
              icon="i-heroicons-check-circle"
              @click="grantReset"
              :loading="isGranting"
            >
              อนุมัติรีเซ็ตรหัสผ่าน
            </UButton>
          </div>
        </div>

        <!-- Chat Messages -->
        <div class="flex-1 overflow-y-auto p-4 flex flex-col gap-4" ref="chatContainer">
          <div v-for="msg in messages" :key="msg.id" class="flex w-full" :class="msg.sender_type === 'admin' ? 'justify-end' : 'justify-start'">
            
            <!-- System Message -->
            <div v-if="msg.sender_type === 'system'" class="w-full flex justify-center my-2">
              <div class="bg-blue-50 border border-blue-100 text-blue-800 text-xs px-3 py-1 rounded-full text-center">
                {{ msg.message }}
              </div>
            </div>
            
            <!-- User Message -->
            <div v-else-if="msg.sender_type === 'user'" class="flex gap-3 max-w-[70%]">
              <UAvatar src="/images/avatar.jpg" alt="User" size="sm" class="bg-gray-200 mt-1" />
              <div class="bg-white border border-gray-200 text-gray-800 px-4 py-3 rounded-2xl rounded-tl-none shadow-sm">
                <p class="text-sm whitespace-pre-wrap">{{ msg.message }}</p>
                <span class="text-[10px] text-gray-400 mt-1 block">{{ formatDate(msg.created_at) }}</span>
              </div>
            </div>

            <!-- Admin Message -->
            <div v-else class="flex gap-3 max-w-[70%] flex-row-reverse">
              <div class="bg-primary-500 text-white px-4 py-3 rounded-2xl rounded-tr-none shadow-sm">
                <p class="text-sm whitespace-pre-wrap">{{ msg.message }}</p>
                <span class="text-[10px] text-primary-100 mt-1 block text-right">{{ formatDate(msg.created_at) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Chat Input -->
        <div class="p-4 bg-white border-t border-gray-200">
          <form @submit.prevent="sendMessage" class="flex gap-3 items-end">
            <UTextarea 
              v-model="newMessage" 
              placeholder="พิมพ์ข้อความตอบกลับผู้ใช้..." 
              class="flex-1"
              :rows="1"
              autoresize
              :disabled="selectedTicket.status !== 'pending' || isSending"
              @keydown.enter.prevent="sendMessage"
            />
            <UButton 
              type="submit" 
              color="primary" 
              icon="i-heroicons-paper-airplane" 
              class="h-10 px-4 rounded-xl shadow-sm disabled:opacity-50"
              :loading="isSending"
              :disabled="!newMessage.trim() || selectedTicket.status !== 'pending'"
            >
              ส่ง
            </UButton>
          </form>
        </div>
      </template>
    </div>

    <!-- Reject Modal -->
    <UModal v-model:open="isRejectModalOpen">
      <template #content>
        <UCard class="font-kanit" :ui="{ ring: '', divide: 'divide-y divide-gray-100 dark:divide-gray-800' }">
          <template #header>
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-bold text-gray-900 font-['Kanit']">ปฏิเสธคำขอ</h3>
              <UButton color="gray" variant="ghost" icon="i-heroicons-x-mark" class="-my-1" @click="isRejectModalOpen = false" />
            </div>
          </template>
          
          <p class="text-sm text-gray-500 mb-4 font-['Kanit']">กรุณาระบุเหตุผลในการปฏิเสธ (เว้นว่างได้)</p>
          
          <UInput 
            v-model="rejectReason" 
            placeholder="เช่น ข้อมูลไม่ถูกต้อง, หมดเวลา ฯลฯ" 
            class="mb-6 font-['Kanit']"
            autofocus
            @keyup.enter="submitReject"
          />
          
          <template #footer>
            <div class="flex justify-end gap-3 font-['Kanit']">
              <UButton color="gray" variant="ghost" @click="isRejectModalOpen = false">ยกเลิก</UButton>
              <UButton color="red" @click="submitReject" :loading="isRejecting">ยืนยันการปฏิเสธ</UButton>
            </div>
          </template>
        </UCard>
      </template>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue'

definePageMeta({
  layout: 'chat',
  middleware: 'auth'
})

const baseUrl = useBaseUrl()
const toast = useToast()

const tickets = ref<any[]>([])
const isLoadingList = ref(false)
const selectedTicket = ref<any>(null)

const messages = ref<any[]>([])
const newMessage = ref('')
const isSending = ref(false)
const isGranting = ref(false)
const isRejecting = ref(false)
const isRejectModalOpen = ref(false)
const rejectReason = ref('')
const chatContainer = ref<HTMLElement | null>(null)

let listPollInterval: any = null
let chatPollInterval: any = null
let lastMessageId = 0

const formatDate = (dateStr: string) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return date.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' })
}

const scrollToBottom = () => {
  nextTick(() => {
    if (chatContainer.value) {
      chatContainer.value.scrollTop = chatContainer.value.scrollHeight
    }
  })
}

const fetchTickets = async () => {
  try {
    const data = await $fetch<any>(`${baseUrl}/api/tickets/admin_list.php`, {
      credentials: 'include'
    })
    tickets.value = data.tickets || []
    
    // Update selected ticket status if it changed
    if (selectedTicket.value) {
      const updated = tickets.value.find(t => t.id === selectedTicket.value.id)
      if (updated) {
        selectedTicket.value.status = updated.status
      }
    }
  } catch (e) {
    console.error('Failed to fetch tickets', e)
  }
}

const selectTicket = (ticket: any) => {
  selectedTicket.value = ticket
  messages.value = []
  lastMessageId = 0
  fetchMessages()
}

const fetchMessages = async () => {
  if (!selectedTicket.value) return
  
  try {
    const data = await $fetch<any>(`${baseUrl}/api/tickets/messages.php?ticket_id=${selectedTicket.value.id}&last_id=${lastMessageId}`, {
      credentials: 'include'
    })
    
    if (data.messages && data.messages.length > 0) {
      messages.value = [...messages.value, ...data.messages]
      lastMessageId = data.messages[data.messages.length - 1].id
      scrollToBottom()
    }
    
    selectedTicket.value.status = data.ticket_status
    
  } catch (e) {
    console.error('Failed to fetch messages', e)
  }
}

const sendMessage = async () => {
  if (!newMessage.value.trim() || isSending.value || !selectedTicket.value) return
  
  const text = newMessage.value.trim()
  newMessage.value = ''
  isSending.value = true
  
  try {
    await $fetch(`${baseUrl}/api/tickets/messages.php?ticket_id=${selectedTicket.value.id}`, {
      method: 'POST',
      body: { message: text },
      credentials: 'include'
    })
    await fetchMessages()
  } catch (e) {
    toast.add({ title: 'ส่งข้อความไม่สำเร็จ', color: 'red' })
  } finally {
    isSending.value = false
  }
}

const grantReset = async () => {
  if (!selectedTicket.value) return
  
  isGranting.value = true
  try {
    await $fetch(`${baseUrl}/api/tickets/grant_reset.php`, {
      method: 'POST',
      body: { ticket_id: selectedTicket.value.id },
      credentials: 'include'
    })
    toast.add({ title: 'อนุมัติเรียบร้อย', color: 'green' })
    selectedTicket.value = null
    messages.value = []
    await fetchTickets()
  } catch (e: any) {
    toast.add({ title: 'เกิดข้อผิดพลาด', description: e.data?.error || '', color: 'red' })
  } finally {
    isGranting.value = false
  }
}

const rejectTicket = () => {
  if (!selectedTicket.value) return
  rejectReason.value = ''
  isRejectModalOpen.value = true
}

const submitReject = async () => {
  if (!selectedTicket.value) return
  
  isRejecting.value = true
  try {
    await $fetch(`${baseUrl}/api/tickets/reject_ticket.php`, {
      method: 'POST',
      body: { ticket_id: selectedTicket.value.id, reason: rejectReason.value },
      credentials: 'include'
    })
    toast.add({ title: 'ปฏิเสธคำขอเรียบร้อย', color: 'gray' })
    isRejectModalOpen.value = false
    selectedTicket.value = null
    messages.value = []
    await fetchTickets()
  } catch (e: any) {
    toast.add({ title: 'เกิดข้อผิดพลาด', description: e.data?.error || '', color: 'red' })
  } finally {
    isRejecting.value = false
  }
}

onMounted(() => {
  fetchTickets()
  // Poll list every 10 seconds
  listPollInterval = setInterval(fetchTickets, 10000)
  // Poll messages every 3 seconds
  chatPollInterval = setInterval(fetchMessages, 3000)
})

onUnmounted(() => {
  if (listPollInterval) clearInterval(listPollInterval)
  if (chatPollInterval) clearInterval(chatPollInterval)
})
</script>
