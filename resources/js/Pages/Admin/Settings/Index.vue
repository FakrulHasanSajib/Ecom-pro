<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const API_URL = 'http://127.0.0.1:73/api/admin/settings';
const loading = ref(true);
const saving = ref(false);

const form = ref({
    store_name: '',
    store_phone: '',
    store_email: '',
    store_address: '',
    steadfast_api_key: '',
    steadfast_secret_key: ''
});

// Load Settings
const fetchSettings = async () => {
    try {
        const token = localStorage.getItem('token');
        const res = await axios.get(API_URL, { headers: { Authorization: `Bearer ${token}` } });

        // Data format onujayi fill korben
        if(res.data.data) {
           form.value = { ...form.value, ...res.data.data };
        }
    } catch (error) {
        console.error("Failed to load settings");
    } finally {
        loading.value = false;
    }
};

// Save Settings
const saveSettings = async () => {
    saving.value = true;
    try {
        const token = localStorage.getItem('token');
        await axios.post(API_URL, form.value, { headers: { Authorization: `Bearer ${token}` } });
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Settings saved successfully!', showConfirmButton: false, timer: 1500 });
    } catch (error) {
        Swal.fire('Error', 'Failed to save settings', 'error');
    } finally {
        saving.value = false;
    }
};

onMounted(() => fetchSettings());
</script>

<template>
    <AdminLayout>
        <div class="max-w-5xl mx-auto pb-10">
            <h2 class="text-2xl font-black text-slate-800 mb-6">⚙️ Global Settings</h2>

            <div v-if="loading" class="text-center py-20 text-slate-500 font-bold animate-pulse">Loading settings...</div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <h3 class="text-lg font-bold text-slate-800 border-b pb-3 mb-4">🏪 Store Information</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Store Name</label>
                            <input v-model="form.store_name" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Store Phone</label>
                            <input v-model="form.store_phone" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Store Email</label>
                            <input v-model="form.store_email" type="email" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Store Address</label>
                            <textarea v-model="form.store_address" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:border-indigo-500"></textarea>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <h3 class="text-lg font-bold text-slate-800 border-b pb-3 mb-4">🚚 Steadfast Courier API Setup</h3>
                    <p class="text-xs text-slate-500 mb-4">You can find your API Key and Secret Key in your Steadfast Merchant Dashboard.</p>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">API Key</label>
                            <input v-model="form.steadfast_api_key" type="password" placeholder="e.g. dfkjsdf78sdf7s8df..." class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Secret Key</label>
                            <input v-model="form.steadfast_secret_key" type="password" placeholder="e.g. xxxxxxxx-xxxx-xxxx..." class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:border-emerald-500">
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button @click="saveSettings" :disabled="saving" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black px-8 py-3 rounded-lg shadow-lg transition-all flex items-center gap-2 disabled:opacity-50">
                    {{ saving ? 'Saving...' : '💾 Save All Settings' }}
                </button>
            </div>
        </div>
    </AdminLayout>
</template>
