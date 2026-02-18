<script setup>
import { ref, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const isSidebarOpen = ref(true);
const isProductMenuOpen = ref(false);
const page = usePage();

// পেজ লোড হলে যদি আমরা প্রোডাক্ট রিলেটেড কোনো পেজে থাকি, মেনু ওপেন থাকবে
onMounted(() => {
    if (page.url.startsWith('/admin/products')) {
        isProductMenuOpen.value = true;
    }
});

// প্রোডাক্ট মেনু টগল করার ফাংশন
const toggleProductMenu = () => {
    // সাইডবার বন্ধ থাকলে আগে সাইডবার খুলবে, তারপর মেনু খুলবে
    if (!isSidebarOpen.value) {
        isSidebarOpen.value = true;
        isProductMenuOpen.value = true;
    } else {
        isProductMenuOpen.value = !isProductMenuOpen.value;
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

            <nav class="mt-4 px-2 space-y-2 flex-1 overflow-y-auto">

                <Link href="/admin/dashboard" class="flex items-center p-3 hover:bg-indigo-600 rounded-lg transition" :class="{'bg-indigo-600': $page.url === '/admin/dashboard'}">
                    <span class="text-xl w-6 text-center">📊</span>
                    <span v-if="isSidebarOpen" class="ml-3 font-medium">Dashboard</span>
                </Link>

                <div>
                    <button @click="toggleProductMenu"
                            class="w-full flex items-center justify-between p-3 hover:bg-indigo-600 rounded-lg transition focus:outline-none"
                            :class="{'bg-slate-800': isProductMenuOpen || $page.url.startsWith('/admin/products')}">
                        <div class="flex items-center">
                            <span class="text-xl w-6 text-center">📦</span>
                            <span v-if="isSidebarOpen" class="ml-3 font-medium">Products</span>
                        </div>
                        <svg v-if="isSidebarOpen"
                             xmlns="http://www.w3.org/2000/svg"
                             class="h-4 w-4 transition-transform duration-200"
                             :class="{'rotate-180': isProductMenuOpen}"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div v-show="isSidebarOpen && isProductMenuOpen" class="mt-1 space-y-1 bg-slate-800/50 rounded-lg overflow-hidden">
                        <Link href="/admin/products"
                              class="flex items-center pl-12 pr-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-indigo-500 transition"
                              :class="{'text-white bg-indigo-500': $page.url === '/admin/products'}">
                            Product List
                        </Link>
                        <Link href="/admin/products/create"
                              class="flex items-center pl-12 pr-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-indigo-500 transition"
                              :class="{'text-white bg-indigo-500': $page.url === '/admin/products/create'}">
                            Add New Product
                        </Link>
                    </div>
                </div>

                <Link href="/admin/categories" class="flex items-center p-3 hover:bg-indigo-600 rounded-lg transition">
                    <span class="text-xl w-6 text-center">📂</span>
                    <span v-if="isSidebarOpen" class="ml-3 font-medium">Categories</span>
                </Link>

                <Link href="/admin/settings" class="flex items-center p-3 hover:bg-indigo-600 rounded-lg transition">
                    <span class="text-xl w-6 text-center">⚙️</span>
                    <span v-if="isSidebarOpen" class="ml-3 font-medium">Settings</span>
                </Link>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col min-h-screen transition-all duration-300"
             :class="isSidebarOpen ? 'ml-64' : 'ml-20'">

            <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8 sticky top-0 z-40">
                <h2 class="text-lg font-semibold text-gray-700">
                    Welcome, {{ $page.props.auth?.user?.name }}
                </h2>
                <div class="flex items-center space-x-4">
                    <Link href="/logout" method="post" as="button" class="text-red-500 font-medium hover:text-red-700 transition">
                        Logout
                    </Link>
                </div>
            </header>

            <main class="p-8 flex-1 overflow-x-hidden">
                <slot />
            </main>
        </div>
    </div>
</template>
