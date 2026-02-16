<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object
});
</script>

<template>
    <Head title="Dashboard" />
    <AdminLayout>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-gray-500 text-sm">Total Sales</p>
                <h3 class="text-2xl font-bold text-gray-800">৳ {{ stats?.total_sales || 0 }}</h3>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-gray-500 text-sm">Total Orders</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ stats?.total_orders || 0 }}</h3>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-gray-500 text-sm">Products</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ stats?.total_products || 0 }}</h3>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-gray-500 text-sm">Low Stock</p>
                <h3 class="text-2xl font-bold text-red-600">{{ stats?.low_stock_products || 0 }}</h3>
            </div>
        </div>

        <div class="mt-8 bg-white p-6 rounded-xl shadow-sm">
            <h3 class="font-bold text-lg mb-4">Recent Orders</h3>
            <p class="text-gray-400">Order list will appear here...</p>
        </div>
    </AdminLayout>
</template>
