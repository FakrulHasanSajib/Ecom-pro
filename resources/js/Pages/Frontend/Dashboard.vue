<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useAuthStore } from '../../stores/auth';
import {
    SfButton,
    SfIconPerson,
    SfIconShoppingCart,
    SfIconFavorite,
    SfIconLocationOn,
    SfIconLogout,
    SfIconArrowBack,
    SfIconClose,
    SfLoaderCircular
} from '@storefront-ui/vue';

const router = useRouter();
const authStore = useAuthStore();
const activeTab = ref('overview');

const backendUrl = 'http://127.0.0.1:73';

// State for Data
const orders = ref([]);
const wishlists = ref([]);
const isLoadingData = ref(false);

// ডাটা ফেচ করার ফাংশন (আপডেটেড: Array.isArray চেক সহ)
const fetchCustomerData = async () => {
    isLoadingData.value = true;
    try {
        const config = {
            headers: { Authorization: `Bearer ${authStore.token}` }
        };

        // Orders এবং Wishlist ডাটা ব্যাকএন্ড থেকে কল করা
        const [ordersRes, wishlistRes] = await Promise.all([
            axios.get(`${backendUrl}/api/orders`, config).catch(() => ({ data: [] })),
            axios.get(`${backendUrl}/api/wishlist`, config).catch(() => ({ data: [] }))
        ]);

        // 🔥 ডাটাগুলো আসলেই অ্যারে (Array) কি না তা চেক করা হচ্ছে
        const oData = ordersRes.data?.data || ordersRes.data;
        orders.value = Array.isArray(oData) ? oData : [];

        const wData = wishlistRes.data?.data || wishlistRes.data;
        wishlists.value = Array.isArray(wData) ? wData : [];

    } catch (error) {
        console.error("Failed to fetch dashboard data:", error);
        orders.value = [];
        wishlists.value = [];
    } finally {
        isLoadingData.value = false;
    }
};

// ইমেজ পাথ ঠিক করার ফাংশন
const getImageUrl = (path) => {
    if (!path) return 'https://placehold.co/400x400?text=No+Image';
    return path.startsWith('http') ? path : `${backendUrl}/storage/${path}`;
};

// কম্পোনেন্ট মাউন্ট হলে চেক করবে লগিন আছে কি না, থাকলে ডাটা আনবে
onMounted(() => {
    if (!authStore.isAuthenticated) {
        window.location.href = '/login';
    } else {
        fetchCustomerData();
    }
});

const handleLogout = async () => {
    try {
        await axios.post(`${backendUrl}/api/logout`, {}, {
            headers: { Authorization: `Bearer ${authStore.token}` }
        });
    } catch (error) {
        console.error("Logout failed on server:", error);
    } finally {
        authStore.logout();
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Logged out successfully! 👋',
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true,
        });

        setTimeout(() => {
            window.location.href = '/login';
        }, 1500);
    }
};

const goBack = () => router.push('/');
</script>

<template>
    <div class="bg-slate-50 min-h-screen font-sans">

        <header class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
                <button @click="goBack" class="flex items-center gap-2 text-slate-500 hover:text-indigo-600 font-bold transition-colors">
                    <SfIconArrowBack size="sm" /> Back to Shop
                </button>
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🛍️</span>
                    <span class="font-black text-xl text-indigo-700 tracking-tight">E-Shop</span>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 py-8 md:py-12">

            <div class="flex flex-col md:flex-row gap-8">

                <aside class="w-full md:w-1/4 lg:w-1/5">
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 sticky top-24">
                        <div class="flex flex-col items-center mb-8 border-b border-slate-100 pb-6">
                            <div class="w-20 h-20 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center font-black text-3xl shadow-inner border-2 border-indigo-100 mb-3">
                                {{ authStore.user?.name ? authStore.user.name.charAt(0).toUpperCase() : 'U' }}
                            </div>
                            <h2 class="font-bold text-slate-800 text-lg text-center">{{ authStore.user?.name || 'Customer' }}</h2>
                            <p class="text-xs font-medium text-slate-500 truncate w-full text-center">{{ authStore.user?.email || 'No email provided' }}</p>
                        </div>

                        <nav class="space-y-2">
                            <button @click="activeTab = 'overview'" :class="{'bg-indigo-50 text-indigo-600 border-indigo-200': activeTab === 'overview', 'text-slate-600 hover:bg-slate-50 border-transparent': activeTab !== 'overview'}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-bold transition-all border">
                                <SfIconPerson size="sm" /> Overview
                            </button>
                            <button @click="activeTab = 'orders'" :class="{'bg-indigo-50 text-indigo-600 border-indigo-200': activeTab === 'orders', 'text-slate-600 hover:bg-slate-50 border-transparent': activeTab !== 'orders'}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-bold transition-all border">
                                <SfIconShoppingCart size="sm" /> My Orders
                            </button>
                            <button @click="activeTab = 'wishlist'" :class="{'bg-indigo-50 text-indigo-600 border-indigo-200': activeTab === 'wishlist', 'text-slate-600 hover:bg-slate-50 border-transparent': activeTab !== 'wishlist'}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-bold transition-all border">
                                <SfIconFavorite size="sm" /> Wishlist
                            </button>
                            <button @click="activeTab = 'addresses'" :class="{'bg-indigo-50 text-indigo-600 border-indigo-200': activeTab === 'addresses', 'text-slate-600 hover:bg-slate-50 border-transparent': activeTab !== 'addresses'}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-bold transition-all border">
                                <SfIconLocationOn size="sm" /> Addresses
                            </button>
                        </nav>

                        <div class="mt-8 pt-6 border-t border-slate-100">
                            <button @click="handleLogout" class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-xl font-bold text-red-500 hover:bg-red-50 hover:text-red-600 transition-all border border-transparent hover:border-red-100">
                                <SfIconLogout size="sm" /> Logout
                            </button>
                        </div>
                    </div>
                </aside>

                <div class="w-full md:w-3/4 lg:w-4/5">
                    <div class="bg-white rounded-[2rem] p-8 md:p-12 shadow-sm border border-slate-100 min-h-[600px]">

                        <div v-if="isLoadingData" class="flex flex-col items-center justify-center h-full py-20">
                            <SfLoaderCircular size="xl" class="text-indigo-600 mb-4" />
                            <p class="text-slate-500 font-medium animate-pulse">Loading your data...</p>
                        </div>

                        <template v-else>
                            <div v-if="activeTab === 'overview'" class="animate-fade-in">
                                <h2 class="text-2xl font-black text-slate-800 mb-6">Welcome back, {{ authStore.user?.name?.split(' ')[0] || 'User' }}! 👋</h2>
                                <p class="text-slate-500 mb-10 font-medium">From your account dashboard, you can view your recent orders, manage your shipping addresses, and edit your password and account details.</p>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div @click="activeTab = 'orders'" class="bg-indigo-50/50 border border-indigo-100 rounded-2xl p-6 flex items-center gap-4 cursor-pointer hover:shadow-md hover:border-indigo-300 transition-all group">
                                        <div class="w-14 h-14 bg-white rounded-full flex items-center justify-center text-indigo-600 shadow-sm group-hover:scale-110 transition-transform">
                                            <SfIconShoppingCart />
                                        </div>
                                        <div>
                                            <h3 class="font-black text-xl text-slate-800">{{ orders.length }} Orders</h3>
                                            <p class="text-sm font-medium text-slate-500">View history</p>
                                        </div>
                                    </div>
                                    <div @click="activeTab = 'wishlist'" class="bg-amber-50/50 border border-amber-100 rounded-2xl p-6 flex items-center gap-4 cursor-pointer hover:shadow-md hover:border-amber-300 transition-all group">
                                        <div class="w-14 h-14 bg-white rounded-full flex items-center justify-center text-amber-500 shadow-sm group-hover:scale-110 transition-transform">
                                            <SfIconFavorite />
                                        </div>
                                        <div>
                                            <h3 class="font-black text-xl text-slate-800">{{ wishlists.length }} Items</h3>
                                            <p class="text-sm font-medium text-slate-500">In wishlist</p>
                                        </div>
                                    </div>
                                    <div @click="activeTab = 'addresses'" class="bg-emerald-50/50 border border-emerald-100 rounded-2xl p-6 flex items-center gap-4 cursor-pointer hover:shadow-md hover:border-emerald-300 transition-all group">
                                        <div class="w-14 h-14 bg-white rounded-full flex items-center justify-center text-emerald-500 shadow-sm group-hover:scale-110 transition-transform">
                                            <SfIconLocationOn />
                                        </div>
                                        <div>
                                            <h3 class="font-black text-xl text-slate-800">Addresses</h3>
                                            <p class="text-sm font-medium text-slate-500">Manage info</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="activeTab === 'orders'" class="animate-fade-in">
                                <h2 class="text-2xl font-black text-slate-800 mb-6 border-b pb-4">My Orders</h2>

                                <div v-if="orders.length === 0" class="text-center py-16 bg-slate-50 rounded-2xl border border-slate-100">
                                    <div class="text-5xl mb-4">🛒</div>
                                    <h3 class="text-lg font-bold text-slate-700">No orders found</h3>
                                    <p class="text-slate-500 text-sm mt-2">Looks like you haven't made any purchases yet.</p>
                                    <SfButton @click="goBack" class="mt-6 bg-indigo-600 hover:bg-indigo-700">Start Shopping</SfButton>
                                </div>

                                <div v-else class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr class="bg-slate-50 text-slate-600 text-sm uppercase tracking-wider">
                                                <th class="p-4 rounded-tl-xl">Order ID</th>
                                                <th class="p-4">Date</th>
                                                <th class="p-4">Status</th>
                                                <th class="p-4">Total</th>
                                                <th class="p-4 rounded-tr-xl">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-slate-700 text-sm">
                                            <tr v-for="order in orders" :key="order.id" class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                                <td class="p-4 font-bold text-indigo-600">#{{ order.order_number || order.id }}</td>
                                                <td class="p-4">{{ new Date(order.created_at).toLocaleDateString() }}</td>
                                                <td class="p-4">
                                                    <span class="px-3 py-1 rounded-full text-xs font-bold"
                                                          :class="{'bg-emerald-100 text-emerald-700': order.status === 'Delivered', 'bg-amber-100 text-amber-700': order.status === 'Pending', 'bg-blue-100 text-blue-700': order.status === 'Processing'}">
                                                        {{ order.status || 'Pending' }}
                                                    </span>
                                                </td>
                                                <td class="p-4 font-bold">৳{{ order.total_amount || 0 }}</td>
                                                <td class="p-4">
                                                    <button class="text-indigo-600 hover:text-indigo-800 font-bold underline text-xs">View Details</button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div v-if="activeTab === 'wishlist'" class="animate-fade-in">
                                <h2 class="text-2xl font-black text-slate-800 mb-6 border-b pb-4">My Wishlist</h2>

                                <div v-if="wishlists.length === 0" class="text-center py-16 bg-slate-50 rounded-2xl border border-slate-100">
                                    <div class="text-5xl mb-4">❤️</div>
                                    <h3 class="text-lg font-bold text-slate-700">Your wishlist is empty</h3>
                                    <p class="text-slate-500 text-sm mt-2">Save items you like to your wishlist.</p>
                                    <SfButton @click="goBack" class="mt-6 bg-indigo-600 hover:bg-indigo-700">Explore Products</SfButton>
                                </div>

                                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div v-for="item in wishlists" :key="item.id" class="bg-white border border-slate-100 rounded-2xl p-4 hover:shadow-lg transition-shadow flex flex-col">
                                        <div class="bg-slate-50 h-40 rounded-xl mb-4 p-2 flex items-center justify-center overflow-hidden relative">
                                            <img :src="getImageUrl(item.product?.thumbnail)" class="max-h-full object-contain" />
                                        </div>
                                        <h3 class="font-bold text-slate-800 text-sm mb-2 line-clamp-2">{{ item.product?.name || 'Unknown Product' }}</h3>
                                        <div class="flex justify-between items-center mt-auto pt-4">
                                            <span class="font-black text-indigo-600">৳{{ item.product?.sale_price || item.product?.base_price || 0 }}</span>
                                            <button class="w-8 h-8 rounded-full bg-red-50 text-red-500 flex items-center justify-center hover:bg-red-500 hover:text-white transition-colors">
                                                <SfIconClose size="xs" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="activeTab === 'addresses'" class="flex flex-col items-center justify-center py-16 animate-fade-in text-center bg-slate-50 rounded-2xl border border-slate-100">
                                <div class="w-20 h-20 bg-indigo-100 rounded-full flex items-center justify-center text-4xl mb-4 text-indigo-500">
                                    📍
                                </div>
                                <h3 class="text-xl font-black text-slate-800 mb-2">Saved Addresses</h3>
                                <p class="text-slate-500 font-medium text-sm max-w-sm">You haven't added any shipping addresses yet. This feature will be available soon.</p>
                            </div>

                        </template>
                    </div>
                </div>

            </div>
        </main>
    </div>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-in-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
