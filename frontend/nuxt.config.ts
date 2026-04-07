export default defineNuxtConfig({
  devtools: { enabled: true },
  modules: ['@nuxtjs/tailwindcss', '@pinia/nuxt'],
  imports: {
    dirs: ['./stores', './composables']
  },
  components: {
    dirs: [
      '~/components'
    ]
  },
  nitro: {
    prerender: {
      crawlLinks: false,
      routes: ['/']
    },
    headers: {
      'Cache-Control': 'public, max-age=0, must-revalidate'
    }
  },
  runtimeConfig: {
    public: {
      apiBase: process.env.API_BASE_URL || 'http://localhost:8080/api',
      socketUrl: process.env.SOCKET_URL || 'http://localhost:3001'
    }
  },
  app: {
    head: {
      title: 'QueueLy',
      link: [
        { rel: 'stylesheet', href: 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap' }
      ]
    }
  },
  css: ['~/assets/css/main.css'],
  ssr: true,
  experimental: {
    payloadExtraction: false
  }
})