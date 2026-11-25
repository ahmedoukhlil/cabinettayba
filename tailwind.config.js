/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#1e3a8a',
          light: '#e6eaf2',
          dark: '#152a5c',
        },
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
}
