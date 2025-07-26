module.exports = {
  content: ['./index.html', './src/**/*.{vue,js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        primary: 'rgba(143, 171, 218, 1)',
        secondary: 'rgba(30, 41, 57, 1)',
        gray: {
          light: 'rgba(243, 244, 246, 1)',
          medium: 'rgba(153, 161, 175, 1)',
          dark: 'rgba(106, 114, 130, 1)'
        }
      }
    }
  },
  plugins: []
} 