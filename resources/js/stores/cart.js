import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';

export const useCartStore = defineStore('cart', () => {
    // 1. State: LocalStorage থেকে কার্টের ডাটা নেবে, না থাকলে ফাঁকা অ্যারে
    const items = ref(JSON.parse(localStorage.getItem('cart_items')) || []);

    // 2. Getters: টোটাল আইটেম সংখ্যা হিসাব করা
    const totalItems = computed(() => {
        return items.value.reduce((total, item) => total + item.quantity, 0);
    });

    // Getters: টোটাল দাম হিসাব করা
    const totalPrice = computed(() => {
        return items.value.reduce((total, item) => total + (item.price * item.quantity), 0);
    });

    // 3. Actions: প্রোডাক্ট কার্টে অ্যাড করা
    const addToCart = (product, quantity = 1) => {
        // চেক করা প্রোডাক্টটি আগে থেকেই কার্টে আছে কি না
        const existingItem = items.value.find(item => item.id === product.id);

        if (existingItem) {
            existingItem.quantity += quantity; // থাকলে পরিমাণ বাড়বে
        } else {
            // না থাকলে নতুন করে কার্টে ঢুকবে
            items.value.push({
                id: product.id,
                name: product.name,
                price: product.base_price, // আপনার ডাটাবেসে base_price আছে
                thumbnail: product.thumbnail,
                quantity: quantity
            });
        }
    };

    // 4. Actions: কার্ট থেকে প্রোডাক্ট রিমুভ করা
    const removeFromCart = (productId) => {
        items.value = items.value.filter(item => item.id !== productId);
    };

    // 5. Actions: পরিমাণ কমানো বা বাড়ানো
    const updateQuantity = (productId, quantity) => {
        const item = items.value.find(item => item.id === productId);
        if (item && quantity > 0) {
            item.quantity = quantity;
        }
    };

    // 6. Watcher: কার্টে কোনো পরিবর্তন হলেই সাথে সাথে LocalStorage এ সেভ করে রাখবে
    watch(items, (newItems) => {
        localStorage.setItem('cart_items', JSON.stringify(newItems));
    }, { deep: true });

    // ফাংশনগুলো রিটার্ন করা যাতে অন্য পেজ থেকে ব্যবহার করা যায়
    return {
        items,
        totalItems,
        totalPrice,
        addToCart,
        removeFromCart,
        updateQuantity
    };
});
