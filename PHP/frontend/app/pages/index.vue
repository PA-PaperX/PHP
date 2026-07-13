<template>
  <div class="text-center font-['Kanit'] w-full flex flex-col items-center h-full pt-8 sm:pt-16">
    <div v-motion-pop-bottom class="flex flex-col items-center justify-center mb-12">
      <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-2">ยินดีต้อนรับ</h1>
      <p class="text-base text-gray-500">เว็บแจ้งรายงานซ่อมอุปกรณ์ IT</p>
    </div>
    
    <!-- Feature Previews -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full max-w-3xl mb-12" v-motion-slide-visible-bottom :delay="200">
      <div class="bg-gray-50 p-6 rounded-2xl flex flex-col items-center text-center">
        <UIcon name="i-heroicons-wrench-screwdriver" class="w-8 h-8 text-primary-500 mb-3" />
        <h3 class="font-bold text-gray-900 mb-1">แจ้งซ่อมรวดเร็ว</h3>
        <p class="text-xs text-gray-500">แจ้งปัญหาอุปกรณ์และติดตามสถานะได้แบบเรียลไทม์</p>
      </div>
      <div class="bg-gray-50 p-6 rounded-2xl flex flex-col items-center text-center">
        <UIcon name="i-heroicons-cube" class="w-8 h-8 text-primary-500 mb-3" />
        <h3 class="font-bold text-gray-900 mb-1">ยืม-คืนอุปกรณ์</h3>
        <p class="text-xs text-gray-500">ระบบจัดการคลังอุปกรณ์ ยืมคืนง่าย ตรวจสอบได้</p>
      </div>
      <div class="bg-gray-50 p-6 rounded-2xl flex flex-col items-center text-center">
        <UIcon name="i-heroicons-chart-pie" class="w-8 h-8 text-primary-500 mb-3" />
        <h3 class="font-bold text-gray-900 mb-1">สถิติชัดเจน</h3>
        <p class="text-xs text-gray-500">สรุปข้อมูลการดำเนินงานผ่าน Dashboard ทันสมัย</p>
      </div>
    </div>
    
    <!-- CTA -->
    <div v-motion :initial="{ opacity: 0, y: 20 }" :enter="{ opacity: 1, y: 0, transition: { delay: 400 } }" class="w-full flex justify-center mb-10 px-4">
      <UButton to="/login" size="xl" color="primary" variant="solid" class="font-bold px-12 py-4 text-lg shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all rounded-full">
        เริ่มใช้งาน
      </UButton>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: 'auth'
})
</script>
