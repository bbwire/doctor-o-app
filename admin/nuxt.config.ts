// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  modules: [
    '@nuxt/eslint',
    '@nuxt/ui'
  ],

  devtools: {
    enabled: true
  },

  css: ['~/assets/css/main.css'],

  routeRules: {
    '/': { prerender: true }
  },

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
      // apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000/api/v1'
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'https://doctoroapi.umbrit.com/public/api/v1'
    }
  }
})
