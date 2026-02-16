<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const isSidebarOpen = ref(true);
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <aside :class="isSidebarOpen ? 'w-64' : 'w-20'" class="fixed left-0 top-0 h-full bg-slate-900 text-white transition-all duration-300 z-50">
            <div class="p-4 flex items-center justify-between border-b border-slate-700">
                <span v-if="isSidebarOpen" class="text-xl font-bold">Admin Panel</span>
                <button @click="isSidebarOpen = !isSidebarOpen" class="p-1 hover:bg-slate-800 rounded">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                    </svg>
                </button>
            </div>

            <nav class="mt-4 px-2 space-y-2">
                <Link href="/admin/dashboard" class="flex items-center p-3 hover:bg-indigo-600 rounded-lg transition" :class="{'bg-indigo-600': $page.url === '/admin/dashboard'}">
                    <span class="mr-3">📊</span>
                    <span v-if="isSidebarOpen">Dashboard</span>
                </Link>
                <Link href="/admin/products" class="flex items-center p-3 hover:bg-indigo-600 rounded-lg transition">
                    <span class="mr-3">📦</span>
                    <span v-if="isSidebarOpen">Products</span>
                </Link>
                <Link href="/admin/categories" class="flex items-center p-3 hover:bg-indigo-600 rounded-lg transition">
                    <span class="mr-3">📂</span>
                    <span v-if="isSidebarOpen">Categories</span>
                </Link>
                <Link href="/admin/settings" class="flex items-center p-3 hover:bg-indigo-600 rounded-lg transition">
                    <span class="mr-3">⚙️</span>
                    <span v-if="isSidebarOpen">Settings</span>
                </Link>
            </nav>
        </aside>

        <div :class="isSidebarOpen ? 'ml-64' : 'ml-20'" class="transition-all duration-300">
            <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8">
                <h2 class="text-lg font-semibold text-gray-700">Welcome, {{ $page.props.auth?.user?.name }}</h2>
                <div class="flex items-center space-x-4">
                    <Link href="/logout" method="post" as="button" class="text-red-500 font-medium">Logout</Link>
                </div>
            </header>

            <main class="p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
