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

const countdown = ref({ h: '00', m: '00', s: '00', d: '00' });
let countdownInterval = null;

const searchQuery = ref('');
const selectedCategory = ref(null);
const sortOption = ref('');
const minPrice = ref('');
const maxPrice = ref('');

const backendUrl = 'http://127.0.0.1:73';

// Computed
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
    categories.value.slice(0, 6).forEach(c => tabs.push({ slug: c.slug, name: c.name }));
    return tabs;
});

// ─── Settings helpers ───────────────────────────────────────────
// DB groups: general | contact | appearance | social | payment
// ────────────────────────────────────────────────────────────────
const normalize = (str) => str ? String(str).replace(/[_\-\s]+/g, '').toLowerCase() : '';

// Search in a specific group
const getSetting = (group, key, defaultValue = '') => {
    if (!settings.value) return defaultValue;
    const tg = Object.keys(settings.value).find(k => normalize(k) === normalize(group));
    if (tg && Array.isArray(settings.value[tg])) {
        const item = settings.value[tg].find(s =>
            normalize(s.key) === normalize(key) || normalize(s.name) === normalize(key));
        if (item && item.value !== null && item.value !== undefined && item.value !== '')
            return item.type === 'image' ? (item.value_url || item.value) : item.value;
    }
    return defaultValue;
};

// Search across ALL groups (fallback)
const getSettingAny = (key, def = '') => {
    if (!settings.value || typeof settings.value !== 'object') return def;
    for (const group of Object.values(settings.value)) {
        if (!Array.isArray(group)) continue;
        const item = group.find(s =>
            normalize(s.key) === normalize(key) || normalize(s.name) === normalize(key));
        if (item && item.value !== null && item.value !== undefined && item.value !== '')
            return item.type === 'image' ? (item.value_url || item.value) : item.value;
    }
    return def;
};

// Contact info lives in 'contact' group → tries contact → general → any
const getContact = (key) =>
    getSetting('contact', key) ||
    getSetting('general', key) ||
    getSettingAny(key) || '';

// Primary color lives in 'appearance' group
const primaryColor = computed(() =>
    getSetting('appearance', 'primary_color') ||
    getSetting('general', 'primary_color') ||
    '#2ecc71'
);
// ────────────────────────────────────────────────────────────────

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

        // Handle both:
        //   grouped object  → { general:[...], contact:[...], appearance:[...] }
        //   flat array      → [ {group:'contact', key:'phone', value:'...'}, ... ]
        const rawSettings = settingRes.data?.data || settingRes.data || {};
        if (Array.isArray(rawSettings)) {
            const grouped = {};
            rawSettings.forEach(item => {
                const g = item.group || 'general';
                if (!grouped[g]) grouped[g] = [];
                grouped[g].push(item);
            });
            settings.value = grouped;
        } else {
            settings.value = rawSettings;
        }

    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
        initialLoading.value = false;
    }
};

const filterByCategory = (slug) => {
    selectedCategory.value = selectedCategory.value === slug ? null : slug;
    activeProductTab.value = slug || 'all';
    fetchData();
    document.getElementById('products-section')?.scrollIntoView({ behavior: 'smooth' });
};

const resetFilters = () => {
    selectedCategory.value = null;
    searchQuery.value = '';
    sortOption.value = '';
    minPrice.value = '';
    maxPrice.value = '';
    activeProductTab.value = 'all';
    fetchData();
};

const handleAddToCart = async (product) => {
    try {
        await cartStore.addToCart(product);
        const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, timerProgressBar: true });
        Toast.fire({ icon: 'success', title: `${product.name} added to cart!` });
    } catch {
        Swal.fire({ icon: 'error', title: 'Oops!', text: 'Failed to add to cart.' });
    }
};

const toggleWishlist = (id) => {
    wishlist.value.includes(id)
        ? (wishlist.value = wishlist.value.filter(x => x !== id))
        : wishlist.value.push(id);
};

const navigateToProduct = (id) => router.push(`/product/${id}`);
const handleScroll = () => { showBackToTop.value = window.scrollY > 600; };
const scrollToTop = () => window.scrollTo({ top: 0, behavior: 'smooth' });

// Slider
let slideInterval;
const startSlider = () => {
    slideInterval = setInterval(() => {
        if (heroBanners.value.length > 1) currentSlide.value = (currentSlide.value + 1) % heroBanners.value.length;
    }, 5000);
};

// Countdown timer
const startCountdown = () => {
    let end = Date.now() + 3600000; // 1 hour
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

const discountPercent = (product) => {
    if (!product.discount_price && product.base_price && product.sale_price && product.base_price > product.sale_price) {
        return Math.round((product.base_price - product.sale_price) / product.base_price * 100);
    }
    return 0;
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
<div class="min-h-screen bg-[#f7f8fa] text-gray-800"
     :style="`font-family:'Inter','Segoe UI',sans-serif;--pc:${primaryColor};--pch:${primaryColor}dd`">

<!-- ======================================================
     INITIAL LOADING
====================================================== -->
<div v-if="initialLoading" class="fixed inset-0 bg-white z-[200] flex items-center justify-center">
    <div class="flex flex-col items-center gap-4">
        <div class="relative w-14 h-14">
            <div class="absolute inset-0 border-4 border-[#e8f5e9] rounded-full"></div>
            <div class="absolute inset-0 border-4 rounded-full animate-spin"
                 :style="`border-color:transparent;border-top-color:${primaryColor}`"></div>
        </div>
        <span class="text-gray-500 text-sm font-medium tracking-wide">{{ getSetting('general','site_name','Loading…') }}</span>
    </div>
</div>

<!-- ======================================================
     TOP UTILITY BAR
====================================================== -->
<div class="bg-[#1a1a2e] text-gray-300 text-xs hidden md:block">
    <div class="max-w-[1320px] mx-auto px-4 h-9 flex items-center justify-between">
        <span class="text-gray-400">{{ getSetting('general','topbar_text','Need Support? Call Us') }}
            <!-- phone from 'contact' group -->
            <a v-if="getContact('phone')"
               :href="`tel:${getContact('phone')}`"
               class="font-semibold ml-1 hover:opacity-80 transition-opacity"
               :style="`color:${primaryColor}`">
                {{ getContact('phone') }}
            </a>
        </span>
        <div class="flex items-center gap-4 text-gray-400">
            <!-- <span class="text-yellow-400 font-semibold">{{ getSetting('general','topbar_promo','🔥 Fashion Category — 25% OFF Today!') }}</span> -->
            <a href="/my-account" class="hover:text-white transition-colors">My Account</a>
            <a href="/wishlist" class="hover:text-white transition-colors">Wishlist</a>
            <a href="/orders" class="hover:text-white transition-colors">Order Tracking</a>
        </div>
    </div>
</div>

<!-- ======================================================
     MAIN HEADER
====================================================== -->
<header class="bg-white border-b border-gray-100 sticky top-0 z-50 shadow-sm">
    <div class="max-w-[1320px] mx-auto px-4 h-[70px] flex items-center gap-5">
        <!-- Logo -->
        <router-link to="/" class="flex items-center gap-2 flex-shrink-0 min-w-[160px]">
            <img v-if="getSetting('general','site_logo')" :src="getSetting('general','site_logo')" class="h-10 w-auto object-contain" alt="Logo" />
            <div v-else class="flex items-center gap-2">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-black text-base shadow-md"
                     :style="`background:${primaryColor}`">S</div>
                <span class="font-black text-xl text-gray-900 tracking-tight">{{ getSetting('general','site_name','Sellzy') }}</span>
            </div>
        </router-link>

        <!-- Search -->
        <div class="flex-1 max-w-[580px] hidden md:flex">
            <div class="flex w-full border-2 rounded-full overflow-hidden shadow-sm"
                 :style="`border-color:${primaryColor}`">
                <input
                    v-model="searchQuery"
                    type="text"
                    :placeholder="getSetting('general','search_placeholder','Search for the Items…')"
                    class="flex-1 px-5 py-2.5 text-sm focus:outline-none bg-white text-gray-700"
                />
                <button class="px-6 text-sm font-semibold text-white flex-shrink-0 hover:opacity-90 transition-opacity"
                        :style="`background:${primaryColor}`">
                    🔍 Search
                </button>
            </div>
        </div>

        <!-- Right Actions -->
        <div class="flex items-center gap-3 ml-auto">
            <!-- Auth -->
            <template v-if="authStore.isAuthenticated">
                <router-link to="/admin/dashboard"
                    class="hidden sm:flex items-center gap-1.5 text-sm font-semibold text-gray-600 transition-colors px-3 py-2 rounded-xl"
                    @mouseover="$event.currentTarget.style.background=primaryColor+'18'"
                    @mouseleave="$event.currentTarget.style.background=''">
                    <span class="text-base">👤</span> Dashboard
                </router-link>
            </template>
            <template v-else>
                <router-link to="/login"
                    class="hidden sm:flex items-center gap-1.5 text-sm font-semibold text-gray-600 transition-colors px-3 py-2 rounded-xl"
                    @mouseover="$event.currentTarget.style.background=primaryColor+'18'"
                    @mouseleave="$event.currentTarget.style.background=''">
                    <span class="text-base">👤</span> log in / Sign Up
                </router-link>
            </template>

            <!-- Cart -->
            <router-link to="/checkout" class="flex items-center gap-2 relative">
                <div class="relative w-11 h-11 flex items-center justify-center rounded-xl hover:opacity-90 transition-colors cursor-pointer"
                     :style="`background:${primaryColor}18`">
                    <span class="text-xl">🛒</span>
                    <span v-if="cartStore.items.length" class="absolute -top-1.5 -right-1.5 bg-[#e74c3c] text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1">
                        {{ cartStore.items.length }}
                    </span>
                </div>
                <div class="hidden sm:block">
                    <p class="text-[10px] text-gray-400 leading-none">Cart</p>
                    <p class="text-sm font-bold text-gray-900 leading-none mt-0.5">{{ cartStore.items.length }} Items</p>
                </div>
            </router-link>
        </div>
    </div>
</header>

<!-- ======================================================
     SECOND NAVBAR — Category Dropdown + Nav Links
====================================================== -->
<nav class="bg-white border-b border-gray-100 shadow-sm hidden md:block">
    <div class="max-w-[1320px] mx-auto px-4 flex items-center h-12 gap-0">
        <!-- Category Mega Dropdown -->
        <div class="relative group h-full flex-shrink-0">
            <button class="flex items-center gap-2 px-5 h-full text-sm font-semibold text-white transition-colors"
                    :style="`background:${primaryColor}`">
                <span class="text-base">☰</span> Explore All Categories
                <svg class="w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 12 12"><path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg>
            </button>
            <!-- Dropdown -->
            <div class="absolute top-full left-0 bg-white border border-gray-100 rounded-b-2xl shadow-xl w-64 py-2 z-40 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 translate-y-1 group-hover:translate-y-0">
                <button
                    v-for="cat in categories"
                    :key="cat.id"
                    @click="filterByCategory(cat.slug)"
                    class="w-full text-left px-5 py-2.5 text-sm flex items-center justify-between transition-colors group/item"
                    :class="selectedCategory === cat.slug ? 'font-semibold' : 'text-gray-600'"
                    :style="selectedCategory === cat.slug ? `color:${primaryColor};background:${primaryColor}12` : ''"
                    @mouseover="$event.currentTarget.style.background=primaryColor+'12'"
                    @mouseleave="$event.currentTarget.style.background=selectedCategory===cat.slug ? primaryColor+'12' : ''"
                >
                    <span class="flex items-center gap-2.5">
                        <img v-if="cat.image" :src="getImageUrl(cat.image)" class="w-5 h-5 rounded-full object-cover" />
                        <span v-else class="w-5 h-5 rounded-full flex items-center justify-center text-[10px]" :style="`background:${primaryColor}20`">🏷</span>
                        {{ cat.name }}
                    </span>
                    <svg class="w-3 h-3 opacity-0 group-hover/item:opacity-100" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                         :style="`color:${primaryColor}`"><path d="M9 18l6-6-6-6"/></svg>
                </button>
                <div v-if="!categories.length" class="px-5 py-3 text-sm text-gray-400 italic">Loading categories…</div>
            </div>
        </div>

        <!-- Nav Links -->
        <div class="flex items-center gap-1 ml-4 overflow-x-auto scrollbar-hide">
            <router-link to="/" class="nav-item" :class="{'nav-item-active': $route.path === '/'}">Home</router-link>
            <router-link to="/shop" class="nav-item">Shop</router-link>
            <router-link v-if="getSetting('general','show_about')" to="/about" class="nav-item">About Us</router-link>
            <router-link to="/contact" class="nav-item">Contact</router-link>
            <a v-if="getSetting('general','custom_nav1_label')" :href="getSetting('general','custom_nav1_url','#')" class="nav-item">{{ getSetting('general','custom_nav1_label') }}</a>
        </div>

        <!-- Support phone right side — from 'contact' group -->
        <div class="ml-auto flex-shrink-0 flex items-center gap-2 text-sm text-gray-500 pl-4">
            <span class="text-base">📞</span>
            <div>
                <p class="text-[10px] text-gray-400 leading-none">24/7 Support</p>
                <p class="text-sm font-bold text-gray-800 leading-none mt-0.5">
                    {{ getContact('phone') || '888-777-999' }}
                </p>
            </div>
        </div>
    </div>
</nav>

<!-- Mobile Search -->
<div class="md:hidden bg-white border-b border-gray-100 px-4 py-3">
    <div class="flex border-2 rounded-full overflow-hidden" :style="`border-color:${primaryColor}`">
        <input v-model="searchQuery" type="text" :placeholder="getSetting('general','search_placeholder','Search…')" class="flex-1 px-4 py-2 text-sm focus:outline-none" />
        <button class="px-4 text-white text-sm font-semibold" :style="`background:${primaryColor}`">🔍</button>
    </div>
</div>

<!-- ======================================================
     HERO SECTION — Full Width Slider + 2 Promo Cards
====================================================== -->
<section class="bg-white py-4">
    <div class="max-w-[1320px] mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_280px] gap-4">

            <!-- MAIN SLIDER -->
            <div class="relative rounded-2xl overflow-hidden h-[380px] md:h-[420px] bg-gradient-to-br from-green-50 to-teal-100 shadow-sm">
                <transition name="hero-slide" mode="out-in">
                    <div v-if="heroBanners.length > 0" :key="currentSlide" class="absolute inset-0">
                        <img v-if="heroBanners[currentSlide].image_url" :src="heroBanners[currentSlide].image_url" class="w-full h-full object-cover" alt="" />
                        <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-black/30 to-transparent"></div>
                        <div class="absolute inset-0 flex items-center px-10 md:px-16">
                            <div class="max-w-lg">
                                <span class="inline-block text-xs font-bold uppercase tracking-[0.2em] text-white px-4 py-1.5 rounded-full mb-4"
                                      :style="`background:${primaryColor}`">
                                    {{ heroBanners[currentSlide].category_name || getSetting('general','hero_badge','Exclusive Offer') }}
                                </span>
                                <h1 v-if="heroBanners[currentSlide].title" class="text-3xl md:text-5xl font-black text-white leading-[1.08] mb-4">
                                    {{ heroBanners[currentSlide].title }}
                                </h1>
                                <p v-if="heroBanners[currentSlide].description" class="text-white/70 text-sm mb-6 max-w-md leading-relaxed">
                                    {{ heroBanners[currentSlide].description }}
                                </p>
                                <a :href="heroBanners[currentSlide].link || '/shop'"
                                   class="inline-flex items-center gap-2 text-white font-bold px-8 py-3.5 rounded-full text-sm transition-all hover:scale-105 shadow-lg"
                                   :style="`background:${primaryColor}`">
                                    Shop Now →
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- Fallback no sliders -->
                    <div v-else class="absolute inset-0 flex items-center px-10 md:px-16">
                        <div class="max-w-lg">
                            <span class="inline-block text-xs font-bold uppercase tracking-[0.2em] text-white px-4 py-1.5 rounded-full mb-4"
                                  :style="`background:${primaryColor}`">
                                {{ getSetting('general','hero_badge','Exclusive Offer') }}
                            </span>
                            <h1 class="text-3xl md:text-5xl font-black text-gray-900 leading-[1.08] mb-4">
                                {{ getSetting('general','hero_title','Discover Your') }}
                                <span :style="`color:${primaryColor}`">{{ getSetting('general','hero_highlight',' Best Products') }}</span>
                            </h1>
                            <p class="text-gray-500 text-sm mb-6 max-w-md leading-relaxed">
                                {{ getSetting('general','hero_subtitle','Quality products at unbeatable prices.') }}
                            </p>
                            <button @click="$router.push('/shop')"
                                    class="inline-flex items-center gap-2 text-white font-bold px-8 py-3.5 rounded-full text-sm transition-all hover:scale-105 shadow-lg"
                                    :style="`background:${primaryColor}`">
                                Shop Now →
                            </button>
                        </div>
                    </div>
                </transition>

                <!-- Slider Dots -->
                <div v-if="heroBanners.length > 1" class="absolute bottom-5 left-1/2 -translate-x-1/2 flex gap-2 z-20">
                    <button v-for="(_, i) in heroBanners" :key="i"
                        @click="currentSlide = i; clearInterval(slideInterval); startSlider()"
                        class="h-2 rounded-full transition-all duration-300"
                        :class="currentSlide === i ? 'w-7' : 'w-2 bg-white/50 hover:bg-white/80'"
                        :style="currentSlide === i ? `background:${primaryColor}` : ''">
                    </button>
                </div>
                <!-- Slider Arrows -->
                <button v-if="heroBanners.length > 1" @click="currentSlide = (currentSlide - 1 + heroBanners.length) % heroBanners.length" class="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/20 hover:bg-white/40 rounded-full flex items-center justify-center text-white transition-all z-20 backdrop-blur-sm">
                    ‹
                </button>
                <button v-if="heroBanners.length > 1" @click="currentSlide = (currentSlide + 1) % heroBanners.length" class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/20 hover:bg-white/40 rounded-full flex items-center justify-center text-white transition-all z-20 backdrop-blur-sm">
                    ›
                </button>
            </div>

            <!-- RIGHT: 2 Stacked Promo Cards -->
            <div class="hidden lg:flex flex-col gap-4">
                <!-- Promo Card 1 -->
                <div class="flex-1 relative rounded-2xl overflow-hidden cursor-pointer group shadow-sm"
                    :style="getSetting('general','promo1_bg') ? `background:url(${getSetting('general','promo1_bg')}) center/cover no-repeat` : 'background:linear-gradient(135deg,#e8f8f0,#c3f0da)'"
                    @click="$router.push(getSetting('general','promo1_link','/shop'))">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>
                    <div class="relative z-10 p-5 h-full flex flex-col justify-end">
                        <h3 class="font-black text-gray-900 text-lg leading-tight mb-2">{{ getSetting('general','promo1_title','Your Daily Store.') }}</h3>
                        <p class="text-xs text-gray-500 mb-3">{{ getSetting('general','promo1_sub','Essentials, deals, and more.') }}</p>
                        <span class="inline-flex items-center gap-1 bg-white/90 hover:bg-white text-gray-800 text-xs font-bold px-4 py-1.5 rounded-full w-fit transition-all group-hover:shadow-md">Shop Now →</span>
                    </div>
                </div>
                <!-- Promo Card 2 -->
                <div class="flex-1 relative rounded-2xl overflow-hidden cursor-pointer group shadow-sm"
                    :style="getSetting('general','promo2_bg') ? `background:url(${getSetting('general','promo2_bg')}) center/cover no-repeat` : 'background:linear-gradient(135deg,#fff8e8,#fde9a2)'"
                    @click="$router.push(getSetting('general','promo2_link','/shop'))">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>
                    <div class="relative z-10 p-5 h-full flex flex-col justify-end">
                        <h3 class="font-black text-gray-900 text-lg leading-tight mb-2">{{ getSetting('general','promo2_title','Everyday Made Simple.') }}</h3>
                        <p class="text-xs text-gray-500 mb-3">{{ getSetting('general','promo2_sub','Quick, easy, and effortless.') }}</p>
                        <span class="inline-flex items-center gap-1 bg-white/90 hover:bg-white text-gray-800 text-xs font-bold px-4 py-1.5 rounded-full w-fit transition-all group-hover:shadow-md">Shop Now →</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ======================================================
     TRUST / SERVICE BAR
====================================================== -->
<div class="bg-white border-y border-gray-100">
    <div class="max-w-[1320px] mx-auto px-4 py-4 grid grid-cols-2 md:grid-cols-4 divide-x divide-gray-100">
        <div class="flex items-center gap-3 px-4 first:pl-0">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl flex-shrink-0" :style="`background:${primaryColor}15`">🚚</div>
            <div>
                <p class="text-sm font-bold text-gray-900">{{ getSetting('general','trust1_title','Free Shipping') }}</p>
                <p class="text-xs text-gray-400">{{ getSetting('general','trust1_sub','On orders over ৳500') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3 px-4">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-xl flex-shrink-0">🔄</div>
            <div>
                <p class="text-sm font-bold text-gray-900">{{ getSetting('general','trust2_title','Easy Returns') }}</p>
                <p class="text-xs text-gray-400">{{ getSetting('general','trust2_sub','30 day return policy') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3 px-4">
            <div class="w-10 h-10 rounded-xl bg-yellow-50 flex items-center justify-center text-xl flex-shrink-0">🔐</div>
            <div>
                <p class="text-sm font-bold text-gray-900">{{ getSetting('general','trust3_title','Secure Payment') }}</p>
                <p class="text-xs text-gray-400">{{ getSetting('general','trust3_sub','100% protected') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3 px-4">
            <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center text-xl flex-shrink-0">💬</div>
            <div>
                <p class="text-sm font-bold text-gray-900">{{ getSetting('general','trust4_title','24/7 Support') }}</p>
                <p class="text-xs text-gray-400">{{ getSetting('general','trust4_sub','Round-the-clock assistance') }}</p>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================
     SHOP BY CATEGORY — Image Cards
====================================================== -->
<section v-if="categories.length" class="py-10">
    <div class="max-w-[1320px] mx-auto px-4">
        <div class="flex items-end justify-between mb-6">
            <div>
                <h2 class="text-2xl font-black text-gray-900">{{ getSetting('general','cat_section_title','Shop By Category') }}</h2>
                <p class="text-sm text-gray-400 mt-1">{{ getSetting('general','cat_section_sub','Up to 69% discount for limited time 🔥') }}</p>
            </div>
            <router-link to="/categories" class="text-sm font-semibold hover:underline flex items-center gap-1"
                         :style="`color:${primaryColor}`">View All ›</router-link>
        </div>
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-9 gap-3">
            <div
                v-for="cat in categories"
                :key="cat.id"
                @click="filterByCategory(cat.slug)"
                class="group cursor-pointer"
                :class="selectedCategory === cat.slug ? 'opacity-100' : ''"
            >
                <div class="relative rounded-2xl overflow-hidden aspect-square border-2 transition-all duration-300"
                    :class="selectedCategory === cat.slug ? 'shadow-lg' : 'border-transparent hover:shadow-md'"
                    :style="selectedCategory === cat.slug ? `border-color:${primaryColor}` : ''">
                    <img v-if="cat.image" :src="getImageUrl(cat.image)" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" alt="" />
                    <div v-else class="w-full h-full bg-gradient-to-br from-green-50 to-teal-100 flex items-center justify-center text-3xl">🛍</div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                </div>
                <p class="text-center text-xs font-semibold mt-2 text-gray-700 transition-colors truncate px-1"
                   :style="selectedCategory === cat.slug ? `color:${primaryColor}` : ''">{{ cat.name }}</p>
            </div>
        </div>
    </div>
</section>

<!-- ======================================================
     TODAY'S TOP OFFER — Countdown + Product Cards
====================================================== -->
<section v-if="featuredProducts.length" class="py-10 bg-white">
    <div class="max-w-[1320px] mx-auto px-4">
        <!-- Section Header with Countdown -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-black text-gray-900">{{ getSetting('general','flash_title',"Today's Top Offer") }}</h2>
                <p class="text-sm text-gray-400 mt-1">{{ getSetting('general','flash_sub','Up to 69% discount for limited time 🔥') }}</p>
            </div>
            <!-- Countdown Timer -->
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-gray-600">Ends in:</span>
                <div class="flex items-center gap-1.5">
                    <div class="countdown-box">{{ countdown.h }}<span class="countdown-label">HH</span></div>
                    <span class="text-lg font-black text-gray-400">:</span>
                    <div class="countdown-box">{{ countdown.m }}<span class="countdown-label">MM</span></div>
                    <span class="text-lg font-black text-gray-400">:</span>
                    <div class="countdown-box">{{ countdown.s }}<span class="countdown-label">SS</span></div>
                </div>
            </div>
            <router-link to="/shop" class="text-sm font-bold hover:underline" :style="`color:${primaryColor}`">View All Products ›</router-link>
        </div>

        <!-- Flash Product Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            <div
                v-for="product in featuredProducts.slice(0, 6)"
                :key="product.id"
                class="product-card group"
                @click="navigateToProduct(product.id)"
            >
                <div class="relative bg-gray-50 rounded-xl overflow-hidden mb-3 aspect-square">
                    <!-- Badges -->
                    <span class="badge-sales">SALES</span>
                    <button @click.stop="toggleWishlist(product.id)" class="absolute top-2 right-2 z-10 w-7 h-7 bg-white rounded-full shadow flex items-center justify-center transition-all hover:scale-110">
                        <span class="text-xs">{{ isInWishlist(product.id) ? '❤️' : '🤍' }}</span>
                    </button>
                    <img :src="getImageUrl(product.thumbnail)" class="w-full h-full object-contain p-3 group-hover:scale-105 transition-transform duration-500" alt="" />
                </div>
                <div class="px-1">
                    <p class="text-[10px] text-gray-400 font-medium mb-1 truncate">{{ product.category?.name || 'Product' }}</p>
                    <h3 class="text-xs font-semibold text-gray-800 line-clamp-2 mb-2 leading-snug group-hover:[color:var(--pc)] transition-colors">{{ product.name }}</h3>
                    <!-- Rating -->
                    <div class="flex items-center gap-1 mb-2">
                        <div class="flex">
                            <span v-for="i in 5" :key="i" class="text-yellow-400 text-xs">★</span>
                        </div>
                        <span class="text-[10px] text-gray-400">({{ product.reviews_count || 0 }})</span>
                    </div>
                    <!-- Price + Cart -->
                    <div class="flex items-center justify-between gap-1">
                        <div>
                            <span class="text-sm font-black text-gray-900">৳{{ product.sale_price || product.base_price }}</span>
                            <span v-if="discountPercent(product)" class="ml-1 text-[10px] font-semibold text-red-500">{{ discountPercent(product) }}% OFF</span>
                            <br v-if="product.base_price && product.sale_price && product.base_price > product.sale_price" />
                            <span v-if="product.base_price && product.sale_price && product.base_price > product.sale_price" class="text-[10px] text-gray-400 line-through">৳{{ product.base_price }}</span>
                        </div>
                        <button @click.stop="handleAddToCart(product)" class="cart-add-btn" :style="`background:${primaryColor}`">+</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ======================================================
     MOST LOVED PRODUCTS
====================================================== -->
<section v-if="products.length" class="py-10">
    <div class="max-w-[1320px] mx-auto px-4">
        <div class="flex items-end justify-between mb-6">
            <div>
                <h2 class="text-2xl font-black text-gray-900">Most Loved Products</h2>
                <p class="text-sm text-gray-400 mt-1">Customer favorites, handpicked for you</p>
            </div>
            <router-link to="/shop" class="text-sm font-bold hover:underline" :style="`color:${primaryColor}`">View All ›</router-link>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            <div
                v-for="product in products.slice(0, 6)"
                :key="product.id"
                class="product-card group"
                @click="navigateToProduct(product.id)"
            >
                <div class="relative bg-gray-50 rounded-xl overflow-hidden mb-3 aspect-square">
                    <span v-if="discountPercent(product)" class="badge-discount">{{ discountPercent(product) }}% OFF</span>
                    <button @click.stop="toggleWishlist(product.id)" class="absolute top-2 right-2 z-10 w-7 h-7 bg-white rounded-full shadow flex items-center justify-center transition-all hover:scale-110 opacity-0 group-hover:opacity-100">
                        <span class="text-xs">{{ isInWishlist(product.id) ? '❤️' : '🤍' }}</span>
                    </button>
                    <img :src="getImageUrl(product.thumbnail)" class="w-full h-full object-contain p-3 group-hover:scale-105 transition-transform duration-500" alt="" />
                    <!-- Quick View -->
                    <div class="absolute inset-x-0 bottom-0 bg-white/95 py-2 text-center text-xs font-semibold translate-y-full group-hover:translate-y-0 transition-transform"
                         :style="`color:${primaryColor}`">
                        Quick View
                    </div>
                </div>
                <div class="px-1">
                    <h3 class="text-xs font-semibold text-gray-800 line-clamp-2 mb-2 leading-snug group-hover:[color:var(--pc)] transition-colors">{{ product.name }}</h3>
                    <div class="flex items-center gap-1 mb-2">
                        <span v-for="i in 5" :key="i" class="text-yellow-400 text-xs">★</span>
                        <span class="text-[10px] text-gray-400">({{ product.reviews_count || 0 }})</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-sm font-black text-gray-900">৳{{ product.sale_price || product.base_price }}</span>
                            <span v-if="product.base_price && product.sale_price && product.base_price > product.sale_price" class="ml-1 text-[10px] text-gray-400 line-through">৳{{ product.base_price }}</span>
                        </div>
                        <button @click.stop="handleAddToCart(product)" class="cart-add-btn" :style="`background:${primaryColor}`">+</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ======================================================
     ALL PRODUCTS — Tabs + Grid
====================================================== -->
<section id="products-section" class="py-10 bg-white">
    <div class="max-w-[1320px] mx-auto px-4">
        <!-- Header + Sort -->
        <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
            <div>
                <h2 class="text-2xl font-black text-gray-900">{{ getSetting('general','products_title','Our Products') }}</h2>
                <p class="text-sm text-gray-400 mt-1">{{ getSetting('general','products_sub','Up to 69% discount for limited time 🔥') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <select v-model="sortOption" @change="fetchData" class="sort-select">
                    <option value="">Sort: Default</option>
                    <option value="price_low">Price: Low → High</option>
                    <option value="price_high">Price: High → Low</option>
                    <option value="newest">Newest First</option>
                </select>
                <button v-if="selectedCategory || sortOption || minPrice || maxPrice || searchQuery" @click="resetFilters" class="text-xs font-semibold text-red-500 bg-red-50 hover:bg-red-100 px-3 py-2 rounded-lg transition-colors border border-red-100">
                    ✕ Clear
                </button>
            </div>
        </div>

        <!-- Category Tabs -->
        <div class="flex gap-2 overflow-x-auto scrollbar-hide mb-7 pb-1 border-b border-gray-100">
            <button
                v-for="tab in categoryTabs"
                :key="tab.slug"
                @click="activeProductTab = tab.slug; if(tab.slug !== 'all') filterByCategory(tab.slug); else resetFilters()"
                class="px-5 py-2 text-sm font-semibold whitespace-nowrap transition-all border-b-2 flex-shrink-0 -mb-px"
                :class="activeProductTab === tab.slug ? '' : 'border-transparent text-gray-500 hover:text-gray-800'"
                :style="activeProductTab === tab.slug ? `border-color:${primaryColor};color:${primaryColor}` : ''"
            >
                {{ tab.name }}
            </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-24">
            <div class="w-10 h-10 border-2 border-gray-100 rounded-full animate-spin"
                 :style="`border-top-color:${primaryColor}`"></div>
        </div>

        <!-- Grid -->
        <div v-else-if="filteredProducts.length" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <div
                v-for="(product, i) in filteredProducts"
                :key="product.id"
                class="product-card group animate-in"
                :style="{ '--delay': `${i * 30}ms` }"
                @click="navigateToProduct(product.id)"
            >
                <div class="relative bg-gray-50 rounded-xl overflow-hidden mb-3 aspect-square">
                    <span v-if="discountPercent(product)" class="badge-discount">{{ discountPercent(product) }}% OFF</span>
                    <button @click.stop="toggleWishlist(product.id)" class="absolute top-2 right-2 z-10 w-7 h-7 bg-white rounded-full shadow flex items-center justify-center transition-all hover:scale-110 opacity-0 group-hover:opacity-100">
                        <span class="text-xs">{{ isInWishlist(product.id) ? '❤️' : '🤍' }}</span>
                    </button>
                    <img :src="getImageUrl(product.thumbnail)" class="w-full h-full object-contain p-3 group-hover:scale-105 transition-transform duration-500" alt="" />
                    <div class="absolute inset-x-0 bottom-0 bg-white/95 py-2 text-center text-xs font-semibold translate-y-full group-hover:translate-y-0 transition-transform"
                         :style="`color:${primaryColor}`">
                        Quick View
                    </div>
                </div>
                <div class="px-1">
                    <p class="text-[10px] text-gray-400 mb-0.5 truncate">{{ product.category?.name || 'Product' }}</p>
                    <h3 class="text-xs font-semibold text-gray-800 line-clamp-2 mb-2 leading-snug group-hover:[color:var(--pc)] transition-colors">{{ product.name }}</h3>
                    <div class="flex items-center gap-1 mb-2">
                        <span v-for="i in 5" :key="i" class="text-yellow-400 text-xs">★</span>
                        <span class="text-[10px] text-gray-400">({{ product.reviews_count || 0 }})</span>
                    </div>
                    <div class="flex items-center justify-between gap-1">
                        <div>
                            <span class="text-sm font-black text-gray-900">৳{{ product.sale_price || product.base_price }}</span>
                            <span v-if="product.base_price && product.sale_price && product.base_price > product.sale_price" class="ml-1 text-[10px] text-gray-400 line-through">৳{{ product.base_price }}</span>
                        </div>
                        <button @click.stop="handleAddToCart(product)" class="cart-add-btn" :style="`background:${primaryColor}`">
                            Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="flex flex-col items-center justify-center py-28 text-center">
            <div class="w-20 h-20 bg-gray-100 rounded-2xl flex items-center justify-center text-4xl mb-5">🔍</div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">No Products Found</h3>
            <p class="text-gray-400 text-sm mb-6 max-w-xs">Try adjusting your filters or search to find what you're looking for.</p>
            <button @click="resetFilters" class="px-6 py-2.5 rounded-full text-sm font-bold text-white hover:opacity-90 transition-opacity"
                    :style="`background:${primaryColor}`">Clear All Filters</button>
        </div>
    </div>
</section>

<!-- ======================================================
     NEWSLETTER
====================================================== -->
<section class="bg-[#1a1a2e] py-14">
    <div class="max-w-[1320px] mx-auto px-4 text-center">
        <h2 class="text-2xl md:text-3xl font-black text-white mb-2">{{ getSetting('general','newsletter_title','Subscribe to our newsletter') }}</h2>
        <p class="text-gray-400 text-sm mb-8 max-w-md mx-auto">{{ getSetting('general','newsletter_sub','Stay updated! Subscribe to our mailing list for news, updates, and exclusive offers.') }}</p>
        <form @submit.prevent class="flex max-w-lg mx-auto gap-0 shadow-xl rounded-full overflow-hidden border border-white/10">
            <input
                type="email"
                :placeholder="getSetting('general','newsletter_placeholder','Enter your email')"
                class="flex-1 bg-white/10 text-white px-6 py-3.5 text-sm focus:outline-none placeholder-gray-400 backdrop-blur-sm"
            />
            <button class="px-7 py-3.5 text-sm font-bold text-white flex-shrink-0 hover:opacity-90 transition-opacity"
                    :style="`background:${primaryColor}`">
                {{ getSetting('general','newsletter_btn','Subscribe') }}
            </button>
        </form>
    </div>
</section>

<!-- ======================================================
     FOOTER
====================================================== -->
<footer class="bg-[#111827] text-gray-400">
    <div class="max-w-[1320px] mx-auto px-4 pt-14 pb-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-10 mb-12">
            <!-- Brand -->
            <div class="lg:col-span-2">
                <div class="mb-5">
                    <img v-if="getSetting('general','site_logo')" :src="getSetting('general','site_logo')" class="h-9 w-auto filter brightness-0 invert opacity-80 mb-1" alt="Logo" />
                    <span v-else class="font-black text-2xl text-white">{{ getSetting('general','site_name','Sellzy') }}</span>
                </div>
                <p class="text-sm leading-relaxed mb-6 text-gray-500 max-w-xs">
                    {{ getSetting('general','site_description','Quality products at unbeatable prices. Shop with confidence.') }}
                </p>
                <!-- Social -->
                <div class="flex gap-2.5 mb-6">
                    <a v-if="getSetting('social','facebook')"  :href="getSetting('social','facebook')"  target="_blank" class="footer-social-btn">f</a>
                    <a v-if="getSetting('social','instagram')" :href="getSetting('social','instagram')" target="_blank" class="footer-social-btn">in</a>
                    <a v-if="getSetting('social','youtube')"   :href="getSetting('social','youtube')"   target="_blank" class="footer-social-btn">yt</a>
                    <a v-if="getSetting('social','twitter')"   :href="getSetting('social','twitter')"   target="_blank" class="footer-social-btn">𝕏</a>
                    <a v-if="getSetting('social','tiktok')"    :href="getSetting('social','tiktok')"    target="_blank" class="footer-social-btn">tk</a>
                </div>
            </div>

            <!-- About Links -->
            <div>
                <h4 class="footer-heading">About</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="/about"   class="footer-link">About Us</a></li>
                    <li><a href="/terms"   class="footer-link">Terms & Conditions</a></li>
                    <li><a href="/privacy" class="footer-link">Privacy Policy</a></li>
                    <li><a href="/contact" class="footer-link">Contact Us</a></li>
                    <li><a href="/faq"     class="footer-link">FAQs</a></li>
                </ul>
            </div>

            <!-- My Account -->
            <div>
                <h4 class="footer-heading">My Account</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="/my-account" class="footer-link">Your Account</a></li>
                    <li><a href="/wishlist"   class="footer-link">Wishlist</a></li>
                    <li><a href="/orders"     class="footer-link">Track Order</a></li>
                    <li><a href="/refund"     class="footer-link">Return Policies</a></li>
                    <li><a href="/affiliate"  class="footer-link">Affiliate Program</a></li>
                </ul>
            </div>

            <!-- Contact Info — reads from 'contact' group ✅ -->
            <div>
                <h4 class="footer-heading">Contact Information</h4>
                <ul class="space-y-3 text-sm">
                    <li v-if="getContact('address')" class="flex gap-2.5 items-start">
                        <span class="mt-0.5 flex-shrink-0" :style="`color:${primaryColor}`">📍</span>
                        <span class="text-gray-500">{{ getContact('address') }}</span>
                    </li>
                    <li v-if="getContact('phone')" class="flex gap-2.5 items-center">
                        <span class="flex-shrink-0" :style="`color:${primaryColor}`">📞</span>
                        <a :href="`tel:${getContact('phone')}`" class="footer-link">{{ getContact('phone') }}</a>
                    </li>
                    <li v-if="getContact('email')" class="flex gap-2.5 items-center">
                        <span class="flex-shrink-0" :style="`color:${primaryColor}`">✉</span>
                        <a :href="`mailto:${getContact('email')}`" class="footer-link">{{ getContact('email') }}</a>
                    </li>
                    <li v-if="getContact('whatsapp')" class="flex gap-2.5 items-center">
                        <span class="flex-shrink-0 text-green-400">💬</span>
                        <a :href="`https://wa.me/${getContact('whatsapp')}`" target="_blank" class="footer-link">
                            {{ getContact('whatsapp') }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="border-t border-white/5 pt-7 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-xs text-gray-600">{{ getSetting('general','copyright','© 2026 Sajib. All Rights Reserved.') }}</p>
            <!-- Payment Methods -->
            <div class="flex items-center gap-2 flex-wrap">
                <span v-if="getSetting('payment','bkash_enabled')"      class="pay-badge text-pink-400   border-pink-900/30">bKash</span>
                <span v-if="getSetting('payment','nagad_enabled')"      class="pay-badge text-orange-400 border-orange-900/30">Nagad</span>
                <span v-if="getSetting('payment','rocket_enabled')"     class="pay-badge text-purple-400 border-purple-900/30">Rocket</span>
                <span v-if="getSetting('payment','sslcommerz_enabled')" class="pay-badge text-green-400  border-green-900/30">SSLCommerz</span>
                <span v-if="getSetting('payment','cod_enabled')"        class="pay-badge text-blue-400   border-blue-900/30">Cash on Delivery</span>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top -->
<transition name="fade">
    <button v-if="showBackToTop" @click="scrollToTop"
        class="fixed bottom-6 right-6 w-11 h-11 text-white rounded-xl shadow-lg flex items-center justify-center hover:scale-110 active:scale-95 z-50 transition-all font-bold text-lg"
        :style="`background:${primaryColor};box-shadow:0 8px 24px ${primaryColor}50`">
        ↑
    </button>
</transition>

</div>
</template>

<style scoped>
/* ---- Hero Slider Transition ---- */
.hero-slide-enter-active { transition: opacity 0.6s ease, transform 0.6s ease; }
.hero-slide-leave-active { transition: opacity 0.4s ease, transform 0.4s ease; }
.hero-slide-enter-from { opacity: 0; transform: translateX(30px); }
.hero-slide-leave-to { opacity: 0; transform: translateX(-30px); }

/* ---- Fade ---- */
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* ---- Navbar ---- */
.nav-item {
    display: inline-flex;
    align-items: center;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #4b5563;
    text-decoration: none;
    transition: all 0.2s;
    white-space: nowrap;
}
.nav-item:hover, .nav-item-active { color: var(--pc); background: color-mix(in srgb, var(--pc) 8%, white); }
.router-link-active.nav-item { color: var(--pc); }

/* ---- Product Card ---- */
.product-card {
    background: #fff;
    border: 1.5px solid #f3f4f6;
    border-radius: 16px;
    padding: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
}
.product-card:hover {
    border-color: color-mix(in srgb, var(--pc) 40%, white);
    box-shadow: 0 8px 24px color-mix(in srgb, var(--pc) 12%, transparent);
    transform: translateY(-3px);
}

/* ---- Badges ---- */
.badge-sales {
    position: absolute;
    top: 8px; left: 8px; z-index: 10;
    font-size: 9px; font-weight: 800; letter-spacing: 0.08em;
    background: #e74c3c; color: white;
    padding: 3px 8px; border-radius: 99px;
}
.badge-discount {
    position: absolute;
    top: 8px; left: 8px; z-index: 10;
    font-size: 9px; font-weight: 800;
    background: #e74c3c; color: white;
    padding: 3px 8px; border-radius: 99px;
}

/* ---- Cart Button ---- */
.cart-add-btn {
    display: inline-flex;
    align-items: center; justify-content: center;
    color: white;
    font-weight: 700; font-size: 11px;
    padding: 5px 10px; border-radius: 8px;
    transition: all 0.2s; border: none; cursor: pointer;
    white-space: nowrap; flex-shrink: 0;
}
.cart-add-btn:hover { filter: brightness(0.88); transform: scale(1.05); }
.cart-add-btn:active { transform: scale(0.95); }

/* ---- Countdown ---- */
.countdown-box {
    width: 46px; height: 46px;
    background: #1a1a2e; color: white;
    font-size: 1.1rem; font-weight: 900; border-radius: 10px;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    line-height: 1;
}
.countdown-label { font-size: 8px; font-weight: 600; color: #9ca3af; letter-spacing: 0.05em; margin-top: 1px; }

/* ---- Sort Select ---- */
.sort-select {
    font-size: 0.8rem; border: 1.5px solid #e5e7eb; border-radius: 10px;
    padding: 7px 12px; color: #374151; background: white;
    outline: none; cursor: pointer; transition: border-color 0.2s;
}
.sort-select:focus { border-color: var(--pc); }

/* ---- Footer ---- */
.footer-heading { color: white; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; margin-bottom: 18px; }
.footer-link { color: #6b7280; text-decoration: none; transition: color 0.2s; }
.footer-link:hover { color: var(--pc); }
.footer-social-btn {
    width: 32px; height: 32px;
    display: flex; align-items: center; justify-content: center;
    background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);
    border-radius: 9px; font-size: 0.7rem; font-weight: 700; color: #6b7280;
    text-decoration: none; transition: all 0.2s;
}
.footer-social-btn:hover { background: color-mix(in srgb, var(--pc) 15%, transparent); border-color: color-mix(in srgb, var(--pc) 30%, transparent); color: var(--pc); }
.pay-badge { font-size: 0.65rem; font-weight: 700; padding: 3px 10px; background: rgba(255,255,255,0.04); border: 1px solid; border-radius: 6px; }

/* ---- Animate In ---- */
@keyframes animIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
.animate-in { animation: animIn 0.4s ease-out both; animation-delay: var(--delay, 0ms); }

/* ---- Scrollbar Hide ---- */
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
.scrollbar-hide::-webkit-scrollbar { display: none; }

/* ---- Custom Scrollbar ---- */
::-webkit-scrollbar { width: 5px; }
::-webkit-scrollbar-track { background: #f9fafb; }
::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: var(--pc); }
</style>
