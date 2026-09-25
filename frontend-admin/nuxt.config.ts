import process from 'node:process'
import { fileURLToPath } from 'node:url'
import { dirname, resolve } from 'node:path'

const __dirname = dirname(fileURLToPath(import.meta.url))

// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: false },
  spaLoadingTemplate: resolve(__dirname, 'app/spa-loading-template.html'),
  srcDir: 'app',
  components: [
    {
      path: '~/components',
      pathPrefix: false,
    },
  ],
  modules: [
    '@nuxtjs/tailwindcss', 
    '@pinia/nuxt',
    'pinia-plugin-persistedstate/nuxt'
  ],
  // Use the high-density B2C e-commerce stylesheet
  css: [
    '~/assets/css/tailwind.css'
  ],
  
  // Static generation for cPanel hosting
  ssr: false,
  
  alias: {
    '~': resolve(__dirname, 'app'),
    '@': resolve(__dirname, '.'),
  },
  imports: {
    dirs: ['stores'],
  },
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE ?? '',
      siteName: process.env.NUXT_PUBLIC_SITE_NAME ?? '',
    },
  },
  
  // Remove development HMR settings for production
  devServer: {
    host: 'localhost',
    port: 3000,
  },
  
  // Production build configuration
  nitro: {
    preset: 'static', // Static hosting for cPanel
    routeRules: {
      '/**': {
        headers: {
          'X-Frame-Options': 'ALLOWALL',
          'Content-Security-Policy': "frame-ancestors 'self' https://web.telegram.org https://telegram.org; img-src 'self' data: https: http:",
          'Cache-Control': 'no-cache, no-store, must-revalidate',
          'Pragma': 'no-cache',
          'Expires': '0',
        },
      },
    },
  },
  
  // Remove console logs in production
  vite: {
    esbuild: {
      drop: ['console', 'debugger'],
    },
    build: {
      rollupOptions: {
        output: {
          // Add timestamp to filenames to break cache
          entryFileNames: `_nuxt/[name]-[hash]-${Date.now()}.js`,
          chunkFileNames: `_nuxt/[name]-[hash]-${Date.now()}.js`,
          assetFileNames: `_nuxt/[name]-[hash]-${Date.now()}.[ext]`
        }
      }
    }
  },
  app: {
    head: {
      title: `${process.env.NUXT_PUBLIC_SITE_NAME || 'Admin'}`,
      meta: [
        { name: 'viewport', content: 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no' },
        { name: 'format-detection', content: 'telephone=no' },
      ],
      link: [
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        { rel: 'stylesheet', href: 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Roboto:wght@300;400;500;700;900&display=swap' }
      ]
    },
  },
})
