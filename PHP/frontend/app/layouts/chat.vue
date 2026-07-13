<script setup lang="ts">
import AppHeader from '~/components/AppHeader.vue'
</script>

<template>
  <div class="h-dvh flex flex-col bg-gray-50 dark:bg-gray-900 font-inter text-gray-900 dark:text-gray-100 relative z-0">
    <AppHeader class="shrink-0" />
    <main class="flex-1 flex flex-col overflow-hidden">
      <slot />
    </main>
  </div>
</template>
