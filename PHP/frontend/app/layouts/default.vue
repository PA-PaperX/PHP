<script setup lang="ts">
import AppHeader from '~/components/AppHeader.vue'
</script>

<template>
  <div class="min-h-screen bg-coral-50/20 dark:bg-gray-950 font-inter text-gray-900 dark:text-gray-100 relative z-0">
    <AppHeader />
    <main class="p-6 lg:p-8 overflow-x-hidden">
      <div class="max-w-7xl mx-auto w-full">
        <slot />
      </div>
    </main>
  </div>
</template>
