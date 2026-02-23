<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useCartStore } from '../../stores/cart';
import Swal from 'sweetalert2'; // Toast Notification এর জন্য
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
const products = ref([]);
const categories = ref([]);
const loading = ref(true);

// Filters State
const searchQuery = ref('');
const selectedCategory = ref(null);
const sortOption = ref('');
const minPrice = ref('');
const maxPrice = ref('');

// ব্যাকএন্ড ইউআরএল (পোর্ট ৭৩)
const backendUrl = 'http://127.0.0.1:73';

// ইমেজ পাথ ঠিক করার ফাংশন
const getImageUrl = (path) => {
    if (!path) return 'https://placehold.co/400x400?text=No+Image';
    return path.startsWith('http') ? path : `${backendUrl}/storage/${path}`;
};

// ডাটা ফেচ করার ফাংশন
const fetchData = async () => {
    try {
        loading.value = true;

        // কুয়েরি প্যারামিটার তৈরি
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
        console.error("Data fetching failed:", error);
    } finally {
        loading.value = false;
    }
};

// ক্যাটাগরি ফিল্টার
const filterByCategory = (slug) => {
    selectedCategory.value = selectedCategory.value === slug ? null : slug;
    fetchData();
};

// সব ফিল্টার রিসেট
const resetFilters = () => {
    selectedCategory.value = null;
    searchQuery.value = '';
    sortOption.value = '';
    minPrice.value = '';
    maxPrice.value = '';
    fetchData();
};

// কার্টে অ্যাড করার ফাংশন (SweetAlert Toast সহ)
const handleAddToCart = (product) => {
    cartStore.addToCart(product);
    Swal.fire({
        toast: true,
        position: 'bottom-end',
        icon: 'success',
        title: 'Added to Cart Successfully!',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true,
        background: '#4f46e5',
        color: '#fff',
        iconColor: '#fff'
    });
};

// সার্চ টাইপ করার পর একটু অপেক্ষা করে কল হবে (Debounce Effect)
let timeout = null;
watch(searchQuery, () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        fetchData();
    }, 500);
});

onMounted(() => {
    fetchData();
});
</script>

<template>
    <div class="bg-gray-50 min-h-screen flex flex-col font-sans">

        <header class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
                <router-link to="/" class="flex items-center gap-2 cursor-pointer group">
                    <span class="text-3xl group-hover:scale-110 transition-transform">🛍️</span>
                    <span class="font-black text-2xl text-indigo-700 tracking-tight">E-Shop</span>
                </router-link>

                <div class="hidden md:flex flex-1 max-w-lg mx-8 relative">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search for products, brands..."
                        class="w-full bg-gray-100 border border-transparent hover:border-indigo-200 rounded-full py-2.5 pl-5 pr-12 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white text-sm transition-all shadow-inner"
                    />
                    <button class="absolute right-4 top-2.5 text-gray-400 hover:text-indigo-600 transition-colors">
                        <SfIconSearch size="sm" />
                    </button>
                </div>

                <div class="flex items-center gap-2 md:gap-4">
                    <SfButton variant="tertiary" square class="text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-full hidden sm:flex transition-all">
                        <SfIconPerson />
                    </SfButton>

                    <router-link to="/checkout">
                        <SfButton variant="tertiary" square class="text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-full relative transition-all">
                            <SfIconShoppingCart />
                            <span v-if="cartStore.items.length > 0" class="absolute top-0 right-0 bg-red-500 text-white text-[10px] font-bold rounded-full h-5 w-5 flex items-center justify-center border-2 border-white shadow-sm transform translate-x-1 -translate-y-1">
                                {{ cartStore.items.length }}
                            </span>
                        </SfButton>
                    </router-link>
                </div>
            </div>
        </header>

        <main class="flex-grow">
            <section class="bg-slate-900 text-white py-16 px-4 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-500 rounded-full opacity-20 blur-[100px] animate-pulse"></div>
                <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between relative z-10">
                    <div class="md:w-1/2 text-center md:text-left">
                        <span class="inline-block py-1 px-3 rounded-full bg-indigo-500/20 text-indigo-300 font-bold tracking-wider uppercase text-xs mb-4 border border-indigo-500/30">New Arrivals 2026</span>
                        <h1 class="text-4xl md:text-6xl font-black mb-6 leading-tight">Elevate Your <br> <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400">Lifestyle</span></h1>
                        <p class="text-gray-400 text-lg mb-8 max-w-md mx-auto md:mx-0">Discover premium quality products crafted for excellence. Unbeatable prices just a click away.</p>
                        <SfButton size="lg" class="bg-indigo-600 hover:bg-indigo-500 border-none shadow-[0_0_20px_rgba(79,70,229,0.4)] transition-all duration-300 rounded-full px-8">Shop Collection</SfButton>
                    </div>
                    <div class="md:w-1/2 mt-12 md:mt-0 flex justify-center relative">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-transparent z-10"></div>
                        <img src="https://storage.googleapis.com/sfui_docs_artifacts/containers/watch.png" class="h-72 md:h-96 object-contain drop-shadow-[0_20px_50px_rgba(0,0,0,0.5)] hover:scale-105 transition duration-700 z-0 relative" alt="Banner" />
                    </div>
                </div>
            </section>

            <section v-if="categories.length > 0" class="max-w-7xl mx-auto px-4 py-16">
                <h2 class="text-2xl font-black text-slate-800 mb-8 flex items-center gap-2">
                    <span class="w-2 h-8 bg-indigo-600 rounded-full"></span> Top Categories
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <div v-for="category in categories" :key="category.id"
                         @click="filterByCategory(category.slug)"
                         :class="{'border-indigo-500 ring-2 ring-indigo-200 bg-indigo-50 shadow-md transform -translate-y-1': selectedCategory === category.slug, 'bg-white border-slate-100': selectedCategory !== category.slug}"
                         class="flex flex-col items-center p-6 border rounded-2xl hover:shadow-xl hover:border-indigo-200 transition-all duration-300 group cursor-pointer">
                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-3xl mb-4 group-hover:scale-110 transition-transform duration-300 overflow-hidden shadow-inner">
                            <img v-if="category.image" :src="getImageUrl(category.image)" class="w-full h-full object-cover" />
                            <span v-else>📁</span>
                        </div>
                        <span class="font-bold text-sm text-slate-700 text-center group-hover:text-indigo-600 transition-colors">{{ category.name }}</span>
                    </div>
                </div>
            </section>

            <section class="max-w-7xl mx-auto px-4 pb-20">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4 bg-white p-4 rounded-2xl shadow-sm border border-slate-100">

                    <div>
                        <h2 class="text-2xl font-black text-slate-800">
                            {{ selectedCategory ? 'Filtered Products' : 'All Products' }}
                        </h2>
                        <p class="text-sm text-slate-500 mt-1">Showing {{ products.length }} items</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                        <input v-model="minPrice" @change="fetchData" type="number" placeholder="Min ৳" class="w-24 bg-slate-50 border border-slate-200 rounded-lg py-2 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" />
                        <span class="text-slate-400">-</span>
                        <input v-model="maxPrice" @change="fetchData" type="number" placeholder="Max ৳" class="w-24 bg-slate-50 border border-slate-200 rounded-lg py-2 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" />

                        <select v-model="sortOption" @change="fetchData" class="bg-slate-50 border border-slate-200 rounded-lg py-2 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm cursor-pointer text-slate-700">
                            <option value="">Default Sort</option>
                            <option value="price_low">Price: Low to High</option>
                            <option value="price_high">Price: High to Low</option>
                        </select>

                        <button v-if="selectedCategory || sortOption || minPrice || maxPrice || searchQuery" @click="resetFilters" class="text-sm font-bold text-red-500 hover:text-red-700 flex items-center gap-1 bg-red-50 px-3 py-2 rounded-lg transition-colors">
                            <SfIconClose size="xs" /> Reset
                        </button>
                    </div>
                </div>

                <div v-if="loading" class="flex flex-col items-center justify-center py-32">
                    <SfLoaderCircular size="xl" class="text-indigo-600 mb-4" />
                    <p class="text-slate-500 font-medium animate-pulse">Fetching amazing products...</p>
                </div>

                <div v-else-if="products.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <div v-for="product in products" :key="product.id"
                         class="group bg-white border border-slate-100 rounded-2xl overflow-hidden hover:shadow-2xl hover:shadow-indigo-500/10 transition-all duration-300 flex flex-col relative">

                        <div v-if="product.discount_price" class="absolute top-3 left-3 z-10 bg-red-500 text-white text-xs font-black px-2 py-1 rounded-md shadow-sm">
                            SALE
                        </div>

                        <div class="absolute top-3 right-3 z-10 translate-x-5 opacity-0 group-hover:translate-x-0 group-hover:opacity-100 transition-all duration-300">
                            <button class="w-8 h-8 bg-white/90 backdrop-blur shadow-md rounded-full text-slate-400 hover:text-red-500 hover:bg-red-50 flex items-center justify-center transition-colors">
                                <SfIconFavorite size="xs" />
                            </button>
                        </div>

                        <router-link :to="'/product/' + product.id" class="relative h-56 overflow-hidden bg-slate-50 flex items-center justify-center p-6 cursor-pointer">
                            <img :src="getImageUrl(product.thumbnail)"
                                 class="max-w-full max-h-full object-contain group-hover:scale-110 transition-transform duration-700" />
                        </router-link>

                        <div class="p-5 flex-grow flex flex-col justify-between">
                            <div>
                                <p class="text-[10px] text-indigo-500 font-bold uppercase tracking-widest mb-2">{{ product.category?.name || 'General' }}</p>
                                <router-link :to="'/product/' + product.id">
                                    <h3 class="font-bold text-slate-800 text-base mb-1 line-clamp-2 hover:text-indigo-600 transition-colors">
                                        {{ product.name }}
                                    </h3>
                                </router-link>
                            </div>

                            <div class="mt-4 border-t border-slate-100 pt-4">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex flex-col">
                                        <span class="text-xl font-black text-slate-900">৳{{ product.sale_price || product.base_price }}</span>
                                        <span v-if="product.discount_price" class="text-xs text-slate-400 line-through">৳{{ product.base_price }}</span>
                                    </div>
                                </div>

                                <button @click="handleAddToCart(product)" class="w-full bg-slate-100 hover:bg-indigo-600 text-slate-800 hover:text-white py-3 rounded-xl flex items-center justify-center gap-2 font-bold transition-all duration-300 active:scale-95 group/btn">
                                    <SfIconShoppingCart size="sm" class="group-hover/btn:animate-bounce" />
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-32 bg-white rounded-3xl border border-slate-100 shadow-sm">
                    <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center text-4xl mx-auto mb-6 text-slate-300">🔍</div>
                    <h3 class="text-2xl font-black text-slate-800 mb-2">No products found</h3>
                    <p class="text-slate-500 max-w-md mx-auto mb-6">We couldn't find anything matching your search or filters.</p>
                    <button @click="resetFilters" class="bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-indigo-500/30 hover:bg-indigo-700 transition-colors">
                        Clear All Filters
                    </button>
                </div>
            </section>
        </main>

        <footer class="bg-slate-900 text-slate-300 pt-20 pb-10 mt-auto relative overflow-hidden">
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-5"></div>
            <div class="max-w-7xl mx-auto px-4 relative z-10">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-16 border-b border-slate-800 pb-16">

                    <div>
                        <div class="flex items-center gap-2 mb-6">
                            <span class="text-3xl">🛍️</span>
                            <span class="font-black text-2xl text-white tracking-tight">E-Shop</span>
                        </div>
                        <p class="text-slate-400 leading-relaxed mb-8 text-sm">Experience the best online shopping with premium products, secure payments, and fast delivery right to your doorstep.</p>
                        <div class="flex gap-3">
                            <a href="#" class="w-10 h-10 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center hover:bg-indigo-600 hover:border-indigo-600 text-white transition-all shadow-lg">f</a>
                            <a href="#" class="w-10 h-10 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center hover:bg-indigo-600 hover:border-indigo-600 text-white transition-all shadow-lg">in</a>
                            <a href="#" class="w-10 h-10 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center hover:bg-indigo-600 hover:border-indigo-600 text-white transition-all shadow-lg">X</a>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-white font-bold text-lg mb-6 flex items-center gap-2"><span class="w-1 h-4 bg-indigo-500 rounded-full"></span> Quick Links</h3>
                        <ul class="space-y-3 text-sm font-medium">
                            <li><a href="#" class="hover:text-indigo-400 hover:translate-x-1 inline-block transition-all">Home</a></li>
                            <li><a href="#" class="hover:text-indigo-400 hover:translate-x-1 inline-block transition-all">Shop Collection</a></li>
                            <li><a href="#" class="hover:text-indigo-400 hover:translate-x-1 inline-block transition-all">About Us</a></li>
                            <li><a href="#" class="hover:text-indigo-400 hover:translate-x-1 inline-block transition-all">Contact Support</a></li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-white font-bold text-lg mb-6 flex items-center gap-2"><span class="w-1 h-4 bg-indigo-500 rounded-full"></span> Customer Support</h3>
                        <ul class="space-y-3 text-sm font-medium">
                            <li><a href="#" class="hover:text-indigo-400 hover:translate-x-1 inline-block transition-all">Track Order</a></li>
                            <li><a href="#" class="hover:text-indigo-400 hover:translate-x-1 inline-block transition-all">Shipping & Returns</a></li>
                            <li><a href="#" class="hover:text-indigo-400 hover:translate-x-1 inline-block transition-all">Secure Payment</a></li>
                            <li><a href="#" class="hover:text-indigo-400 hover:translate-x-1 inline-block transition-all">Terms & Privacy</a></li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-white font-bold text-lg mb-6 flex items-center gap-2"><span class="w-1 h-4 bg-indigo-500 rounded-full"></span> Newsletter</h3>
                        <p class="text-slate-400 mb-4 text-sm">Subscribe to get special offers, free giveaways, and deals.</p>
                        <form @submit.prevent class="flex flex-col gap-3">
                            <input type="email" placeholder="Enter your email address" class="w-full bg-slate-800/50 border border-slate-700 rounded-lg py-3 px-4 focus:outline-none focus:border-indigo-500 text-sm text-white transition-colors" />
                            <button class="bg-indigo-600 hover:bg-indigo-500 text-white py-3 rounded-lg font-bold transition-colors shadow-lg shadow-indigo-500/20">
                                Subscribe Now
                            </button>
                        </form>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row justify-between items-center text-sm text-slate-500">
                    <p>&copy; 2026 E-Shop Platform. Developed with ❤️ in Bangladesh.</p>
                    <div class="flex gap-4 mt-4 md:mt-0 font-bold opacity-50">
                        <span>SSLCOMMERZ</span>
                        <span>VISA</span>
                        <span>bKash</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* Scrollbar স্টাইলিং */
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #4f46e5; border-radius: 20px; }
</style>
