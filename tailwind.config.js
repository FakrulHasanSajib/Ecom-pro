/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue", // এই লাইনটি অত্যন্ত জরুরি
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
