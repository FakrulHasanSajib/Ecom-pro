<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const API_URL = 'http://127.0.0.1:73/api/admin/settings'; // আপাতত একই API ব্যবহার করছি
const loading = ref(true);
const saving = ref(false);
const activeTab = ref('payment'); // Default tab

const form = ref({
    // bKash
    bkash_username: '', bkash_app_key: '', bkash_app_secret: '', bkash_password: '', bkash_status: false,
    // Steadfast Courier
    steadfast_api_key: '', steadfast_secret_key: '', steadfast_status: false,
    // SMS
    sms_api_key: '', sms_sender_id: '', sms_status: false,
});

const tabs = [
    { id: 'payment', name: 'Payment Gateways', icon: '💳' },
    { id: 'courier', name: 'Courier API', icon: '🚚' },
    { id: 'sms', name: 'SMS Gateway', icon: '💬' },
    { id: 'marketing', name: 'Pixel & GTM', icon: '🎯' },
];

const fetchSettings = async () => {
    try {
        const token = localStorage.getItem('token');
        const res = await axios.get(API_URL, { headers: { Authorization: `Bearer ${token}` } });
        if(res.data && res.data.data) {
           form.value = { ...form.value, ...res.data.data };
        }
    } catch (error) { console.error(error); } finally { loading.value = false; }
};

const saveSettings = async () => {
    saving.value = true;
    try {
        const token = localStorage.getItem('token');
        await axios.post(API_URL, form.value, { headers: { Authorization: `Bearer ${token}` } });
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'API Settings Saved!', showConfirmButton: false, timer: 1500 });
    } catch (error) { Swal.fire('Error', 'Failed to save', 'error'); } finally { saving.value = false; }
};

onMounted(() => fetchSettings());
</script>

<template>
    <AdminLayout>
        <div class="max-w-6xl mx-auto pb-10">
            <h2 class="text-2xl font-black text-slate-800 mb-6">🔌 API Integrations</h2>

            <div v-if="loading" class="text-center py-20 text-slate-500 font-bold animate-pulse">Loading APIs...</div>

            <div v-else class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="flex flex-wrap border-b border-slate-200 bg-slate-50">
                    <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
                        class="px-6 py-4 text-sm font-bold transition-all border-b-2 flex items-center gap-2"
                        :class="activeTab === tab.id ? 'border-indigo-600 text-indigo-600 bg-white' : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-100'">
                        <span>{{ tab.icon }}</span> {{ tab.name }}
                    </button>
                </div>

                <div class="p-6">
                    <div v-show="activeTab === 'payment'" class="space-y-8 animate-fadeIn">
                        <div class="border border-slate-200 rounded-xl p-5 bg-slate-50/50">
                            <div class="flex justify-between items-center mb-4 border-b pb-3">
                                <h3 class="text-lg font-black text-pink-600">bKash Integration</h3>
                                <label class="flex items-center cursor-pointer">
                                    <div class="relative">
                                        <input type="checkbox" v-model="form.bkash_status" class="sr-only">
                                        <div class="block bg-slate-300 w-10 h-6 rounded-full transition" :class="{'bg-emerald-500': form.bkash_status}"></div>
                                        <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition" :class="{'transform translate-x-4': form.bkash_status}"></div>
                                    </div>
                                    <span class="ml-3 text-sm font-bold text-slate-700">Enable</span>
                                </label>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div><label class="block text-xs font-bold text-slate-600 mb-1">User Name</label><input v-model="form.bkash_username" type="text" class="w-full px-3 py-2 border rounded-lg outline-none focus:border-indigo-500"></div>
                                <div><label class="block text-xs font-bold text-slate-600 mb-1">App Key</label><input v-model="form.bkash_app_key" type="text" class="w-full px-3 py-2 border rounded-lg outline-none focus:border-indigo-500"></div>
                                <div><label class="block text-xs font-bold text-slate-600 mb-1">App Secret</label><input v-model="form.bkash_app_secret" type="password" class="w-full px-3 py-2 border rounded-lg outline-none focus:border-indigo-500"></div>
                                <div><label class="block text-xs font-bold text-slate-600 mb-1">Password</label><input v-model="form.bkash_password" type="password" class="w-full px-3 py-2 border rounded-lg outline-none focus:border-indigo-500"></div>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeTab === 'courier'" class="space-y-8 animate-fadeIn">
                        <div class="border border-slate-200 rounded-xl p-5 bg-slate-50/50">
                            <h3 class="text-lg font-black text-emerald-600 mb-4 border-b pb-3">Steadfast Courier</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div><label class="block text-xs font-bold text-slate-600 mb-1">API Key</label><input v-model="form.steadfast_api_key" type="password" class="w-full px-3 py-2 border rounded-lg outline-none focus:border-emerald-500"></div>
                                <div><label class="block text-xs font-bold text-slate-600 mb-1">Secret Key</label><input v-model="form.steadfast_secret_key" type="password" class="w-full px-3 py-2 border rounded-lg outline-none focus:border-emerald-500"></div>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeTab === 'sms'" class="animate-fadeIn">
                        <h3 class="text-lg font-black text-amber-600 mb-4">Bulk SMS Setup</h3>
                        <p class="text-slate-500 text-sm">Add your SMS provider details here.</p>
                        </div>

                    <div class="mt-8 flex justify-end">
                        <button @click="saveSettings" :disabled="saving" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black px-8 py-3 rounded-lg shadow-lg transition-all">
                            {{ saving ? 'Saving...' : '💾 Save Current API Settings' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
.animate-fadeIn { animation: fadeIn 0.3s ease-out forwards; }
</style>
