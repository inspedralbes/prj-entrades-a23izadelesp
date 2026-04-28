import type { Config } from 'tailwindcss'

export default {
  content: [
    './components/**/*.{js,vue,ts}',
    './layouts/**/*.vue',
    './pages/**/*.vue',
    './plugins/**/*.{js,ts}',
    './app.vue'
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Plus Jakarta Sans', 'sans-serif']
      },
      colors: {
        background: '#F3F4F6',
        primary: '#10B981',
        secondary: '#F59E0B',
        accent: '#EF4444'
      },
      boxShadow: {
        brutal: '4px 4px 0px 0px #000000'
      },
      borderWidth: {
        DEFAULT: '2px'
      }
    }
  },
  plugins: []
} satisfies Config