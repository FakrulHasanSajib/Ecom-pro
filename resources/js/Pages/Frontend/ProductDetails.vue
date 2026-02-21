<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import {
    SfButton, SfIconShoppingCart, SfIconArrowBack, SfLoaderCircular,
    SfRating, SfIconFavorite
} from '@storefront-ui/vue';

const route = useRoute();
const router = useRouter();

const product = ref(null);
const relatedProducts = ref([]);
const loading = ref(true);
const activeImage = ref('');

// Image Zoom Logic
const zoomStyle = ref({ transformOrigin: 'center center' });
const handleZoom = (e) => {
    const { left, top, width, height } = e.target.getBoundingClientRect();
    const x = ((e.clientX - left) / width) * 100;
    const y = ((e.clientY - top) / height) * 100;
    zoomStyle.value.transformOrigin = `${x}% ${y}%`;
};

// Review Form State
const reviewForm = ref({ rating: 5, comment: '', name: '' });

// Fetch Product Details
const fetchProduct = async () => {
    try {
        loading.value = true;

        // 1. Fetch Current Product
        const res = await axios.get(`/api/public/products/${route.params.id}`);
        product.value = res.data.data;

        // Set Main Image
        activeImage.value = product.value.thumbnail
            ? '/storage/' + product.value.thumbnail
            : 'https://placehold.co/600x600?text=No+Image';

        // 2. Fetch Related Products
        const relatedRes = await axios.get('/api/public/products');
        relatedProducts.value = (relatedRes.data.data || []).filter(p => p.id !== product.value.id).slice(0, 4);

    } catch (error) {
        console.error("Failed to load product", error);
    } finally {
        loading.value = false;
    }
};

// YouTube URL to Embed URL Converter
const embedVideoUrl = computed(() => {
    if (!product.value?.video_link) return null;
    let url = product.value.video_link;
    if (url.includes('watch?v=')) {
        return url.replace('watch?v=', 'embed/');
    }
    return url;
});

// JSON Parsers
const getGalleryImages = computed(() => {
    if (!product.value?.images) return [];
    try {
        return typeof product.value.images === 'string' ? JSON.parse(product.value.images) : product.value.images;
    } catch(e) { return []; }
});

const getTestimonials = computed(() => {
    if (!product.value?.testimonials) return [];
    try {
        return typeof product.value.testimonials === 'string' ? JSON.parse(product.value.testimonials) : product.value.testimonials;
    } catch(e) { return []; }
});

const goBack = () => router.push('/');

// Fake Submit Review
const submitReview = () => {
    alert(`Thanks ${reviewForm.value.name}! Your ${reviewForm.value.rating}-star review has been submitted.`);
    reviewForm.value = { rating: 5, comment: '', name: '' };
};

onMounted(() => {
    fetchProduct();
});
</script>

<template>
    <div class="bg-gray-50 min-h-screen pb-20 font-sans flex flex-col">

        <header class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
                <div @click="goBack" class="flex items-center gap-2 cursor-pointer">
                    <span class="text-3xl">🛍️</span>
                    <span class="font-bold text-2xl text-indigo-700 tracking-tight">E-Shop</span>
                </div>
                <div class="flex items-center gap-4">
                    <SfButton variant="tertiary" square class="text-gray-600 hover:text-indigo-600 relative">
                        <SfIconShoppingCart />
                        <span class="absolute top-1 right-1 bg-red-500 text-white text-[10px] font-bold rounded-full h-4 w-4 flex items-center justify-center">0</span>
                    </SfButton>
                </div>
            </div>
        </header>

        <div v-if="loading" class="flex justify-center items-center py-40 flex-grow">
            <SfLoaderCircular size="2xl" class="text-indigo-600" />
        </div>

        <main v-else-if="product" class="max-w-7xl mx-auto px-4 py-8 flex-grow w-full">

            <nav class="flex items-center gap-2 text-sm text-gray-500 mb-8">
                <button @click="goBack" class="hover:text-indigo-600 flex items-center gap-1"><SfIconArrowBack size="xs"/> Home</button>
                <span>/</span>
                <span class="text-indigo-600 font-medium">{{ product.category?.name || 'Category' }}</span>
                <span>/</span>
                <span class="truncate max-w-[200px]">{{ product.name }}</span>
            </nav>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden flex flex-col lg:flex-row mb-12">

                <div class="lg:w-1/2 p-6 md:p-10 bg-gray-50 flex flex-col items-center border-r border-gray-100">
                    <div class="relative w-full aspect-square bg-white rounded-2xl overflow-hidden cursor-crosshair group shadow-inner border border-gray-100"
                         @mousemove="handleZoom">
                        <img :src="activeImage"
                             class="w-full h-full object-contain transition-transform duration-300 group-hover:scale-[2]"
                             :style="zoomStyle"
                             alt="Product Image" />
                    </div>

                    <div v-if="getGalleryImages.length > 0" class="flex gap-4 mt-6 overflow-x-auto w-full pb-2 px-2">
                        <img :src="'/storage/' + product.thumbnail"
                             @click="activeImage = '/storage/' + product.thumbnail"
                             :class="{'ring-2 ring-indigo-600 scale-105': activeImage === '/storage/' + product.thumbnail}"
                             class="w-20 h-20 object-cover rounded-xl border border-gray-200 cursor-pointer hover:shadow-md transition flex-shrink-0 bg-white p-1" />

                        <img v-for="(img, idx) in getGalleryImages" :key="idx"
                             :src="'/storage/' + img"
                             @click="activeImage = '/storage/' + img"
                             :class="{'ring-2 ring-indigo-600 scale-105': activeImage === '/storage/' + img}"
                             class="w-20 h-20 object-cover rounded-xl border border-gray-200 cursor-pointer hover:shadow-md transition flex-shrink-0 bg-white p-1" />
                    </div>
                </div>

                <div class="lg:w-1/2 p-8 md:p-12 flex flex-col justify-center">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-black tracking-widest text-indigo-500 uppercase bg-indigo-50 px-3 py-1 rounded-full">
                            {{ product.category?.name || 'New Arrival' }}
                        </span>
                        <div class="flex items-center gap-1 text-amber-500">
                            <SfRating :value="4.5" :max="5" size="xs" />
                            <span class="text-sm text-gray-500 ml-1">(12 Reviews)</span>
                        </div>
                    </div>

                    <h1 class="text-3xl md:text-5xl font-extrabold text-slate-900 mb-4 leading-tight">
                        {{ product.name }}
                    </h1>

                    <div class="flex items-baseline gap-4 mb-6">
                        <span class="text-4xl font-black text-indigo-600">৳{{ product.base_price }}</span>
                        <span v-if="product.offer_price" class="text-xl text-gray-400 line-through">৳{{ product.offer_price }}</span>
                    </div>

                    <div class="prose text-gray-600 leading-relaxed mb-8 border-t border-b border-gray-100 py-6">
                        <p>{{ product.description || 'Experience premium quality and outstanding performance. This product is designed to meet your everyday needs with elegance and durability.' }}</p>
                    </div>

                    <div class="flex items-center gap-4 mt-auto">
                        <div class="flex items-center border-2 border-gray-200 rounded-xl overflow-hidden bg-white">
                            <button class="px-5 py-3 hover:bg-gray-100 font-bold text-gray-600 text-xl transition">-</button>
                            <span class="px-6 py-3 font-bold text-lg w-12 text-center">1</span>
                            <button class="px-5 py-3 hover:bg-gray-100 font-bold text-gray-600 text-xl transition">+</button>
                        </div>

                        <SfButton size="lg" class="flex-grow bg-slate-900 hover:bg-indigo-600 text-white py-4 rounded-xl flex items-center justify-center gap-2 border-none shadow-xl shadow-slate-200 transition-all duration-300">
                            <template #prefix><SfIconShoppingCart /></template>
                            Add to Cart
                        </SfButton>

                        <SfButton size="lg" variant="tertiary" square class="border-2 border-gray-200 rounded-xl text-gray-500 hover:text-red-500 hover:border-red-200 hover:bg-red-50 transition-all">
                            <SfIconFavorite />
                        </SfButton>
                    </div>
                </div>
            </div>

            <div v-if="embedVideoUrl" class="mb-16">
                <div class="flex items-center gap-3 mb-6">
                    <svg class="w-8 h-8 text-indigo-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path>
                    </svg>
                    <h2 class="text-2xl font-bold text-slate-800">Product Video</h2>
                </div>
                <div class="w-full aspect-video rounded-3xl overflow-hidden shadow-2xl border-4 border-white bg-black relative">
                    <iframe
                        :src="embedVideoUrl"
                        class="absolute top-0 left-0 w-full h-full"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                </div>
            </div>

            <div class="mb-16 bg-white p-8 md:p-12 rounded-3xl shadow-sm border border-gray-100">
                <h2 class="text-2xl font-bold text-slate-800 mb-8 border-b pb-4">Customer Reviews</h2>

                <div class="flex flex-col md:flex-row gap-12">
                    <div class="md:w-3/5 space-y-6">
                        <div v-if="getTestimonials.length === 0" class="text-gray-500 italic bg-gray-50 p-6 rounded-2xl">
                            No reviews yet. Be the first to review this product!
                        </div>
                        <div v-else v-for="(review, idx) in getTestimonials" :key="idx" class="border-b border-gray-100 pb-6 last:border-0">
                            <div class="flex items-center gap-4 mb-2">
                                <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-xl">
                                    {{ review.name ? review.name.charAt(0) : 'U' }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800">{{ review.name || 'Anonymous User' }}</h4>
                                    <SfRating :value="5" :max="5" size="xs" class="text-amber-500" />
                                </div>
                            </div>
                            <p class="text-gray-600 ml-16">{{ review.comment || review }}</p>
                        </div>
                    </div>

                    <div class="md:w-2/5 bg-gray-50 p-8 rounded-3xl">
                        <h3 class="font-bold text-lg mb-4 text-slate-800">Write a Review</h3>
                        <form @submit.prevent="submitReview" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Your Rating</label>
                                <select v-model="reviewForm.rating" class="w-full border-gray-300 rounded-xl p-3 focus:ring-indigo-500 outline-none">
                                    <option value="5">⭐⭐⭐⭐⭐ (Excellent)</option>
                                    <option value="4">⭐⭐⭐⭐ (Good)</option>
                                    <option value="3">⭐⭐⭐ (Average)</option>
                                    <option value="2">⭐⭐ (Poor)</option>
                                    <option value="1">⭐ (Terrible)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Your Name</label>
                                <input v-model="reviewForm.name" type="text" required placeholder="John Doe" class="w-full border-gray-300 rounded-xl p-3 focus:ring-indigo-500 outline-none" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Review</label>
                                <textarea v-model="reviewForm.comment" required rows="3" placeholder="What did you like or dislike?" class="w-full border-gray-300 rounded-xl p-3 focus:ring-indigo-500 outline-none"></textarea>
                            </div>
                            <SfButton type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 rounded-xl py-3">Submit Review</SfButton>
                        </form>
                    </div>
                </div>
            </div>

            <div v-if="relatedProducts.length > 0">
                <h2 class="text-2xl font-bold text-slate-800 mb-8 border-l-4 border-indigo-600 pl-4">Related Products</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <router-link v-for="rel in relatedProducts" :key="rel.id" :to="'/product/' + rel.id"
                         class="group bg-white border border-gray-200 rounded-2xl overflow-hidden hover:shadow-xl hover:border-indigo-100 transition-all duration-300 cursor-pointer flex flex-col">

                        <div class="relative h-48 overflow-hidden bg-white p-4 flex justify-center items-center">
                            <img :src="rel.thumbnail ? '/storage/'+rel.thumbnail : 'https://placehold.co/400x400'"
                                 class="max-w-full max-h-full object-contain group-hover:scale-110 transition-transform duration-500" />
                        </div>

                        <div class="p-4 border-t border-gray-100 flex-grow flex flex-col justify-between">
                            <div>
                                <h3 class="font-bold text-gray-800 text-sm mb-2 line-clamp-2 group-hover:text-indigo-600">{{ rel.name }}</h3>
                            </div>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-lg font-black text-slate-900">৳{{ rel.base_price }}</span>
                                <SfButton size="sm" variant="tertiary" square class="bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white rounded-lg transition-colors">
                                    <SfIconShoppingCart size="sm" />
                                </SfButton>
                            </div>
                        </div>
                    </router-link>
                </div>
            </div>

        </main>

        <div v-else class="text-center py-40 flex-grow">
            <h2 class="text-4xl font-black text-gray-300 mb-4">404</h2>
            <p class="text-xl font-bold text-gray-600 mb-8">Product not found!</p>
            <SfButton @click="goBack" class="bg-indigo-600 hover:bg-indigo-700">Go Back Home</SfButton>
        </div>
    </div>
</template>
