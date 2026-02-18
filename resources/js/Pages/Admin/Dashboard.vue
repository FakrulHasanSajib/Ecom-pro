<script setup>
import { ref, onMounted } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';

const stats = ref({
    total_sales: 0,
    total_orders: 0,
    total_products: 0,
    low_stock_products: 0,
    recent_orders: [] // রিসেন্ট অর্ডারের জন্য খালি অ্যারে
});

const loading = ref(true);

const fetchStats = async () => {
    try {
        const response = await axios.get('/api/admin/dashboard-stats');
        stats.value = response.data;
    } catch (error) {
        console.error("Dashboard data error:", error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchStats();
});
</script>

<template>
    <AdminLayout>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-gray-500 text-sm font-medium uppercase">Total Sales</p>
                <h3 class="text-2xl font-bold text-indigo-600 mt-2">৳ {{ stats.total_sales }}</h3>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-gray-500 text-sm font-medium uppercase">Total Orders</p>
                <h3 class="text-2xl font-bold text-gray-800 mt-2">{{ stats.total_orders }}</h3>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-gray-500 text-sm font-medium uppercase">Products</p>
                <h3 class="text-2xl font-bold text-gray-800 mt-2">{{ stats.total_products }}</h3>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-gray-500 text-sm font-medium uppercase text-red-500">Low Stock</p>
                <h3 class="text-2xl font-bold text-red-600 mt-2">{{ stats.low_stock_products }}</h3>
            </div>
        </div>

        <div class="mt-8 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="font-bold text-lg mb-4 text-gray-800">Recent Orders</h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-gray-400 text-sm uppercase border-b">
                            <th class="pb-3 font-semibold">Order ID</th>
                            <th class="pb-3 font-semibold">Customer</th>
                            <th class="pb-3 font-semibold">Amount</th>
                            <th class="pb-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="order in stats.recent_orders" :key="order.id" class="hover:bg-gray-50">
                            <td class="py-4 font-medium">#{{ order.id }}</td>
                            <td class="py-4">{{ order.user?.name || 'Guest' }}</td>
                            <td class="py-4">৳ {{ order.grand_total }}</td>
                            <td class="py-4">
                                <span :class="order.payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'" class="px-2 py-1 rounded text-xs font-bold uppercase">
                                    {{ order.payment_status }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="stats.recent_orders.length === 0">
                            <td colspan="4" class="py-10 text-center text-gray-400">No recent orders found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
