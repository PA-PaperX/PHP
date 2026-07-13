<template>
  <div class="min-h-screen bg-white relative overflow-hidden font-['Kanit'] flex flex-col">
    <!-- Top Wave Background -->
    <div class="absolute top-0 left-0 w-full h-72 md:h-96 z-0 pointer-events-none">
      <div class="w-full h-full bg-primary-400 relative overflow-hidden">
        <!-- Topography Pattern -->
        <div class="absolute inset-0 opacity-40 mix-blend-overlay animate-topography" style="background-image: url('/12315.svg');"></div>
      </div>
      <!-- SVG Wave Mask (White) covering the bottom to create the wave shape -->
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" class="absolute left-0 w-full text-white" style="bottom: -2px; height: auto;" preserveAspectRatio="none">
        <path fill="currentColor" fill-opacity="1" d="M0,256L60,240C120,224,240,192,360,192C480,192,600,224,720,240C840,256,960,256,1080,245.3C1200,235,1320,213,1380,202.7L1440,192L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z"></path>
      </svg>
    </div>
    
    <!-- Content container -->
    <div class="relative z-10 flex-1 flex flex-col p-6 w-full max-w-lg mx-auto pt-48 md:pt-64">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
// Auth Layout
</script>
