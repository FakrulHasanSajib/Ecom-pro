<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const items = ref([]);
const showModal = ref(false);
const form = ref({ name: '', status: true });
const API_URL = 'http://127.0.0.1:73/api/admin/sizes';

const fetchItems = async () => {
    try {
        const res = await axios.get(API_URL, { headers: { Authorization: `Bearer ${localStorage.getItem('token')}` } });
        items.value = res.data;
    } catch (error) { console.error(error); }
};

const saveItem = async () => {
    if (!form.value.name) return Swal.fire('Error', 'Name is required', 'warning');
    try {
        await axios.post(API_URL, form.value, { headers: { Authorization: `Bearer ${localStorage.getItem('token')}` } });
        showModal.value = false;
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Size Saved!', showConfirmButton: false, timer: 1500 });
        fetchItems();
    } catch (error) {
        Swal.fire('Error', 'Failed to save size!', 'error');
        console.error(error);
    }
};

const toggleStatus = async (id) => {
    try {
        await axios.post(`${API_URL}/${id}/toggle-status`, {}, { headers: { Authorization: `Bearer ${localStorage.getItem('token')}` } });
        fetchItems();
    } catch (error) { console.error(error); }
};

const deleteItem = async (id) => {
    if ((await Swal.fire({ title: 'Are you sure?', icon: 'warning', showCancelButton: true })).isConfirmed) {
        try {
            await axios.delete(`${API_URL}/${id}`, { headers: { Authorization: `Bearer ${localStorage.getItem('token')}` } });
            fetchItems();
        } catch (error) { Swal.fire('Error', 'Failed to delete!', 'error'); }
    }
};

onMounted(fetchItems);
</script>

<template>
    <AdminLayout>
        <div class="p-6 bg-[#f8f9fa] min-h-screen">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-slate-800">Size Manage</h2>
                <button @click="showModal = true; form.name = ''" class="bg-[#6366f1] hover:bg-[#4f46e5] text-white px-6 py-2 rounded-full shadow-sm transition">Create Size</button>
            </div>

            <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-x-auto p-4">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-slate-200 text-slate-600">
                            <th class="p-4 font-semibold">SL</th>
                            <th class="p-4 font-semibold">Size Name</th>
                            <th class="p-4 font-semibold">Status</th>
                            <th class="p-4 font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="items.length === 0"><td colspan="4" class="p-4 text-center text-slate-500">No sizes found</td></tr>
                        <tr v-for="(item, i) in items" :key="item.id" class="border-b border-slate-100 hover:bg-slate-50 transition">
                            <td class="p-4 text-slate-600">{{ i + 1 }}</td>
                            <td class="p-4 font-bold text-slate-700">{{ item.name }}</td>
                            <td class="p-4">
                                <span :class="item.status ? 'text-green-700 bg-green-100' : 'text-red-700 bg-red-100'" class="px-3 py-1 rounded-full text-xs font-bold">
                                    {{ item.status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="p-4 flex gap-2">
                                <button @click="toggleStatus(item.id)" class="bg-gray-500 hover:bg-gray-600 text-white p-2 rounded transition" title="Toggle Status">👍</button>
                                <button @click="deleteItem(item.id)" class="bg-red-500 hover:bg-red-600 text-white p-2 rounded transition" title="Delete">🗑️</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 bg-slate-900/50 flex items-center justify-center z-[100]">
            <div class="bg-white p-6 rounded-xl w-full max-w-md shadow-2xl">
                <h3 class="font-bold text-lg text-slate-800 mb-4">Add New Size</h3>
                <label class="block text-sm font-medium text-slate-600 mb-1">Size Name (e.g. XL, 42, Free Size)</label>
                <input v-model="form.name" type="text" class="w-full border border-slate-300 px-4 py-2 rounded-lg mb-6 focus:outline-none focus:border-indigo-500" placeholder="Enter size name">

                <div class="flex justify-end gap-3">
                    <button @click="showModal = false" class="px-5 py-2 bg-slate-200 text-slate-700 font-medium rounded-lg hover:bg-slate-300 transition">Cancel</button>
                    <button @click="saveItem" class="px-5 py-2 bg-[#6366f1] text-white font-medium rounded-lg hover:bg-[#4f46e5] transition">Save Size</button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
