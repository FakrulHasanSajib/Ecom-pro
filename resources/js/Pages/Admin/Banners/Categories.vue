<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const categories = ref([]);
const loading = ref(true);
const showModal = ref(false);
const form = ref({ name: '', status: true });

const fetchCategories = async () => {
    try {
        const token = localStorage.getItem('token');
        const res = await axios.get('/api/admin/banner-categories', { headers: { Authorization: `Bearer ${token}` } });
        categories.value = res.data;
    } finally { loading.value = false; }
};

const saveCategory = async () => {
    if (!form.value.name) return Swal.fire('Error', 'Name is required', 'warning');
    try {
        const token = localStorage.getItem('token');
        await axios.post('/api/admin/banner-categories', {
            name: form.value.name,
            status: form.value.status ? 1 : 0
        }, { headers: { Authorization: `Bearer ${token}` } });
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Saved!', showConfirmButton: false, timer: 1500 });
        showModal.value = false;
        fetchCategories();
    } catch (e) { Swal.fire('Error', 'Failed to save', 'error'); }
};

const toggleStatus = async (id) => {
    const token = localStorage.getItem('token');
    await axios.post(`/api/admin/banner-categories/${id}/toggle-status`, {}, { headers: { Authorization: `Bearer ${token}` } });
    fetchCategories();
};

const deleteCat = async (id) => {
    const result = await Swal.fire({ title: 'Are you sure?', icon: 'warning', showCancelButton: true });
    if (result.isConfirmed) {
        const token = localStorage.getItem('token');
        await axios.delete(`/api/admin/banner-categories/${id}`, { headers: { Authorization: `Bearer ${token}` } });
        fetchCategories();
    }
};

onMounted(() => fetchCategories());
</script>

<template>
    <AdminLayout>
        <div class="p-6 bg-[#f8f9fa] min-h-screen">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-slate-800">Category Manage</h2>
                <button @click="showModal = true; form.name = ''" class="bg-[#6366f1] hover:bg-[#4f46e5] text-white px-6 py-2 rounded-full font-medium transition shadow-sm">Create</button>
            </div>

            <div class="bg-white border border-slate-200 rounded-lg shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[#f8f9fa] text-slate-600 text-sm font-semibold border-b">
                                <th class="p-4">SL</th>
                                <th class="p-4">Name</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <tr v-for="(cat, index) in categories" :key="cat.id" class="border-b hover:bg-slate-50">
                                <td class="p-4 text-slate-600">{{ index + 1 }}</td>
                                <td class="p-4 text-slate-600">{{ cat.name }}</td>
                                <td class="p-4">
                                    <span :class="cat.status ? 'bg-[#a7f3d0] text-[#065f46]' : 'bg-red-100 text-red-700'" class="px-2.5 py-1 rounded text-xs font-semibold">
                                        {{ cat.status ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="p-4 flex gap-2">
                                    <button @click="toggleStatus(cat.id)" class="bg-gray-500 text-white p-1.5 rounded" title="Toggle Status">👍</button>
                                    <button @click="deleteCat(cat.id)" class="bg-red-500 text-white p-1.5 rounded">🗑️</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 bg-slate-900/50 flex items-center justify-center z-[50]">
            <div class="bg-white rounded-lg w-full max-w-md shadow-2xl overflow-hidden p-6">
                <h3 class="text-lg font-bold mb-4">Add Category</h3>
                <input v-model="form.name" type="text" class="w-full border p-2 rounded mb-4" placeholder="e.g. Free Shipping">
                <div class="flex justify-end gap-2">
                    <button @click="showModal = false" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                    <button @click="saveCategory" class="px-4 py-2 bg-indigo-600 text-white rounded">Save</button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
