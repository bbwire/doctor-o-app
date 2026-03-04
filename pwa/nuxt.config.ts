// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  modules: [
    '@nuxt/eslint',
    '@nuxt/ui',
    '@vite-pwa/nuxt'
  ],

  pwa: {
    registerType: 'autoUpdate',
    manifest: {
      name: 'Dr. O Medical Services',
      short_name: 'Dr. O',
      description: 'Connect with healthcare professionals through secure text, audio, and video consultations.',
      theme_color: '#0f172a',
      background_color: '#0f172a',
      display: 'standalone',
      scope: '/',
      start_url: '/',
      icons: [
        { src: '/icon.svg', sizes: 'any', type: 'image/svg+xml', purpose: 'any' },
        { src: '/icon.svg', sizes: '512x512', type: 'image/svg+xml', purpose: 'maskable' }
      ]
    },
    client: {
      installPrompt: 'pwa-install-dismissed'
    },
    devOptions: {
      enabled: false
    }
  },

  ssr: false,

  nitro: {
    preset: 'static',
    prerender: {
      crawlLinks: false,
      routes: ['/']
    }
  },

  devtools: {
    enabled: true
  },

  css: ['~/assets/css/main.css'],

  eslint: {
    config: {
      stylistic: {
        commaDangle: 'never',
        braceStyle: '1tbs'
      }
    }
  },

  runtimeConfig: {
    public: {
      // apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://127.0.0.1:8000/api/v1'
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'https://doctoroapi.umbrit.com/public/api/v1'
    }
  }
})
