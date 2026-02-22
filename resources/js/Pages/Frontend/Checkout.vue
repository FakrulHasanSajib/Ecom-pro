<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useCartStore } from '../../stores/cart';
import axios from 'axios';
import { SfButton, SfIconArrowBack } from '@storefront-ui/vue';
import Swal from 'sweetalert2';

const router = useRouter();
const cartStore = useCartStore();

const form = ref({
    name: '',
    phone: '',
    address: '',
    area: 'inside_dhaka',
    payment_method: 'cod'
});

const isSubmitting = ref(false);

const shippingCost = computed(() => {
    return form.value.area === 'inside_dhaka' ? 70 : 130;
});

const grandTotal = computed(() => {
    return cartStore.totalPrice + shippingCost.value;
});

onMounted(() => {
    if (cartStore.items.length === 0) {
        Swal.fire({
            title: 'Empty Cart!',
            text: 'আপনার কার্ট খালি! অনুগ্রহ করে প্রোডাক্ট যুক্ত করুন।',
            icon: 'warning',
            confirmButtonColor: '#4f46e5'
        }).then(() => {
            router.push('/');
        });
    }
});

const placeOrder = async () => {
    // 🔥 ১. ফ্রন্টএন্ড কড়া ভ্যালিডেশন (খালি ফর্ম সাবমিট বন্ধ করা)
    if (!form.value.name || !form.value.phone || !form.value.address) {
        Swal.fire({
            title: 'তথ্য অসম্পূর্ণ!',
            text: 'দয়া করে আপনার নাম, ফোন নাম্বার এবং সম্পূর্ণ ঠিকানা দিন।',
            icon: 'warning',
            confirmButtonColor: '#4f46e5'
        });
        return;
    }

    try {
        isSubmitting.value = true;

        const formattedItems = cartStore.items.map(item => ({
            product_id: item.id,
            quantity: item.quantity
        }));

        const orderData = {
            name: form.value.name,
            phone: form.value.phone,
            address: form.value.address,
            area: form.value.area,
            payment_method: form.value.payment_method,
            shipping_charge: shippingCost.value,
            total_amount: grandTotal.value,
            items: formattedItems
        };

        const res = await axios.post('http://127.0.0.1:73/api/public/checkout', orderData);

        if (form.value.payment_method === 'sslcommerz') {
            if (res.data.payment_url) {
                window.location.href = res.data.payment_url;
            } else {
                Swal.fire('Error!', 'পেমেন্ট গেটওয়ে লিংক পেতে সমস্যা হয়েছে!', 'error');
                isSubmitting.value = false;
            }
        } else {
            Swal.fire({
                title: 'Order Successful! 🎉',
                text: 'আপনার অর্ডারটি সফলভাবে রিসিভ করা হয়েছে।',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                cartStore.items = [];
                localStorage.removeItem('cart_items');
                const orderNum = res.data.data.order_number;
                router.push(`/order-success?order_number=${orderNum}`);
            });
        }

    } catch (error) {
        console.error("Order failed", error);
        isSubmitting.value = false;

        // 🔥 ২. ব্যাকএন্ডের আসল এররটি SweetAlert এ দেখানো
        if (error.response) {
            if (error.response.status === 422) {
                Swal.fire('Validation Error!', 'দয়া করে ফর্মের সব তথ্য সঠিকভাবে পূরণ করুন।', 'error');
            } else if (error.response.status === 400) {
                // ডাটাবেস বা OrderService এর আসল এররটি ধরবে
                const backendError = error.response.data.error_detail || error.response.data.message || 'অজানা সমস্যা';
                Swal.fire({
                    title: 'সার্ভার এরর!',
                    text: 'অর্ডার সেভ হতে সমস্যা: ' + backendError,
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
            } else {
                Swal.fire('Oops...', 'সার্ভার এরর: ' + error.response.status, 'error');
            }
        } else {
            Swal.fire('Network Error', 'সার্ভারের সাথে কানেক্ট করা যাচ্ছে না। পোর্ট 73 চালু আছে কি না দেখুন।', 'error');
        }
    }
};

const goBack = () => router.push('/');

const backendUrl = 'http://127.0.0.1:73';
const getImageUrl = (path) => {
    if (!path) return 'https://placehold.co/100x100?text=No+Image';
    return path.startsWith('http') ? path : `${backendUrl}/storage/${path}`;
};
</script>

<template>
    <div class="bg-slate-50 min-h-screen pb-20 font-sans">

        <header class="bg-white sticky top-0 z-50 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <div class="max-w-7xl mx-auto px-4 h-20 flex items-center justify-between">
                <div @click="goBack" class="flex items-center gap-2 cursor-pointer group">
                    <span class="text-3xl group-hover:scale-110 transition-transform">🛍️</span>
                    <span class="font-black text-2xl text-slate-800 tracking-tight">E-Shop</span>
                </div>
                <div class="flex items-center gap-2 text-emerald-600 bg-emerald-50 px-4 py-2 rounded-full font-bold text-sm border border-emerald-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    Secure Checkout
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 py-10">
            <button @click="goBack" class="text-slate-500 hover:text-indigo-600 font-bold flex items-center gap-2 mb-8 transition-colors">
                <SfIconArrowBack size="sm" /> Return to Cart
            </button>

            <div class="flex flex-col lg:flex-row gap-10">

                <div class="lg:w-2/3 space-y-8">

                    <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
                        <div class="flex items-center gap-3 mb-8 border-b border-slate-100 pb-4">
                            <span class="bg-indigo-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold">1</span>
                            <h2 class="text-2xl font-black text-slate-800">Shipping Details</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Full Name</label>
                                <input v-model="form.name" type="text" required placeholder="Enter your name" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-4 focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:border-indigo-500 transition-all outline-none" />
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Phone Number</label>
                                <input v-model="form.phone" type="tel" required placeholder="01XXXXXXXXX" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-4 focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:border-indigo-500 transition-all outline-none" />
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-bold text-slate-700 mb-3">Delivery Area</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <label :class="{'border-indigo-600 bg-indigo-50 ring-1 ring-indigo-600': form.area === 'inside_dhaka', 'border-slate-200 bg-white hover:border-indigo-300': form.area !== 'inside_dhaka'}" class="border-2 rounded-2xl p-4 cursor-pointer transition-all flex items-start gap-4 shadow-sm">
                                    <input type="radio" v-model="form.area" value="inside_dhaka" class="mt-1 w-5 h-5 text-indigo-600 focus:ring-indigo-500" />
                                    <div>
                                        <p class="font-black text-slate-800 text-lg">Inside Dhaka</p>
                                        <p class="text-sm text-slate-500 font-medium mt-1">Delivery Charge: ৳70</p>
                                    </div>
                                </label>
                                <label :class="{'border-indigo-600 bg-indigo-50 ring-1 ring-indigo-600': form.area === 'outside_dhaka', 'border-slate-200 bg-white hover:border-indigo-300': form.area !== 'outside_dhaka'}" class="border-2 rounded-2xl p-4 cursor-pointer transition-all flex items-start gap-4 shadow-sm">
                                    <input type="radio" v-model="form.area" value="outside_dhaka" class="mt-1 w-5 h-5 text-indigo-600 focus:ring-indigo-500" />
                                    <div>
                                        <p class="font-black text-slate-800 text-lg">Outside Dhaka</p>
                                        <p class="text-sm text-slate-500 font-medium mt-1">Delivery Charge: ৳130</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Detailed Address</label>
                            <textarea v-model="form.address" required rows="3" placeholder="House no, Road no, Thana, District" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-4 focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:border-indigo-500 transition-all outline-none"></textarea>
                        </div>
                    </div>

                    <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
                        <div class="flex items-center gap-3 mb-8 border-b border-slate-100 pb-4">
                            <span class="bg-indigo-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold">2</span>
                            <h2 class="text-2xl font-black text-slate-800">Payment Method</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <label :class="{'border-emerald-500 bg-emerald-50 ring-2 ring-emerald-500': form.payment_method === 'cod', 'border-slate-200 bg-white hover:border-emerald-300': form.payment_method !== 'cod'}" class="border-2 rounded-2xl p-5 cursor-pointer transition-all flex flex-col items-center justify-center gap-3 shadow-sm relative overflow-hidden">
                                <div v-if="form.payment_method === 'cod'" class="absolute top-3 right-3 text-emerald-500">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                </div>
                                <input type="radio" v-model="form.payment_method" value="cod" class="hidden" />
                                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-md text-emerald-500">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                </div>
                                <p class="font-black text-slate-800 text-lg">Cash on Delivery</p>
                                <p class="text-xs text-slate-500 font-medium text-center">Pay with cash upon delivery.</p>
                            </label>

                            <label :class="{'border-blue-600 bg-blue-50 ring-2 ring-blue-600': form.payment_method === 'sslcommerz', 'border-slate-200 bg-white hover:border-blue-300': form.payment_method !== 'sslcommerz'}" class="border-2 rounded-2xl p-5 cursor-pointer transition-all flex flex-col items-center justify-center gap-3 shadow-sm relative overflow-hidden">
                                <div v-if="form.payment_method === 'sslcommerz'" class="absolute top-3 right-3 text-blue-600">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                </div>
                                <input type="radio" v-model="form.payment_method" value="sslcommerz" class="hidden" />
                                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-md text-blue-600">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                </div>
                                <p class="font-black text-slate-800 text-lg">SSLCommerz</p>
                                <p class="text-xs text-slate-500 font-medium text-center">bKash, Nagad, Cards & More.</p>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="lg:w-1/3">
                    <div class="bg-slate-900 text-white p-8 rounded-3xl shadow-2xl sticky top-28">
                        <h2 class="text-2xl font-black mb-6 border-b border-slate-700 pb-4">Order Summary</h2>

                        <div class="space-y-4 mb-6 max-h-[350px] overflow-y-auto pr-2 custom-scrollbar">
                            <div v-for="item in cartStore.items" :key="item.id" class="flex gap-4 items-center bg-slate-800 p-3 rounded-2xl">
                                <div class="relative">
                                    <img :src="getImageUrl(item.thumbnail)" class="w-14 h-14 object-cover rounded-xl border border-slate-600 bg-white" />
                                    <span class="absolute -top-2 -right-2 bg-indigo-500 text-white text-[10px] font-black rounded-full w-5 h-5 flex items-center justify-center shadow-md">{{ item.quantity }}</span>
                                </div>
                                <div class="flex-grow">
                                    <h3 class="text-sm font-bold text-slate-200 line-clamp-1">{{ item.name }}</h3>
                                    <p class="text-xs text-slate-400 mt-1">৳{{ item.price }} x {{ item.quantity }}</p>
                                </div>
                                <div class="font-black text-white">
                                    ৳{{ item.price * item.quantity }}
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-slate-700 pt-5 space-y-4">
                            <div class="flex justify-between text-slate-400 font-medium">
                                <span>Subtotal</span>
                                <span class="text-white">৳{{ cartStore.totalPrice }}</span>
                            </div>
                            <div class="flex justify-between text-slate-400 font-medium">
                                <span>Shipping Fee</span>
                                <span class="text-white">৳{{ shippingCost }}</span>
                            </div>
                            <div class="flex justify-between text-xl pt-4 border-t border-dashed border-slate-700">
                                <span class="font-bold text-slate-300">Total Payable</span>
                                <span class="font-black text-emerald-400 text-3xl">৳{{ grandTotal }}</span>
                            </div>
                        </div>

                        <div class="mt-8">
                            <button @click="placeOrder" :disabled="isSubmitting" class="w-full bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white py-4 rounded-2xl font-black text-lg shadow-[0_0_20px_rgba(79,70,229,0.4)] transition-all duration-300 disabled:opacity-70 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                                <span v-if="isSubmitting">Processing...</span>
                                <template v-else>
                                    <span v-if="form.payment_method === 'cod'">Confirm Order</span>
                                    <span v-else>Pay with SSLCommerz</span>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </template>
                            </button>
                            <p class="text-center text-xs text-slate-500 mt-4 flex items-center justify-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                Your information is 100% secure.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #475569;
    border-radius: 20px;
}
</style>
