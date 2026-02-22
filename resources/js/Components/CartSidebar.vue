<script setup>
import { useCartStore } from '../stores/cart';
import { useRouter } from 'vue-router';

const props = defineProps({
    isOpen: Boolean
});
const emit = defineEmits(['close']);

const cartStore = useCartStore();
const router = useRouter();

const backendUrl = 'http://127.0.0.1:8000';

const getImageUrl = (path) => {
    if (!path) return 'https://placehold.co/100x100?text=No+Image';
    return path.startsWith('http') ? path : `${backendUrl}/storage/${path}`;
};

const goToCheckout = () => {
    emit('close'); // সাইডবার বন্ধ করবে
    router.push('/checkout'); // চেকআউট পেজে নিয়ে যাবে
};
</script>

<template>
    <div>
        <transition enter-active-class="transition-opacity duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-opacity duration-300" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="isOpen" @click="emit('close')" class="fixed inset-0 bg-slate-900 bg-opacity-50 z-[100] backdrop-blur-sm"></div>
        </transition>

        <div :class="isOpen ? 'translate-x-0' : 'translate-x-full'" class="fixed top-0 right-0 h-full w-full sm:w-[400px] bg-white shadow-2xl z-[110] transition-transform duration-300 ease-in-out flex flex-col">

            <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h2 class="text-xl font-extrabold text-slate-800 flex items-center gap-2">
                    🛒 Your Cart
                    <span class="bg-indigo-100 text-indigo-600 text-sm px-2 py-1 rounded-full">{{ cartStore.totalItems }}</span>
                </h2>
                <button @click="emit('close')" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-200 hover:bg-red-100 hover:text-red-500 transition-colors font-bold text-lg">
                    &times;
                </button>
            </div>

            <div class="flex-grow overflow-y-auto p-5 space-y-4">
                <div v-if="cartStore.items.length === 0" class="text-center text-gray-400 mt-20 flex flex-col items-center">
                    <div class="text-6xl mb-4 opacity-50">🛍️</div>
                    <h3 class="text-xl font-bold text-gray-600">Your cart is empty</h3>
                    <p class="text-sm mt-2">Looks like you haven't added anything yet.</p>
                    <button @click="emit('close')" class="mt-6 px-6 py-2 bg-indigo-50 text-indigo-600 font-bold rounded-xl hover:bg-indigo-600 hover:text-white transition-colors">
                        Continue Shopping
                    </button>
                </div>

                <div v-else v-for="item in cartStore.items" :key="item.id" class="flex gap-4 border-b border-gray-100 pb-4 last:border-0">
                    <img :src="getImageUrl(item.thumbnail)" class="w-20 h-20 object-cover rounded-xl border border-gray-100 p-1" />

                    <div class="flex-grow flex flex-col justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-gray-800 line-clamp-2 leading-tight">{{ item.name }}</h3>
                            <p class="text-indigo-600 font-black mt-1">৳{{ item.price }}</p>
                        </div>

                        <div class="flex justify-between items-center mt-2">
                            <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden bg-white">
                                <button @click="cartStore.updateQuantity(item.id, item.quantity - 1)" class="w-8 h-8 flex items-center justify-center bg-gray-50 hover:bg-gray-200 font-bold text-gray-600 transition">-</button>
                                <span class="w-8 text-center text-sm font-bold">{{ item.quantity }}</span>
                                <button @click="cartStore.updateQuantity(item.id, item.quantity + 1)" class="w-8 h-8 flex items-center justify-center bg-gray-50 hover:bg-gray-200 font-bold text-gray-600 transition">+</button>
                            </div>

                            <button @click="cartStore.removeFromCart(item.id)" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="cartStore.items.length > 0" class="p-6 border-t border-gray-100 bg-white shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.05)]">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-gray-500 font-bold text-lg">Total Amount:</span>
                    <span class="text-3xl font-black text-indigo-600">৳{{ cartStore.totalPrice }}</span>
                </div>
                <button @click="goToCheckout" class="w-full bg-slate-900 hover:bg-indigo-600 text-white py-4 rounded-xl font-bold text-lg flex justify-center items-center gap-2 shadow-xl shadow-slate-200 transition-all duration-300">
                    Proceed to Checkout
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </button>
            </div>

        </div>
    </div>
</template>
