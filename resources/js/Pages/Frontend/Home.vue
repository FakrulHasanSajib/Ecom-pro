<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useCartStore } from '../../stores/cart'; // কার্ট স্টোর ইমপোর্ট
import {
    SfButton,
    SfIconShoppingCart,
    SfIconFavorite,
    SfLoaderCircular,
    SfIconPerson,
    SfIconSearch
} from '@storefront-ui/vue';

const cartStore = useCartStore();
const products = ref([]);
const categories = ref([]);
const loading = ref(true);
const searchQuery = ref(''); // সার্চের জন্য
const selectedCategory = ref(null); // ক্যাটাগরি ফিল্টারের জন্য

// ব্যাকএন্ড ইউআরএল (পোর্ট ৭৩)
const backendUrl = 'http://127.0.0.1:73';

// ইমেজ পাথ ঠিক করার ফাংশন
const getImageUrl = (path) => {
    if (!path) return 'https://placehold.co/400x400?text=No+Image';
    return path.startsWith('http') ? path : `${backendUrl}/storage/${path}`;
};

// ডাটা ফেচ করার ফাংশন (সার্চ এবং ক্যাটাগরি ফিল্টারসহ)
const fetchData = async () => {
    try {
        loading.value = true;

        // কুয়েরি প্যারামিটার তৈরি
        const params = {};
        if (searchQuery.value) params.search = searchQuery.value;
        if (selectedCategory.value) params.category_slug = selectedCategory.value;

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

// ক্যাটাগরি ক্লিক করলে ফিল্টার হবে
const filterByCategory = (slug) => {
    selectedCategory.value = selectedCategory.value === slug ? null : slug;
    fetchData();
};

// সার্চ করার ফাংশন
const handleSearch = () => {
    fetchData();
};

onMounted(() => {
    fetchData();
});
</script>

<template>
    <div class="bg-white min-h-screen flex flex-col font-sans">

        <header class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
                <router-link to="/" class="flex items-center gap-2 cursor-pointer group">
                    <span class="text-3xl group-hover:scale-110 transition-transform">🛍️</span>
                    <span class="font-bold text-2xl text-indigo-700 tracking-tight">E-Shop</span>
                </router-link>

                <div class="hidden md:flex flex-1 max-w-lg mx-8 relative">
                    <input
                        v-model="searchQuery"
                        @keyup.enter="handleSearch"
                        type="text"
                        placeholder="Search for products..."
                        class="w-full bg-gray-100 border-none rounded-full py-2.5 pl-5 pr-12 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
                    />
                    <button @click="handleSearch" class="absolute right-3 top-2 text-gray-500 hover:text-indigo-600 transition-colors">
                        <SfIconSearch size="sm" />
                    </button>
                </div>

                <div class="flex items-center gap-2 md:gap-4">
                    <SfButton variant="tertiary" square class="text-gray-600 hover:text-indigo-600 hidden sm:flex">
                        <SfIconPerson />
                    </SfButton>

                    <router-link to="/checkout">
                        <SfButton variant="tertiary" square class="text-gray-600 hover:text-indigo-600 relative">
                            <SfIconShoppingCart />
                            <span v-if="cartStore.items.length > 0" class="absolute top-1 right-1 bg-red-500 text-white text-[10px] font-bold rounded-full h-4 w-4 flex items-center justify-center border border-white">
                                {{ cartStore.items.length }}
                            </span>
                        </SfButton>
                    </router-link>
                </div>
            </div>
        </header>

        <main class="flex-grow">
            <section class="bg-slate-900 text-white py-12 px-4 relative overflow-hidden">
                <div class="absolute top-0 right-0 -mt-20 -mr-20 w-80 h-80 bg-indigo-500 rounded-full opacity-20 blur-3xl"></div>
                <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between relative z-10">
                    <div class="md:w-1/2">
                        <span class="text-indigo-400 font-bold tracking-wider uppercase text-sm mb-2 block">New Arrivals</span>
                        <h1 class="text-4xl md:text-6xl font-extrabold mb-4 leading-tight">Explore Our <br> <span class="text-indigo-400">New Collection</span></h1>
                        <p class="text-gray-400 text-lg mb-8 max-w-md">Get the best quality products at unbeatable prices.</p>
                        <SfButton size="lg" class="bg-indigo-600 hover:bg-indigo-700 border-none shadow-lg shadow-indigo-500/30">Shop Now</SfButton>
                    </div>
                    <div class="md:w-1/2 mt-10 md:mt-0 flex justify-center">
                        <img src="https://storage.googleapis.com/sfui_docs_artifacts/containers/watch.png" class="h-64 md:h-80 object-contain drop-shadow-2xl hover:scale-105 transition duration-500" alt="Banner" />
                    </div>
                </div>
            </section>

            <section v-if="categories.length > 0" class="max-w-7xl mx-auto px-4 py-16">
                <h2 class="text-2xl md:text-3xl font-bold text-slate-800 mb-8">Shop by Categories</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
                    <div v-for="category in categories" :key="category.id"
                         @click="filterByCategory(category.slug)"
                         :class="{'border-indigo-600 ring-2 ring-indigo-100 bg-indigo-50': selectedCategory === category.slug}"
                         class="flex flex-col items-center p-6 border border-gray-100 rounded-2xl hover:shadow-xl hover:border-indigo-100 transition-all duration-300 bg-white group cursor-pointer">
                        <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center text-3xl mb-4 group-hover:bg-indigo-600 group-hover:text-white transition-colors duration-300 overflow-hidden">
                            <img v-if="category.image" :src="getImageUrl(category.image)" class="w-full h-full object-cover" />
                            <span v-else>📁</span>
                        </div>
                        <span class="font-bold text-gray-700 text-center group-hover:text-indigo-600 transition-colors">{{ category.name }}</span>
                    </div>
                </div>
            </section>

            <section class="bg-gray-50 py-16">
                <div class="max-w-7xl mx-auto px-4">
                    <div class="flex justify-between items-end mb-10">
                        <div>
                            <span class="text-indigo-600 font-bold tracking-wider uppercase text-sm block mb-1">
                                {{ selectedCategory ? 'Filtered Results' : 'Top Selling' }}
                            </span>
                            <h2 class="text-2xl md:text-3xl font-bold text-slate-800">Featured Products</h2>
                        </div>
                        <SfButton @click="selectedCategory = null; searchQuery = ''; fetchData();" variant="tertiary" class="text-indigo-600 hover:bg-indigo-50">Reset Filters</SfButton>
                    </div>

                    <div v-if="loading" class="flex flex-col items-center py-20">
                        <SfLoaderCircular size="lg" class="text-indigo-600" />
                        <p class="mt-4 text-gray-500 font-medium">Loading amazing products...</p>
                    </div>

                    <div v-else-if="products.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
                        <div v-for="product in products" :key="product.id"
                             class="group bg-white border border-gray-200 rounded-2xl overflow-hidden hover:shadow-2xl hover:border-transparent transition-all duration-300 relative flex flex-col">

                            <router-link :to="'/product/' + product.id" class="relative h-64 overflow-hidden bg-white p-4 flex items-center justify-center block cursor-pointer">
                                <img :src="getImageUrl(product.thumbnail)"
                                     class="max-w-full max-h-full object-contain group-hover:scale-110 transition-transform duration-500" />
                            </router-link>

                            <div class="p-5 border-t border-gray-100 flex-grow flex flex-col justify-between">
                                <div>
                                    <p class="text-xs text-indigo-500 font-bold uppercase mb-2 tracking-wider">{{ product.category?.name || 'General' }}</p>
                                    <router-link :to="'/product/' + product.id">
                                        <h3 class="font-bold text-gray-800 text-lg mb-2 line-clamp-2 hover:text-indigo-600 transition-colors cursor-pointer">
                                            {{ product.name }}
                                        </h3>
                                    </router-link>
                                </div>

                                <div class="mt-4">
                                    <div class="flex items-center gap-2 mb-4">
                                        <span class="text-2xl font-black text-slate-900">৳{{ product.sale_price || product.base_price }}</span>
                                        <span v-if="product.discount_price" class="text-sm text-gray-400 line-through">৳{{ product.discount_price }}</span>
                                    </div>
                                    <SfButton @click="cartStore.addToCart(product)" size="sm" class="w-full bg-slate-900 hover:bg-indigo-600 text-white py-2.5 rounded-xl flex items-center justify-center gap-2 border-none transition-colors shadow-md hover:shadow-indigo-500/30">
                                        <template #prefix><SfIconShoppingCart size="sm" /></template>
                                        Add to Cart
                                    </SfButton>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-24 bg-white rounded-3xl border-2 border-dashed border-gray-200">
                        <div class="text-6xl mb-4 opacity-50">🛒</div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">No products found</h3>
                        <p class="text-gray-500 max-w-md mx-auto">Try adjusting your search or category filters.</p>
                    </div>
                </div>
            </section>
        </main>

        <footer class="bg-slate-900 text-gray-300 pt-16 pb-8 border-t-4 border-indigo-600 mt-auto">
            <div class="max-w-7xl mx-auto px-4 text-center">
                <p>&copy; 2026 E-Shop. All rights reserved.</p>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #475569; border-radius: 20px; }
</style>
