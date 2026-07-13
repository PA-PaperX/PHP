// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },

  future: {
    compatibilityVersion: 4,
  },

  modules: [
    '@nuxt/ui',
    '@vueuse/nuxt',
    '@vueuse/motion/nuxt'
  ],

  ui: {
    safelistColors: ['red', 'success', 'warning', 'primary', 'gray']
  },

  colorMode: {
    preference: 'light'
  },

  runtimeConfig: {
    public: {
      promptpayId: process.env.NUXT_PUBLIC_PROMPTPAY_ID || '0899999999',
    }
  },

  css: [
    '~/assets/css/main.css'
  ],

  app: {
    pageTransition: { name: 'page', mode: 'out-in' },
    layoutTransition: { name: 'layout', mode: 'out-in' },
    head: {
      title: 'ไอย๊าห์ Iya - IT Support',
      link: [
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Prompt:wght@300;400;500;600;700&display=swap'
        }
      ]
    }
  },

  motion: {
    directives: {
      'pop-bottom': {
        initial: { scale: 0, opacity: 0, y: 100 },
        visible: { scale: 1, opacity: 1, y: 0 }
      }
    }
  },

  vite: {
    optimizeDeps: {
      include: ['chart.js', 'vue-chartjs']
    }
  }
})
