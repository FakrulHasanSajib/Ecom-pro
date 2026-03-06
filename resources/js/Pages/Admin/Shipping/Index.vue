<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const API_URL = 'http://127.0.0.1:73/api/admin/settings';
const loading = ref(true);
const saving = ref(false);

const form = ref({
    shipping_inside_dhaka: '70',
    shipping_outside_dhaka: '130'
});

// ডাটাবেস থেকে শুধু শিপিং চার্জ নিয়ে আসা
const fetchSettings = async () => {
    try {
        const token = localStorage.getItem('token');
        const res = await axios.get(API_URL, { headers: { Authorization: `Bearer ${token}` } });

        const settingsData = res.data?.data || {};

        if (settingsData.shipping) {
            settingsData.shipping.forEach(item => {
                const key = item.key || item.name;
                if (form.value[key] !== undefined) {
                    form.value[key] = item.value;
                }
            });
        }
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
};

const saveSettings = async () => {
    saving.value = true;
    try {
        const token = localStorage.getItem('token');
        await axios.post(API_URL, form.value, { headers: { Authorization: `Bearer ${token}` } });

        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Delivery Charges Updated!', showConfirmButton: false, timer: 1500 });

        fetchSettings();
    } catch (error) {
        Swal.fire('Error', 'Failed to save', 'error');
    } finally {
        saving.value = false;
    }
};

onMounted(() => fetchSettings());
</script>

<template>
    <AdminLayout>
        <div class="max-w-4xl mx-auto pb-10 mt-6">
            <div class="flex items-center gap-3 mb-6">
                <span class="text-3xl">🚚</span>
                <div>
                    <h2 class="text-2xl font-black text-slate-800">Delivery & Shipping</h2>
                    <p class="text-sm text-slate-500">Manage delivery charges for different areas</p>
                </div>
            </div>

            <div v-if="loading" class="text-center py-20 text-slate-500 font-bold animate-pulse">
                Loading Data...
            </div>

            <div v-else class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden p-8">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="bg-indigo-50 p-6 rounded-xl border border-indigo-100 relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-10 text-5xl">🏙️</div>
                        <label class="block text-sm font-black text-indigo-700 mb-2 relative z-10">Inside Dhaka Charge (৳)</label>
                        <p class="text-xs text-indigo-500 mb-4 relative z-10">Standard delivery inside Dhaka city</p>
                        <input v-model="form.shipping_inside_dhaka" type="number" placeholder="e.g. 70" class="w-full px-4 py-3 border border-white rounded-lg outline-none focus:ring-2 focus:ring-indigo-500 text-xl font-bold bg-white/80 backdrop-blur shadow-sm relative z-10">
                    </div>

                    <div class="bg-slate-50 p-6 rounded-xl border border-slate-200 relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-10 text-5xl">🛣️</div>
                        <label class="block text-sm font-black text-slate-700 mb-2 relative z-10">Outside Dhaka Charge (৳)</label>
                        <p class="text-xs text-slate-500 mb-4 relative z-10">Courier delivery to other districts</p>
                        <input v-model="form.shipping_outside_dhaka" type="number" placeholder="e.g. 130" class="w-full px-4 py-3 border border-white rounded-lg outline-none focus:ring-2 focus:ring-slate-400 text-xl font-bold bg-white/80 backdrop-blur shadow-sm relative z-10">
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-100 flex justify-end">
                    <button @click="saveSettings" :disabled="saving" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black px-8 py-3.5 rounded-xl shadow-lg shadow-indigo-200 transition-all flex items-center gap-2 disabled:opacity-50 active:scale-95">
                        <span v-if="saving" class="animate-spin text-xl">⏳</span>
                        <span v-else class="text-xl">💾</span>
                        {{ saving ? 'Saving Changes...' : 'Save Delivery Charges' }}
                    </button>
                </div>

            </div>
        </div>
    </AdminLayout>
</template>
