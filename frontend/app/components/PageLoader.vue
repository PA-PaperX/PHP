<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useNuxtApp } from '#app'

const nuxtApp = useNuxtApp()
const isLoading = ref(true)

onMounted(() => {
  // Hide the loader shortly after initial mount
  setTimeout(() => {
    isLoading.value = false
  }, 500)
})

nuxtApp.hook('page:start', () => {
  isLoading.value = true
})

nuxtApp.hook('page:finish', () => {
  setTimeout(() => {
    isLoading.value = false
  }, 300)
})
</script>

<template>
  <Transition name="fade">
    <div v-if="isLoading" class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-50/80 dark:bg-gray-950/80 backdrop-blur-md">
      <div class="loader-wrapper">
          <div class="circle bg-primary-500 dark:bg-primary-400"></div>
          <div class="circle bg-primary-500 dark:bg-primary-400"></div>
          <div class="circle bg-primary-500 dark:bg-primary-400"></div>
          <div class="loader-shadow dark:bg-black/50"></div>
          <div class="loader-shadow dark:bg-black/50"></div>
          <div class="loader-shadow dark:bg-black/50"></div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* From Uiverse.io by mobinkakei */ 
.loader-wrapper {
  width: 200px;
  height: 60px;
  position: relative;
  z-index: 1;
}

.circle {
  width: 20px;
  height: 20px;
  position: absolute;
  border-radius: 50%;
  left: 15%;
  transform-origin: 50%;
  animation: circle7124 .5s alternate infinite ease;
}

@keyframes circle7124 {
  0% {
    top: 60px;
    height: 5px;
    border-radius: 50px 50px 25px 25px;
    transform: scaleX(1.7);
  }

  40% {
    height: 20px;
    border-radius: 50%;
    transform: scaleX(1);
  }

  100% {
    top: 0%;
  }
}

.circle:nth-child(2) {
  left: 45%;
  animation-delay: .2s;
}

.circle:nth-child(3) {
  left: auto;
  right: 15%;
  animation-delay: .3s;
}

.loader-shadow {
  width: 20px;
  height: 4px;
  border-radius: 50%;
  background-color: rgba(0,0,0,0.2);
  position: absolute;
  top: 62px;
  transform-origin: 50%;
  z-index: -1;
  left: 15%;
  filter: blur(1px);
  animation: shadow046 .5s alternate infinite ease;
}

@keyframes shadow046 {
  0% {
    transform: scaleX(1.5);
  }

  40% {
    transform: scaleX(1);
    opacity: .7;
  }

  100% {
    transform: scaleX(.2);
    opacity: .4;
  }
}

.loader-shadow:nth-child(4) {
  left: 45%;
  animation-delay: .2s
}

.loader-shadow:nth-child(5) {
  left: auto;
  right: 15%;
  animation-delay: .3s;
}
</style>
