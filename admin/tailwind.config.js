/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './components/**/*.{js,vue,ts}',
    './layouts/**/*.vue',
    './pages/**/*.vue',
    './plugins/**/*.{js,ts}',
    './app.vue',
    './error.vue'
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#E6F4FF',
          100: '#CCE9FF',
          200: '#99D3FF',
          300: '#66BDFF',
          400: '#33A7FF',
          500: '#0080E0', // Primary brand blue
          600: '#0066CC', // Primary dark
          700: '#004C99',
          800: '#003366',
          900: '#001933',
          950: '#000D1A'
        }
      }
    }
  },
  plugins: []
}
