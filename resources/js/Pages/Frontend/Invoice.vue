<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { SfButton, SfLoaderCircular, SfIconArrowBack } from '@storefront-ui/vue';

const route = useRoute();
const router = useRouter();

const invoiceData = ref(null);
const loading = ref(true);
const backendUrl = 'http://127.0.0.1:73';

const fetchInvoice = async () => {
    try {
        const res = await axios.get(`${backendUrl}/api/public/invoice/${route.params.order_number}`);
        invoiceData.value = res.data.invoice_data;
    } catch (error) {
        console.error("Invoice fetch failed:", error);
        alert('Invoice not found!');
        router.push('/');
    } finally {
        loading.value = false;
    }
};

const printInvoice = () => {
    window.print();
};

const goBack = () => {
    router.push('/');
};

onMounted(() => {
    fetchInvoice();
});
</script>

<template>
    <div class="bg-slate-100 min-h-screen py-10 font-sans flex flex-col items-center print:bg-white print:py-0">

        <div class="w-full max-w-4xl flex justify-between items-center mb-6 px-4 print:hidden">
            <button @click="goBack" class="flex items-center gap-2 text-slate-600 hover:text-indigo-600 font-bold transition-colors">
                <SfIconArrowBack size="sm" /> Back to Home
            </button>
            <SfButton @click="printInvoice" class="bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg">
                🖨️ Print Invoice
            </SfButton>
        </div>

        <div v-if="loading" class="flex flex-col items-center justify-center py-32">
            <SfLoaderCircular size="xl" class="text-indigo-600 mb-4" />
            <p class="text-slate-500 font-medium animate-pulse">Generating Invoice...</p>
        </div>

        <div v-else-if="invoiceData" class="bg-white w-full max-w-4xl shadow-2xl rounded-2xl p-10 md:p-16 print:shadow-none print:rounded-none print:p-0">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b-2 border-slate-100 pb-8 mb-8">
                <div>
                    <h1 class="text-4xl font-black text-indigo-600 tracking-tight mb-2 flex items-center gap-2">
                        🛍️ {{ invoiceData.company.name }}
                    </h1>
                    <p class="text-slate-500 text-sm">{{ invoiceData.company.address }}</p>
                    <p class="text-slate-500 text-sm">Email: {{ invoiceData.company.email }} | Phone: {{ invoiceData.company.phone }}</p>
                </div>
                <div class="mt-6 md:mt-0 text-left md:text-right">
                    <h2 class="text-3xl font-black text-slate-200 uppercase tracking-widest mb-2">Invoice</h2>
                    <p class="font-bold text-slate-800 text-lg">#{{ invoiceData.order.order_number }}</p>
                    <p class="text-slate-500 text-sm mt-1">Date: {{ new Date(invoiceData.order.created_at).toLocaleDateString() }}</p>
                </div>
            </div>

            <div class="flex flex-col md:flex-row justify-between mb-10 bg-slate-50 p-6 rounded-xl border border-slate-100 print:border-none print:bg-transparent print:p-0">
                <div class="mb-6 md:mb-0">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Billed To:</h3>
                    <p class="font-bold text-slate-800 text-lg">{{ invoiceData.order.name || 'Guest Customer' }}</p>
                    <p class="text-slate-600 mt-1">{{ invoiceData.order.phone }}</p>
                    <p class="text-slate-600 max-w-xs">{{ invoiceData.order.address }}, {{ invoiceData.order.area }}</p>
                </div>
                <div class="text-left md:text-right">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Payment Details:</h3>
                    <p class="text-slate-600 mb-1">Method: <span class="font-bold text-slate-800 uppercase">{{ invoiceData.order.payment_method }}</span></p>
                    <p class="text-slate-600">Status:
                        <span :class="{'text-emerald-500 bg-emerald-50': invoiceData.order.payment_status === 'paid', 'text-amber-500 bg-amber-50': invoiceData.order.payment_status === 'pending'}" class="font-bold px-2 py-1 rounded-md text-xs uppercase ml-1">
                            {{ invoiceData.order.payment_status }}
                        </span>
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto mb-10">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-y-2 border-slate-100 text-slate-500 text-sm uppercase tracking-wider">
                            <th class="py-4 px-2 font-bold">Item Description</th>
                            <th class="py-4 px-2 font-bold text-center">Qty</th>
                            <th class="py-4 px-2 font-bold text-right">Price</th>
                            <th class="py-4 px-2 font-bold text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="text-slate-700">
                        <tr v-for="item in invoiceData.order.items" :key="item.id" class="border-b border-slate-100 last:border-0">
                            <td class="py-5 px-2 font-medium">
                                <p class="text-slate-800">{{ item.product_name }}</p>
                            </td>
                            <td class="py-5 px-2 text-center font-bold bg-slate-50">{{ item.quantity }}</td>
                            <td class="py-5 px-2 text-right">৳{{ item.price }}</td>
                            <td class="py-5 px-2 text-right font-black text-slate-800">৳{{ item.price * item.quantity }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end pt-6">
                <div class="w-full md:w-1/2 lg:w-1/3 space-y-3">
                    <div class="flex justify-between text-slate-500">
                        <span>Subtotal</span>
                        <span class="font-medium text-slate-800">৳{{ invoiceData.order.sub_total }}</span>
                    </div>
                    <div class="flex justify-between text-slate-500">
                        <span>Shipping Charge</span>
                        <span class="font-medium text-slate-800">৳{{ invoiceData.order.shipping_charge }}</span>
                    </div>
                    <div class="flex justify-between items-center border-t-2 border-slate-100 pt-4 mt-4">
                        <span class="text-lg font-bold text-slate-800">Grand Total</span>
                        <span class="text-3xl font-black text-indigo-600">৳{{ invoiceData.order.grand_total }}</span>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-100 mt-16 pt-8 text-center text-slate-400 text-sm">
                <p class="font-bold text-slate-500 mb-1">Thank you for your business!</p>
                <p>If you have any questions about this invoice, please contact our support team.</p>
                <p class="mt-4 text-xs">Generated on: {{ invoiceData.generated_at }}</p>
            </div>

        </div>
    </div>
</template>

<style scoped>
/* Print Styles */
@media print {
    body { background-color: white !important; }
    @page { margin: 0; }
}
</style>
