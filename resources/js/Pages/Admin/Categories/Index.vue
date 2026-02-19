<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

// --- State Variables ---
const categories = ref([]);
const loading = ref(false);
const isSubmitting = ref(false);
const errors = ref({});

// Edit Mode State
const isEditing = ref(false);
const editId = ref(null);

// Form Data
const formData = ref({
    name: ''
});

// --- ১. ক্যাটাগরি লিস্ট লোড করা ---
const fetchCategories = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/admin/categories');
        // লারাভেল API Resource সাধারণত data র‍্যাপার দেয়, সেটা চেক করা হচ্ছে
        categories.value = response.data.data || response.data;
    } catch (error) {
        console.error("Error fetching categories", error);
        Swal.fire('Error', 'Failed to load categories', 'error');
    } finally {
        loading.value = false;
    }
};

// --- ২. সাবমিট (Create অথবা Update) ---
const submit = async () => {
    isSubmitting.value = true;
    errors.value = {};

    try {
        if (isEditing.value) {
            // --- Update Logic ---
            await axios.put(`/api/admin/categories/${editId.value}`, formData.value);
            Swal.fire({ icon: 'success', title: 'Updated!', text: 'Category updated successfully.', timer: 2000, showConfirmButton: false });
        } else {
            // --- Create Logic ---
            await axios.post('/api/admin/categories', formData.value);
            Swal.fire({ icon: 'success', title: 'Created!', text: 'Category created successfully.', timer: 2000, showConfirmButton: false });
        }

        // সফল হলে রিসেট এবং রিফ্রেশ
        resetForm();
        await fetchCategories();

    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            Swal.fire({ icon: 'error', title: 'Error!', text: 'Something went wrong!' });
        }
    } finally {
        isSubmitting.value = false;
    }
};

// --- ৩. এডিট মোড চালু করা ---
const editCategory = (category) => {
    formData.value.name = category.name;
    editId.value = category.id;
    isEditing.value = true;
    errors.value = {};
};

// --- ৪. ফর্ম রিসেট (Cancel Edit) ---
const resetForm = () => {
    formData.value.name = '';
    editId.value = null;
    isEditing.value = false;
    errors.value = {};
};

// --- ৫. ডিলিট ফাংশন ---
const deleteCategory = async (id) => {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    });

    if (result.isConfirmed) {
        try {
            await axios.delete(`/api/admin/categories/${id}`);
            // UI থেকে রিমুভ (API কল না করে)
            categories.value = categories.value.filter(cat => cat.id !== id);

            // যদি এডিট মোডে থাকা ক্যাটাগরি ডিলিট করা হয়, ফর্ম রিসেট হবে
            if (editId.value === id) resetForm();

            Swal.fire('Deleted!', 'Category has been deleted.', 'success');
        } catch (error) {
            Swal.fire('Error!', 'Failed to delete category.', 'error');
        }
    }
};

onMounted(() => {
    fetchCategories();
});
</script>

<template>
    <AdminLayout>
        <div class="max-w-6xl mx-auto p-6">
            <h2 class="text-2xl font-bold mb-6 text-gray-800">Product Categories</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

                <div class="md:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div v-if="loading" class="p-10 flex justify-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                    </div>

                    <table v-else class="w-full text-left">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="p-4 font-semibold text-gray-600 text-sm uppercase">ID</th>
                                <th class="p-4 font-semibold text-gray-600 text-sm uppercase">Name</th>
                                <th class="p-4 font-semibold text-gray-600 text-sm uppercase text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="category in categories" :key="category.id" class="hover:bg-gray-50 transition">
                                <td class="p-4 text-gray-700 text-sm">{{ category.id }}</td>
                                <td class="p-4 text-gray-700 font-medium">{{ category.name }}</td>
                                <td class="p-4 text-right">
                                    <div class="flex justify-end space-x-3">
                                        <button @click="editCategory(category)" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium transition">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <button @click="deleteCategory(category.id)" class="text-red-500 hover:text-red-700 text-sm font-medium transition">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="categories.length === 0">
                                <td colspan="3" class="p-8 text-center text-gray-400">No categories found. Add one from the right.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 h-fit sticky top-24">
                    <h3 class="font-bold text-lg mb-4 text-gray-700 border-b pb-2 flex justify-between items-center">
                        <span>{{ isEditing ? 'Edit Category' : 'Add New Category' }}</span>
                        <button v-if="isEditing" @click="resetForm" class="text-xs text-red-500 hover:underline">Cancel</button>
                    </h3>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Category Name</label>
                            <input
                                v-model="formData.name"
                                type="text"
                                placeholder="e.g. Electronics"
                                class="mt-1 block w-full border-gray-200 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition px-3 py-2"
                                :class="{'border-red-500': errors.name}"
                                :disabled="isSubmitting"
                            >
                            <div v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name[0] }}</div>
                        </div>

                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="w-full text-white py-2.5 rounded-lg font-semibold transition duration-200 shadow-md disabled:opacity-50 flex justify-center items-center gap-2"
                            :class="isEditing ? 'bg-orange-500 hover:bg-orange-600' : 'bg-indigo-600 hover:bg-indigo-700'"
                        >
                            <span v-if="isSubmitting" class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
                            <span>{{ isEditing ? 'Update Category' : 'Save Category' }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
