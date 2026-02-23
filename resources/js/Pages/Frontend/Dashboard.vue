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
    SfIconArrowBack
} from '@storefront-ui/vue';

const router = useRouter();
const authStore = useAuthStore();
const activeTab = ref('overview');

const backendUrl = 'http://127.0.0.1:73';

// যদি ইউজার লগিন করা না থাকে, তাহলে লগিন পেজে পাঠিয়ে দিবে
onMounted(() => {
    if (!authStore.isAuthenticated) {
        router.push('/login');
    }
});

const handleLogout = async () => {
    try {
        // ব্যাকএন্ডে লগআউট রিকোয়েস্ট পাঠানো (ঐচ্ছিক)
        await axios.post(`${backendUrl}/api/logout`, {}, {
            headers: { Authorization: `Bearer ${authStore.token}` }
        });
    } catch (error) {
        console.error("Logout failed on server:", error);
    } finally {
        // ফ্রন্টএন্ড থেকে টোকেন এবং ইউজার ডিলিট করা
        authStore.logout();
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Logged out successfully! 👋',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
        });
        router.push('/login');
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
                            <p class="text-xs font-medium text-slate-500 truncate w-full text-center">{{ authStore.user?.email }}</p>
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

                        <div v-if="activeTab === 'overview'" class="animate-fade-in">
                            <h2 class="text-2xl font-black text-slate-800 mb-6">Welcome back, {{ authStore.user?.name?.split(' ')[0] || 'User' }}! 👋</h2>
                            <p class="text-slate-500 mb-10 font-medium">From your account dashboard, you can view your recent orders, manage your shipping addresses, and edit your password and account details.</p>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div @click="activeTab = 'orders'" class="bg-indigo-50/50 border border-indigo-100 rounded-2xl p-6 flex items-center gap-4 cursor-pointer hover:shadow-md hover:border-indigo-300 transition-all group">
                                    <div class="w-14 h-14 bg-white rounded-full flex items-center justify-center text-indigo-600 shadow-sm group-hover:scale-110 transition-transform">
                                        <SfIconShoppingCart />
                                    </div>
                                    <div>
                                        <h3 class="font-black text-xl text-slate-800">Orders</h3>
                                        <p class="text-sm font-medium text-slate-500">View history</p>
                                    </div>
                                </div>
                                <div @click="activeTab = 'wishlist'" class="bg-amber-50/50 border border-amber-100 rounded-2xl p-6 flex items-center gap-4 cursor-pointer hover:shadow-md hover:border-amber-300 transition-all group">
                                    <div class="w-14 h-14 bg-white rounded-full flex items-center justify-center text-amber-500 shadow-sm group-hover:scale-110 transition-transform">
                                        <SfIconFavorite />
                                    </div>
                                    <div>
                                        <h3 class="font-black text-xl text-slate-800">Wishlist</h3>
                                        <p class="text-sm font-medium text-slate-500">Saved items</p>
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

                        <div v-if="activeTab !== 'overview'" class="flex flex-col items-center justify-center h-full py-20 animate-fade-in text-center">
                            <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center text-5xl mb-6 text-slate-300">
                                🚧
                            </div>
                            <h3 class="text-2xl font-black text-slate-800 mb-2 capitalize">{{ activeTab }}</h3>
                            <p class="text-slate-500 font-medium">This section is currently under construction. We will add data here soon!</p>
                            <SfButton @click="activeTab = 'overview'" class="mt-8 bg-indigo-600 hover:bg-indigo-700">Back to Overview</SfButton>
                        </div>

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
