<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

// ১. স্টেট ডিক্লেয়ারেশন
const categories = ref([]);
const loading = ref(false);
const errors = ref({});

// ২. ফর্ম ডাটা (Inertia useForm এর বদলে সাধারণ ref)
const formData = ref({
    name: ''
});

// ৩. ক্যাটাগরি লিস্ট নিয়ে আসা (API থেকে)
const fetchCategories = async () => {
    try {
        const response = await axios.get('/api/admin/categories'); // API রাউট
        categories.value = response.data.data; // লারাভেল রিসোর্স সাধারণত 'data' এর ভেতর থাকে
    } catch (error) {
        console.error("Error fetching categories", error);
    }
};

// ৪. নতুন ক্যাটাগরি তৈরি করা (Axios POST)
const submit = async () => {
    loading.value = true;
    errors.value = {};

    try {
        const response = await axios.post('/api/admin/categories', formData.value);

        // সাকসেস হ্যান্ডলিং
        formData.value.name = ''; // ফর্ম রিসেট
        await fetchCategories(); // লিস্ট রিফ্রেশ

        Swal.fire({
            title: 'Success!',
            text: 'Category Created Successfully!',
            icon: 'success',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            Swal.fire({
                title: 'Error!',
                text: 'Something went wrong!',
                icon: 'error',
            });
        }
    } finally {
        loading.value = false;
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
                    <table class="w-full text-left">
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
                                        <button class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">Edit</button>
                                        <button class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="categories.length === 0">
                                <td colspan="3" class="p-8 text-center text-gray-400">No categories found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 h-fit">
                    <h3 class="font-bold text-lg mb-4 text-gray-700 border-b pb-2">Add New Category</h3>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Category Name</label>
                            <input
                                v-model="formData.name"
                                type="text"
                                placeholder="e.g. Electronics"
                                class="mt-1 block w-full border-gray-200 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition px-3 py-2"
                                :disabled="loading"
                            >
                            <div v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name[0] }}</div>
                        </div>
                        <button
                            type="submit"
                            :disabled="loading"
                            class="w-full bg-indigo-600 text-white py-2.5 rounded-lg font-semibold hover:bg-indigo-700 transition duration-200 shadow-md disabled:opacity-50"
                        >
                            <span v-if="loading">Saving...</span>
                            <span v-else>Save Category</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
