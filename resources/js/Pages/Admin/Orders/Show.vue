<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const route = useRoute();
const router = useRouter();
const orderId = route.params.id;
const order = ref({});
const settings = ref({});
const loading = ref(true);

const API_URL = 'http://127.0.0.1:73/api/admin';
const PUBLIC_API_URL = 'http://127.0.0.1:73/api/public';
const ASSET_URL = 'http://127.0.0.1:73/storage';

// সেটিংসের জন্য হেল্পার ফাংশন
const normalize = (str) => str ? String(str).replace(/[_-\s]+/g, '').toLowerCase() : '';
const getSetting = (group, key, defaultValue = '') => {
    if (!settings.value) return defaultValue;
    const targetGroup = Object.keys(settings.value).find(k => normalize(k) === normalize(group));
    if (targetGroup && Array.isArray(settings.value[targetGroup])) {
        const item = settings.value[targetGroup].find(s => normalize(s.key) === normalize(key) || normalize(s.name) === normalize(key));
        if (item && item.value !== null && item.value !== '') {
            return item.type === 'image' ? item.value_url : item.value;
        }
    }
    return defaultValue;
};

// ডাটা ফেচ করা (অর্ডার + সাইট সেটিংস)
const fetchOrderAndSettings = async () => {
    try {
        const token = localStorage.getItem('token');

        const [orderRes, settingsRes] = await Promise.all([
            axios.get(`${API_URL}/orders/${orderId}`, { headers: { Authorization: `Bearer ${token}` } }),
            axios.get(`${PUBLIC_API_URL}/settings`) // ডাইনামিক লোগো ও এড্রেস আনার জন্য
        ]);

        order.value = orderRes.data.data || {};
        settings.value = settingsRes.data?.data || {};

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

// কারেন্সি ফরম্যাট
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount || 0);
};

// প্রিন্ট ফাংশন
const printInvoice = () => {
    window.print();
};

onMounted(() => {
    fetchOrderAndSettings();
});
</script>

<template>
    <AdminLayout>
        <div class="max-w-5xl mx-auto pb-10">
            <div v-if="loading" class="flex flex-col items-center justify-center py-20">
                <div class="w-12 h-12 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
                <span class="text-slate-500 font-bold mt-4 animate-pulse">Loading Invoice...</span>
            </div>

            <div v-else>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6 flex justify-between items-center print:hidden">
                    <button @click="router.push('/admin/orders')" class="text-slate-600 font-bold hover:text-indigo-600 transition flex items-center gap-2 bg-slate-100 px-4 py-2 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" /></svg>
                        Back to Orders
                    </button>
                    <button @click="printInvoice" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg font-bold shadow-md shadow-indigo-500/30 transition flex items-center gap-2 active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                        Download PDF / Print
                    </button>
                </div>

                <div id="printable-invoice" class="bg-white rounded-xl shadow-lg border border-slate-200 p-8 md:p-12 relative print:shadow-none print:border-none print:p-0">

                    <div class="absolute inset-0 flex justify-center items-center opacity-[0.03] pointer-events-none print:opacity-[0.05]">
                        <img v-if="getSetting('general', 'site_logo')" :src="getSetting('general', 'site_logo')" class="w-1/2 grayscale" alt="">
                    </div>

                    <div class="flex flex-col md:flex-row justify-between items-start border-b border-slate-200 pb-8 mb-8 relative z-10">
                        <div class="flex flex-col gap-2">
                            <div class="mb-2">
                                <img v-if="getSetting('general', 'site_logo')" :src="getSetting('general', 'site_logo')" class="h-12 w-auto object-contain print:max-h-12" alt="Company Logo">
                                <h1 v-else class="text-3xl font-black text-indigo-600 tracking-tight">{{ getSetting('general', 'site_name', 'E-Shop') }}</h1>
                            </div>
                            <p class="text-slate-500 text-sm mt-1 max-w-xs leading-relaxed"><strong class="text-slate-700">A:</strong> {{ getSetting('general', 'address', '123 E-commerce Street, Dhaka, Bangladesh') }}</p>
                            <p class="text-slate-500 text-sm"><strong class="text-slate-700">P:</strong> {{ getSetting('general', 'phone', '+880 1234 567 890') }}</p>
                            <p class="text-slate-500 text-sm"><strong class="text-slate-700">E:</strong> {{ getSetting('general', 'email', 'support@eshop.com') }}</p>
                        </div>

                        <div class="text-left md:text-right mt-6 md:mt-0">
                            <h2 class="text-4xl font-black text-slate-200 tracking-widest uppercase mb-4 print:text-slate-400">Invoice</h2>
                            <p class="text-slate-800 font-bold mb-1">Invoice No: <span class="text-indigo-600">#{{ order.order_number || order.id }}</span></p>
                            <p class="text-slate-500 text-sm mb-1">Date: {{ formatDate(order.created_at) }}</p>
                            <span class="inline-block mt-2 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider print:border print:border-gray-300" :class="getStatusBadgeClass(order.status)">
                                {{ order.status }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10 relative z-10">
                        <div class="bg-slate-50 p-5 rounded-xl border border-slate-100 print:bg-transparent print:border-none print:p-0">
                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3 print:text-slate-500">Bill To:</h4>
                            <p class="text-lg font-bold text-slate-800">{{ order.name }}</p>
                            <p class="text-sm font-medium text-slate-600 mt-2 flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500" viewBox="0 0 20 20" fill="currentColor"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" /></svg> {{ order.phone }}</p>
                            <p class="text-sm font-medium text-slate-600 mt-1 flex items-start gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500 mt-0.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg> <span class="flex-1">{{ order.address }}<br>{{ order.area || '' }}</span></p>
                        </div>
                        <div class="bg-slate-50 p-5 rounded-xl border border-slate-100 print:bg-transparent print:border-none print:p-0">
                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3 print:text-slate-500">Order Information:</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm border-b border-slate-200 pb-2 print:border-gray-200">
                                    <span class="text-slate-500 font-medium">Payment Method</span>
                                    <span class="font-bold text-slate-800 uppercase">{{ order.payment_method || 'COD' }}</span>
                                </div>
                                <div class="flex justify-between text-sm border-b border-slate-200 pb-2 print:border-gray-200">
                                    <span class="text-slate-500 font-medium">Order Source</span>
                                    <span class="font-bold text-slate-800 uppercase">{{ order.order_source || 'Website' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-slate-200 mb-8 relative z-10 print:border-gray-300 print:rounded-none">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-indigo-600 text-white text-xs font-black uppercase tracking-wider print:bg-gray-200 print:text-gray-800">
                                    <th class="p-4">Description</th>
                                    <th class="p-4 text-center">Rate</th>
                                    <th class="p-4 text-center">Qty</th>
                                    <th class="p-4 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                <tr v-for="item in (order.items || order.order_items)" :key="item.id" class="text-sm">
                                    <td class="p-4">
                                        <p class="font-bold text-slate-800">{{ item.product_name || item.name || item.product?.name }}</p>
                                    </td>
                                    <td class="p-4 text-center text-slate-600 font-medium">৳{{ formatCurrency(item.price) }}</td>
                                    <td class="p-4 text-center font-black text-slate-800">{{ item.quantity }}</td>
                                    <td class="p-4 text-right font-bold text-slate-800">৳{{ formatCurrency(item.price * item.quantity) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end relative z-10">
                        <div class="w-full md:w-1/2 lg:w-1/3 space-y-3 bg-slate-50 p-6 rounded-xl border border-slate-100 print:bg-transparent print:border-none print:p-0">
                            <div class="flex justify-between text-sm font-medium text-slate-600 border-b border-slate-200 pb-2 print:border-gray-200">
                                <span>Subtotal:</span>
                                <span class="font-bold text-slate-800">৳{{ formatCurrency(order.sub_total || (order.total_amount - (order.shipping_charge||0))) }}</span>
                            </div>
                            <div class="flex justify-between text-sm font-medium text-slate-600 border-b border-slate-200 pb-2 print:border-gray-200">
                                <span>Shipping Charge:</span>
                                <span class="font-bold text-slate-800">৳{{ formatCurrency(order.shipping_charge || order.delivery_charge || 0) }}</span>
                            </div>
                            <div v-if="order.discount > 0" class="flex justify-between text-sm font-medium text-rose-500 border-b border-slate-200 pb-2 print:border-gray-200">
                                <span>Discount:</span>
                                <span class="font-bold">- ৳{{ formatCurrency(order.discount) }}</span>
                            </div>
                            <div class="flex justify-between text-xl font-black text-indigo-600 pt-2 print:text-black">
                                <span>Grand Total:</span>
                                <span>৳{{ formatCurrency(order.grand_total || order.total_amount || 0) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-16 pt-8 border-t border-slate-200 text-center relative z-10 print:mt-10">
                        <h4 class="font-black text-slate-800 mb-1">Thank You For Your Business!</h4>
                        <p class="text-slate-500 text-sm">If you have any questions about this invoice, please contact us at <strong>{{ getSetting('general', 'phone', '+880 1234 567 890') }}</strong></p>
                    </div>

                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* ================= PRINT CSS (MAGIC FIX FOR EMPTY PDF) ================= */
@media print {
    /* ১. পুরো পেজের অহেতুক জিনিসগুলো লুকানো */
    body * {
        visibility: hidden;
    }

    /* ২. ব্যাকগ্রাউন্ড কালার যেন প্রিন্টে আসে (যেমন টেবিলের হেডার) */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* ৩. শুধুমাত্র ইনভয়েস এরিয়াকে ভিজিবল করা এবং ফুল স্ক্রিন করা */
    #printable-invoice, #printable-invoice * {
        visibility: visible;
    }

    #printable-invoice {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
        /* স্ক্রল বা হাইট বাগ ফিক্স */
        overflow: visible !important;
        height: auto !important;
    }

    /* ৪. পেজ সাইজ A4 করে দেওয়া */
    @page {
        size: A4 portrait;
        margin: 15mm;
    }

    /* ৫. অন্যান্য UI এলিমেন্ট ফোর্স হাইড */
    .print\:hidden {
        display: none !important;
    }
}
</style>
