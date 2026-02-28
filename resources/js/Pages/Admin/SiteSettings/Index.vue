<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const API_URL = 'http://127.0.0.1:73/api/admin/settings';
const loading = ref(true);
const saving = ref(false);
const activeTab = ref('appearance');

const form = ref({
    // Appearance
    primary_color: '#4f46e5', secondary_color: '#f8fafc', header_bg: '#ffffff', button_radius: 'rounded-md',
    // General
    store_name: '', store_slogan: '',
    // Contact
    contact_phone: '', contact_email: '', store_address: '',
    // Social
    facebook_url: '', instagram_url: '', twitter_url: '', linkedin_url: '', youtube_url: '', whatsapp_number: ''
});

const tabs = [
    { id: 'appearance', name: 'Appearance (UI/Color)', icon: '🎨' },
    { id: 'general', name: 'General Setting', icon: '⚙️' },
    { id: 'contact', name: 'Contact Info', icon: '📞' },
    { id: 'social', name: 'Social Media', icon: '🌐' },
];

const fetchSettings = async () => {
    try {
        const token = localStorage.getItem('token');
        const res = await axios.get(API_URL, { headers: { Authorization: `Bearer ${token}` } });
        if(res.data && res.data.data) { form.value = { ...form.value, ...res.data.data }; }
    } catch (error) { console.error(error); } finally { loading.value = false; }
};

const saveSettings = async () => {
    saving.value = true;
    try {
        const token = localStorage.getItem('token');
        await axios.post(API_URL, form.value, { headers: { Authorization: `Bearer ${token}` } });
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Site Settings Saved!', showConfirmButton: false, timer: 1500 });
    } catch (error) { Swal.fire('Error', 'Failed to save', 'error'); } finally { saving.value = false; }
};

onMounted(() => fetchSettings());
</script>

<template>
    <AdminLayout>
        <div class="max-w-6xl mx-auto pb-10">
            <h2 class="text-2xl font-black text-slate-800 mb-6">⚙️ Site Settings</h2>

            <div v-if="loading" class="text-center py-20 text-slate-500 font-bold animate-pulse">Loading Settings...</div>

            <div v-else class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col md:flex-row min-h-[500px]">

                <div class="w-full md:w-64 border-r border-slate-200 bg-slate-50 flex flex-col">
                    <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
                        class="px-6 py-4 text-left text-sm font-bold transition-all border-l-4"
                        :class="activeTab === tab.id ? 'border-indigo-600 text-indigo-700 bg-white shadow-sm z-10' : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-100'">
                        <span class="mr-2">{{ tab.icon }}</span> {{ tab.name }}
                    </button>
                </div>

                <div class="flex-1 p-6 lg:p-10 bg-white">

                    <div v-show="activeTab === 'appearance'" class="animate-fadeIn space-y-6">
                        <h3 class="text-xl font-black text-slate-800 border-b pb-3 mb-6">🎨 UI & Color Scheme</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2">Primary Color (Buttons, Links)</label>
                                <div class="flex items-center gap-3">
                                    <input v-model="form.primary_color" type="color" class="w-12 h-12 rounded cursor-pointer border-0 p-0">
                                    <input v-model="form.primary_color" type="text" class="px-3 py-2 border border-slate-300 rounded-lg outline-none font-mono text-sm w-32 focus:border-indigo-500">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2">Header Background Color</label>
                                <div class="flex items-center gap-3">
                                    <input v-model="form.header_bg" type="color" class="w-12 h-12 rounded cursor-pointer border-0 p-0">
                                    <input v-model="form.header_bg" type="text" class="px-3 py-2 border border-slate-300 rounded-lg outline-none font-mono text-sm w-32 focus:border-indigo-500">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeTab === 'general'" class="animate-fadeIn space-y-6">
                        <h3 class="text-xl font-black text-slate-800 border-b pb-3 mb-6">⚙️ General Information</h3>
                        <div><label class="block text-xs font-bold text-slate-600 mb-1">Store Name</label><input v-model="form.store_name" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:border-indigo-500"></div>
                        <div><label class="block text-xs font-bold text-slate-600 mb-1">Store Slogan / Title</label><input v-model="form.store_slogan" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:border-indigo-500"></div>
                    </div>

                    <div v-show="activeTab === 'contact'" class="animate-fadeIn space-y-6">
                        <h3 class="text-xl font-black text-slate-800 border-b pb-3 mb-6">📞 Contact Info</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div><label class="block text-xs font-bold text-slate-600 mb-1">Phone Number</label><input v-model="form.contact_phone" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:border-indigo-500"></div>
                            <div><label class="block text-xs font-bold text-slate-600 mb-1">Email Address</label><input v-model="form.contact_email" type="email" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:border-indigo-500"></div>
                        </div>
                        <div><label class="block text-xs font-bold text-slate-600 mb-1">Office Address</label><textarea v-model="form.store_address" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:border-indigo-500"></textarea></div>
                    </div>

                    <div v-show="activeTab === 'social'" class="animate-fadeIn space-y-6">
                        <h3 class="text-xl font-black text-slate-800 border-b pb-3 mb-6">🌐 Social Media Links</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-blue-600 mb-1">Facebook URL</label>
                                <input v-model="form.facebook_url" type="url" placeholder="https://facebook.com/yourpage" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-pink-600 mb-1">Instagram URL</label>
                                <input v-model="form.instagram_url" type="url" placeholder="https://instagram.com/yourpage" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:border-pink-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-sky-500 mb-1">Twitter (X) URL</label>
                                <input v-model="form.twitter_url" type="url" placeholder="https://twitter.com/yourpage" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:border-sky-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-blue-700 mb-1">LinkedIn URL</label>
                                <input v-model="form.linkedin_url" type="url" placeholder="https://linkedin.com/in/yourpage" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:border-blue-700">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-red-600 mb-1">YouTube Channel URL</label>
                                <input v-model="form.youtube_url" type="url" placeholder="https://youtube.com/c/yourchannel" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:border-red-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-emerald-600 mb-1">WhatsApp Number</label>
                                <input v-model="form.whatsapp_number" type="text" placeholder="+8801XXXXXXXXX" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:border-emerald-500">
                            </div>
                        </div>
                    </div>

                    <div class="mt-10 pt-6 border-t border-slate-100 flex justify-end">
                        <button @click="saveSettings" :disabled="saving" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black px-8 py-3 rounded-lg shadow-lg transition-all flex items-center gap-2 disabled:opacity-50">
                            <span>💾</span> {{ saving ? 'Saving...' : 'Save Settings' }}
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
@keyframes fadeIn { from { opacity: 0; transform: translateX(-10px); } to { opacity: 1; transform: translateX(0); } }
.animate-fadeIn { animation: fadeIn 0.3s ease-out forwards; }
input[type="color"] { -webkit-appearance: none; border: none; }
input[type="color"]::-webkit-color-swatch-wrapper { padding: 0; }
input[type="color"]::-webkit-color-swatch { border: none; border-radius: 8px; }
</style>
