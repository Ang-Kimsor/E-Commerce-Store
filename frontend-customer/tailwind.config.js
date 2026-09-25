/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./app/components/**/*.{js,vue,ts}",
    "./app/layouts/**/*.vue",
    "./app/pages/**/*.vue",
    "./app/plugins/**/*.{js,ts}",
    "./app/app.vue",
    "./app/error.vue",
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'Roboto', 'sans-serif'],
      },
      colors: {
        'primary': {
          DEFAULT: '#1E3A8A', /* Premium Blue */
          'dark': '#1E40AF',
          'border': '#E2E8F0',
          'border-hover': '#CBD5E1',
          'container': '#1E3A8A',
        },
        'danger': {
          DEFAULT: '#dc3545',
          'dark': '#b02a37',
        },
        'background': {
          'light': '#f8f9fa',
          'light-hover': '#e9ecef',
        },
        'text': {
          'primary': '#212529',
          'secondary': '#6c757d',
        }
      }
    },
  },
  plugins: [],
}
