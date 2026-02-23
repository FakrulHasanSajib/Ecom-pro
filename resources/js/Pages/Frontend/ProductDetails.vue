<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2'; // 🔥 SweetAlert যুক্ত করা হলো
import {
    SfButton, SfIconShoppingCart, SfIconArrowBack, SfLoaderCircular,
    SfRating, SfIconFavorite
} from '@storefront-ui/vue';

import { useCartStore } from '../../stores/cart';
import CartSidebar from '../../Components/CartSidebar.vue';

const route = useRoute();
const router = useRouter();
const cartStore = useCartStore();

const product = ref(null);
const relatedProducts = ref([]);
const loading = ref(true);
const activeImage = ref('');
const quantity = ref(1);

const isCartSidebarOpen = ref(false);

// 🔥 ব্যাকএন্ড ইউআরএল আপডেট (পোর্ট ৭৩)
const backendUrl = 'http://127.0.0.1:73';

const getImageUrl = (path) => {
    if (!path) return 'https://placehold.co/600x600?text=No+Image';
    return path.startsWith('http') ? path : `${backendUrl}/storage/${path}`;
};

const zoomStyle = ref({ transformOrigin: 'center center' });
const handleZoom = (e) => {
    const { left, top, width, height } = e.target.getBoundingClientRect();
    const x = ((e.clientX - left) / width) * 100;
    const y = ((e.clientY - top) / height) * 100;
    zoomStyle.value.transformOrigin = `${x}% ${y}%`;
};

const reviewForm = ref({ rating: 5, comment: '', name: '' });

const fetchProduct = async () => {
    try {
        loading.value = true;
        window.scrollTo({ top: 0, behavior: 'smooth' });
        quantity.value = 1;

        const res = await axios.get(`${backendUrl}/api/public/products/${route.params.id}`);
        product.value = res.data.data;

        activeImage.value = getImageUrl(product.value.thumbnail);

        const relatedRes = await axios.get(`${backendUrl}/api/public/products`);
        relatedProducts.value = (relatedRes.data.data || []).filter(p => p.id !== product.value.id).slice(0, 4);

    } catch (error) {
        console.error("Failed to load product", error);
    } finally {
        loading.value = false;
    }
};

watch(
    () => route.params.id,
    (newId) => {
        if (newId) fetchProduct();
    }
);

// 🔥 SweetAlert Toast Function
const showToast = (message) => {
    Swal.fire({
        toast: true,
        position: 'bottom-end',
        icon: 'success',
        title: message,
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true,
        background: '#4f46e5',
        color: '#fff',
        iconColor: '#fff'
    });
};

const handleAddToCart = () => {
    if (product.value) {
        cartStore.addToCart(product.value, quantity.value);
        showToast('Added to Cart Successfully! 🛒');
        isCartSidebarOpen.value = true;
    }
};

const handleRelatedAddToCart = (relProduct) => {
    cartStore.addToCart(relProduct, 1);
    showToast('Added to Cart Successfully! 🛒');
    isCartSidebarOpen.value = true;
};

const submitReview = () => {
    showToast(`Thanks ${reviewForm.value.name}! Your review is submitted.`);
    reviewForm.value = { rating: 5, comment: '', name: '' };
};

const embedVideoUrl = computed(() => {
    if (!product.value?.video_link) return null;
    let url = product.value.video_link;
    if (url.includes('watch?v=')) {
        return url.replace('watch?v=', 'embed/');
    }
    return url;
});

const getGalleryImages = computed(() => {
    if (!product.value?.images) return [];
    try {
        return typeof product.value.images === 'string' ? JSON.parse(product.value.images) : product.value.images;
    } catch(e) { return []; }
});

const getTestimonials = computed(() => {
    if (!product.value?.testimonials) return [];
    try {
        let items = typeof product.value.testimonials === 'string' ? JSON.parse(product.value.testimonials) : product.value.testimonials;
        if (Array.isArray(items)) {
            return items.map(item => {
                if (typeof item === 'string' && item.trim().startsWith('{')) {
                    try { return JSON.parse(item); } catch (e) { return item; }
                }
                return item;
            });
        }
        return items;
    } catch(e) { return []; }
});

const goBack = () => router.push('/');

onMounted(() => {
    fetchProduct();
});
</script>

<template>
    <div class="bg-slate-50 min-h-screen pb-20 font-sans flex flex-col relative overflow-hidden">

        <header class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
                <div @click="goBack" class="flex items-center gap-2 cursor-pointer group">
                    <span class="text-3xl group-hover:scale-110 transition-transform">🛍️</span>
                    <span class="font-black text-2xl text-indigo-700 tracking-tight">E-Shop</span>
                </div>
                <div class="flex items-center gap-4">
                    <SfButton @click="isCartSidebarOpen = true" variant="tertiary" square class="text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-full relative transition-all">
                        <SfIconShoppingCart />
                        <span v-if="cartStore.totalItems > 0" class="absolute top-0 right-0 bg-red-500 text-white text-[10px] font-bold rounded-full h-5 w-5 flex items-center justify-center border-2 border-white shadow-sm transform translate-x-1 -translate-y-1">
                            {{ cartStore.totalItems }}
                        </span>
                    </SfButton>
                </div>
            </div>
        </header>

        <div v-if="loading" class="flex flex-col justify-center items-center py-40 flex-grow">
            <SfLoaderCircular size="2xl" class="text-indigo-600 mb-4" />
            <p class="text-slate-500 font-medium animate-pulse">Loading amazing details...</p>
        </div>

        <main v-else-if="product" class="max-w-7xl mx-auto px-4 py-8 flex-grow w-full">

            <nav class="flex items-center gap-2 text-sm text-slate-500 mb-8 font-medium">
                <button @click="goBack" class="hover:text-indigo-600 flex items-center gap-1 transition-colors"><SfIconArrowBack size="xs"/> Home</button>
                <span class="text-slate-300">/</span>
                <span class="text-indigo-600">{{ product.category?.name || 'Category' }}</span>
                <span class="text-slate-300">/</span>
                <span class="truncate max-w-[200px] text-slate-700">{{ product.name }}</span>
            </nav>

            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden flex flex-col lg:flex-row mb-16">

                <div class="lg:w-1/2 p-6 md:p-10 bg-slate-50/50 flex flex-col items-center border-r border-slate-100">
                    <div class="relative w-full aspect-square bg-white rounded-3xl overflow-hidden cursor-crosshair group shadow-sm border border-slate-100"
                         @mousemove="handleZoom">
                        <img :src="activeImage"
                             class="w-full h-full object-contain transition-transform duration-300 group-hover:scale-[2]"
                             :style="zoomStyle"
                             alt="Product Image" />
                    </div>

                    <div v-if="getGalleryImages.length > 0" class="flex gap-4 mt-6 overflow-x-auto w-full pb-4 px-2 custom-scrollbar">
                        <img :src="getImageUrl(product.thumbnail)"
                             @click="activeImage = getImageUrl(product.thumbnail)"
                             :class="{'ring-2 ring-indigo-600 scale-105 shadow-md': activeImage === getImageUrl(product.thumbnail), 'border-slate-200': activeImage !== getImageUrl(product.thumbnail)}"
                             class="w-20 h-20 object-contain rounded-2xl border cursor-pointer hover:shadow-md transition-all flex-shrink-0 bg-white p-2" />

                        <img v-for="(img, idx) in getGalleryImages" :key="idx"
                             :src="getImageUrl(img)"
                             @click="activeImage = getImageUrl(img)"
                             :class="{'ring-2 ring-indigo-600 scale-105 shadow-md': activeImage === getImageUrl(img), 'border-slate-200': activeImage !== getImageUrl(img)}"
                             class="w-20 h-20 object-contain rounded-2xl border cursor-pointer hover:shadow-md transition-all flex-shrink-0 bg-white p-2" />
                    </div>
                </div>

                <div class="lg:w-1/2 p-8 md:p-12 flex flex-col justify-center">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-black tracking-widest text-indigo-600 uppercase bg-indigo-50 px-3 py-1.5 rounded-md border border-indigo-100">
                            {{ product.category?.name || 'Premium Item' }}
                        </span>
                        <div class="flex items-center gap-1 text-amber-500 bg-amber-50 px-3 py-1 rounded-full border border-amber-100">
                            <SfRating :value="4.5" :max="5" size="xs" />
                            <span class="text-xs font-bold text-amber-700 ml-1">4.5</span>
                        </div>
                    </div>

                    <h1 class="text-3xl md:text-5xl font-black text-slate-800 mb-4 leading-tight tracking-tight">
                        {{ product.name }}
                    </h1>

                    <div class="flex items-end gap-4 mb-8">
                        <span class="text-4xl font-black text-indigo-600">৳{{ product.sale_price || product.base_price }}</span>
                        <span v-if="product.discount_price" class="text-xl font-bold text-slate-400 line-through mb-1">৳{{ product.discount_price }}</span>
                    </div>

                    <div class="prose prose-indigo prose-sm sm:prose-base text-slate-600 leading-relaxed mb-8 border-y border-slate-100 py-6">
                        <div v-html="product.description || '<p>Experience premium quality and outstanding performance. Crafted with excellence to meet your everyday needs.</p>'"></div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-4 mt-auto">
                        <div class="flex items-center border-2 border-slate-200 rounded-xl overflow-hidden bg-white h-14">
                            <button @click="quantity > 1 ? quantity-- : null" class="px-5 h-full hover:bg-slate-100 font-bold text-slate-600 text-xl transition-colors">-</button>
                            <span class="px-4 font-black text-lg w-12 text-center text-slate-800">{{ quantity }}</span>
                            <button @click="quantity++" class="px-5 h-full hover:bg-slate-100 font-bold text-slate-600 text-xl transition-colors">+</button>
                        </div>

                        <SfButton @click="handleAddToCart" size="lg" class="flex-grow w-full sm:w-auto h-14 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl flex items-center justify-center gap-2 border-none shadow-lg shadow-indigo-500/30 transition-all duration-300 active:scale-95 text-lg font-bold">
                            <template #prefix><SfIconShoppingCart /></template>
                            Add to Cart
                        </SfButton>

                        <SfButton size="lg" variant="tertiary" square class="h-14 w-14 border-2 border-slate-200 rounded-xl text-slate-400 hover:text-red-500 hover:border-red-200 hover:bg-red-50 transition-all flex items-center justify-center flex-shrink-0">
                            <SfIconFavorite />
                        </SfButton>
                    </div>
                </div>
            </div>

            <div v-if="embedVideoUrl" class="mb-16">
                <div class="flex items-center gap-3 mb-6">
                    <span class="w-2 h-8 bg-indigo-600 rounded-full"></span>
                    <h2 class="text-2xl font-black text-slate-800">Product Video</h2>
                </div>
                <div class="w-full aspect-video rounded-[2rem] overflow-hidden shadow-2xl border-4 border-white bg-slate-900 relative">
                    <iframe
                        :src="embedVideoUrl"
                        class="absolute top-0 left-0 w-full h-full"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                </div>
            </div>

            <div class="mb-16 bg-white p-8 md:p-12 rounded-[2rem] shadow-sm border border-slate-100">
                <div class="flex items-center gap-3 mb-8 border-b border-slate-100 pb-6">
                    <span class="w-2 h-8 bg-amber-500 rounded-full"></span>
                    <h2 class="text-2xl font-black text-slate-800">Customer Reviews</h2>
                </div>

                <div class="flex flex-col md:flex-row gap-12">
                    <div class="md:w-3/5 space-y-6">
                        <div v-if="getTestimonials.length === 0" class="text-slate-500 italic bg-slate-50 p-8 rounded-2xl border border-dashed border-slate-200 text-center font-medium">
                            No reviews yet. Be the first to review this product! ⭐
                        </div>
                        <div v-else v-for="(review, idx) in getTestimonials" :key="idx" class="border-b border-slate-100 pb-6 last:border-0">
                            <div class="flex items-center gap-4 mb-3">
                                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center font-black text-xl shadow-inner border border-indigo-100">
                                    {{ review.name ? review.name.charAt(0).toUpperCase() : 'U' }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 text-lg">{{ review.name || 'Anonymous User' }}</h4>
                                    <SfRating :value="5" :max="5" size="xs" class="text-amber-500" />
                                </div>
                            </div>
                            <p class="text-slate-600 ml-16 leading-relaxed bg-slate-50 p-4 rounded-2xl rounded-tl-none">{{ review.comment || review.content || (typeof review === 'string' ? review : 'No comment.') }}</p>
                        </div>
                    </div>

                    <div class="md:w-2/5 bg-slate-50 p-8 rounded-3xl border border-slate-100">
                        <h3 class="font-black text-xl mb-6 text-slate-800">Write a Review</h3>
                        <form @submit.prevent="submitReview" class="space-y-5">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Your Rating</label>
                                <select v-model="reviewForm.rating" class="w-full bg-white border border-slate-200 rounded-xl p-3.5 focus:ring-2 focus:ring-indigo-500 outline-none font-medium text-slate-700">
                                    <option value="5">⭐⭐⭐⭐⭐ (Excellent)</option>
                                    <option value="4">⭐⭐⭐⭐ (Good)</option>
                                    <option value="3">⭐⭐⭐ (Average)</option>
                                    <option value="2">⭐⭐ (Poor)</option>
                                    <option value="1">⭐ (Terrible)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Your Name</label>
                                <input v-model="reviewForm.name" type="text" required placeholder="John Doe" class="w-full bg-white border border-slate-200 rounded-xl p-3.5 focus:ring-2 focus:ring-indigo-500 outline-none font-medium" />
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Review</label>
                                <textarea v-model="reviewForm.comment" required rows="3" placeholder="What did you like or dislike?" class="w-full bg-white border border-slate-200 rounded-xl p-3.5 focus:ring-2 focus:ring-indigo-500 outline-none font-medium resize-none"></textarea>
                            </div>
                            <SfButton type="submit" class="w-full bg-slate-900 hover:bg-indigo-600 text-white rounded-xl py-3.5 font-bold shadow-lg shadow-slate-200 transition-all active:scale-95">Submit Review</SfButton>
                        </form>
                    </div>
                </div>
            </div>

            <div v-if="relatedProducts.length > 0">
                <div class="flex items-center gap-3 mb-8">
                    <span class="w-2 h-8 bg-indigo-600 rounded-full"></span>
                    <h2 class="text-2xl font-black text-slate-800">Related Products</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <div v-for="rel in relatedProducts" :key="rel.id"
                         class="group bg-white border border-slate-100 rounded-2xl overflow-hidden hover:shadow-2xl hover:shadow-indigo-500/10 transition-all duration-300 relative flex flex-col">

                        <router-link :to="'/product/' + rel.id" class="relative h-56 overflow-hidden bg-slate-50 flex items-center justify-center p-6 cursor-pointer">
                            <img :src="getImageUrl(rel.thumbnail)"
                                 class="max-w-full max-h-full object-contain group-hover:scale-110 transition-transform duration-700" />
                        </router-link>

                        <div class="p-5 flex-grow flex flex-col justify-between">
                            <div>
                                <p class="text-[10px] text-indigo-500 font-bold uppercase tracking-widest mb-2">{{ rel.category?.name || 'General' }}</p>
                                <router-link :to="'/product/' + rel.id">
                                    <h3 class="font-bold text-slate-800 text-base mb-1 line-clamp-2 hover:text-indigo-600 transition-colors">
                                        {{ rel.name }}
                                    </h3>
                                </router-link>
                            </div>

                            <div class="mt-4 border-t border-slate-100 pt-4">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-xl font-black text-slate-900">৳{{ rel.sale_price || rel.base_price }}</span>
                                    <span v-if="rel.discount_price" class="text-xs text-slate-400 line-through">৳{{ rel.base_price }}</span>
                                </div>

                                <button @click.prevent="handleRelatedAddToCart(rel)" class="w-full bg-slate-100 hover:bg-indigo-600 text-slate-800 hover:text-white py-2.5 rounded-xl flex items-center justify-center gap-2 font-bold transition-all duration-300 active:scale-95 group/btn">
                                    <SfIconShoppingCart size="sm" class="group-hover/btn:animate-bounce" />
                                    Add
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>

        <div v-else class="text-center py-40 flex-grow">
            <h2 class="text-6xl font-black text-slate-200 mb-4">404</h2>
            <p class="text-xl font-bold text-slate-500 mb-8">Oops! Product not found.</p>
            <SfButton @click="goBack" class="bg-indigo-600 hover:bg-indigo-700 rounded-xl px-8 py-3 shadow-lg shadow-indigo-500/30">Go Back Home</SfButton>
        </div>

        <CartSidebar :isOpen="isCartSidebarOpen" @close="isCartSidebarOpen = false" />

    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { height: 6px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #e2e8f0; border-radius: 20px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #cbd5e1; }
</style>
