<script setup lang="ts">
import { useHead } from '#imports'

useHead({
  link: [
    { rel: 'stylesheet', href: 'https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css' }
  ]
})
</script>

<template>
  <UApp>
    <div class="fixed inset-0 z-[-1] bg-gray-50 dark:bg-gray-950"></div>
    
    <PageLoader />
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </UApp>
</template>
