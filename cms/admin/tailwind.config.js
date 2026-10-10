/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,jsx}'],
  theme: {
    extend: {
      colors: {
        navy: { DEFAULT: '#0C1322', 800: '#1A253E', 700: '#243356' },
        gold: { DEFAULT: '#D4AF37', dark: '#B8860B', bronze: '#7E591B', light: '#EADBAC' },
        cream: { DEFAULT: '#FAF7F0', 2: '#F6F2E8' },
      },
      fontFamily: {
        display: ['Cinzel', 'Georgia', 'serif'],
        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
};
