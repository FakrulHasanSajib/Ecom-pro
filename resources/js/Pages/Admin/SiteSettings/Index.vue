<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const API_URL = 'http://127.0.0.1:73/api/admin/settings';
const loading = ref(true);
const saving = ref(false);
const activeTab = ref('appearance');

const form = ref({
    primary_color: '#4f46e5', secondary_color: '#f8fafc', header_bg: '#ffffff', button_radius: 'rounded-md',
    site_name: '', site_description: '',
    phone: '', email: '', address: '',
    facebook: '', instagram: '', twitter: '', linkedin: '', youtube: '', whatsapp: ''
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

        const settingsData = res.data?.data || {};

        Object.keys(settingsData).forEach(group => {
            const items = settingsData[group];
            items.forEach(item => {
                if (form.value[item.key] !== undefined) {
                    form.value[item.key] = item.value;
                }
            });
        });
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
};

// 🔥 Shudhu Active Tab er data save korar logic
const saveSettings = async () => {
    saving.value = true;

    // Kon tab e achen, tar upore base kore data pathano hobe
    let dataToSend = {};

    if (activeTab.value === 'appearance') {
        dataToSend = {
            primary_color: form.value.primary_color,
            secondary_color: form.value.secondary_color,
            header_bg: form.value.header_bg,
            button_radius: form.value.button_radius
        };
    } else if (activeTab.value === 'general') {
        dataToSend = {
            site_name: form.value.site_name,
            site_description: form.value.site_description
        };
    } else if (activeTab.value === 'contact') {
        dataToSend = {
            phone: form.value.phone,
            email: form.value.email,
            address: form.value.address
        };
    } else if (activeTab.value === 'social') {
        dataToSend = {
            facebook: form.value.facebook,
            instagram: form.value.instagram,
            twitter: form.value.twitter,
            linkedin: form.value.linkedin,
            youtube: form.value.youtube,
            whatsapp: form.value.whatsapp
        };
    }

    try {
        const token = localStorage.getItem('token');
        await axios.post(API_URL, dataToSend, { headers: { Authorization: `Bearer ${token}` } });

        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Saved Successfully!', showConfirmButton: false, timer: 1500 });

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
        <div class="max-w-6xl mx-auto pb-10 mt-6">
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

                <div class="flex-1 p-6 lg:p-10 bg-white flex flex-col">

                    <div v-show="activeTab === 'appearance'" class="animate-fadeIn space-y-6 flex-1">
                        <h3 class="text-xl font-black text-slate-800 border-b pb-3 mb-6">🎨 UI & Color Scheme</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div><label class="block text-xs font-bold text-slate-600 mb-2">Primary Color</label><input v-model="form.primary_color" type="color" class="w-12 h-12 border-0 p-0"><input v-model="form.primary_color" type="text" class="px-3 py-2 border ml-2 w-24 rounded"></div>
                            <div><label class="block text-xs font-bold text-slate-600 mb-2">Header Background</label><input v-model="form.header_bg" type="color" class="w-12 h-12 border-0 p-0"><input v-model="form.header_bg" type="text" class="px-3 py-2 border ml-2 w-24 rounded"></div>
                        </div>
                    </div>

                    <div v-show="activeTab === 'general'" class="animate-fadeIn space-y-6 flex-1">
                        <h3 class="text-xl font-black text-slate-800 border-b pb-3 mb-6">⚙️ General Information</h3>
                        <div><label class="block text-xs font-bold text-slate-600 mb-1">Site Name</label><input v-model="form.site_name" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:border-indigo-500"></div>
                        <div><label class="block text-xs font-bold text-slate-600 mb-1">Site Slogan / Description</label><input v-model="form.site_description" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:border-indigo-500"></div>
                    </div>

                    <div v-show="activeTab === 'contact'" class="animate-fadeIn space-y-6 flex-1">
                        <h3 class="text-xl font-black text-slate-800 border-b pb-3 mb-6">📞 Contact Info</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div><label class="block text-xs font-bold text-slate-600 mb-1">Phone Number</label><input v-model="form.phone" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:border-indigo-500"></div>
                            <div><label class="block text-xs font-bold text-slate-600 mb-1">Email Address</label><input v-model="form.email" type="email" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:border-indigo-500"></div>
                        </div>
                        <div><label class="block text-xs font-bold text-slate-600 mb-1">Office Address</label><textarea v-model="form.address" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none focus:border-indigo-500"></textarea></div>
                    </div>

                    <div v-show="activeTab === 'social'" class="animate-fadeIn space-y-6 flex-1">
                        <h3 class="text-xl font-black text-slate-800 border-b pb-3 mb-6">🌐 Social Media Links</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div><label class="block text-xs font-bold text-blue-600 mb-1">Facebook</label><input v-model="form.facebook" type="url" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
                            <div><label class="block text-xs font-bold text-pink-600 mb-1">Instagram</label><input v-model="form.instagram" type="url" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
                            <div><label class="block text-xs font-bold text-sky-500 mb-1">Twitter (X)</label><input v-model="form.twitter" type="url" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
                            <div><label class="block text-xs font-bold text-blue-700 mb-1">LinkedIn</label><input v-model="form.linkedin" type="url" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
                            <div><label class="block text-xs font-bold text-red-600 mb-1">YouTube</label><input v-model="form.youtube" type="url" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
                            <div><label class="block text-xs font-bold text-emerald-600 mb-1">WhatsApp</label><input v-model="form.whatsapp" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></div>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-slate-100 flex justify-end">
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
