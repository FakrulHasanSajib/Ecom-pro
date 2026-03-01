<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const isSidebarOpen = ref(true);
const isProductMenuOpen = ref(false);
const isOrderMenuOpen = ref(false);
const isBannerMenuOpen = ref(false);
const isAttributeMenuOpen = ref(false); // Attributes ড্রপডাউনের জন্য

const router = useRouter();
const route = useRoute();

const userName = ref(localStorage.getItem('user_name') || 'Admin');
const orderStatuses = ref([]);

onMounted(async () => {
    // মেনু ওপেন রাখা (অ্যাক্টিভ রাউট অনুযায়ী)
    if (route.path.startsWith('/admin/products')) isProductMenuOpen.value = true;
    if (route.path.startsWith('/admin/orders')) isOrderMenuOpen.value = true;
    if (route.path.startsWith('/admin/banner')) isBannerMenuOpen.value = true;
    if (route.path.startsWith('/admin/brands') || route.path.startsWith('/admin/colors') || route.path.startsWith('/admin/sizes')) {
        isAttributeMenuOpen.value = true;
    }

    // ডাটাবেস থেকে ডাইনামিক স্ট্যাটাসগুলো সাইডবারের জন্য কল করা
    try {
        const token = localStorage.getItem('token');
        if (token) {
            const res = await axios.get('http://127.0.0.1:73/api/admin/order-statuses', {
                headers: { Authorization: `Bearer ${token}` }
            });
            orderStatuses.value = res.data.data || res.data || [];
        }
    } catch (error) {
        console.error("Failed to fetch order statuses for sidebar");
    }
});

// মেনু টগল ফাংশনগুলো
const toggleOrderMenu = () => {
    if (!isSidebarOpen.value) {
        isSidebarOpen.value = true;
        isOrderMenuOpen.value = true;
    } else {
        isOrderMenuOpen.value = !isOrderMenuOpen.value;
    }
};

const toggleProductMenu = () => {
    if (!isSidebarOpen.value) {
        isSidebarOpen.value = true;
        isProductMenuOpen.value = true;
    } else {
        isProductMenuOpen.value = !isProductMenuOpen.value;
    }
};

const toggleBannerMenu = () => {
    if (!isSidebarOpen.value) {
        isSidebarOpen.value = true;
        isBannerMenuOpen.value = true;
    } else {
        isBannerMenuOpen.value = !isBannerMenuOpen.value;
    }
};

const toggleAttributeMenu = () => {
    if (!isSidebarOpen.value) {
        isSidebarOpen.value = true;
        isAttributeMenuOpen.value = true;
    } else {
        isAttributeMenuOpen.value = !isAttributeMenuOpen.value;
    }
};

const logout = async () => {
    try {
        const result = await Swal.fire({
            title: 'Logout?',
            text: "Are you sure you want to log out?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Yes, Logout'
        });

        if (result.isConfirmed) {
            const token = localStorage.getItem('token');
            await axios.post('http://127.0.0.1:73/api/logout', {}, {
                headers: { Authorization: `Bearer ${token}` }
            });

            localStorage.clear();
            delete axios.defaults.headers.common['Authorization'];
            router.push('/login');
        }
    } catch (error) {
        localStorage.clear();
        router.push('/login');
    }
};
</script>

<template>
    <div class="min-h-screen bg-gray-100 flex">
        <aside :class="isSidebarOpen ? 'w-64' : 'w-20'" class="fixed left-0 top-0 h-full bg-slate-900 text-white transition-all duration-300 z-50 flex flex-col shadow-xl">
            <div class="p-4 flex items-center justify-between border-b border-slate-700 h-16">
                <span v-if="isSidebarOpen" class="text-xl font-bold truncate tracking-wide">Admin Panel</span>
                <button @click="isSidebarOpen = !isSidebarOpen" class="p-1 hover:bg-slate-800 rounded focus:outline-none ml-auto transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                    </svg>
                </button>
            </div>

            <nav class="mt-4 px-3 space-y-1.5 flex-1 overflow-y-auto custom-scrollbar pb-6">

                <router-link to="/admin/dashboard" class="flex items-center p-3 hover:bg-indigo-600 rounded-lg transition" :class="route.path === '/admin/dashboard' ? 'bg-indigo-600 shadow-md' : ''">
                    <span class="text-xl w-6 text-center">📊</span>
                    <span v-if="isSidebarOpen" class="ml-3 font-medium">Dashboard</span>
                </router-link>

                <div>
                    <button @click="toggleOrderMenu" class="w-full flex items-center justify-between p-3 hover:bg-indigo-600 rounded-lg transition focus:outline-none" :class="isOrderMenuOpen || route.path.startsWith('/admin/orders') ? 'bg-slate-800' : ''">
                        <div class="flex items-center">
                            <span class="text-xl w-6 text-center">🛒</span>
                            <span v-if="isSidebarOpen" class="ml-3 font-medium">Orders</span>
                        </div>
                        <svg v-if="isSidebarOpen" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200" :class="isOrderMenuOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div v-show="isSidebarOpen && isOrderMenuOpen" class="mt-1 space-y-1 bg-slate-800/40 rounded-lg overflow-hidden py-1">
                        <router-link to="/admin/orders" class="flex items-center pl-12 pr-4 py-2.5 text-sm transition" :class="route.path === '/admin/orders' && !route.query.status ? 'text-white bg-indigo-500 font-bold shadow' : 'text-gray-300 hover:text-white hover:bg-slate-700'">
                            All Orders
                        </router-link>
                        <router-link v-for="status in orderStatuses" :key="status.id" :to="`/admin/orders?status=${status.name}`" class="flex items-center pl-12 pr-4 py-2.5 text-sm transition" :class="route.query.status === status.name ? 'text-white bg-indigo-500 font-bold shadow' : 'text-gray-300 hover:text-white hover:bg-slate-700'">
                            {{ status.name }} Orders
                        </router-link>
                    </div>
                </div>

                <div>
                    <button @click="toggleProductMenu" class="w-full flex items-center justify-between p-3 hover:bg-indigo-600 rounded-lg transition focus:outline-none" :class="isProductMenuOpen || route.path.startsWith('/admin/products') ? 'bg-slate-800' : ''">
                        <div class="flex items-center">
                            <span class="text-xl w-6 text-center">📦</span>
                            <span v-if="isSidebarOpen" class="ml-3 font-medium">Products</span>
                        </div>
                        <svg v-if="isSidebarOpen" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200" :class="isProductMenuOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div v-show="isSidebarOpen && isProductMenuOpen" class="mt-1 space-y-1 bg-slate-800/40 rounded-lg overflow-hidden py-1">
                        <router-link to="/admin/products" class="flex items-center pl-12 pr-4 py-2.5 text-sm transition" :class="route.path === '/admin/products' ? 'text-white bg-indigo-500 font-bold shadow' : 'text-gray-300 hover:text-white hover:bg-slate-700'">
                            Product List
                        </router-link>
                        <router-link to="/admin/products/create" class="flex items-center pl-12 pr-4 py-2.5 text-sm transition" :class="route.path === '/admin/products/create' ? 'text-white bg-indigo-500 font-bold shadow' : 'text-gray-300 hover:text-white hover:bg-slate-700'">
                            Add New Product
                        </router-link>
                    </div>
                </div>

                <router-link to="/admin/categories" class="flex items-center p-3 hover:bg-indigo-600 rounded-lg transition" :class="route.path.startsWith('/admin/categories') ? 'bg-indigo-600 shadow-md' : ''">
                    <span class="text-xl w-6 text-center">📂</span>
                    <span v-if="isSidebarOpen" class="ml-3 font-medium">Categories</span>
                </router-link>

                <div>
                    <button @click="toggleAttributeMenu" class="w-full flex items-center justify-between p-3 hover:bg-indigo-600 rounded-lg transition focus:outline-none" :class="isAttributeMenuOpen || route.path.startsWith('/admin/brands') || route.path.startsWith('/admin/colors') || route.path.startsWith('/admin/sizes') ? 'bg-slate-800' : ''">
                        <div class="flex items-center">
                            <span class="text-xl w-6 text-center">🎨</span>
                            <span v-if="isSidebarOpen" class="ml-3 font-medium">Attributes</span>
                        </div>
                        <svg v-if="isSidebarOpen" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200" :class="isAttributeMenuOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div v-show="isSidebarOpen && isAttributeMenuOpen" class="mt-1 space-y-1 bg-slate-800/40 rounded-lg overflow-hidden py-1">
                        <router-link to="/admin/brands" class="flex items-center pl-12 pr-4 py-2.5 text-sm transition" :class="route.path === '/admin/brands' ? 'text-white bg-[#2563eb] font-bold shadow' : 'text-gray-300 hover:text-white hover:bg-slate-700'">
                            Brands
                        </router-link>
                        <router-link to="/admin/colors" class="flex items-center pl-12 pr-4 py-2.5 text-sm transition" :class="route.path === '/admin/colors' ? 'text-white bg-[#2563eb] font-bold shadow' : 'text-gray-300 hover:text-white hover:bg-slate-700'">
                            Colors
                        </router-link>
                        <router-link to="/admin/sizes" class="flex items-center pl-12 pr-4 py-2.5 text-sm transition" :class="route.path === '/admin/sizes' ? 'text-white bg-[#2563eb] font-bold shadow' : 'text-gray-300 hover:text-white hover:bg-slate-700'">
                            Sizes
                        </router-link>
                    </div>
                </div>

                <div>
                    <button @click="toggleBannerMenu" class="w-full flex items-center justify-between p-3 hover:bg-indigo-600 rounded-lg transition focus:outline-none" :class="isBannerMenuOpen || route.path.startsWith('/admin/banner') ? 'bg-slate-800' : ''">
                        <div class="flex items-center">
                            <span class="text-xl w-6 text-center">🪧</span>
                            <span v-if="isSidebarOpen" class="ml-3 font-medium">Banner & Ads</span>
                        </div>
                        <svg v-if="isSidebarOpen" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200" :class="isBannerMenuOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div v-show="isSidebarOpen && isBannerMenuOpen" class="mt-1 space-y-1 bg-slate-800/40 rounded-lg overflow-hidden py-1">
                        <router-link to="/admin/banner-categories" class="flex items-center pl-12 pr-4 py-2.5 text-sm transition" :class="route.path === '/admin/banner-categories' ? 'text-white bg-[#2563eb] font-bold shadow' : 'text-gray-300 hover:text-white hover:bg-slate-700'">
                            Banner Category
                        </router-link>
                        <router-link to="/admin/banners" class="flex items-center pl-12 pr-4 py-2.5 text-sm transition" :class="route.path === '/admin/banners' ? 'text-white bg-[#2563eb] font-bold shadow' : 'text-gray-300 hover:text-white hover:bg-slate-700'">
                            Banner & Ads
                        </router-link>
                    </div>
                </div>

                <router-link to="/admin/order-statuses" class="flex items-center p-3 hover:bg-indigo-600 rounded-lg transition" :class="route.path.startsWith('/admin/order-statuses') ? 'bg-indigo-600 shadow-md' : ''">
                    <span class="text-xl w-6 text-center">🏷️</span>
                    <span v-if="isSidebarOpen" class="ml-3 font-medium">Order Statuses</span>
                </router-link>

                <router-link to="/admin/media" class="flex items-center p-3 hover:bg-indigo-600 rounded-lg transition" :class="route.path.startsWith('/admin/media') ? 'bg-indigo-600 shadow-md' : ''">
                    <span class="text-xl w-6 text-center">📸</span>
                    <span v-if="isSidebarOpen" class="ml-3 font-medium">Photo Gallery</span>
                </router-link>

                <router-link to="/admin/api-integration" class="flex items-center p-3 hover:bg-indigo-600 rounded-lg transition" :class="route.path.startsWith('/admin/api-integration') ? 'bg-indigo-600 shadow-md' : ''">
                    <span class="text-xl w-6 text-center">🔌</span>
                    <span v-if="isSidebarOpen" class="ml-3 font-medium">API Integration</span>
                </router-link>

                <router-link to="/admin/site-settings" class="flex items-center p-3 hover:bg-indigo-600 rounded-lg transition" :class="route.path.startsWith('/admin/site-settings') ? 'bg-indigo-600 shadow-md' : ''">
                    <span class="text-xl w-6 text-center">⚙️</span>
                    <span v-if="isSidebarOpen" class="ml-3 font-medium">Site Settings</span>
                </router-link>

            </nav>
        </aside>

        <div class="flex-1 flex flex-col min-h-screen transition-all duration-300" :class="isSidebarOpen ? 'ml-64' : 'ml-20'">
            <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8 sticky top-0 z-40 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-700">Welcome, <span class="text-indigo-600 font-bold">{{ userName }}</span></h2>
                <div class="flex items-center space-x-4">
                    <button @click="logout" class="flex items-center space-x-2 bg-red-50 hover:bg-red-100 text-red-600 px-4 py-2 rounded-lg font-medium transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        <span>Logout</span>
                    </button>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #475569; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-track { background-color: transparent; }
</style>
