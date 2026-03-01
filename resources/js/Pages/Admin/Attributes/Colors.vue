<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const items = ref([]);
const showModal = ref(false);
const form = ref({ name: '', code: '#000000', status: true });
const API_URL = 'http://127.0.0.1:73/api/admin/colors';

const fetchItems = async () => {
    try {
        const res = await axios.get(API_URL, { headers: { Authorization: `Bearer ${localStorage.getItem('token')}` } });
        items.value = res.data;
    } catch (error) { console.error(error); }
};

const saveItem = async () => {
    if (!form.value.name || !form.value.code) return Swal.fire('Error', 'Name and Color Code are required', 'warning');
    try {
        await axios.post(API_URL, form.value, { headers: { Authorization: `Bearer ${localStorage.getItem('token')}` } });
        showModal.value = false;
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Color Saved!', showConfirmButton: false, timer: 1500 });
        fetchItems();
    } catch (error) {
        Swal.fire('Error', 'Failed to save color!', 'error');
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
                <h2 class="text-2xl font-semibold text-slate-800">Color Manage</h2>
                <button @click="showModal = true; form.name = ''; form.code = '#000000'" class="bg-[#6366f1] hover:bg-[#4f46e5] text-white px-6 py-2 rounded-full shadow-sm transition">Create Color</button>
            </div>

            <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-x-auto p-4">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-slate-200 text-slate-600">
                            <th class="p-4 font-semibold">SL</th>
                            <th class="p-4 font-semibold">Color Name</th>
                            <th class="p-4 font-semibold">Color View</th>
                            <th class="p-4 font-semibold">Status</th>
                            <th class="p-4 font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="items.length === 0"><td colspan="5" class="p-4 text-center text-slate-500">No colors found</td></tr>
                        <tr v-for="(item, i) in items" :key="item.id" class="border-b border-slate-100 hover:bg-slate-50 transition">
                            <td class="p-4 text-slate-600">{{ i + 1 }}</td>
                            <td class="p-4 font-bold text-slate-700">{{ item.name }}</td>
                            <td class="p-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full border border-gray-300 shadow-sm" :style="{ backgroundColor: item.code }"></div>
                                    <span class="text-xs text-slate-500">{{ item.code }}</span>
                                </div>
                            </td>
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
                <h3 class="font-bold text-lg text-slate-800 mb-4">Add New Color</h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-600 mb-1">Color Name (e.g. Red, Navy Blue)</label>
                    <input v-model="form.name" type="text" class="w-full border border-slate-300 px-4 py-2 rounded-lg focus:outline-none focus:border-indigo-500" placeholder="Enter color name">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-600 mb-1">Select Color Code</label>
                    <div class="flex items-center gap-3">
                        <input v-model="form.code" type="color" class="h-10 w-16 cursor-pointer border-0 rounded p-0">
                        <input v-model="form.code" type="text" class="border border-slate-300 px-4 py-2 rounded-lg focus:outline-none focus:border-indigo-500 flex-1">
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button @click="showModal = false" class="px-5 py-2 bg-slate-200 text-slate-700 font-medium rounded-lg hover:bg-slate-300 transition">Cancel</button>
                    <button @click="saveItem" class="px-5 py-2 bg-[#6366f1] text-white font-medium rounded-lg hover:bg-[#4f46e5] transition">Save Color</button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
