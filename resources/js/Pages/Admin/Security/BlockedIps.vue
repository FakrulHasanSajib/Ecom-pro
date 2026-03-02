<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import AdminLayout from '@/Layouts/AdminLayout.vue'; // 🔥 Admin Layout ইম্পোর্ট করা হলো

// State
const blockedIps = ref([]);
const showModal = ref(false);
const form = ref({
    ip_address: '',
    reason: ''
});
const loading = ref(false);

const API_URL = 'http://127.0.0.1:73/api/admin/blocked-ips';

// API Call Headers
const getHeaders = () => {
    return {
        headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
    };
};

// Fetch All Blocked IPs
const fetchBlockedIps = async () => {
    loading.value = true;
    try {
        const res = await axios.get(API_URL, getHeaders());
        blockedIps.value = res.data;
    } catch (error) {
        console.error('Failed to fetch IPs:', error);
    } finally {
        loading.value = false;
    }
};

// Block New IP
const saveIp = async () => {
    if (!form.value.ip_address) {
        return Swal.fire('Error', 'IP Address is required!', 'warning');
    }

    try {
        await axios.post(API_URL, form.value, getHeaders());

        showModal.value = false;
        form.value = { ip_address: '', reason: '' }; // Reset form

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'IP Blocked Successfully!',
            showConfirmButton: false,
            timer: 1500
        });

        fetchBlockedIps(); // Refresh list
    } catch (error) {
        let msg = 'Failed to block IP!';
        if (error.response && error.response.data && error.response.data.message) {
            msg = error.response.data.message; // Show validation error (e.g. "IP already blocked")
        }
        Swal.fire('Error', msg, 'error');
    }
};

// Unblock IP (Delete)
const unblockIp = async (id) => {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: "This IP will be unblocked and the user will get access again.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10b981', // green
        cancelButtonColor: '#ef4444', // red
        confirmButtonText: 'Yes, Unblock it!'
    });

    if (result.isConfirmed) {
        try {
            await axios.delete(`${API_URL}/${id}`, getHeaders());

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'IP Unblocked Successfully!',
                showConfirmButton: false,
                timer: 1500
            });

            fetchBlockedIps();
        } catch (error) {
            Swal.fire('Error', 'Failed to unblock IP!', 'error');
        }
    }
};

// Date Formatter
const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

onMounted(() => {
    fetchBlockedIps();
});
</script>

<template>
    <AdminLayout>
        <div class="p-6 bg-slate-50 min-h-screen">
            <div class="flex justify-between items-center mb-8 bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                        <span class="text-red-500">🚫</span> IP Blocking System
                    </h1>
                    <p class="text-slate-500 text-sm mt-1">Manage blocked IP addresses to secure your website.</p>
                </div>
                <button @click="showModal = true" class="bg-red-500 hover:bg-red-600 text-white px-6 py-2.5 rounded-xl font-medium shadow-lg shadow-red-500/30 transition-all active:scale-95 flex items-center gap-2">
                    <span>➕</span> Block New IP
                </button>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 text-sm uppercase tracking-wider">
                                <th class="p-4 font-semibold border-b border-slate-200">#</th>
                                <th class="p-4 font-semibold border-b border-slate-200">IP Address</th>
                                <th class="p-4 font-semibold border-b border-slate-200">Reason</th>
                                <th class="p-4 font-semibold border-b border-slate-200">Blocked At</th>
                                <th class="p-4 font-semibold border-b border-slate-200 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="loading">
                                <td colspan="5" class="p-8 text-center text-slate-500">Loading data...</td>
                            </tr>
                            <tr v-else-if="blockedIps.length === 0">
                                <td colspan="5" class="p-10 text-center">
                                    <div class="text-5xl mb-3">🛡️</div>
                                    <p class="text-slate-500 text-lg">No IP addresses are currently blocked.</p>
                                </td>
                            </tr>
                            <tr v-else v-for="(item, index) in blockedIps" :key="item.id" class="hover:bg-slate-50 transition-colors">
                                <td class="p-4 text-slate-600">{{ index + 1 }}</td>
                                <td class="p-4 font-bold text-red-600">{{ item.ip_address }}</td>
                                <td class="p-4 text-slate-600">{{ item.reason || 'No reason provided' }}</td>
                                <td class="p-4 text-slate-500 text-sm">{{ formatDate(item.created_at) }}</td>
                                <td class="p-4 text-center">
                                    <button @click="unblockIp(item.id)" class="bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white px-4 py-2 rounded-lg font-medium transition-colors text-sm">
                                        Unlock
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="showModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden animate-fade-in-up">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                        <h3 class="font-bold text-xl text-slate-800">Block IP Address</h3>
                        <button @click="showModal = false" class="text-slate-400 hover:text-red-500 transition-colors text-xl">✕</button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">IP Address <span class="text-red-500">*</span></label>
                            <input
                                v-model="form.ip_address"
                                type="text"
                                placeholder="e.g. 192.168.1.100"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Reason (Optional)</label>
                            <textarea
                                v-model="form.reason"
                                rows="3"
                                placeholder="Why is this IP being blocked?"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all"
                            ></textarea>
                        </div>
                    </div>

                    <div class="p-6 border-t border-slate-100 flex gap-3 justify-end bg-slate-50">
                        <button @click="showModal = false" class="px-6 py-2.5 rounded-xl font-medium text-slate-600 hover:bg-slate-200 transition-colors">
                            Cancel
                        </button>
                        <button @click="saveIp" class="px-6 py-2.5 rounded-xl font-medium bg-red-500 text-white hover:bg-red-600 shadow-lg shadow-red-500/30 transition-colors">
                            Block IP
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up {
    animation: fadeInUp 0.3s ease-out forwards;
}
</style>
