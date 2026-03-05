<script setup>
import { ref, onMounted, watch, computed, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useCartStore } from '../../stores/cart';
import { useAuthStore } from '../../stores/auth';
import Swal from 'sweetalert2';

const router = useRouter();
const cartStore = useCartStore();
const authStore = useAuthStore();

const products = ref([]);
const categories = ref([]);
const featuredProducts = ref([]);
const loading = ref(true);
const initialLoading = ref(true);
const wishlist = ref([]);
const showBackToTop = ref(false);
const activeProductTab = ref('all');

const sliders = ref([]);
const currentSlide = ref(0);
const settings = ref({});

const countdown = ref({ h: '00', m: '00', s: '00' });
let countdownInterval = null;

const searchQuery = ref('');
const selectedCategory = ref(null);
const sortOption = ref('');
const minPrice = ref('');
const maxPrice = ref('');

const backendUrl = 'http://127.0.0.1:73';

const activeFilterCount = computed(() => {
    let c = 0;
    if (selectedCategory.value) c++;
    if (sortOption.value) c++;
    if (minPrice.value || maxPrice.value) c++;
    if (searchQuery.value) c++;
    return c;
});

const isInWishlist = (id) => wishlist.value.includes(id);

const heroBanners = computed(() => {
    if (!Array.isArray(sliders.value)) return [];
    const main = sliders.value.filter(s => s.category_name === 'Slider' || s.category_name === 'Home Banner');
    return main.length > 0 ? main : sliders.value;
});

const filteredProducts = computed(() => {
    if (activeProductTab.value === 'all') return products.value;
    return products.value.filter(p => p.category?.slug === activeProductTab.value);
});

const categoryTabs = computed(() => {
    const tabs = [{ slug: 'all', name: 'All Products' }];
    categories.value.slice(0, 5).forEach(c => tabs.push({ slug: c.slug, name: c.name }));
    return tabs;
});

const normalize = (str) => str ? String(str).replace(/[_\-\s]+/g, '').toLowerCase() : '';
const getSetting = (group, key, defaultValue = '') => {
    if (!settings.value) return defaultValue;
    const tg = Object.keys(settings.value).find(k => normalize(k) === normalize(group));
    if (tg && Array.isArray(settings.value[tg])) {
        const item = settings.value[tg].find(s => normalize(s.key) === normalize(key) || normalize(s.name) === normalize(key));
        if (item && item.value !== null && item.value !== '') return item.type === 'image' ? item.value_url : item.value;
    }
    return defaultValue;
};

const getImageUrl = (path) => {
    if (!path) return 'https://placehold.co/300x300/f0fdf4/10b981?text=Product';
    return path.startsWith('http') ? path : `${backendUrl}/storage/${path}`;
};

const fetchData = async () => {
    try {
        loading.value = true;
        const params = {};
        if (searchQuery.value) params.search = searchQuery.value;
        if (selectedCategory.value) params.category_slug = selectedCategory.value;
        if (sortOption.value) params.sort = sortOption.value;
        if (minPrice.value) params.min_price = minPrice.value;
        if (maxPrice.value) params.max_price = maxPrice.value;

        const [prodRes, catRes, featuredRes, sliderRes, settingRes] = await Promise.all([
            axios.get(`${backendUrl}/api/public/products`, { params }).catch(() => ({ data: [] })),
            axios.get(`${backendUrl}/api/public/categories`).catch(() => ({ data: [] })),
            axios.get(`${backendUrl}/api/public/products/featured`).catch(() => ({ data: [] })),
            axios.get(`${backendUrl}/api/public/sliders`).catch(() => ({ data: [] })),
            axios.get(`${backendUrl}/api/public/settings`).catch(() => ({ data: {} }))
        ]);

        products.value = prodRes.data?.data || [];
        categories.value = catRes.data?.data || catRes.data || [];
        featuredProducts.value = featuredRes.data?.data || featuredRes.data || [];
        const raw = sliderRes.data?.data || sliderRes.data || [];
        sliders.value = Array.isArray(raw) ? raw : [];
        settings.value = settingRes.data?.data || {};
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
        initialLoading.value = false;
    }
};

const filterByCategory = (slug) => {
    selectedCategory.value = selectedCategory.value === slug ? null : slug;
    fetchData();
    document.getElementById('products-section')?.scrollIntoView({ behavior: 'smooth' });
};

const resetFilters = () => {
    selectedCategory.value = null;
    searchQuery.value = '';
    sortOption.value = '';
    minPrice.value = '';
    maxPrice.value = '';
    fetchData();
};

const handleAddToCart = async (product) => {
    try {
        await cartStore.addToCart(product);
        const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
        Toast.fire({ icon: 'success', title: `${product.name} added to cart` });
    } catch {
        Swal.fire({ icon: 'error', title: 'Failed', text: 'Please try again' });
    }
};

const toggleWishlist = (id) => {
    wishlist.value.includes(id)
        ? (wishlist.value = wishlist.value.filter(x => x !== id))
        : wishlist.value.push(id);
};

const navigateToProduct = (id) => router.push(`/product/${id}`);
const handleScroll = () => { showBackToTop.value = window.scrollY > 500; };
const scrollToTop = () => window.scrollTo({ top: 0, behavior: 'smooth' });

let slideInterval;
const startSlider = () => {
    slideInterval = setInterval(() => {
        if (heroBanners.value.length > 1) currentSlide.value = (currentSlide.value + 1) % heroBanners.value.length;
    }, 5000);
};

const startCountdown = () => {
    let end = Date.now() + 3600000;
    const tick = () => {
        const diff = Math.max(0, end - Date.now());
        countdown.value = {
            h: String(Math.floor(diff / 3600000)).padStart(2, '0'),
            m: String(Math.floor((diff % 3600000) / 60000)).padStart(2, '0'),
            s: String(Math.floor((diff % 60000) / 1000)).padStart(2, '0'),
        };
        if (diff === 0) end = Date.now() + 3600000;
    };
    tick();
    countdownInterval = setInterval(tick, 1000);
};

let debounce = null;
watch(searchQuery, () => { clearTimeout(debounce); debounce = setTimeout(fetchData, 500); });
watch([minPrice, maxPrice], () => { clearTimeout(debounce); debounce = setTimeout(fetchData, 800); });
watch(wishlist, (v) => localStorage.setItem('wishlist', JSON.stringify(v)), { deep: true });

onMounted(() => {
    fetchData();
    const saved = localStorage.getItem('wishlist');
    if (saved) wishlist.value = JSON.parse(saved);
    window.addEventListener('scroll', handleScroll);
    startSlider();
    startCountdown();
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    clearInterval(slideInterval);
    clearInterval(countdownInterval);
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 font-sans antialiased text-gray-800">

        <!-- Initial Loading -->
        <div v-if="initialLoading" class="fixed inset-0 bg-white z-[100] flex items-center justify-center">
            <div class="text-center">
                <div class="w-14 h-14 border-2 border-emerald-100 border-t-emerald-500 rounded-full animate-spin mx-auto mb-4"></div>
                <p class="text-gray-400 text-sm">{{ getSetting('general', 'site_name', 'Loading…') }}</p>
            </div>
        </div>

        <!-- ===== TOP BAR ===== -->
        <div class="bg-emerald-600 text-white text-xs py-2 hidden md:block">
            <div class="max-w-screen-xl mx-auto px-4 flex items-center justify-between">
                <span>{{ getSetting('general', 'topbar_text', '🎉 Free shipping on orders over ৳500 | Use code: WELCOME10') }}</span>
                <div class="flex items-center gap-5">
                    <span v-if="getSetting('general', 'phone')">📞 {{ getSetting('general', 'phone') }}</span>
                    <a v-if="getSetting('social', 'facebook')" :href="getSetting('social', 'facebook')" target="_blank" class="hover:underline">Facebook</a>
                    <a v-if="getSetting('social', 'instagram')" :href="getSetting('social', 'instagram')" target="_blank" class="hover:underline">Instagram</a>
                </div>
            </div>
        </div>

        <!-- ===== HEADER ===== -->
        <header class="bg-white border-b border-gray-100 sticky top-0 z-50 shadow-sm">
            <div class="max-w-screen-xl mx-auto px-4 h-16 flex items-center gap-4">
                <!-- Logo -->
                <router-link to="/" class="flex items-center gap-2 flex-shrink-0">
                    <img v-if="getSetting('general', 'site_logo')" :src="getSetting('general', 'site_logo')" class="h-9 w-auto object-contain" alt="Logo" />
                    <div v-else class="flex items-center gap-1.5">
                        <div class="w-8 h-8 bg-emerald-500 rounded-lg flex items-center justify-center text-white font-black text-sm shadow-md shadow-emerald-200">S</div>
                        <span class="font-black text-xl text-gray-900">{{ getSetting('general', 'site_name', 'Sellzy') }}</span>
                    </div>
                </router-link>

                <!-- Search Bar -->
                <div class="flex-1 max-w-2xl hidden md:flex">
                    <div class="relative w-full flex rounded-full overflow-hidden border border-gray-200 focus-within:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-100 transition-all bg-gray-50">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">🔍</span>
                        <input
                            v-model="searchQuery"
                            type="text"
                            :placeholder="getSetting('general', 'search_placeholder', 'Search for products…')"
                            class="flex-1 py-2.5 pl-10 pr-4 text-sm bg-transparent focus:outline-none"
                        />
                        <button class="bg-emerald-500 hover:bg-emerald-600 text-white px-5 text-sm font-semibold transition-colors flex-shrink-0">
                            Search
                        </button>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2 ml-auto md:ml-0">
                    <template v-if="authStore.isAuthenticated">
                        <router-link to="/admin/dashboard" class="hidden sm:flex items-center gap-1.5 text-sm font-semibold text-gray-600 hover:text-emerald-600 px-3 py-2 rounded-lg hover:bg-emerald-50 transition-all">
                            👤 Dashboard
                        </router-link>
                    </template>
                    <template v-else>
                        <router-link to="/login" class="hidden sm:block text-sm font-semibold text-gray-600 hover:text-emerald-600 px-3 py-2 rounded-lg hover:bg-emerald-50 transition-all">
                            Sign In
                        </router-link>
                    </template>

                    <router-link to="/checkout" class="relative flex items-center gap-1.5 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold px-4 py-2 rounded-full text-sm transition-all shadow-sm shadow-emerald-200">
                        🛒
                        <span class="hidden sm:inline">Cart</span>
                        <span v-if="cartStore.items.length" class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center border-2 border-white">
                            {{ cartStore.items.length }}
                        </span>
                    </router-link>
                </div>
            </div>
        </header>

        <!-- ===== HERO + CATEGORY SIDEBAR ===== -->
        <section class="bg-white border-b border-gray-100">
            <div class="max-w-screen-xl mx-auto px-4 py-5">
                <div class="grid lg:grid-cols-[240px_1fr_200px] gap-4 min-h-[340px]">

                    <!-- LEFT: Category Sidebar -->
                    <div class="hidden lg:flex flex-col bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
                        <div class="bg-emerald-500 text-white px-4 py-3 font-bold text-sm">
                            ☰ {{ getSetting('general', 'categories_menu_title', 'All Categories') }}
                        </div>
                        <div class="flex-1 overflow-y-auto py-1">
                            <button
                                v-for="cat in categories.slice(0, 12)"
                                :key="cat.id"
                                @click="filterByCategory(cat.slug)"
                                class="w-full text-left px-4 py-2.5 text-sm flex items-center justify-between group transition-colors"
                                :class="selectedCategory === cat.slug ? 'text-emerald-600 bg-emerald-50 font-semibold' : 'text-gray-600 hover:text-emerald-600 hover:bg-emerald-50'"
                            >
                                <span>{{ cat.name }}</span>
                                <span class="text-gray-300 group-hover:text-emerald-400">›</span>
                            </button>
                            <div v-if="!categories.length" class="px-4 py-3 text-sm text-gray-400 italic">No categories found</div>
                        </div>
                    </div>

                    <!-- CENTER: Main Slider -->
                    <div class="relative rounded-2xl overflow-hidden bg-gradient-to-br from-emerald-50 to-teal-100 min-h-[340px] shadow-sm">
                        <transition name="xfade" mode="out-in">
                            <div v-if="heroBanners.length > 0" :key="currentSlide" class="absolute inset-0 flex items-center">
                                <img v-if="heroBanners[currentSlide].image_url" :src="heroBanners[currentSlide].image_url" class="absolute inset-0 w-full h-full object-cover" alt="" />
                                <div class="absolute inset-0 bg-gradient-to-r from-black/55 via-black/25 to-transparent"></div>
                                <div class="relative z-10 px-10 text-white max-w-sm">
                                    <span class="inline-block text-xs font-bold uppercase tracking-widest bg-emerald-500 px-3 py-1 rounded-full mb-3">
                                        {{ heroBanners[currentSlide].category_name || getSetting('general', 'hero_badge', 'Exclusive Offer') }}
                                    </span>
                                    <h2 class="text-3xl md:text-4xl font-black leading-tight mb-3">
                                        {{ heroBanners[currentSlide].title || getSetting('general', 'hero_title', 'Discover Your Best Products') }}
                                    </h2>
                                    <p class="text-sm text-white/75 mb-5">
                                        {{ heroBanners[currentSlide].description || getSetting('general', 'hero_subtitle', 'Shop the best deals today.') }}
                                    </p>
                                    <a :href="heroBanners[currentSlide].link || '/shop'" class="inline-flex items-center gap-1.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-6 py-2.5 rounded-full text-sm transition-all shadow-lg hover:scale-105">
                                        Shop Now →
                                    </a>
                                </div>
                            </div>
                            <div v-else class="absolute inset-0 flex items-center px-10">
                                <div class="max-w-sm">
                                    <span class="inline-block text-xs font-bold uppercase tracking-widest bg-emerald-500 text-white px-3 py-1 rounded-full mb-3">
                                        {{ getSetting('general', 'hero_badge', 'Exclusive Offer') }}
                                    </span>
                                    <h2 class="text-3xl md:text-4xl font-black leading-tight mb-3 text-gray-900">
                                        {{ getSetting('general', 'hero_title', 'Discover Your') }}
                                        <span class="text-emerald-600"> {{ getSetting('general', 'hero_highlight', 'Best Products') }}</span>
                                    </h2>
                                    <p class="text-sm text-gray-500 mb-5">{{ getSetting('general', 'hero_subtitle', 'Quality products at unbeatable prices.') }}</p>
                                    <button @click="$router.push('/shop')" class="inline-flex items-center gap-1.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-6 py-2.5 rounded-full text-sm transition-all hover:scale-105">
                                        Shop Now →
                                    </button>
                                </div>
                            </div>
                        </transition>
                        <!-- Slider dots -->
                        <div v-if="heroBanners.length > 1" class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-1.5 z-20">
                            <button v-for="(_, i) in heroBanners" :key="i" @click="currentSlide = i"
                                :class="currentSlide === i ? 'w-5 bg-emerald-500' : 'w-2 bg-white/60 hover:bg-white'"
                                class="h-2 rounded-full transition-all duration-300"></button>
                        </div>
                    </div>

                    <!-- RIGHT: Promo Cards -->
                    <div class="hidden lg:flex flex-col gap-3">
                        <!-- Promo 1 -->
                        <div
                            class="flex-1 rounded-2xl overflow-hidden relative cursor-pointer group shadow-sm"
                            :style="getSetting('general','promo1_bg') ? `background:url(${getSetting('general','promo1_bg')}) center/cover` : 'background: linear-gradient(135deg, #d1fae5, #6ee7b7)'"
                            @click="$router.push(getSetting('general','promo1_link','/shop'))"
                        >
                            <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
                            <div class="relative z-10 p-4 h-full flex flex-col justify-end">
                                <p class="text-[11px] font-bold text-emerald-200 mb-0.5">{{ getSetting('general','promo1_label','Up to 20% off') }}</p>
                                <h3 class="font-black text-white text-base leading-tight">{{ getSetting('general','promo1_title','Your Daily Store') }}</h3>
                                <span class="mt-2 text-xs text-white/75 group-hover:text-white font-semibold transition-colors">Shop Now →</span>
                            </div>
                        </div>
                        <!-- Promo 2 -->
                        <div
                            class="flex-1 rounded-2xl overflow-hidden relative cursor-pointer group shadow-sm"
                            :style="getSetting('general','promo2_bg') ? `background:url(${getSetting('general','promo2_bg')}) center/cover` : 'background: linear-gradient(135deg, #fef3c7, #fcd34d)'"
                            @click="$router.push(getSetting('general','promo2_link','/shop'))"
                        >
                            <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
                            <div class="relative z-10 p-4 h-full flex flex-col justify-end">
                                <p class="text-[11px] font-bold text-amber-200 mb-0.5">{{ getSetting('general','promo2_label','Up to 30% off') }}</p>
                                <h3 class="font-black text-white text-base leading-tight">{{ getSetting('general','promo2_title','Click. Shop. Smile.') }}</h3>
                                <span class="mt-2 text-xs text-white/75 group-hover:text-white font-semibold transition-colors">Shop Now →</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== CATEGORY SCROLLABLE ROW ===== -->
        <section v-if="categories.length" class="bg-white border-b border-gray-100 shadow-sm">
            <div class="max-w-screen-xl mx-auto px-4 py-4">
                <div class="flex items-center gap-3 overflow-x-auto scrollbar-hide">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-widest whitespace-nowrap flex-shrink-0">
                        {{ getSetting('general','categories_title','Shop by Category') }} →
                    </span>
                    <button
                        v-for="cat in categories"
                        :key="cat.id"
                        @click="filterByCategory(cat.slug)"
                        class="flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-semibold whitespace-nowrap border transition-all flex-shrink-0"
                        :class="selectedCategory === cat.slug
                            ? 'bg-emerald-500 text-white border-emerald-500 shadow-sm shadow-emerald-200'
                            : 'bg-white text-gray-600 border-gray-200 hover:border-emerald-300 hover:text-emerald-600'"
                    >
                        <img v-if="cat.image" :src="getImageUrl(cat.image)" class="w-4 h-4 rounded-full object-cover" alt="" />
                        {{ cat.name }}
                    </button>
                </div>
            </div>
        </section>

        <!-- ===== TODAY'S FLASH DEALS with Countdown ===== -->
        <section v-if="featuredProducts.length" class="max-w-screen-xl mx-auto px-4 py-10">
            <!-- Section Header -->
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-4 flex-wrap">
                    <div>
                        <h2 class="text-xl font-black text-gray-900">
                            {{ getSetting('general','flash_title',"Today's Top Offers") }}
                            <span class="text-red-500"> 🔥</span>
                        </h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ getSetting('general','flash_subtitle','Up to 69% discount for limited time') }}</p>
                    </div>
                    <!-- Live Countdown -->
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs text-gray-500 font-medium">Ends in:</span>
                        <div class="flex items-center gap-1 font-black text-sm tabular-nums">
                            <span class="bg-gray-900 text-white px-2 py-1 rounded-lg min-w-[30px] text-center">{{ countdown.h }}</span>
                            <span class="text-gray-400">:</span>
                            <span class="bg-gray-900 text-white px-2 py-1 rounded-lg min-w-[30px] text-center">{{ countdown.m }}</span>
                            <span class="text-gray-400">:</span>
                            <span class="bg-gray-900 text-white px-2 py-1 rounded-lg min-w-[30px] text-center">{{ countdown.s }}</span>
                        </div>
                    </div>
                </div>
                <router-link to="/shop" class="text-sm font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-0.5 whitespace-nowrap">
                    View All ›
                </router-link>
            </div>

            <!-- Flash Products -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                <div
                    v-for="product in featuredProducts.slice(0, 6)"
                    :key="product.id"
                    class="bg-white border border-gray-100 rounded-2xl p-3 hover:shadow-md hover:border-emerald-100 transition-all cursor-pointer group"
                    @click="navigateToProduct(product.id)"
                >
                    <div class="relative mb-2.5">
                        <span class="absolute top-0 left-0 z-10 text-[9px] font-bold bg-red-500 text-white px-2 py-0.5 rounded-full">SALE</span>
                        <button @click.stop="toggleWishlist(product.id)" class="absolute top-0 right-0 z-10 w-7 h-7 bg-white rounded-full shadow flex items-center justify-center hover:scale-110 transition-transform">
                            <span class="text-xs">{{ isInWishlist(product.id) ? '❤️' : '🤍' }}</span>
                        </button>
                        <div class="aspect-square bg-gray-50 rounded-xl overflow-hidden flex items-center justify-center p-2">
                            <img :src="getImageUrl(product.thumbnail)" class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-500" alt="" />
                        </div>
                    </div>
                    <p class="text-[9px] text-emerald-500 font-bold uppercase tracking-wider truncate">{{ product.category?.name || 'Product' }}</p>
                    <h3 class="text-xs font-semibold text-gray-800 line-clamp-2 mt-0.5 mb-2 leading-snug group-hover:text-emerald-600 transition-colors">{{ product.name }}</h3>
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-sm font-black text-gray-900">৳{{ product.sale_price || product.base_price }}</span>
                            <span v-if="product.discount_price" class="ml-1 text-xs text-gray-400 line-through">৳{{ product.base_price }}</span>
                        </div>
                        <button @click.stop="handleAddToCart(product)" class="w-7 h-7 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg flex items-center justify-center font-bold text-base transition-all hover:scale-110 active:scale-95">+</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== ALL PRODUCTS with TABS ===== -->
        <section id="products-section" class="max-w-screen-xl mx-auto px-4 pb-14">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-black text-gray-900">
                    {{ getSetting('general','products_section_title','Our Products') }}
                </h2>
                <div class="flex items-center gap-2">
                    <select v-model="sortOption" @change="fetchData" class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:border-emerald-400 bg-white text-gray-600">
                        <option value="">Sort: Default</option>
                        <option value="price_low">Price ↑</option>
                        <option value="price_high">Price ↓</option>
                        <option value="newest">Newest</option>
                    </select>
                    <button v-if="activeFilterCount" @click="resetFilters" class="text-xs font-bold text-red-500 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition-colors">
                        ✕ Clear ({{ activeFilterCount }})
                    </button>
                </div>
            </div>

            <!-- Category Tabs -->
            <div class="flex gap-2 overflow-x-auto scrollbar-hide mb-6 pb-1">
                <button
                    v-for="tab in categoryTabs"
                    :key="tab.slug"
                    @click="activeProductTab = tab.slug"
                    class="px-4 py-2 rounded-full text-sm font-semibold whitespace-nowrap transition-all border flex-shrink-0"
                    :class="activeProductTab === tab.slug
                        ? 'bg-gray-900 text-white border-gray-900'
                        : 'bg-white text-gray-500 border-gray-200 hover:border-gray-400 hover:text-gray-700'"
                >
                    {{ tab.name }}
                </button>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="flex items-center justify-center py-24">
                <div class="w-10 h-10 border-2 border-gray-100 border-t-emerald-500 rounded-full animate-spin"></div>
            </div>

            <!-- Grid -->
            <div v-else-if="filteredProducts.length" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <div
                    v-for="(product, i) in filteredProducts"
                    :key="product.id"
                    class="bg-white border border-gray-100 rounded-2xl p-3 hover:shadow-md hover:border-emerald-100 transition-all cursor-pointer group animate-fade-in"
                    :style="{ animationDelay: `${i * 35}ms` }"
                    @click="navigateToProduct(product.id)"
                >
                    <div class="relative mb-2.5">
                        <div v-if="product.discount_price" class="absolute top-0 left-0 z-10 text-[9px] font-bold bg-red-500 text-white px-2 py-0.5 rounded-full">
                            -{{ Math.round((product.base_price - product.sale_price) / product.base_price * 100) }}%
                        </div>
                        <button @click.stop="toggleWishlist(product.id)" class="absolute top-0 right-0 z-10 w-7 h-7 bg-white rounded-full shadow flex items-center justify-center hover:scale-110 transition-transform opacity-0 group-hover:opacity-100">
                            <span class="text-xs">{{ isInWishlist(product.id) ? '❤️' : '🤍' }}</span>
                        </button>
                        <div class="aspect-square bg-gray-50 rounded-xl overflow-hidden flex items-center justify-center p-2">
                            <img :src="getImageUrl(product.thumbnail)" class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-500" alt="" />
                        </div>
                        <!-- Quick View -->
                        <div class="absolute inset-0 bg-black/20 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <button @click.stop="navigateToProduct(product.id)" class="bg-white text-gray-900 text-xs font-bold px-4 py-1.5 rounded-full shadow-lg translate-y-2 group-hover:translate-y-0 transition-transform">
                                Quick View
                            </button>
                        </div>
                    </div>
                    <p class="text-[9px] text-emerald-500 font-bold uppercase tracking-wider truncate">{{ product.category?.name || 'Product' }}</p>
                    <h3 class="text-xs font-semibold text-gray-800 line-clamp-2 mt-0.5 mb-2 leading-snug group-hover:text-emerald-600 transition-colors">{{ product.name }}</h3>
                    <div class="flex items-center gap-1 mb-2">
                        <span class="text-yellow-400 text-xs">★★★★★</span>
                        <span class="text-[10px] text-gray-400">({{ product.reviews_count || 0 }})</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-sm font-black text-gray-900">৳{{ product.sale_price || product.base_price }}</span>
                            <span v-if="product.discount_price" class="ml-1 text-xs text-gray-400 line-through">৳{{ product.base_price }}</span>
                        </div>
                        <button @click.stop="handleAddToCart(product)" class="w-7 h-7 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg flex items-center justify-center font-bold text-base transition-all hover:scale-110 active:scale-95 shadow-sm">+</button>
                    </div>
                </div>
            </div>

            <!-- Empty -->
            <div v-else class="text-center py-24">
                <div class="text-5xl mb-4">🔍</div>
                <h3 class="text-xl font-bold text-gray-700 mb-2">No Products Found</h3>
                <p class="text-gray-400 text-sm mb-6">Try adjusting your filters or search term.</p>
                <button @click="resetFilters" class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-6 py-2.5 rounded-full text-sm transition-all">Clear Filters</button>
            </div>
        </section>

        <!-- ===== PROMO BANNER ===== -->
        <section class="max-w-screen-xl mx-auto px-4 pb-12">
            <div
                class="rounded-3xl overflow-hidden relative min-h-[180px] flex items-center px-10 shadow-lg"
                :style="getSetting('general','promo_banner_bg') ? `background:url(${getSetting('general','promo_banner_bg')}) center/cover` : 'background: linear-gradient(135deg, #064e3b 0%, #065f46 100%)'"
            >
                <div class="absolute inset-0 bg-gradient-to-r from-black/40 to-transparent"></div>
                <div class="relative z-10 text-white max-w-lg">
                    <span class="text-xs font-bold uppercase tracking-widest text-emerald-300 mb-2 block">
                        {{ getSetting('general','promo_banner_label','Enjoy 20% savings') }}
                    </span>
                    <h2 class="text-2xl font-black mb-4">
                        {{ getSetting('general','promo_banner_title','Your Daily Store. Essentials, deals, and more.') }}
                    </h2>
                    <button @click="$router.push('/shop')" class="inline-flex items-center gap-2 bg-white text-emerald-700 font-bold px-6 py-2.5 rounded-full text-sm hover:bg-emerald-50 transition-all shadow-lg">
                        {{ getSetting('general','promo_banner_btn','Shop Now') }} →
                    </button>
                </div>
            </div>
        </section>

        <!-- ===== NEWSLETTER ===== -->
        <section class="bg-gray-900 py-14">
            <div class="max-w-screen-xl mx-auto px-4 text-center">
                <h2 class="text-2xl font-black text-white mb-2">
                    {{ getSetting('general','newsletter_title','Subscribe to our newsletter') }}
                </h2>
                <p class="text-gray-400 text-sm mb-7 max-w-md mx-auto">
                    {{ getSetting('general','newsletter_subtitle','Stay updated with news, updates, and exclusive offers.') }}
                </p>
                <form @submit.prevent class="flex gap-2 max-w-md mx-auto">
                    <input
                        type="email"
                        :placeholder="getSetting('general','newsletter_placeholder','Enter your email address')"
                        class="flex-1 bg-gray-800 border border-gray-700 text-white rounded-full px-5 py-2.5 text-sm focus:outline-none focus:border-emerald-500 transition-all placeholder-gray-500"
                    />
                    <button class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-6 py-2.5 rounded-full text-sm transition-all flex-shrink-0">
                        {{ getSetting('general','newsletter_btn','Subscribe') }}
                    </button>
                </form>
            </div>
        </section>

        <!-- ===== FOOTER ===== -->
        <footer class="bg-gray-950 text-gray-500 pt-14 pb-8">
            <div class="max-w-screen-xl mx-auto px-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10 mb-12">
                    <div class="lg:col-span-2">
                        <div class="flex items-center gap-2 mb-4">
                            <img v-if="getSetting('general','site_logo')" :src="getSetting('general','site_logo')" class="h-8 w-auto filter brightness-0 invert opacity-70" alt="Logo" />
                            <span v-else class="font-black text-xl text-white">{{ getSetting('general','site_name','Sellzy') }}</span>
                        </div>
                        <p class="text-sm leading-relaxed mb-5 max-w-xs text-gray-600">
                            {{ getSetting('general','site_description','Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt.') }}
                        </p>
                        <div class="flex gap-2">
                            <a v-if="getSetting('social','facebook')" :href="getSetting('social','facebook')" target="_blank" class="social-pill">f</a>
                            <a v-if="getSetting('social','instagram')" :href="getSetting('social','instagram')" target="_blank" class="social-pill">ig</a>
                            <a v-if="getSetting('social','youtube')" :href="getSetting('social','youtube')" target="_blank" class="social-pill">yt</a>
                            <a v-if="getSetting('social','twitter')" :href="getSetting('social','twitter')" target="_blank" class="social-pill">𝕏</a>
                        </div>
                    </div>
                    <div>
                        <h4 class="f-head">About</h4>
                        <ul class="space-y-2.5 text-sm">
                            <li><a href="/about" class="f-link">About Us</a></li>
                            <li><a href="/terms" class="f-link">Terms & Conditions</a></li>
                            <li><a href="/privacy" class="f-link">Privacy Policy</a></li>
                            <li><a href="/contact" class="f-link">Contact Us</a></li>
                            <li><a href="/faq" class="f-link">FAQ</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="f-head">My Account</h4>
                        <ul class="space-y-2.5 text-sm">
                            <li><a href="/my-account" class="f-link">Your Account</a></li>
                            <li><a href="/wishlist" class="f-link">Wishlist</a></li>
                            <li><a href="/orders" class="f-link">Track Order</a></li>
                            <li><a href="/refund" class="f-link">Return Policy</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="f-head">Contact</h4>
                        <ul class="space-y-3 text-sm">
                            <li v-if="getSetting('general','address')" class="flex gap-2">
                                <span class="text-emerald-500 flex-shrink-0 mt-0.5">📍</span>
                                <span>{{ getSetting('general','address') }}</span>
                            </li>
                            <li v-if="getSetting('general','phone')" class="flex gap-2">
                                <span class="text-emerald-500 flex-shrink-0">📞</span>
                                <a :href="`tel:${getSetting('general','phone')}`" class="f-link">{{ getSetting('general','phone') }}</a>
                            </li>
                            <li v-if="getSetting('general','email')" class="flex gap-2">
                                <span class="text-emerald-500 flex-shrink-0">✉</span>
                                <a :href="`mailto:${getSetting('general','email')}`" class="f-link">{{ getSetting('general','email') }}</a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="border-t border-gray-800 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-600">
                    <span>{{ getSetting('general','copyright','© 2026 Sellzy. All Rights Reserved.') }}</span>
                    <div class="flex gap-2">
                        <span v-if="getSetting('payment','bkash_enabled')" class="pay-b text-pink-400">bKash</span>
                        <span v-if="getSetting('payment','nagad_enabled')" class="pay-b text-orange-400">Nagad</span>
                        <span v-if="getSetting('payment','rocket_enabled')" class="pay-b text-purple-400">Rocket</span>
                        <span v-if="getSetting('payment','sslcommerz_enabled')" class="pay-b text-green-400">SSL</span>
                        <span v-if="getSetting('payment','cod_enabled')" class="pay-b text-blue-400">COD</span>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Back to top -->
        <button
            v-if="showBackToTop"
            @click="scrollToTop"
            class="fixed bottom-6 right-6 w-11 h-11 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl shadow-lg shadow-emerald-200 flex items-center justify-center hover:scale-110 z-50 transition-all text-lg font-bold"
        >↑</button>
    </div>
</template>

<style scoped>
.xfade-enter-active { transition: opacity 0.5s ease; }
.xfade-leave-active { transition: opacity 0.3s ease; }
.xfade-enter-from, .xfade-leave-to { opacity: 0; }

.f-head {
    color: white;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 16px;
}
.f-link {
    color: #6b7280;
    text-decoration: none;
    transition: color 0.2s;
}
.f-link:hover { color: #10b981; }

.social-pill {
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 8px;
    font-size: 0.7rem;
    font-weight: 700;
    color: #6b7280;
    text-decoration: none;
    transition: all 0.2s;
}
.social-pill:hover {
    background: rgba(16, 185, 129, 0.15);
    border-color: rgba(16, 185, 129, 0.35);
    color: #10b981;
}

.pay-b {
    padding: 2px 8px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 5px;
    font-size: 0.65rem;
    font-weight: 700;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.4s ease-out forwards;
    opacity: 0;
}

.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
.scrollbar-hide::-webkit-scrollbar { display: none; }

::-webkit-scrollbar { width: 5px; }
::-webkit-scrollbar-track { background: #f9fafb; }
::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: #10b981; }
</style>
