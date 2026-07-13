<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

definePageMeta({
  middleware: 'auth'
})

const toast = useToast()
const router = useRouter()

const categories = [
  { label: 'ฮาร์ดแวร์ (Hardware)', value: 'hardware' },
  { label: 'ซอฟต์แวร์ (Software)', value: 'software' },
  { label: 'เครือข่าย (Network)', value: 'network' },
  { label: 'อื่นๆ (Other)', value: 'other' }
]

const form = ref({
  title: '',
  category: '',
  description: '',
  location: '',
  coordinates: null as { lat: number, lng: number } | null,
  image: null as File | null
})

const isSubmitting = ref(false)

const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    form.value.image = target.files[0]
  }
}

const isDragging = ref(false)

const handleDrop = (e: DragEvent) => {
  isDragging.value = false
  if (e.dataTransfer?.files && e.dataTransfer.files.length > 0) {
    // Only accept images
    const file = e.dataTransfer.files[0]
    if (file.type.startsWith('image/')) {
      form.value.image = file
    } else {
      toast.add({ title: 'ไฟล์ไม่ถูกต้อง', description: 'กรุณาอัปโหลดเฉพาะไฟล์รูปภาพ (PNG, JPG, GIF)', color: 'error' })
    }
  }
}

const submitIssue = async () => {
  isSubmitting.value = true
  try {
    if (!form.value.coordinates) {
      toast.add({ title: 'กรุณาปักหมุดตำแหน่ง', description: 'คลิกบนแผนที่เพื่อระบุตำแหน่งที่เกิดปัญหา', color: 'warning' })
      isSubmitting.value = false
      return
    }

    const formData = new FormData()
    formData.append('title', form.value.title)
    formData.append('category', form.value.category)
    formData.append('description', form.value.description)
    formData.append('location', form.value.location)
    formData.append('lat', form.value.coordinates.lat.toString())
    formData.append('lng', form.value.coordinates.lng.toString())
    if (form.value.image) {
      formData.append('image', form.value.image)
    }

    const baseUrl = useBaseUrl()
    const res = await $fetch<any>(`${baseUrl}/api/issues/create`, {
      method: 'POST',
      body: formData,
      credentials: 'include'
    })
    
    toast.add({ title: 'สำเร็จ', description: 'ส่งเรื่องแจ้งปัญหาเรียบร้อยแล้ว กำลังพาไปยังหน้าติดตามสถานะ...', color: 'success' })
    
    // reset form
    form.value = { title: '', category: '', description: '', location: '', coordinates: null, image: null }
    const fileInput = document.getElementById('image-upload') as HTMLInputElement
    if (fileInput) fileInput.value = ''

    // Redirect to track page
    if (res && res.data && res.data.id) {
      router.push(`/issue/${res.data.id}`)
    }
  } catch (error) {
    console.error(error)
    toast.add({ title: 'เกิดข้อผิดพลาด', description: 'ไม่สามารถแจ้งปัญหาได้', color: 'error' })
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="max-w-2xl mx-auto space-y-8 py-8" v-motion-slide-visible-bottom>
    <div class="text-center">
      <h1 class="text-3xl font-bold font-kanit text-gray-900 dark:text-white">แจ้งปัญหาการใช้งาน</h1>
      <p class="text-gray-500 dark:text-gray-400 mt-2 font-kanit">กรุณากรอกรายละเอียดปัญหาที่คุณพบเพื่อให้เจ้าหน้าที่ช่วยเหลือ</p>
    </div>

    <UCard class="mt-6 shadow-sm">
      <form @submit.prevent="submitIssue" class="space-y-6 font-kanit">
        <UFormField label="หัวข้อปัญหา" name="title">
          <UInput v-model="form.title" placeholder="เช่น คอมพิวเตอร์เปิดไม่ติด, อินเทอร์เน็ตใช้งานไม่ได้" required class="w-full" size="lg" />
        </UFormField>

        <UFormField label="หมวดหมู่" name="category">
          <USelect v-model="form.category" :items="categories" :options="categories" placeholder="เลือกหมวดหมู่ที่เกี่ยวข้อง" required class="w-full" size="lg" />
        </UFormField>

        <UFormField label="สถานที่ / ห้อง" name="location">
          <UInput v-model="form.location" placeholder="เช่น ห้องประชุม 1, โต๊ะทำงาน IT-05" required class="w-full" size="lg" />
        </UFormField>

        <UFormField label="ปักหมุดตำแหน่งที่เกิดปัญหา (บนแผนที่)" name="coordinates">
          <IssueMap v-model="form.coordinates" height="300px" />
        </UFormField>

        <UFormField label="รายละเอียดปัญหา" name="description">
          <UTextarea v-model="form.description" placeholder="อธิบายปัญหาที่พบเจอเพิ่มเติม..." :rows="5" required class="w-full" size="lg" />
        </UFormField>

        <UFormField label="รูปภาพประกอบ (ถ้ามี)" name="image">
          <label 
            for="image-upload"
            class="mt-1 flex justify-center px-6 pt-8 pb-8 border-2 border-dashed rounded-xl transition-all cursor-pointer group"
            :class="isDragging ? 'border-coral-500 bg-coral-50 dark:bg-coral-900/20 scale-[1.01]' : 'border-gray-300 dark:border-gray-700 hover:border-coral-400 hover:bg-gray-50 dark:hover:bg-gray-800'"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
          >
            <div class="space-y-2 text-center pointer-events-none">
              <div :class="isDragging ? 'text-coral-500 animate-bounce' : 'text-gray-400 group-hover:text-coral-500 transition-colors'">
                <UIcon name="i-heroicons-cloud-arrow-up" class="mx-auto h-14 w-14" />
              </div>
              <div class="flex text-sm text-gray-600 dark:text-gray-400 justify-center">
                <span class="relative bg-transparent rounded-md font-medium text-coral-600 group-hover:text-coral-500">
                  อัปโหลดรูปภาพ
                </span>
                <p class="pl-1">หรือลากและวางไฟล์มาที่นี่</p>
              </div>
              <p class="text-xs text-gray-500">
                PNG, JPG, GIF ขนาดไม่เกิน 10MB
              </p>
              <p v-if="form.image" class="text-sm font-bold text-coral-600 mt-3 bg-coral-100 dark:bg-coral-900/30 py-2 px-4 rounded-full inline-block">
                ไฟล์ที่เลือก: {{ form.image.name }}
              </p>
            </div>
            <input id="image-upload" name="image-upload" type="file" class="sr-only" @change="handleFileChange" accept="image/*" />
          </label>
        </UFormField>

        <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
          <UButton type="submit" block color="primary" variant="solid" class="w-full justify-center text-lg py-3 font-semibold !bg-coral-500 hover:!bg-coral-600 !text-white rounded-xl shadow-md" :loading="isSubmitting" size="xl">
            ส่งข้อมูลแจ้งปัญหา
          </UButton>
        </div>
      </form>
    </UCard>
  </div>
</template>
