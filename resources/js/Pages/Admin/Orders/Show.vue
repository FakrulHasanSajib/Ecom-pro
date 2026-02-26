<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const orderId = route.params.id; // URL থেকে ID নেওয়া হচ্ছে
const order = ref({});
const loading = ref(true);

const API_URL = 'http://127.0.0.1:73/api/admin';

// অর্ডার ডাটা ফেচ করা
const fetchOrder = async () => {
    try {
        const token = localStorage.getItem('token');
        const res = await axios.get(`${API_URL}/orders/${orderId}`, {
            headers: { Authorization: `Bearer ${token}` }
        });
        order.value = res.data.data || {};
    } catch (error) {
        console.error("Failed to load order details:", error);
    } finally {
        loading.value = false;
    }
};

// স্ট্যাটাস কালার
const getStatusBadgeClass = (status) => {
    if (!status) return 'bg-slate-100 text-slate-700';
    const s = status.toLowerCase();
    if (s === 'pending') return 'bg-amber-100 text-amber-700 border border-amber-200';
    if (s === 'processing') return 'bg-blue-100 text-blue-700 border border-blue-200';
    if (s === 'shipped') return 'bg-indigo-100 text-indigo-700 border border-indigo-200';
    if (s === 'delivered') return 'bg-emerald-100 text-emerald-700 border border-emerald-200';
    if (s === 'cancelled' || s === 'canceled') return 'bg-red-100 text-red-700 border border-red-200';
    return 'bg-slate-100 text-slate-700 border border-slate-200';
};

// তারিখ ফরম্যাট
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' +
           date.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: true });
};

// প্রিন্ট করার ফাংশন
const printInvoice = () => {
    window.print();
};

onMounted(() => {
    fetchOrder();
});
</script>

<template>
    <AdminLayout>
        <div class="max-w-5xl mx-auto pb-10">
            <div v-if="loading" class="flex flex-col items-center justify-center py-20">
                <div class="w-12 h-12 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
                <span class="text-slate-500 font-bold mt-4 animate-pulse">Loading order details...</span>
            </div>

            <div v-else class="bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden print:shadow-none print:border-none">

                <div class="bg-slate-50 border-b border-slate-200 p-6 flex justify-between items-center print:hidden">
                    <button @click="router.push('/admin/orders')" class="text-slate-600 font-bold hover:text-indigo-600 transition flex items-center gap-2">
                        ← Back
                    </button>
                    <div class="flex gap-3">
                        <button @click="printInvoice" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-bold shadow-sm transition flex items-center gap-2">
                            🖨️ Print Invoice
                        </button>
                    </div>
                </div>

                <div class="p-8 print:p-0">
                    <div class="flex justify-between items-start mb-8 border-b pb-8">
                        <div>
                            <h2 class="text-3xl font-black text-slate-800">Order #{{ order.order_number || order.id }}</h2>
                            <p class="text-sm text-slate-500 font-medium mt-1">Date: {{ formatDate(order.created_at) }}</p>
                            <span class="inline-block mt-3 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider" :class="getStatusBadgeClass(order.status)">
                                {{ order.status }}
                            </span>
                        </div>
                        <div class="text-right">
                            <h3 class="text-xl font-black text-indigo-600">Your Store Name</h3>
                            <p class="text-sm text-slate-500 mt-1">Dhaka, Bangladesh</p>
                            <p class="text-sm text-slate-500">Email: support@yourstore.com</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                        <div class="bg-slate-50 p-5 rounded-xl border border-slate-100">
                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Billed To (Customer)</h4>
                            <p class="text-lg font-bold text-slate-800">{{ order.name }}</p>
                            <p class="text-sm font-medium text-slate-600 mt-1">📞 {{ order.phone }}</p>
                            <p class="text-sm font-medium text-slate-600 mt-1">📍 {{ order.address }}</p>
                        </div>
                        <div class="bg-slate-50 p-5 rounded-xl border border-slate-100">
                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Order Info</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500 font-medium">Payment Method:</span>
                                    <span class="font-bold text-slate-800">{{ order.payment_method || 'COD' }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500 font-medium">Order Source:</span>
                                    <span class="font-bold text-slate-800">{{ order.order_source || 'Website' }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500 font-medium">Assigned To:</span>
                                    <span class="font-bold text-slate-800">{{ order.user?.name || 'Unassigned' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h4 class="text-lg font-black text-slate-800 mb-4">Order Items</h4>
                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-100 text-slate-600 text-xs font-black uppercase tracking-wider">
                                    <th class="p-4">Product Name</th>
                                    <th class="p-4 text-center">Unit Price</th>
                                    <th class="p-4 text-center">Quantity</th>
                                    <th class="p-4 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="item in (order.items || order.order_items)" :key="item.id" class="text-sm">
                                    <td class="p-4 font-bold text-slate-800">{{ item.product_name || item.name }}</td>
                                    <td class="p-4 text-center text-slate-600 font-medium">৳{{ item.price }}</td>
                                    <td class="p-4 text-center font-black text-indigo-600">x{{ item.quantity }}</td>
                                    <td class="p-4 text-right font-bold text-slate-800">৳{{ item.price * item.quantity }}</td>
                                </tr>
                                <tr v-if="!(order.items || order.order_items) || (order.items || order.order_items).length === 0">
                                    <td colspan="4" class="p-6 text-center text-red-400 font-bold">No items found for this order.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end mt-6">
                        <div class="w-full md:w-1/3 space-y-3 bg-slate-50 p-5 rounded-xl border border-slate-100">
                            <div class="flex justify-between text-sm font-medium text-slate-600">
                                <span>Subtotal</span>
                                <span>৳{{ order.sub_total || 0 }}</span>
                            </div>
                            <div class="flex justify-between text-sm font-medium text-slate-600">
                                <span>Shipping Charge</span>
                                <span>৳{{ order.shipping_charge || 0 }}</span>
                            </div>
                            <div class="flex justify-between text-lg font-black text-emerald-600 pt-3 border-t border-slate-200">
                                <span>Grand Total</span>
                                <span>৳{{ order.grand_total || order.total_amount || 0 }}</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
@media print {
    body { background-color: white !important; }
    .print\:hidden { display: none !important; }
    .print\:shadow-none { box-shadow: none !important; }
    .print\:border-none { border: none !important; }
    .print\:p-0 { padding: 0 !important; }
}
</style>
