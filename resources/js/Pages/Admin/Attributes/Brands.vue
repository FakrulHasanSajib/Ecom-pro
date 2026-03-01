<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const items = ref([]);
const showModal = ref(false);
const form = ref({ name: '', status: true });
const API_URL = 'http://127.0.0.1:73/api/admin/brands'; // 🔥 Full URL add kora hoyeche

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
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Brand Saved!', showConfirmButton: false, timer: 1500 });
        fetchItems();
    } catch (error) {
        Swal.fire('Error', 'Failed to save brand! Check console.', 'error');
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
                <h2 class="text-2xl font-semibold text-slate-800">Brand Manage</h2>
                <button @click="showModal = true; form.name = ''" class="bg-[#6366f1] text-white px-6 py-2 rounded-full shadow-sm">Create</button>
            </div>
            <div class="bg-white border rounded-lg shadow-sm overflow-x-auto p-4">
                <table class="w-full text-left">
                    <tr class="bg-gray-50 border-b"><th class="p-3">SL</th><th class="p-3">Name</th><th class="p-3">Status</th><th class="p-3">Action</th></tr>
                    <tr v-for="(item, i) in items" :key="item.id" class="border-b">
                        <td class="p-3">{{ i + 1 }}</td><td class="p-3 font-bold">{{ item.name }}</td>
                        <td class="p-3"><span :class="item.status ? 'text-green-600 bg-green-100' : 'text-red-600 bg-red-100'" class="px-2 py-1 rounded text-xs">{{ item.status ? 'Active' : 'Inactive' }}</span></td>
                        <td class="p-3 flex gap-2">
                            <button @click="toggleStatus(item.id)" class="bg-gray-500 text-white p-1.5 rounded">👍</button>
                            <button @click="deleteItem(item.id)" class="bg-red-500 text-white p-1.5 rounded">🗑️</button>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg w-full max-w-md">
                <h3 class="font-bold mb-4">Add Brand</h3>
                <input v-model="form.name" type="text" class="w-full border p-2 rounded mb-4" placeholder="Brand Name">
                <div class="flex justify-end gap-2">
                    <button @click="showModal = false" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                    <button @click="saveItem" class="px-4 py-2 bg-[#6366f1] text-white rounded">Save</button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
