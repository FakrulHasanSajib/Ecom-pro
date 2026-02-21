<script setup>
import { SfButton, SfIconShoppingCart, SfIconFavorite, SfRating } from '@storefront-ui/vue';

// ডেমো ডাটা
const products = Array(4).fill({
  id: 1,
  name: 'Premium Smart Watch Z10',
  price: '৳ 2,500',
  oldPrice: '৳ 3,200',
  rating: 4.5,
  reviews: 120,
  img: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=400',
});
</script>

<template>
  <div class="bg-white min-h-screen">
    <section class="relative bg-neutral-100 py-16 px-4 mb-10">
      <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between">
        <div class="md:w-1/2 mb-8 md:mb-0">
          <h1 class="typography-headline-1 font-bold text-neutral-900 mb-4">
            New Arrival Collection
          </h1>
          <p class="typography-text-lg text-neutral-600 mb-8">
            Get the best deals on premium gadgets and fashion items. Limited time offer!
          </p>
          <SfButton size="lg" class="bg-primary-700 hover:bg-primary-800">
            Shop Now
          </SfButton>
        </div>
        <div class="md:w-1/2 flex justify-center">
          <img src="https://storage.googleapis.com/sfui_docs_artifacts/containers/watch.png" alt="Hero" class="max-h-[400px]" />
        </div>
      </div>
    </section>

    <div class="max-w-7xl mx-auto px-4 py-8">
      <div class="flex justify-between items-center mb-6">
        <h2 class="typography-headline-3 font-bold">Featured Products</h2>
        <SfButton variant="tertiary" class="text-primary-700">See all</SfButton>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <div v-for="product in products" :key="product.id" class="border border-neutral-200 rounded-lg hover:shadow-xl transition-shadow group">
          <div class="relative overflow-hidden rounded-t-lg">
            <img :src="product.img" :alt="product.name" class="object-cover w-full h-64 transition-transform duration-500 group-hover:scale-110" />
            <div class="absolute top-3 right-3">
              <SfButton variant="tertiary" square size="sm" class="bg-white/80 hover:bg-white rounded-full shadow-md">
                <SfIconFavorite size="sm" />
              </SfButton>
            </div>
          </div>

          <div class="p-4">
            <div class="flex items-center gap-1 mb-2">
              <SfRating :value="product.rating" :max="5" size="xs" />
              <span class="text-xs text-neutral-500">({{ product.reviews }})</span>
            </div>
            <h3 class="typography-text-base font-medium text-neutral-900 mb-2 truncate">
              {{ product.name }}
            </h3>
            <div class="flex items-baseline gap-2 mb-4">
              <span class="typography-text-lg font-bold text-neutral-900">{{ product.price }}</span>
              <span class="typography-text-sm text-neutral-500 line-through">{{ product.oldPrice }}</span>
            </div>

            <SfButton size="sm" class="w-full gap-2">
              <template #prefix>
                <SfIconShoppingCart size="sm" />
              </template>
              Add to Cart
            </SfButton>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
