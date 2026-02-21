import { tailwindConfig } from '@storefront-ui/vue/tailwind-config';

/** @type {import('tailwindcss').Config} */
export default {
  presets: [tailwindConfig],
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./node_modules/@storefront-ui/vue/**/*.{js,mjs}"
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
