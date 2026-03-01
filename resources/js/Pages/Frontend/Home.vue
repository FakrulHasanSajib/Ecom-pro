<script setup>
import { ref, onMounted, watch, computed, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useCartStore } from '../../stores/cart';
import { useAuthStore } from '../../stores/auth';
import Swal from 'sweetalert2';

// শুধু মাত্র যে আইকনগুলো নিশ্চিতভাবে আছে সেগুলো ইম্পোর্ট করুন
import {
    SfButton,
    SfIconShoppingCart,
    SfIconFavorite,
    SfIconFavoriteFilled,
    SfLoaderCircular,
    SfIconPerson,
    SfIconSearch,
    SfIconClose,
    SfIconStar,
    SfIconPackage,
    SfIconLocalShipping
} from '@storefront-ui/vue';

const router = useRouter();
const cartStore = useCartStore();
const authStore = useAuthStore();

// State
const products = ref([]);
const categories = ref([]);
const featuredProducts = ref([]);
const loading = ref(true);
const initialLoading = ref(true);
const wishlist = ref([]);
const showBackToTop = ref(false);

// 🔥 Dynamic Sliders & Settings State
const sliders = ref([]);
const currentSlide = ref(0);
const settings = ref({});

// Filters
const searchQuery = ref('');
const selectedCategory = ref(null);
const sortOption = ref('');
const minPrice = ref('');
const maxPrice = ref('');
const priceRange = ref([0, 100000]);

// Backend URL
const backendUrl = 'http://127.0.0.1:73';

// Computed
const activeFilterCount = computed(() => {
    let count = 0;
    if (selectedCategory.value) count++;
    if (sortOption.value) count++;
    if (minPrice.value || maxPrice.value) count++;
    if (searchQuery.value) count++;
    return count;
});

const isInWishlist = (productId) => {
    return wishlist.value.includes(productId);
};

// 🔥 Safe Computed Property for Sliders (fixes the .filter error)
const heroBanners = computed(() => {
    if (!Array.isArray(sliders.value)) return [];

    const mainBanners = sliders.value.filter(s => s.category_name === 'Slider' || s.category_name === 'Home Banner');
    return mainBanners.length > 0 ? mainBanners : sliders.value;
});

// 🔥 Settings Helper Function
// 🔥 Settings Helper Function (Super Robust & Case-Insensitive)
// 🔥 Settings Helper Function (Super Robust for Database Matches)
const normalize = (str) => {
    // এটি স্পেস, আন্ডারস্কোর, হাইফেন সব মুছে ছোট হাতের অক্ষরে কনভার্ট করবে
    return str ? String(str).replace(/[_-\s]+/g, '').toLowerCase() : '';
};

const getSetting = (group, key, defaultValue = '') => {
    if (!settings.value) return defaultValue;

    // ১. গ্রুপের নাম মেলানো (general == General)
    const targetGroup = Object.keys(settings.value).find(k => normalize(k) === normalize(group));
    
    if (targetGroup && Array.isArray(settings.value[targetGroup])) {
        // ২. key বা name কলাম মেলানো (site_name == Site Name)
        const item = settings.value[targetGroup].find(s => 
            normalize(s.key) === normalize(key) || 
            normalize(s.name) === normalize(key)
        );
        
        // ৩. ভ্যালু থাকলে রিটার্ন করা
        if (item && item.value !== null && item.value !== '') {
            return item.type === 'image' ? item.value_url : item.value;
        }
    }
    return defaultValue; 
};

// Methods
const getImageUrl = (path) => {
    if (!path) return 'https://placehold.co/600x400?text=No+Image';
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

        // API Calls with public routes
        const [prodRes, catRes, featuredRes, sliderRes, settingRes] = await Promise.all([
            axios.get(`${backendUrl}/api/public/products`, { params }).catch(() => ({ data: { data: [] } })),
            axios.get(`${backendUrl}/api/public/categories`).catch(() => ({ data: [] })),
            axios.get(`${backendUrl}/api/public/products/featured`).catch(() => ({ data: [] })),
            axios.get(`${backendUrl}/api/public/sliders`).catch(() => ({ data: [] })),
            
            // 🔥 এখানে /admin/settings এর জায়গায় /public/settings দেওয়া হয়েছে
            axios.get(`${backendUrl}/api/public/settings`).catch(() => ({ data: { data: {} } }))
        ]);

        products.value = prodRes.data?.data || [];
        categories.value = catRes.data?.data || catRes.data || [];
        featuredProducts.value = featuredRes.data?.data || featuredRes.data || [];
        
        // Extract sliders safely
        const rawSliders = sliderRes.data?.data || sliderRes.data || [];
        sliders.value = Array.isArray(rawSliders) ? rawSliders : [];

        // Extract settings safely
        settings.value = settingRes.data?.data || {};

    } catch (error) {
        console.error("Data fetch error:", error);
    } finally {
        loading.value = false;
        initialLoading.value = false;
    }
};

const filterByCategory = (slug) => {
    selectedCategory.value = selectedCategory.value === slug ? null : slug;
    fetchData();
    document.getElementById('products-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const resetFilters = () => {
    selectedCategory.value = null;
    searchQuery.value = '';
    sortOption.value = '';
    minPrice.value = '';
    maxPrice.value = '';
    priceRange.value = [0, 100000];
    fetchData();
};

const handleAddToCart = async (product) => {
    try {
        await cartStore.addToCart(product);

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });

        Toast.fire({
            icon: 'success',
            title: `<span class="text-sm font-medium">${product.name} added to cart</span>`,
            background: '#1e1e2f',
            color: '#fff'
        });

    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Failed to add',
            text: 'Please try again',
            background: '#1e1e2f',
            color: '#fff'
        });
    }
};

const toggleWishlist = (productId) => {
    if (wishlist.value.includes(productId)) {
        wishlist.value = wishlist.value.filter(id => id !== productId);
        Swal.fire({
            icon: 'info',
            title: 'Removed from wishlist',
            showConfirmButton: false,
            timer: 1500,
            background: '#1e1e2f',
            color: '#fff'
        });
    } else {
        wishlist.value.push(productId);
        Swal.fire({
            icon: 'success',
            title: 'Added to wishlist',
            showConfirmButton: false,
            timer: 1500,
            background: '#1e1e2f',
            color: '#fff'
        });
    }
};

const navigateToProduct = (id) => {
    router.push(`/product/${id}`);
};

const handleScroll = () => {
    showBackToTop.value = window.scrollY > 500;
};

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

// 🔥 Auto Slider Timer Logic
let slideInterval;
const startSlider = () => {
    slideInterval = setInterval(() => {
        if (heroBanners.value.length > 1) {
            currentSlide.value = (currentSlide.value + 1) % heroBanners.value.length;
        }
    }, 5000);
};

// Debounced search
let debounceTimer = null;
watch(searchQuery, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchData, 500);
});

watch([minPrice, maxPrice], () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchData, 800);
});

onMounted(() => {
    fetchData();
    const savedWishlist = localStorage.getItem('wishlist');
    if (savedWishlist) {
        wishlist.value = JSON.parse(savedWishlist);
    }
    window.addEventListener('scroll', handleScroll);
    startSlider();
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    clearInterval(slideInterval);
});

watch(wishlist, (newVal) => {
    localStorage.setItem('wishlist', JSON.stringify(newVal));
}, { deep: true });
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50/30 font-sans antialiased">

        <div v-if="initialLoading" class="fixed inset-0 bg-gradient-to-br from-indigo-950 via-purple-950 to-slate-950 z-[100] flex items-center justify-center">
            <div class="text-center">
                <div class="relative mb-8">
                    <div class="w-32 h-32 border-4 border-indigo-500/30 border-t-indigo-500 rounded-full animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-4xl animate-bounce">🛍️</span>
                    </div>
                </div>
                <h2 class="text-3xl font-black text-white mb-3">E-Shop</h2>
                <p class="text-indigo-300/80">Loading amazing products for you...</p>
            </div>
        </div>

        <header class="bg-white/80 backdrop-blur-xl border-b border-indigo-100/50 sticky top-0 z-50 transition-all duration-300">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">

                <router-link to="/" class="flex items-center gap-3 group">
                    <img v-if="getSetting('general', 'site_logo')" :src="getSetting('general', 'site_logo')" class="h-10 w-auto object-contain transition-transform group-hover:scale-105" alt="Logo">
                    <div v-else class="relative">
                        <span class="text-4xl group-hover:scale-110 group-hover:rotate-12 transition-all duration-300 inline-block">🛍️</span>
                        <div class="absolute -inset-2 bg-indigo-500/20 rounded-full blur-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    </div>

                    <span v-if="!getSetting('general', 'site_logo')" class="font-black text-3xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent tracking-tight">
                        {{ getSetting('general', 'site_name', 'E-Shop') }}
                    </span>
                </router-link>

                <div class="hidden md:flex flex-1 max-w-xl mx-10">
                    <div class="relative w-full">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search products..."
                            class="w-full bg-white border-2 border-indigo-100 rounded-full py-4 pl-14 pr-14 text-sm focus:outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 transition-all shadow-sm"
                        />
                        <span class="absolute left-5 top-1/2 -translate-y-1/2 text-indigo-400 text-xl">🔍</span>
                        <button
                            v-if="searchQuery"
                            @click="searchQuery = ''"
                            class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-indigo-600 transition-colors"
                        >
                            ✕
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button class="md:hidden p-3 text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all">
                        <span class="text-xl">🔍</span>
                    </button>

                    <template v-if="authStore.isAuthenticated">
                        <router-link
                            to="/admin/dashboard"
                            class="flex items-center gap-2 bg-gradient-to-r from-indigo-50 to-purple-50 text-indigo-700 font-semibold px-5 py-2.5 rounded-xl hover:from-indigo-100 hover:to-purple-100 transition-all border border-indigo-200/50 shadow-sm"
                        >
                            <span class="text-lg">👤</span>
                            <span class="hidden sm:inline">Dashboard</span>
                        </router-link>
                    </template>
                    <template v-else>
                        <router-link to="/login">
                            <button class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2.5 rounded-xl shadow-lg shadow-indigo-500/30 hover:shadow-xl transition-all">
                                Sign In
                            </button>
                        </router-link>
                    </template>

                    <router-link to="/checkout" class="relative">
                        <button class="p-3 text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all relative">
                            <span class="text-xl">🛒</span>
                            <span v-if="cartStore.items.length" class="absolute -top-1 -right-1 bg-gradient-to-br from-red-500 to-rose-500 text-white text-xs font-bold rounded-full min-w-[22px] h-[22px] flex items-center justify-center border-2 border-white shadow-lg animate-pulse">
                                {{ cartStore.items.length }}
                            </span>
                        </button>
                    </router-link>
                </div>
            </div>
        </header>

        <section class="relative bg-gradient-to-br from-slate-900 via-indigo-900 to-purple-900 text-white overflow-hidden min-h-[500px]">
            <div class="absolute inset-0">
                <div class="absolute inset-0 opacity-20"
                     style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%239C92AC\' fill-opacity=\'0.05\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');">
                </div>
                <div class="absolute top-0 -left-40 w-80 h-80 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
                <div class="absolute top-0 -right-40 w-80 h-80 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
                <div class="absolute bottom-0 left-20 w-80 h-80 bg-pink-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"></div>
            </div>

            <div class="max-w-7xl mx-auto px-6 py-24 md:py-32 relative z-10">
                <transition name="slide-fade" mode="out-in">

                    <div v-if="heroBanners.length > 0" :key="currentSlide" class="grid lg:grid-cols-2 gap-16 items-center">
                        <div class="text-center lg:text-left">
                            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 backdrop-blur-sm border border-white/20 text-indigo-300 text-sm font-medium mb-8 animate-fade-in-up">
                                <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                                {{ heroBanners[currentSlide].category_name || 'New Collection' }}
                            </div>

                            <h1 class="text-5xl md:text-7xl font-black leading-tight mb-6 animate-fade-in-up animation-delay-200 drop-shadow-lg">
                                {{ heroBanners[currentSlide].title || 'Discover Tomorrow\'s Style Today' }}
                            </h1>

                            <p class="text-xl md:text-2xl text-indigo-200/90 max-w-2xl mx-auto lg:mx-0 mb-10 animate-fade-in-up animation-delay-400">
                                {{ heroBanners[currentSlide].description || 'Premium curated products. Exceptional quality. Prices you\'ll love.' }}
                            </p>

                            <div class="flex flex-wrap gap-4 justify-center lg:justify-start animate-fade-in-up animation-delay-600">
                                <a
                                    :href="heroBanners[currentSlide].link || '/shop'"
                                    class="bg-white text-indigo-950 hover:bg-gray-100 shadow-2xl shadow-indigo-500/30 rounded-full px-10 py-5 text-lg font-bold transition-all hover:scale-105 active:scale-95 group"
                                >
                                    Shop Now
                                    <span class="ml-2 group-hover:translate-x-1 transition-transform inline-block">→</span>
                                </a>

                                <button
                                    class="bg-white/10 backdrop-blur-sm border border-white/20 text-white px-10 py-5 rounded-full text-lg font-semibold hover:bg-white/20 transition-all hover:scale-105 active:scale-95"
                                    @click="document.getElementById('categories-section').scrollIntoView({ behavior: 'smooth' })"
                                >
                                    Explore Categories
                                </button>
                            </div>
                        </div>

                        <div class="relative hidden lg:block animate-fade-in">
                            <div class="absolute inset-0 bg-gradient-to-t from-indigo-900/50 via-transparent to-transparent rounded-3xl"></div>
                            <img
                                :src="heroBanners[currentSlide].image_url"
                                class="rounded-3xl shadow-2xl object-cover w-full h-[450px] transform hover:scale-105 transition-transform duration-1000"
                                alt="Hero Product"
                            />
                        </div>
                    </div>

                    <div v-else class="grid lg:grid-cols-2 gap-16 items-center">
                        <div class="text-center lg:text-left">
                            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 backdrop-blur-sm border border-white/20 text-indigo-300 text-sm font-medium mb-8 animate-fade-in-up">
                                <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                                New Collection 2026
                            </div>

                            <h1 class="text-5xl md:text-7xl font-black leading-tight mb-6 animate-fade-in-up animation-delay-200">
                                Discover
                                <span class="bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">Tomorrow's</span>
                                <br>Style Today
                            </h1>

                            <p class="text-xl md:text-2xl text-indigo-200/90 max-w-2xl mx-auto lg:mx-0 mb-10 animate-fade-in-up animation-delay-400">
                                Premium curated products. Exceptional quality.
                                <span class="font-semibold text-white">Prices you'll love.</span>
                            </p>

                            <div class="flex flex-wrap gap-4 justify-center lg:justify-start animate-fade-in-up animation-delay-600">
                                <button
                                    class="bg-white text-indigo-950 hover:bg-gray-100 shadow-2xl shadow-indigo-500/30 rounded-full px-10 py-5 text-lg font-bold transition-all hover:scale-105 active:scale-95 group"
                                    @click="$router.push('/shop')"
                                >
                                    Shop Now
                                    <span class="ml-2 group-hover:translate-x-1 transition-transform inline-block">→</span>
                                </button>
                            </div>
                        </div>

                        <div class="relative hidden lg:block animate-fade-in">
                            <div class="absolute inset-0 bg-gradient-to-t from-indigo-900/50 via-transparent to-transparent rounded-3xl"></div>
                            <img
                                src="https://images.unsplash.com/photo-1523275335684-04c0c856c0e0?q=80&w=1200&auto=format&fit=crop"
                                class="rounded-3xl shadow-2xl transform hover:scale-105 transition-transform duration-1000"
                                alt="Hero Product"
                            />
                        </div>
                    </div>
                </transition>
            </div>

            <div v-if="heroBanners.length > 1" class="absolute bottom-8 left-1/2 -translate-x-1/2 flex gap-3 z-20">
                <button
                    v-for="(_, index) in heroBanners"
                    :key="index"
                    @click="currentSlide = index"
                    :class="currentSlide === index ? 'w-10 bg-indigo-500' : 'w-3 bg-white/50 hover:bg-white/80'"
                    class="h-3 rounded-full transition-all duration-500 shadow-md"
                ></button>
            </div>
        </section>

        <div class="bg-white border-b border-indigo-100 py-6">
            <div class="max-w-7xl mx-auto px-6 grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center text-2xl">
                        📦
                    </div>
                    <div>
                        <div class="font-bold text-gray-900">Free Shipping</div>
                        <div class="text-sm text-gray-600">On orders $50+</div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center text-2xl">
                        🚚
                    </div>
                    <div>
                        <div class="font-bold text-gray-900">Fast Delivery</div>
                        <div class="text-sm text-gray-600">2-3 business days</div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center text-2xl">
                        🔒
                    </div>
                    <div>
                        <div class="font-bold text-gray-900">Secure Payment</div>
                        <div class="text-sm text-gray-600">100% protected</div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center text-2xl">
                        💬
                    </div>
                    <div>
                        <div class="font-bold text-gray-900">24/7 Support</div>
                        <div class="text-sm text-gray-600">Live chat</div>
                    </div>
                </div>
            </div>
        </div>

        <section v-if="featuredProducts.length" class="max-w-7xl mx-auto px-6 py-20">
            <div class="text-center mb-12">
                <h2 class="text-4xl md:text-5xl font-black text-slate-900 mb-4">
                    Featured
                    <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Products</span>
                </h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Hand-picked items just for you
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div
                    v-for="(product, index) in featuredProducts.slice(0, 3)"
                    :key="product.id"
                    class="group relative bg-white rounded-3xl overflow-hidden shadow-xl hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 cursor-pointer"
                    :style="{ animationDelay: `${index * 200}ms` }"
                    @click="navigateToProduct(product.id)"
                >
                    <div class="relative h-72 bg-gradient-to-br from-indigo-50 to-purple-50 p-8">
                        <img
                            :src="getImageUrl(product.thumbnail)"
                            class="w-full h-full object-contain group-hover:scale-110 transition-transform duration-700"
                            alt=""
                        />
                        <div class="absolute top-4 left-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-xs font-bold px-3 py-1.5 rounded-full">
                            Featured
                        </div>
                    </div>

                    <div class="p-6">
                        <h3 class="font-bold text-xl text-slate-900 mb-2 group-hover:text-indigo-600 transition-colors line-clamp-2">
                            {{ product.name }}
                        </h3>
                        <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ product.description || 'Premium quality product with exceptional features.' }}</p>

                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-2xl font-black text-slate-900">৳{{ product.sale_price || product.base_price }}</span>
                                <span v-if="product.discount_price" class="ml-2 text-sm text-gray-500 line-through">৳{{ product.base_price }}</span>
                            </div>
                            <button
                                @click.stop="handleAddToCart(product)"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white p-3 rounded-xl transition-all hover:scale-110 active:scale-95 shadow-lg shadow-indigo-500/30"
                            >
                                <span class="text-xl">🛒</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="categories.length" id="categories-section" class="max-w-7xl mx-auto px-6 py-20">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6 mb-12">
                <div>
                    <h2 class="text-4xl md:text-5xl font-black text-slate-900 mb-4">
                        Shop by
                        <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Category</span>
                    </h2>
                    <p class="text-xl text-gray-600">
                        Explore our curated collections
                    </p>
                </div>
                <router-link
                    to="/categories"
                    class="flex items-center gap-2 text-indigo-600 font-semibold hover:gap-3 transition-all group"
                >
                    View All Categories
                    <span class="group-hover:translate-x-1 transition-transform">→</span>
                </router-link>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                <div
                    v-for="(cat, index) in categories"
                    :key="cat.id"
                    @click="filterByCategory(cat.slug)"
                    class="group relative overflow-hidden rounded-3xl bg-white border-2 border-indigo-100/50 hover:border-indigo-300 shadow-sm hover:shadow-xl transition-all duration-500 cursor-pointer animate-fade-in-up"
                    :style="{ animationDelay: `${index * 100}ms` }"
                >
                    <div class="aspect-square overflow-hidden">
                        <img
                            v-if="cat.image"
                            :src="getImageUrl(cat.image)"
                            class="w-full h-full object-cover group-hover:scale-125 transition-transform duration-700"
                            alt=""
                        />
                        <div v-else class="w-full h-full bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center text-5xl">
                            📦
                        </div>
                    </div>

                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>

                    <div class="absolute bottom-0 left-0 right-0 p-4 text-white transform translate-y-2 group-hover:translate-y-0 transition-transform">
                        <p class="font-bold text-lg drop-shadow-md">{{ cat.name }}</p>
                        <p class="text-sm text-white/80 mt-1 opacity-0 group-hover:opacity-100 transition-opacity">Shop now →</p>
                    </div>

                    <div v-if="selectedCategory === cat.slug" class="absolute top-3 right-3 w-3 h-3 bg-indigo-500 rounded-full animate-pulse"></div>
                </div>
            </div>
        </section>

        <section id="products-section" class="max-w-7xl mx-auto px-6 pb-24">
            <div class="bg-white/70 backdrop-blur-xl rounded-3xl border-2 border-indigo-100/50 shadow-lg p-6 mb-12 sticky top-24 z-40">
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">

                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl flex items-center justify-center text-white shadow-lg text-2xl">
                            ⚙️
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-slate-900">
                                {{ selectedCategory ? 'Curated Selection' : 'All Products' }}
                            </h3>
                            <p class="text-gray-600 text-sm">
                                Showing
                                <span class="font-semibold text-indigo-600">{{ products.length }}</span>
                                premium items
                                <span v-if="activeFilterCount" class="ml-2 px-2 py-0.5 bg-indigo-100 text-indigo-600 rounded-full text-xs">
                                    {{ activeFilterCount }} filters active
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <select
                            v-model="sortOption"
                            @change="fetchData"
                            class="bg-white border-2 border-indigo-100 hover:border-indigo-300 rounded-xl px-4 py-2.5 text-gray-700 focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all cursor-pointer"
                        >
                            <option value="">Sort: Featured</option>
                            <option value="price_low">Price: Low to High</option>
                            <option value="price_high">Price: High to Low</option>
                            <option value="newest">Newest First</option>
                        </select>

                        <button
                            v-if="activeFilterCount"
                            @click="resetFilters"
                            class="flex items-center gap-2 bg-red-50 hover:bg-red-100 text-red-600 px-5 py-2.5 rounded-xl font-medium transition-all border-2 border-red-100 hover:border-red-300"
                        >
                            <span>✕</span>
                            Clear ({{ activeFilterCount }})
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="loading" class="flex flex-col items-center justify-center py-40">
                <div class="relative mb-8">
                    <div class="w-16 h-16 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
                </div>
                <p class="text-gray-600 font-medium">Loading exclusive collection...</p>
            </div>

            <div v-else-if="products.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                <div
                    v-for="(product, index) in products"
                    :key="product.id"
                    class="group bg-white rounded-3xl overflow-hidden hover:shadow-2xl hover:shadow-indigo-500/20 transition-all duration-500 hover:-translate-y-2 animate-fade-in-up cursor-pointer"
                    :style="{ animationDelay: `${index * 100}ms` }"
                    @click="navigateToProduct(product.id)"
                >
                    <div class="relative h-64 bg-gradient-to-br from-indigo-50 to-purple-50 p-6 overflow-hidden">
                        <div v-if="product.discount_price" class="absolute top-4 left-4 z-10 bg-gradient-to-r from-red-500 to-rose-500 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-lg">
                            -{{ Math.round((product.base_price - product.sale_price) / product.base_price * 100) }}%
                        </div>

                        <button
                            @click.stop="toggleWishlist(product.id)"
                            class="absolute top-4 right-4 z-10 w-10 h-10 bg-white/90 backdrop-blur-sm rounded-full shadow-lg flex items-center justify-center hover:bg-red-50 transition-all"
                        >
                            <span :class="isInWishlist(product.id) ? 'text-red-500' : 'text-gray-400'" class="text-xl">
                                {{ isInWishlist(product.id) ? '❤️' : '🤍' }}
                            </span>
                        </button>

                        <img
                            :src="getImageUrl(product.thumbnail)"
                            class="w-full h-full object-contain group-hover:scale-110 transition-transform duration-700"
                            alt=""
                        />

                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <button
                                @click.stop="navigateToProduct(product.id)"
                                class="bg-white text-indigo-600 px-6 py-3 rounded-full font-semibold transform translate-y-4 group-hover:translate-y-0 transition-transform shadow-xl"
                            >
                                Quick View
                            </button>
                        </div>
                    </div>

                    <div class="p-6">
                        <p class="text-xs text-indigo-600 font-semibold uppercase tracking-wide mb-2">
                            {{ product.category?.name || 'Collection' }}
                        </p>

                        <h3 class="font-bold text-lg text-slate-900 mb-2 line-clamp-2 group-hover:text-indigo-600 transition-colors">
                            {{ product.name }}
                        </h3>

                        <div class="flex items-end justify-between mt-4 pt-4 border-t border-indigo-100">
                            <div>
                                <span class="text-2xl font-black text-slate-900">৳{{ product.sale_price || product.base_price }}</span>
                                <span v-if="product.discount_price" class="block text-sm text-gray-500 line-through">৳{{ product.base_price }}</span>
                            </div>

                            <button
                                @click.stop="handleAddToCart(product)"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white p-3 rounded-xl transition-all hover:scale-110 active:scale-95 shadow-lg shadow-indigo-500/30"
                            >
                                <span class="text-xl">🛒</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-40 bg-white/70 backdrop-blur-xl rounded-3xl border-2 border-indigo-100/50 shadow-lg">
                <div class="w-32 h-32 mx-auto mb-8 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-full flex items-center justify-center text-6xl">
                    🔍
                </div>
                <h3 class="text-3xl font-black text-slate-800 mb-4">No Products Found</h3>
                <p class="text-gray-600 text-lg max-w-md mx-auto mb-8">
                    We couldn't find any products matching your criteria.
                    <br>Try adjusting your filters.
                </p>
                <button
                    @click="resetFilters"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-10 py-4 rounded-2xl font-bold text-lg shadow-xl shadow-indigo-500/30"
                >
                    Clear All Filters
                </button>
            </div>
        </section>

        <section class="bg-gradient-to-r from-indigo-600 to-purple-600 py-20">
            <div class="max-w-7xl mx-auto px-6 text-center">
                <h2 class="text-4xl md:text-5xl font-black text-white mb-4">Stay in the Loop</h2>
                <p class="text-xl text-indigo-100 mb-10 max-w-2xl mx-auto">
                    Subscribe to get exclusive offers, early access to sales, and new arrivals.
                </p>

                <form @submit.prevent class="flex flex-col sm:flex-row gap-4 max-w-lg mx-auto">
                    <input
                        type="email"
                        placeholder="Enter your email"
                        class="flex-1 bg-white/10 backdrop-blur-sm border-2 border-white/20 rounded-xl px-6 py-4 text-white placeholder-indigo-200 focus:outline-none focus:border-white/50 transition-all"
                        required
                    />
                    <button class="bg-white text-indigo-600 hover:bg-indigo-50 px-8 py-4 rounded-xl font-bold text-lg shadow-xl transition-all hover:scale-105 active:scale-95">
                        Subscribe
                    </button>
                </form>

                <p class="text-indigo-200 text-sm mt-4">
                    No spam. Unsubscribe anytime.
                </p>
            </div>
        </section>

        <footer class="bg-gradient-to-b from-slate-950 to-black text-gray-300 pt-20 pb-12">
            <div class="max-w-7xl mx-auto px-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-12 mb-16">
                    <div class="lg:col-span-2">
                        <div class="flex items-center gap-3 mb-6">
                            <img v-if="getSetting('general', 'site_logo')" :src="getSetting('general', 'site_logo')" class="h-10 w-auto filter brightness-0 invert" alt="Logo">
                            <span v-else class="font-black text-3xl text-white tracking-tight">{{ getSetting('general', 'site_name', 'E-Shop') }}</span>
                        </div>
                        <p class="text-gray-400 text-sm leading-relaxed mb-6 max-w-md">
                            {{ getSetting('general', 'site_description', 'Premium shopping experience — curated, secure, delivered fast. We bring you the best products from around the world at prices you\'ll love.') }}
                        </p>

                        <div class="flex gap-4">
                            <a v-if="getSetting('social', 'facebook')" :href="getSetting('social', 'facebook')" target="_blank" class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center hover:bg-blue-600 transition-colors text-xl">
                                📘
                            </a>
                            <a v-if="getSetting('social', 'instagram')" :href="getSetting('social', 'instagram')" target="_blank" class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center hover:bg-pink-600 transition-colors text-xl">
                                📷
                            </a>
                            <a v-if="getSetting('social', 'youtube')" :href="getSetting('social', 'youtube')" target="_blank" class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center hover:bg-red-600 transition-colors text-xl">
                                ▶️
                            </a>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-white font-bold text-lg mb-6">Contact Info</h3>
                        <ul class="space-y-3 text-sm">
                            <li class="flex gap-2"><span>📞</span> {{ getSetting('general', 'phone', '+880123456789') }}</li>
                            <li class="flex gap-2"><span>✉️</span> {{ getSetting('general', 'email', 'support@eshop.com') }}</li>
                            <li class="flex gap-2"><span>📍</span> {{ getSetting('general', 'address', 'Dhaka, Bangladesh') }}</li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-white font-bold text-lg mb-6">Quick Links</h3>
                        <ul class="space-y-3 text-sm">
                            <li><a href="#" class="hover:text-indigo-400 transition-colors">Home</a></li>
                            <li><a href="#" class="hover:text-indigo-400 transition-colors">Shop</a></li>
                            <li><a href="#" class="hover:text-indigo-400 transition-colors">About Us</a></li>
                            <li><a href="#" class="hover:text-indigo-400 transition-colors">Contact</a></li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-white font-bold text-lg mb-6">Legal</h3>
                        <ul class="space-y-3 text-sm">
                            <li><a href="#" class="hover:text-indigo-400 transition-colors">Privacy Policy</a></li>
                            <li><a href="#" class="hover:text-indigo-400 transition-colors">Terms & Conditions</a></li>
                            <li><a href="#" class="hover:text-indigo-400 transition-colors">Refund Policy</a></li>
                        </ul>
                    </div>
                </div>

                <div class="pt-10 border-t border-slate-800 text-center text-sm text-gray-500">
                    {{ getSetting('general', 'copyright', '© 2026 E-Shop. Built with ❤️ in Bangladesh') }}
                </div>
            </div>
        </footer>

        <button
            v-if="showBackToTop"
            @click="scrollToTop"
            class="fixed bottom-8 right-8 w-14 h-14 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl shadow-2xl shadow-indigo-500/50 flex items-center justify-center transition-all hover:scale-110 active:scale-95 z-50 animate-bounce text-2xl"
        >
            ↑
        </button>
    </div>
</template>

<style scoped>
/* Slider Fade Animation */
.slide-fade-enter-active {
    transition: all 0.6s ease-out;
}
.slide-fade-leave-active {
    transition: all 0.4s cubic-bezier(1, 0.5, 0.8, 1);
}
.slide-fade-enter-from {
    transform: translateX(30px);
    opacity: 0;
}
.slide-fade-leave-to {
    transform: translateX(-30px);
    opacity: 0;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes blob {
    0% { transform: translate(0px, 0px) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
    100% { transform: translate(0px, 0px) scale(1); }
}

@keyframes float {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
    100% { transform: translateY(0px); }
}

.animate-fade-in-up {
    animation: fadeInUp 0.6s ease-out forwards;
    opacity: 0;
}

.animate-blob {
    animation: blob 7s infinite;
}

.animate-float {
    animation: float 3s ease-in-out infinite;
}

.animation-delay-200 {
    animation-delay: 200ms;
}

.animation-delay-400 {
    animation-delay: 400ms;
}

.animation-delay-600 {
    animation-delay: 600ms;
}

.animation-delay-800 {
    animation-delay: 800ms;
}

.animation-delay-1000 {
    animation-delay: 1000ms;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}

html {
    scroll-behavior: smooth;
}

::-webkit-scrollbar {
    width: 10px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #6366f1, #a855f7);
    border-radius: 5px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #4f46e5, #9333ea);
}
</style>
