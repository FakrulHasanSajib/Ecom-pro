<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useCartStore } from '../../stores/cart';
import { useAuthStore } from '../../stores/auth';
import Swal from 'sweetalert2';
import {
    SfButton,
    SfIconShoppingCart,
    SfIconFavorite,
    SfLoaderCircular,
    SfIconPerson,
    SfIconSearch,
    SfIconClose
} from '@storefront-ui/vue';

const cartStore = useCartStore();
const authStore = useAuthStore();

const products = ref([]);
const categories = ref([]);
const loading = ref(true);

// Filters
const searchQuery = ref('');
const selectedCategory = ref(null);
const sortOption = ref('');
const minPrice = ref('');
const maxPrice = ref('');

const backendUrl = 'http://127.0.0.1:73';

const getImageUrl = (path) => {
    if (!path) return 'https://placehold.co/400x400?text=No+Image';
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

        const [prodRes, catRes] = await Promise.all([
            axios.get(`${backendUrl}/api/public/products`, { params }),
            axios.get(`${backendUrl}/api/public/categories`).catch(() => ({ data: [] }))
        ]);

        products.value = prodRes.data.data || [];
        categories.value = catRes.data.data || catRes.data || [];

    } catch (error) {
        console.error("Data fetch error:", error);
    } finally {
        loading.value = false;
    }
};

const filterByCategory = (slug) => {
    selectedCategory.value = selectedCategory.value === slug ? null : slug;
    fetchData();
};

const resetFilters = () => {
    selectedCategory.value = null;
    searchQuery.value = '';
    sortOption.value = '';
    minPrice.value = '';
    maxPrice.value = '';
    fetchData();
};

const handleAddToCart = (product) => {
    cartStore.addToCart(product);
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Added to Cart!',
        showConfirmButton: false,
        timer: 2200,
        timerProgressBar: true,
        background: '#4f46e5',
        color: '#ffffff',
        iconColor: '#ffffff',
        padding: '1rem 1.5rem',
    });
};

let debounceTimer = null;
watch(searchQuery, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchData, 600);
});

onMounted(fetchData);
</script>

<template>
    <div class="bg-gray-50/40 min-h-screen font-sans antialiased">

        <!-- Header ──────────────────────────────────────── -->
        <header class="bg-white/80 backdrop-blur-md border-b border-gray-200/60 sticky top-0 z-50 transition-all">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <router-link to="/" class="flex items-center gap-2.5 group">
                    <span class="text-3xl group-hover:rotate-12 transition-transform duration-300">🛍️</span>
                    <span class="font-extrabold text-2xl bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent tracking-tight">E-Shop</span>
                </router-link>

                <div class="hidden md:flex flex-1 max-w-xl mx-10 relative">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search products, brands, categories..."
                        class="w-full bg-white/70 backdrop-blur-sm border border-gray-200 rounded-full py-3 pl-5 pr-12 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-transparent shadow-sm transition-all"
                    />
                    <button class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-indigo-600 transition-colors">
                        <SfIconSearch size="sm" />
                    </button>
                </div>

                <div class="flex items-center gap-3 md:gap-5">
                    <template v-if="authStore.isAuthenticated">
                        <router-link
                            to="/dashboard"
                            class="flex items-center gap-2 text-indigo-700 font-semibold hover:bg-indigo-50 px-4 py-2 rounded-xl transition-all text-sm"
                        >
                            <SfIconPerson size="sm" />
                            <span class="hidden sm:inline">Dashboard</span>
                        </router-link>
                    </template>
                    <template v-else>
                        <router-link to="/login">
                            <SfButton variant="tertiary" class="font-semibold text-indigo-700 hover:bg-indigo-50/80 px-5 py-2 rounded-xl transition-all text-sm">
                                Sign In
                            </SfButton>
                        </router-link>
                    </template>

                    <router-link to="/checkout" class="relative">
                        <SfButton variant="tertiary" square class="text-gray-600 hover:text-indigo-700 hover:bg-indigo-50/80 rounded-full p-3 transition-all">
                            <SfIconShoppingCart />
                            <span v-if="cartStore.items.length" class="absolute -top-1 -right-1 bg-gradient-to-br from-red-500 to-rose-600 text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center border-2 border-white shadow">
                                {{ cartStore.items.length }}
                            </span>
                        </SfButton>
                    </router-link>
                </div>
            </div>
        </header>

        <!-- Hero ────────────────────────────────────────── -->
        <section class="relative bg-gradient-to-br from-slate-950 via-indigo-950 to-purple-950 text-white overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_70%,rgba(79,70,229,0.15),transparent_50%)] animate-pulse-slow"></div>

            <div class="max-w-7xl mx-auto px-6 py-24 md:py-32 relative z-10 grid md:grid-cols-2 gap-12 items-center">
                <div class="text-center md:text-left">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 backdrop-blur-sm border border-white/10 text-indigo-300 text-xs font-semibold tracking-wider uppercase mb-6">
                        New Collection 2026
                    </div>
                    <h1 class="text-5xl md:text-7xl font-black leading-tight mb-6">
                        Discover <span class="bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">Tomorrow's</span><br>Style Today
                    </h1>
                    <p class="text-lg md:text-xl text-gray-300 max-w-lg mx-auto md:mx-0 mb-10">
                        Premium curated products. Exceptional quality. Prices you'll love.
                    </p>
                    <SfButton size="lg" class="bg-white text-indigo-950 hover:bg-gray-100 shadow-2xl shadow-indigo-500/30 rounded-full px-10 py-6 text-lg font-bold transition-all hover:scale-105 active:scale-95">
                        Shop Now
                    </SfButton>
                </div>

                <div class="relative flex justify-center">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent z-10"></div>
                    <img
                        src="https://images.unsplash.com/photo-1523275335684-04c0c856c0e0?q=80&w=1200&auto=format&fit=crop"
                        class="h-80 md:h-[520px] object-contain drop-shadow-2xl hover:scale-105 transition-transform duration-1000"
                        alt="Hero Product"
                    />
                </div>
            </div>
        </section>

        <!-- Categories Bento ─────────────────────────────── -->
        <section v-if="categories.length" class="max-w-7xl mx-auto px-6 py-20">
            <h2 class="text-3xl md:text-4xl font-black text-slate-900 mb-10 flex items-center gap-4">
                <span class="w-3 h-10 bg-gradient-to-b from-indigo-600 to-purple-600 rounded-full"></span>
                Explore Categories
            </h2>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-5">
                <div
                    v-for="cat in categories"
                    :key="cat.id"
                    @click="filterByCategory(cat.slug)"
                    class="group relative overflow-hidden rounded-3xl border border-gray-200/50 bg-white/60 backdrop-blur-sm hover:bg-white/90 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 cursor-pointer"
                    :class="{ 'ring-2 ring-indigo-500/50 bg-white/95 shadow-xl -translate-y-1': selectedCategory === cat.slug }"
                >
                    <div class="aspect-square overflow-hidden">
                        <img
                            v-if="cat.image"
                            :src="getImageUrl(cat.image)"
                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                            alt=""
                        />
                        <div v-else class="w-full h-full bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center text-5xl">
                            📦
                        </div>
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="absolute bottom-4 left-4 right-4 text-white">
                        <p class="font-bold text-lg drop-shadow-md">{{ cat.name }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Products + Filters ───────────────────────────── -->
        <section class="max-w-7xl mx-auto px-6 pb-24">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-12 bg-white/70 backdrop-blur-sm p-6 rounded-3xl border border-gray-200/50 shadow-sm">

                <div>
                    <h2 class="text-3xl font-black text-slate-900">
                        {{ selectedCategory ? 'Curated Selection' : 'Featured Products' }}
                    </h2>
                    <p class="text-gray-600 mt-1.5">Showing {{ products.length }} premium items</p>
                </div>

                <div class="flex flex-wrap gap-4">
                    <input v-model="minPrice" @change="fetchData" type="number" placeholder="Min ৳" class="w-28 bg-white border border-gray-200 rounded-xl py-3 px-4 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/40 shadow-sm" />
                    <span class="self-center text-gray-400">-</span>
                    <input v-model="maxPrice" @change="fetchData" type="number" placeholder="Max ৳" class="w-28 bg-white border border-gray-200 rounded-xl py-3 px-4 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/40 shadow-sm" />

                    <select v-model="sortOption" @change="fetchData" class="bg-white border border-gray-200 rounded-xl py-3 px-4 text-sm text-gray-700 cursor-pointer shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                        <option value="">Sort: Featured</option>
                        <option value="price_low">Price: Low → High</option>
                        <option value="price_high">Price: High → Low</option>
                    </select>

                    <button
                        v-if="selectedCategory || sortOption || minPrice || maxPrice || searchQuery"
                        @click="resetFilters"
                        class="flex items-center gap-2 bg-red-50/80 hover:bg-red-100 text-red-700 px-5 py-3 rounded-xl font-medium transition-colors shadow-sm"
                    >
                        <SfIconClose size="xs" /> Clear
                    </button>
                </div>
            </div>

            <div v-if="loading" class="flex flex-col items-center justify-center py-40">
                <SfLoaderCircular size="xl" class="text-indigo-600 mb-6 animate-spin-slow" />
                <p class="text-gray-600 font-medium">Loading exclusive collection...</p>
            </div>

            <div v-else-if="products.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-7">
                <div
                    v-for="product in products"
                    :key="product.id"
                    class="group bg-white border border-gray-200/60 rounded-3xl overflow-hidden hover:shadow-2xl hover:shadow-indigo-500/15 transition-all duration-400 flex flex-col relative"
                >
                    <div v-if="product.discount_price" class="absolute top-4 left-4 z-20 bg-gradient-to-r from-red-600 to-rose-600 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-lg">
                        SAVE {{ Math.round((product.base_price - product.sale_price) / product.base_price * 100) }}%
                    </div>

                    <div class="absolute top-4 right-4 z-20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 translate-x-2 group-hover:translate-x-0">
                        <button class="w-10 h-10 bg-white/90 backdrop-blur-md rounded-full shadow-lg flex items-center justify-center text-gray-500 hover:text-red-600 hover:bg-red-50 transition-colors">
                            <SfIconFavorite size="sm" />
                        </button>
                    </div>

                    <router-link :to="`/product/${product.id}`" class="relative h-64 bg-gradient-to-b from-gray-50 to-white flex items-center justify-center p-8 overflow-hidden">
                        <img
                            :src="getImageUrl(product.thumbnail)"
                            class="max-w-full max-h-full object-contain group-hover:scale-110 transition-transform duration-800 ease-out"
                            alt=""
                        />
                    </router-link>

                    <div class="p-6 flex flex-col flex-grow">
                        <p class="text-xs text-indigo-600 font-semibold uppercase tracking-wide mb-2">{{ product.category?.name || 'Collection' }}</p>

                        <router-link :to="`/product/${product.id}`">
                            <h3 class="font-bold text-lg text-slate-900 mb-3 line-clamp-2 group-hover:text-indigo-700 transition-colors">
                                {{ product.name }}
                            </h3>
                        </router-link>

                        <div class="mt-auto pt-4 border-t border-gray-100">
                            <div class="flex items-end gap-3 mb-5">
                                <span class="text-2xl font-black text-slate-900">৳{{ product.sale_price || product.base_price }}</span>
                                <span v-if="product.discount_price" class="text-sm text-gray-500 line-through">৳{{ product.base_price }}</span>
                            </div>

                            <SfButton
                                @click="handleAddToCart(product)"
                                class="w-full bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-purple-700 text-white py-4 rounded-2xl font-bold shadow-lg shadow-indigo-500/25 hover:shadow-xl hover:shadow-indigo-500/40 transition-all active:scale-98 flex items-center justify-center gap-3 group/btn"
                            >
                                <SfIconShoppingCart class="group-hover/btn:animate-bounce" />
                                Add to Cart
                            </SfButton>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-40 bg-white/70 backdrop-blur-sm rounded-3xl border border-gray-200/50 shadow-sm">
                <div class="w-28 h-28 mx-auto mb-8 bg-gradient-to-br from-indigo-50 to-purple-50 rounded-full flex items-center justify-center text-6xl text-indigo-300">
                    🔍
                </div>
                <h3 class="text-3xl font-black text-slate-800 mb-4">Nothing found</h3>
                <p class="text-gray-600 max-w-md mx-auto mb-8">Try adjusting your filters or search term.</p>
                <SfButton @click="resetFilters" class="bg-indigo-600 hover:bg-indigo-700 text-white px-10 py-4 rounded-2xl font-bold shadow-xl shadow-indigo-500/30">
                    Reset Filters
                </SfButton>
            </div>
        </section>

        <!-- Footer (simplified modern version) ─────────────── -->
        <footer class="bg-gradient-to-b from-slate-950 to-black text-gray-300 pt-20 pb-12 mt-auto">
            <div class="max-w-7xl mx-auto px-6 grid md:grid-cols-4 gap-12">
                <div>
                    <div class="flex items-center gap-3 mb-6">
                        <span class="text-4xl">🛍️</span>
                        <span class="font-black text-3xl text-white tracking-tight">E-Shop</span>
                    </div>
                    <p class="text-gray-400 text-sm leading-relaxed mb-8">
                        Premium shopping experience — curated, secure, delivered fast.
                    </p>
                </div>

                <div>
                    <h3 class="text-white font-bold text-lg mb-6">Quick Links</h3>
                    <ul class="space-y-3 text-sm">
                        <li><a href="#" class="hover:text-indigo-400 transition-colors">Home</a></li>
                        <li><a href="#" class="hover:text-indigo-400 transition-colors">Shop</a></li>
                        <li><a href="#" class="hover:text-indigo-400 transition-colors">About</a></li>
                        <li><a href="#" class="hover:text-indigo-400 transition-colors">Contact</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-white font-bold text-lg mb-6">Support</h3>
                    <ul class="space-y-3 text-sm">
                        <li><a href="#" class="hover:text-indigo-400 transition-colors">Track Order</a></li>
                        <li><a href="#" class="hover:text-indigo-400 transition-colors">Returns</a></li>
                        <li><a href="#" class="hover:text-indigo-400 transition-colors">Payment</a></li>
                        <li><a href="#" class="hover:text-indigo-400 transition-colors">Privacy</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-white font-bold text-lg mb-6">Newsletter</h3>
                    <p class="text-gray-400 text-sm mb-5">Get exclusive deals & early access.</p>
                    <form @submit.prevent class="flex flex-col gap-4">
                        <input type="email" placeholder="Your email" class="bg-slate-800/60 border border-slate-700 rounded-xl py-3 px-5 text-white placeholder-gray-400 focus:outline-none focus:border-indigo-500 transition-colors" />
                        <SfButton class="bg-indigo-600 hover:bg-indigo-500 text-white py-3 rounded-xl font-bold shadow-lg">
                            Subscribe
                        </SfButton>
                    </form>
                </div>
            </div>

            <div class="mt-16 pt-10 border-t border-slate-800 text-center text-sm text-gray-500">
                © 2026 E-Shop — Built with passion in Bangladesh
            </div>
        </footer>

    </div>
</template>

<style scoped>
@keyframes pulse-slow {
    0%, 100% { opacity: 0.6; }
    50% { opacity: 0.9; }
}
.animate-pulse-slow {
    animation: pulse-slow 8s infinite ease-in-out;
}
.animate-spin-slow {
    animation: spin 3s linear infinite;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
</style>
