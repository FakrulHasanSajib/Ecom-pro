<script setup>
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

// URL থেকে অর্ডার নাম্বারটি ধরবে (যেমন: /order-success?order_number=ORD-XYZ)
const orderNumber = route.query.order_number || 'Processing...';

const goHome = () => {
    router.push('/');
};

const trackOrder = () => {
    // পরবর্তীতে ইনভয়েস বা ট্র্যাকিং পেজ বানালে সেখানে পাঠাবেন
    alert('Tracking feature coming soon!');
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex flex-col justify-center items-center p-4 font-sans relative overflow-hidden">

        <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-emerald-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
        <div class="absolute top-[-10%] right-[-10%] w-96 h-96 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"></div>

        <div class="bg-white p-8 md:p-12 rounded-[2rem] shadow-2xl shadow-slate-200/50 max-w-lg w-full text-center relative z-10 border border-slate-100">

            <div class="mb-8 flex justify-center">
                <div class="w-24 h-24 bg-emerald-100 rounded-full flex items-center justify-center shadow-inner relative">
                    <div class="absolute inset-0 bg-emerald-400 rounded-full animate-ping opacity-20"></div>
                    <svg class="w-12 h-12 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>

            <h1 class="text-4xl font-black text-slate-800 mb-4 tracking-tight">Order Confirmed!</h1>
            <p class="text-slate-500 mb-8 font-medium text-lg leading-relaxed">
                Thank you for your purchase. We've received your order and are currently processing it.
            </p>

            <div class="bg-slate-50 rounded-2xl p-6 mb-8 border border-slate-100 shadow-sm">
                <p class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-1">Your Order Number</p>
                <p class="text-2xl font-black text-indigo-600 tracking-wide">{{ orderNumber }}</p>
            </div>

            <div class="space-y-4">
                <button @click="trackOrder" class="w-full bg-slate-900 hover:bg-indigo-600 text-white py-4 rounded-xl font-bold text-lg shadow-[0_8px_20px_-6px_rgba(0,0,0,0.3)] transition-all duration-300 hover:-translate-y-1">
                    View Order Details
                </button>
                <button @click="goHome" class="w-full bg-white border-2 border-slate-200 text-slate-600 hover:border-indigo-600 hover:text-indigo-600 py-4 rounded-xl font-bold text-lg transition-all duration-300">
                    Continue Shopping
                </button>
            </div>

        </div>

        <p class="mt-8 text-sm text-slate-400 font-medium z-10">
            A confirmation email has been sent to your email address.
        </p>
    </div>
</template>

<style scoped>
/* Background Animation Styles */
.animate-blob {
    animation: blob 7s infinite;
}
.animation-delay-2000 {
    animation-delay: 2s;
}
@keyframes blob {
    0% { transform: translate(0px, 0px) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
    100% { transform: translate(0px, 0px) scale(1); }
}
</style>
