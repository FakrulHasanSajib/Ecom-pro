<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, onMounted } from 'vue';
import Swal from 'sweetalert2';
import axios from 'axios';

// --- State Variables ---
const productList = ref([]);
const isLoading = ref(true);

// --- Fetch Data from API ---
const fetchProducts = async () => {
    try {
        // আপনার api.php তে Route::get('/products', ...) আছে
        const response = await axios.get('/api/admin/products');

        // ব্যাকএন্ড থেকে আসা ডাটা সেট করা (Data wrapper চেক করা হচ্ছে)
        productList.value = response.data.data || response.data;
    } catch (error) {
        console.error("Failed to load products:", error);
        Swal.fire('Error', 'Failed to load product list', 'error');
    } finally {
        isLoading.value = false;
    }
};

// পেজ লোড হলে ডাটা আনবে
onMounted(() => {
    fetchProducts();
});

// --- Delete Function ---
const deleteProduct = async (id) => {
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
            await axios.delete(`/api/admin/products/${id}`);

            // UI থেকে প্রোডাক্ট রিমুভ করা (পেজ রিফ্রেশ ছাড়া)
            productList.value = productList.value.filter(product => product.id !== id);

            Swal.fire('Deleted!', 'Your product has been deleted.', 'success');
        } catch (error) {
            Swal.fire('Error!', 'Something went wrong.', 'error');
        }
    }
};
</script>

<template>
    <AdminLayout>
        <div class="p-6 bg-gray-100 min-h-screen">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Product List</h2>

                <router-link to="/admin/products/create" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow flex items-center gap-2 transition text-decoration-none">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Add New Product</span>
                </router-link>
            </div>

            <div v-if="isLoading" class="flex justify-center items-center h-64 bg-white rounded-lg shadow">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <div v-else class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-200 text-gray-700 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 text-left">Image</th>
                                <th class="py-3 px-6 text-left">Product Name</th>
                                <th class="py-3 px-6 text-center">Category</th>
                                <th class="py-3 px-6 text-center">Price</th>
                                <th class="py-3 px-6 text-center">Stock</th>
                                <th class="py-3 px-6 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm font-light">
                            <tr v-for="product in productList" :key="product.id" class="border-b border-gray-200 hover:bg-gray-50 transition">

                                <td class="py-3 px-6 text-left">
                                    <div class="flex items-center">
                                        <div class="mr-2">
                                            <img :src="product.thumbnail || 'https://via.placeholder.com/50'"
                                                 class="w-10 h-10 rounded-full border border-gray-300 object-cover"
                                                 alt="Product Image">
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3 px-6 text-left whitespace-nowrap">
                                    <span class="font-medium text-gray-800">{{ product.name }}</span>
                                    <br>
                                    <span class="text-xs text-gray-500">SKU: {{ product.sku || 'N/A' }}</span>
                                </td>

                                <td class="py-3 px-6 text-center">
                                    <span class="bg-purple-100 text-purple-600 py-1 px-3 rounded-full text-xs font-semibold">
                                        {{ product.category ? product.category.name : 'Uncategorized' }}
                                    </span>
                                </td>

                                <td class="py-3 px-6 text-center font-bold text-gray-700">
                                    ৳{{ product.base_price }}
                                </td>

                                <td class="py-3 px-6 text-center">
                                    <span :class="product.total_stock > 10 ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'"
                                          class="py-1 px-3 rounded-full text-xs font-bold">
                                        {{ product.total_stock }}
                                    </span>
                                </td>

                                <td class="py-3 px-6 text-center">
                                    <div class="flex item-center justify-center gap-3">

                                        <a :href="`/product/${product.slug}`" target="_blank"
                                           class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center hover:bg-blue-200 transition"
                                           title="View Live">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                        </a>

                                        <router-link :to="`/admin/products/${product.id}/edit`"
                                           class="w-8 h-8 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center hover:bg-yellow-200 transition text-decoration-none"
                                           title="Edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                            </svg>
                                        </router-link>

                                        <button @click="deleteProduct(product.id)"
                                                class="w-8 h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center hover:bg-red-200 transition"
                                                title="Delete">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="productList.length === 0">
                                <td colspan="6" class="py-10 text-center text-gray-500">
                                    No products found.
                                    <router-link to="/admin/products/create" class="text-blue-500 underline hover:text-blue-700">
                                        Create one?
                                    </router-link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
