<script setup lang="ts">
import AppHeader from '~/components/AppHeader.vue'
import AppSidebar from '~/components/AppSidebar.vue'

const isOpen = useSidebar()
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 font-inter text-gray-900 dark:text-gray-100 relative z-0">
    <AppHeader />
    <main class="overflow-x-hidden">
      <slot />
    </main>

    <USlideover v-model:open="isOpen" side="left" :ui="{ overlay: { background: 'bg-gray-900/50 backdrop-blur-sm' }, content: 'w-screen max-w-xs' }">
      <template #content>
        <AppSidebar />
      </template>
    </USlideover>
  </div>
</template>
