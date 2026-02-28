<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const API_URL = 'http://127.0.0.1:73/api/admin/settings';
const loading = ref(true);
const saving = ref(false);
const activeTab = ref('Payment');

// Pathao Modal State
const showPathaoModal = ref(false);

const form = ref({
    // bKash
    bkash_username: '', bkash_app_key: '', bkash_app_secret: '', bkash_base_url: '', bkash_password: '', bkash_status: false,

    // Shurjopay
    shurjopay_username: '', shurjopay_prefix: '', shurjopay_success_url: '', shurjopay_status: false,

    // Steadfast
    steadfast_api_key: '', steadfast_secret_key: '', steadfast_url: '', steadfast_status: false,

    // Pathao
    pathao_url: '', pathao_token: '', pathao_api_key: '', pathao_secret_key: '', pathao_username: '', pathao_password: '', pathao_status: false,

    // Pixel
    pixel_id: '', pixel_meta_token: '', pixel_ad_account: '', pixel_status: false,

    // SMS
    sms_api_key: '', sms_sender_id: '', sms_base_url: '', sms_status: false,

    // Tiktok
    tiktok_pixel_id: '', tiktok_status: false,

    // GTM
    gtm_id: '', gtm_status: false
});

const tabs = ['Payment', 'SMS', 'Courier', 'Tiktok', 'GTM', 'Pixel'];

// 🔥 Backend থেকে গ্রুপ করা ডাটা এনে ফর্মে সেট করার লজিক
const fetchSettings = async () => {
    try {
        const token = localStorage.getItem('token');
        const res = await axios.get(API_URL, { headers: { Authorization: `Bearer ${token}` } });

        if(res.data && res.data.data) {
           let flatSettings = {};

           // Backend এর Group Data কে ভেঙ্গে Flat object এ রূপান্তর করা
           for (const group in res.data.data) {
               res.data.data[group].forEach(setting => {
                   // Status গুলোকে string থেকে Boolean (true/false) এ রূপান্তর করা
                   if(setting.key.includes('status')) {
                       flatSettings[setting.key] = (setting.value === '1' || setting.value === 'true' || setting.value === true);
                   } else {
                       flatSettings[setting.key] = setting.value || '';
                   }
               });
           }

           form.value = { ...form.value, ...flatSettings };
        }
    } catch (error) {
        console.error("Fetch Error:", error);
    } finally {
        loading.value = false;
    }
};

const saveSettings = async () => {
    saving.value = true;
    try {
        const token = localStorage.getItem('token');

        // Status value গুলোকে true/false থেকে 1/0 তে রূপান্তর করা (Backend এর সুবিধার জন্য)
        let payload = { ...form.value };
        Object.keys(payload).forEach(key => {
            if (typeof payload[key] === 'boolean') {
                payload[key] = payload[key] ? 1 : 0;
            }
        });

        await axios.post(API_URL, payload, { headers: { Authorization: `Bearer ${token}` } });
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Saved Successfully!', showConfirmButton: false, timer: 1500 });
        showPathaoModal.value = false;
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
        <div class="max-w-7xl mx-auto pb-10 bg-[#f4f7f6] min-h-screen">

            <div v-if="loading" class="text-center py-20 text-slate-500 font-bold animate-pulse">Loading APIs...</div>

            <div v-else class="p-4 md:p-8">
                <div class="flex items-center gap-2 mb-8 bg-white p-2 lg:px-6 rounded-lg shadow-sm overflow-x-auto w-full mx-auto justify-start lg:justify-center border border-slate-100">
                    <button v-for="tab in tabs" :key="tab" @click="activeTab = tab"
                        class="px-8 py-2.5 rounded-full text-[15px] font-bold transition-all whitespace-nowrap"
                        :class="activeTab === tab ? 'bg-[#6366f1] text-white shadow' : 'text-slate-600 hover:text-slate-900'">
                        {{ tab }}
                    </button>
                </div>

                <div v-show="activeTab === 'Payment'" class="space-y-6 animate-fadeIn">

                    <div class="bg-white border border-slate-100 rounded-lg p-8 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-800 mb-8">Bkash</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                            <div><label class="block text-sm text-slate-600 mb-2">User Name *</label><input v-model="form.bkash_username" type="text" class="form-input"></div>
                            <div><label class="block text-sm text-slate-600 mb-2">App Key *</label><input v-model="form.bkash_app_key" type="text" class="form-input"></div>
                            <div><label class="block text-sm text-slate-600 mb-2">App Secret *</label><input v-model="form.bkash_app_secret" type="password" class="form-input"></div>
                            <div><label class="block text-sm text-slate-600 mb-2">Base Url *</label><input v-model="form.bkash_base_url" type="url" class="form-input"></div>
                            <div><label class="block text-sm text-slate-600 mb-2">Password *</label><input v-model="form.bkash_password" type="password" class="form-input"></div>
                            <div>
                                <label class="block text-sm text-slate-600 mb-2">Status</label>
                                <label class="relative inline-flex items-center cursor-pointer mt-1">
                                    <input type="checkbox" v-model="form.bkash_status" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#00a8e1]"></div>
                                </label>
                            </div>
                        </div>
                        <button @click="saveSettings" class="bg-[#10b981] hover:bg-[#059669] text-white px-8 py-2.5 rounded font-medium transition">Submit</button>
                    </div>

                    <div class="bg-white border border-slate-100 rounded-lg p-8 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-800 mb-8">Shurjopay</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                            <div><label class="block text-sm text-slate-600 mb-2">User Name *</label><input v-model="form.shurjopay_username" type="text" class="form-input"></div>
                            <div><label class="block text-sm text-slate-600 mb-2">Prefix *</label><input v-model="form.shurjopay_prefix" type="text" class="form-input"></div>
                            <div><label class="block text-sm text-slate-600 mb-2">Success Url *</label><input v-model="form.shurjopay_success_url" type="url" class="form-input"></div>
                            <div>
                                <label class="block text-sm text-slate-600 mb-2">Status</label>
                                <label class="relative inline-flex items-center cursor-pointer mt-1">
                                    <input type="checkbox" v-model="form.shurjopay_status" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#00a8e1]"></div>
                                </label>
                            </div>
                        </div>
                        <button @click="saveSettings" class="bg-[#10b981] hover:bg-[#059669] text-white px-8 py-2.5 rounded font-medium transition">Submit</button>
                    </div>
                </div>

                <div v-show="activeTab === 'SMS'" class="space-y-6 animate-fadeIn">
                    <div class="bg-white border border-slate-100 rounded-lg p-8 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-800 mb-8">SMS Configuration</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div><label class="block text-sm text-slate-600 mb-2">API Key *</label><input v-model="form.sms_api_key" type="text" class="form-input"></div>
                            <div><label class="block text-sm text-slate-600 mb-2">Sender ID *</label><input v-model="form.sms_sender_id" type="text" class="form-input"></div>
                            <div class="md:col-span-2"><label class="block text-sm text-slate-600 mb-2">Base Url *</label><input v-model="form.sms_base_url" type="url" class="form-input"></div>
                            <div>
                                <label class="block text-sm text-slate-600 mb-2">Status</label>
                                <label class="relative inline-flex items-center cursor-pointer mt-1">
                                    <input type="checkbox" v-model="form.sms_status" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#00a8e1]"></div>
                                </label>
                            </div>
                        </div>
                        <button @click="saveSettings" class="bg-[#10b981] hover:bg-[#059669] text-white px-8 py-2.5 rounded font-medium transition">Submit</button>
                    </div>
                </div>

                <div v-show="activeTab === 'Courier'" class="space-y-6 animate-fadeIn">

                    <div class="bg-white border border-slate-100 rounded-lg p-8 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-800 mb-8">Steadfast Courier</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div><label class="block text-sm text-slate-600 mb-2">API key *</label><input v-model="form.steadfast_api_key" type="password" class="form-input"></div>
                            <div><label class="block text-sm text-slate-600 mb-2">Secret key *</label><input v-model="form.steadfast_secret_key" type="password" class="form-input"></div>
                            <div><label class="block text-sm text-slate-600 mb-2">URL *</label><input v-model="form.steadfast_url" type="url" class="form-input"></div>
                            <div>
                                <label class="block text-sm text-slate-600 mb-2">Status</label>
                                <label class="relative inline-flex items-center cursor-pointer mt-1">
                                    <input type="checkbox" v-model="form.steadfast_status" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#00a8e1]"></div>
                                </label>
                            </div>
                        </div>
                        <button @click="saveSettings" class="bg-[#10b981] hover:bg-[#059669] text-white px-8 py-2.5 rounded font-medium transition">Submit</button>
                    </div>

                    <div class="bg-white border border-slate-100 rounded-lg p-8 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-800 mb-8">Pathao Courier</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div><label class="block text-sm text-slate-600 mb-2">URL *</label><input v-model="form.pathao_url" type="url" class="form-input"></div>
                            <div><label class="block text-sm text-slate-600 mb-2">Token *</label><input v-model="form.pathao_token" type="text" class="form-input bg-slate-50 text-slate-400" readonly></div>
                            <div>
                                <label class="block text-sm text-slate-600 mb-2">Status</label>
                                <label class="relative inline-flex items-center cursor-pointer mt-1">
                                    <input type="checkbox" v-model="form.pathao_status" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#00a8e1]"></div>
                                </label>
                            </div>
                            <div>
                                <label class="block text-sm text-slate-600 mb-2">Token Generate</label>
                                <button @click="showPathaoModal = true" class="bg-[#6366f1] hover:bg-[#4f46e5] text-white px-8 py-2.5 rounded shadow-sm font-medium transition flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                    Generate
                                </button>
                            </div>
                        </div>
                        <button @click="saveSettings" class="bg-[#10b981] hover:bg-[#059669] text-white px-8 py-2.5 rounded font-medium transition">Submit</button>
                    </div>
                </div>

                <div v-show="activeTab === 'Tiktok'" class="space-y-6 animate-fadeIn">
                    <div class="bg-white border border-slate-100 rounded-lg p-8 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-800 mb-8">Tiktok Pixel Configuration</h3>
                        <div class="grid grid-cols-1 gap-6 mb-8 max-w-2xl">
                            <div><label class="block text-sm text-slate-600 mb-2">Tiktok Pixel ID *</label><input v-model="form.tiktok_pixel_id" type="text" class="form-input"></div>
                            <div>
                                <label class="block text-sm text-slate-600 mb-2">Status</label>
                                <label class="relative inline-flex items-center cursor-pointer mt-1">
                                    <input type="checkbox" v-model="form.tiktok_status" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#00a8e1]"></div>
                                </label>
                            </div>
                        </div>
                        <button @click="saveSettings" class="bg-[#10b981] hover:bg-[#059669] text-white px-8 py-2.5 rounded font-medium transition">Submit</button>
                    </div>
                </div>

                <div v-show="activeTab === 'GTM'" class="space-y-6 animate-fadeIn">
                    <div class="bg-white border border-slate-100 rounded-lg p-8 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-800 mb-8">Google Tag Manager (GTM)</h3>
                        <div class="grid grid-cols-1 gap-6 mb-8 max-w-2xl">
                            <div><label class="block text-sm text-slate-600 mb-2">GTM ID *</label><input v-model="form.gtm_id" type="text" placeholder="GTM-XXXXXXX" class="form-input"></div>
                            <div>
                                <label class="block text-sm text-slate-600 mb-2">Status</label>
                                <label class="relative inline-flex items-center cursor-pointer mt-1">
                                    <input type="checkbox" v-model="form.gtm_status" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#00a8e1]"></div>
                                </label>
                            </div>
                        </div>
                        <button @click="saveSettings" class="bg-[#10b981] hover:bg-[#059669] text-white px-8 py-2.5 rounded font-medium transition">Submit</button>
                    </div>
                </div>

                <div v-show="activeTab === 'Pixel'" class="space-y-6 animate-fadeIn">
                    <div class="bg-white border border-slate-100 rounded-lg p-8 shadow-sm">
                        <div class="space-y-6 mb-8 max-w-3xl">
                            <div><label class="block text-sm text-slate-600 mb-2">Pixels ID *</label><input v-model="form.pixel_id" type="text" class="form-input"></div>
                            <div><label class="block text-sm text-slate-600 mb-2">Meta Access Token</label><input v-model="form.pixel_meta_token" type="text" class="form-input"></div>
                            <div><label class="block text-sm text-slate-600 mb-2">Ad Account ID (act_xxxxxxx) *</label><input v-model="form.pixel_ad_account" type="text" class="form-input"></div>
                            <div>
                                <label class="block text-sm text-slate-600 mb-2">Status</label>
                                <label class="relative inline-flex items-center cursor-pointer mt-1">
                                    <input type="checkbox" v-model="form.pixel_status" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#00a8e1]"></div>
                                </label>
                            </div>
                        </div>
                        <button @click="saveSettings" class="bg-[#10b981] hover:bg-[#059669] text-white px-8 py-2.5 rounded font-medium transition">Submit</button>
                    </div>
                </div>

            </div>
        </div>

        <div v-if="showPathaoModal" class="fixed inset-0 bg-slate-900/50 flex items-center justify-center z-50 animate-fadeIn">
            <div class="bg-white rounded-lg w-full max-w-lg shadow-2xl overflow-hidden">
                <div class="flex justify-between items-center p-4 border-b">
                    <h3 class="text-lg font-bold text-slate-800">Token</h3>
                    <button @click="showPathaoModal = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div><label class="block text-sm text-slate-600 mb-2">Api Key</label><input v-model="form.pathao_api_key" type="text" class="form-input"></div>
                    <div><label class="block text-sm text-slate-600 mb-2">Secret Key</label><input v-model="form.pathao_secret_key" type="text" class="form-input"></div>
                    <div><label class="block text-sm text-slate-600 mb-2">Username</label><input v-model="form.pathao_username" type="text" class="form-input"></div>
                    <div><label class="block text-sm text-slate-600 mb-2">Password</label><input v-model="form.pathao_password" type="password" class="form-input"></div>
                </div>
                <div class="p-4 border-t bg-slate-50 flex justify-end gap-3">
                    <button @click="showPathaoModal = false" class="px-6 py-2 bg-slate-500 text-white rounded font-medium hover:bg-slate-600 transition">Close</button>
                    <button @click="saveSettings" class="px-6 py-2 bg-[#10b981] text-white rounded font-medium hover:bg-[#059669] transition">Submit</button>
                </div>
            </div>
        </div>

    </AdminLayout>
</template>

<style scoped>
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
.animate-fadeIn { animation: fadeIn 0.2s ease-out forwards; }

.form-input {
    width: 100%;
    padding: 0.6rem 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.25rem;
    outline: none;
    transition: border-color 0.2s;
    color: #334155;
}
.form-input:focus {
    border-color: #94a3b8;
}
</style>
