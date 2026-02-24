<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const isSidebarOpen = ref(true);
const isProductMenuOpen = ref(false);
const isOrderMenuOpen = ref(false); // 🔥 অর্ডারের সাব-মেনু খোলার জন্য

const router = useRouter();
const route = useRoute();

const userName = ref(localStorage.getItem('user_name') || 'Admin');
const orderStatuses = ref([]); // 🔥 ডাইনামিক স্ট্যাটাস রাখার জন্য

onMounted(async () => {
    // মেনু ওপেন রাখা
    if (route.path.startsWith('/admin/products')) isProductMenuOpen.value = true;
    if (route.path.startsWith('/admin/orders')) isOrderMenuOpen.value = true;

    // ডাটাবেস থেকে ডাইনামিক স্ট্যাটাসগুলো সাইডবারের জন্য কল করা
    try {
        const token = localStorage.getItem('token');
        const res = await axios.get('http://127.0.0.1:73/api/admin/order-statuses', {
            headers: { Authorization: `Bearer ${token}` }
        });
        orderStatuses.value = res.data.data || [];
    } catch (error) {
        console.error("Failed to fetch order statuses for sidebar");
    }
});

const toggleProductMenu = () => {
    if (!isSidebarOpen.value) {
        isSidebarOpen.value = true;
        isProductMenuOpen.value = true;
    } else {
        isProductMenuOpen.value = !isProductMenuOpen.value;
    }
};

const toggleOrderMenu = () => {
    if (!isSidebarOpen.value) {
        isSidebarOpen.value = true;
        isOrderMenuOpen.value = true;
    } else {
        isOrderMenuOpen.value = !isOrderMenuOpen.value;
    }
};

const logout = async () => {
    try {
        const result = await Swal.fire({
            title: 'Logout?', text: "Are you sure you want to log out?", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#4f46e5', cancelButtonColor: '#ef4444', confirmButtonText: 'Yes, Logout'
        });

        if (result.isConfirmed) {
            await axios.post('/api/logout');
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
        <aside :class="isSidebarOpen ? 'w-64' : 'w-20'" class="fixed left-0 top-0 h-full bg-slate-900 text-white transition-all duration-300 z-50 flex flex-col">
            <div class="p-4 flex items-center justify-between border-b border-slate-700 h-16">
                <span v-if="isSidebarOpen" class="text-xl font-bold truncate">Admin Panel</span>
                <button @click="isSidebarOpen = !isSidebarOpen" class="p-1 hover:bg-slate-800 rounded focus:outline-none ml-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                    </svg>
                </button>
            </div>

            <nav class="mt-4 px-2 space-y-2 flex-1 overflow-y-auto custom-scrollbar">
                <router-link to="/admin/dashboard" class="flex items-center p-3 hover:bg-indigo-600 rounded-lg transition" :class="{'bg-indigo-600': route.path === '/admin/dashboard'}">
                    <span class="text-xl w-6 text-center">📊</span>
                    <span v-if="isSidebarOpen" class="ml-3 font-medium">Dashboard</span>
                </router-link>

                <div>
                    <button @click="toggleOrderMenu" class="w-full flex items-center justify-between p-3 hover:bg-indigo-600 rounded-lg transition focus:outline-none" :class="{'bg-slate-800': isOrderMenuOpen || route.path.startsWith('/admin/orders')}">
                        <div class="flex items-center">
                            <span class="text-xl w-6 text-center">🛒</span>
                            <span v-if="isSidebarOpen" class="ml-3 font-medium">Orders</span>
                        </div>
                        <svg v-if="isSidebarOpen" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200" :class="{'rotate-180': isOrderMenuOpen}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div v-show="isSidebarOpen && isOrderMenuOpen" class="mt-1 space-y-1 bg-slate-800/50 rounded-lg overflow-hidden">
                        <router-link to="/admin/orders" class="flex items-center pl-12 pr-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-indigo-500 transition" :class="{'text-white bg-indigo-500': route.path === '/admin/orders' && !route.query.status}">
                            All Orders
                        </router-link>
                        <router-link v-for="status in orderStatuses" :key="status.id" :to="`/admin/orders?status=${status.name}`" class="flex items-center pl-12 pr-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-indigo-500 transition" :class="{'text-white bg-indigo-500': route.query.status === status.name}">
                            {{ status.name }} Orders
                        </router-link>
                    </div>
                </div>

                <div>
                    <button @click="toggleProductMenu" class="w-full flex items-center justify-between p-3 hover:bg-indigo-600 rounded-lg transition focus:outline-none" :class="{'bg-slate-800': isProductMenuOpen || route.path.startsWith('/admin/products')}">
                        <div class="flex items-center">
                            <span class="text-xl w-6 text-center">📦</span>
                            <span v-if="isSidebarOpen" class="ml-3 font-medium">Products</span>
                        </div>
                        <svg v-if="isSidebarOpen" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200" :class="{'rotate-180': isProductMenuOpen}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div v-show="isSidebarOpen && isProductMenuOpen" class="mt-1 space-y-1 bg-slate-800/50 rounded-lg overflow-hidden">
                        <router-link to="/admin/products" class="flex items-center pl-12 pr-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-indigo-500 transition" :class="{'text-white bg-indigo-500': route.path === '/admin/products'}">Product List</router-link>
                        <router-link to="/admin/products/create" class="flex items-center pl-12 pr-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-indigo-500 transition" :class="{'text-white bg-indigo-500': route.path === '/admin/products/create'}">Add New Product</router-link>
                    </div>
                </div>

                <router-link to="/admin/categories" class="flex items-center p-3 hover:bg-indigo-600 rounded-lg transition" :class="{'bg-indigo-600': route.path === '/admin/categories'}">
                    <span class="text-xl w-6 text-center">📂</span>
                    <span v-if="isSidebarOpen" class="ml-3 font-medium">Categories</span>
                </router-link>

                <router-link to="/admin/order-statuses" class="flex items-center p-3 hover:bg-indigo-600 rounded-lg transition" :class="{'bg-indigo-600': route.path === '/admin/order-statuses'}">
                    <span class="text-xl w-6 text-center">🏷️</span>
                    <span v-if="isSidebarOpen" class="ml-3 font-medium">Order Statuses</span>
                </router-link>

                <router-link to="/admin/settings" class="flex items-center p-3 hover:bg-indigo-600 rounded-lg transition" :class="{'bg-indigo-600': route.path === '/admin/settings'}">
                    <span class="text-xl w-6 text-center">⚙️</span>
                    <span v-if="isSidebarOpen" class="ml-3 font-medium">Settings</span>
                </router-link>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col min-h-screen transition-all duration-300" :class="isSidebarOpen ? 'ml-64' : 'ml-20'">
            <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8 sticky top-0 z-40 border-b">
                <h2 class="text-lg font-semibold text-gray-700">Welcome, <span class="text-indigo-600">{{ userName }}</span></h2>
                <div class="flex items-center space-x-4">
                    <button @click="logout" class="flex items-center space-x-1 text-red-500 font-medium hover:text-red-700 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        <span>Logout</span>
                    </button>
                </div>
            </header>

            <main class="p-8 flex-1 overflow-x-hidden">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.router-link-active:not(.w-full) { background-color: #4f46e5; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #4b5563; border-radius: 10px; }
</style>
