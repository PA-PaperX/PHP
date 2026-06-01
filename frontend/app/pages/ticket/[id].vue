<template>
  <div class="min-h-screen bg-gray-50 flex flex-col font-['Kanit']">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between sticky top-0 z-10 shadow-sm">
      <div class="flex items-center gap-3">
        <UButton icon="i-heroicons-arrow-left" color="gray" variant="ghost" to="/login" />
        <h1 class="text-xl font-bold text-gray-800">Support Ticket #{{ ticketId }}</h1>
      </div>
      <div>
        <UBadge :color="statusColor" class="px-3 py-1 text-sm rounded-full capitalize shadow-sm">
          {{ statusText }}
        </UBadge>
      </div>
    </header>

    <!-- Chat Area -->
    <main class="flex-1 p-4 md:p-6 overflow-y-auto flex flex-col gap-4 max-w-4xl w-full mx-auto" ref="chatContainer">
      <div v-for="msg in messages" :key="msg.id" class="flex w-full" :class="msg.sender_type === 'user' ? 'justify-end' : 'justify-start'">
        
        <!-- System Message -->
        <div v-if="msg.sender_type === 'system'" class="w-full flex flex-col items-center my-4">
          <div class="bg-blue-50 border border-blue-100 text-blue-800 text-sm px-4 py-2 rounded-xl shadow-sm text-center flex flex-col items-center gap-2">
            <UIcon name="i-heroicons-information-circle" class="w-5 h-5 text-blue-500" />
            <span>{{ msg.message }}</span>
            <div v-if="ticketStatus === 'resolved' && resetToken" class="mt-2">
              <UButton color="primary" @click="goToReset" class="shadow-md">
                ตั้งรหัสผ่านใหม่
              </UButton>
            </div>
          </div>
        </div>
        
        <!-- Admin Message -->
        <div v-else-if="msg.sender_type === 'admin'" class="flex gap-3 max-w-[80%]">
          <UAvatar src="/images/avatar.jpg" alt="Admin" size="sm" class="bg-primary-500 mt-1" />
          <div class="bg-white border border-gray-200 text-gray-800 px-4 py-3 rounded-2xl rounded-tl-none shadow-sm">
            <p class="text-sm whitespace-pre-wrap">{{ msg.message }}</p>
            <span class="text-[10px] text-gray-400 mt-1 block">{{ formatDate(msg.created_at) }}</span>
          </div>
        </div>

        <!-- User Message -->
        <div v-else class="flex gap-3 max-w-[80%] flex-row-reverse">
          <div class="bg-primary-500 text-white px-4 py-3 rounded-2xl rounded-tr-none shadow-sm">
            <p class="text-sm whitespace-pre-wrap">{{ msg.message }}</p>
            <span class="text-[10px] text-primary-100 mt-1 block text-right">{{ formatDate(msg.created_at) }}</span>
          </div>
        </div>
      </div>

    </main>

    <!-- Input Area -->
    <footer class="bg-white border-t border-gray-200 p-4 sticky bottom-0">
      <div class="max-w-4xl w-full mx-auto">
        <form @submit.prevent="sendMessage" class="flex gap-3 items-end">
          <UTextarea 
            v-model="newMessage" 
            placeholder="พิมพ์ข้อความของคุณที่นี่..." 
            class="flex-1"
            :rows="1"
            autoresize
            :disabled="ticketStatus !== 'pending' || isSending"
            @keydown.enter.prevent="sendMessage"
          />
          <UButton 
            type="submit" 
            color="primary" 
            icon="i-heroicons-paper-airplane" 
            class="h-10 px-4 rounded-xl shadow-md disabled:opacity-50"
            :loading="isSending"
            :disabled="!newMessage.trim() || ticketStatus !== 'pending'"
          >
            ส่ง
          </UButton>
        </form>
        <p v-if="ticketStatus !== 'pending'" class="text-xs text-center text-gray-500 mt-2">
          Ticket นี้ถูกปิดลงแล้ว ไม่สามารถส่งข้อความเพิ่มเติมได้
        </p>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

definePageMeta({
  layout: false // Custom full-page layout
})

const route = useRoute()
const router = useRouter()
const baseUrl = useBaseUrl()
const ticketId = route.params.id

const messages = ref<any[]>([])
const newMessage = ref('')
const isSending = ref(false)
const ticketStatus = ref('pending')
const resetToken = ref('')
const chatContainer = ref<HTMLElement | null>(null)

let pollInterval: any = null
let lastMessageId = 0

const token = import.meta.client ? localStorage.getItem(`ticket_${ticketId}`) : ''

const statusColor = computed(() => {
  if (ticketStatus.value === 'resolved') return 'green'
  if (ticketStatus.value === 'cancelled') return 'red'
  return 'orange'
})

const statusText = computed(() => {
  if (ticketStatus.value === 'resolved') return 'อนุมัติแล้ว'
  if (ticketStatus.value === 'cancelled') return 'ยกเลิก'
  return 'รอการตรวจสอบ'
})

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

const fetchMessages = async () => {
  try {
    const data = await $fetch<any>(`${baseUrl}/api/tickets/messages.php?ticket_id=${ticketId}&token=${token}&last_id=${lastMessageId}`)
    
    if (data.messages && data.messages.length > 0) {
      messages.value = [...messages.value, ...data.messages]
      lastMessageId = data.messages[data.messages.length - 1].id
      scrollToBottom()
    }
    
    ticketStatus.value = data.ticket_status
    resetToken.value = data.reset_token
    
  } catch (e) {
    console.error('Failed to fetch messages', e)
  }
}

const sendMessage = async () => {
  if (!newMessage.value.trim() || isSending.value) return
  
  const text = newMessage.value.trim()
  newMessage.value = ''
  isSending.value = true
  
  try {
    await $fetch(`${baseUrl}/api/tickets/messages.php?ticket_id=${ticketId}&token=${token}`, {
      method: 'POST',
      body: { message: text }
    })
    
    await fetchMessages()
  } catch (e) {
    console.error(e)
    useToast().add({ title: 'ส่งข้อความไม่สำเร็จ', color: 'red' })
  } finally {
    isSending.value = false
  }
}

const goToReset = () => {
  if (resetToken.value) {
    router.push(`/reset-password?token=${resetToken.value}`)
  }
}

onMounted(() => {
  if (!token) {
    useToast().add({ title: 'Unauthorized access', color: 'red' })
    router.push('/login')
    return
  }
  fetchMessages()
  pollInterval = setInterval(fetchMessages, 3000) // Poll every 3 seconds
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})
</script>
