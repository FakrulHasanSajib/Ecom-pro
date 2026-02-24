<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const statuses = ref([]);
const newStatus = ref('');
const loading = ref(true);

const fetchStatuses = async () => {
    loading.value = true;
    try {
        const token = localStorage.getItem('token');
        const res = await axios.get('http://127.0.0.1:73/api/admin/order-statuses', {
            headers: { Authorization: `Bearer ${token}` }
        });
        statuses.value = res.data.data || [];
    } catch (error) {
        console.error("Failed to fetch statuses:", error);
    } finally {
        loading.value = false;
    }
};

const addStatus = async () => {
    if (!newStatus.value) return Swal.fire('Error', 'Status name is required', 'error');

    try {
        const token = localStorage.getItem('token');
        await axios.post('http://127.0.0.1:73/api/admin/order-statuses',
            { name: newStatus.value, color_class: 'text-slate-800' },
            { headers: { Authorization: `Bearer ${token}` } }
        );
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Status Added!', showConfirmButton: false, timer: 1500 });
        newStatus.value = '';
        fetchStatuses();
    } catch (error) {
        Swal.fire('Error', error.response?.data?.message || 'Failed to add status', 'error');
    }
};

const deleteStatus = async (id) => {
    if (!confirm('Are you sure you want to delete this status?')) return;
    try {
        const token = localStorage.getItem('token');
        await axios.delete(`http://127.0.0.1:73/api/admin/order-statuses/${id}`, {
            headers: { Authorization: `Bearer ${token}` }
        });
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Deleted!', showConfirmButton: false, timer: 1500 });
        fetchStatuses();
    } catch (error) {
        Swal.fire('Error', 'Failed to delete status', 'error');
    }
};

onMounted(() => {
    fetchStatuses();
});
</script>

<template>
    <AdminLayout>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 max-w-3xl mx-auto">
            <h2 class="text-2xl font-black text-slate-800 mb-6 border-b pb-4">Manage Order Statuses</h2>

            <form @submit.prevent="addStatus" class="flex gap-4 mb-8">
                <input
                    v-model="newStatus"
                    type="text"
                    placeholder="Enter new status (e.g. Refunded, On Hold)"
                    class="flex-1 bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 outline-none font-bold text-slate-700"
                />
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition-colors">
                    Add Status
                </button>
            </form>

            <div v-if="loading" class="text-center text-slate-500 font-bold animate-pulse py-4">Loading...</div>

            <div v-else class="space-y-3">
                <div v-if="statuses.length === 0" class="text-center text-slate-500">No custom statuses found.</div>

                <div v-for="status in statuses" :key="status.id" class="flex items-center justify-between bg-slate-50 border border-slate-100 p-4 rounded-xl">
                    <span class="font-black text-slate-700 text-lg">{{ status.name }}</span>
                    <button @click="deleteStatus(status.id)" class="text-red-500 hover:bg-red-50 px-3 py-1 rounded-lg font-bold transition-colors">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
