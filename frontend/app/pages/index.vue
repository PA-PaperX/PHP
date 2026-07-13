<template>
  <div class="text-center font-['Kanit'] w-full flex flex-col items-center h-full pt-20 sm:pt-32">
    <div v-motion-pop-bottom class="flex-1 flex flex-col items-center justify-center">
      <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-2">ยินดีต้อนรับ</h1>
      <p class="text-sm text-gray-400">เว็บแจ้งรายงานซ่อมอุปกรณ์ IT</p>
    </div>
    
    <div v-motion :initial="{ opacity: 0, y: 20 }" :enter="{ opacity: 1, y: 0, transition: { delay: 300 } }" class="w-full flex justify-end mt-24 mb-10 px-4">
      <NuxtLink to="/login" class="inline-flex items-center gap-3 group">
        <span class="text-xs font-medium text-gray-500 group-hover:text-gray-800 transition-colors">ดำเนินการต่อ</span>
        <div class="w-8 h-8 rounded-full bg-black text-white flex items-center justify-center shadow-md transform group-hover:scale-105 transition-transform">
          <UIcon name="i-heroicons-arrow-right" class="w-4 h-4" />
        </div>
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: 'auth'
})
</script>
