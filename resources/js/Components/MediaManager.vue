<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const emit = defineEmits(['close', 'select']);
const images = ref([]);
const uploading = ref(false);
const selectedImage = ref(null);

// ১. ছবি লোড করা
const fetchImages = async () => {
    try {
        const res = await axios.get('/api/admin/media');
        images.value = res.data;
    } catch (error) {
        console.error("Gallery Load Error:", error);
    }
};

// ২. নতুন ছবি আপলোড করা
const handleFileUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    let formData = new FormData();
    formData.append('file', file);
    uploading.value = true;

    try {
        await axios.post('/api/admin/media', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        // টোস্ট অ্যালার্ট
        Swal.fire({
            icon: 'success', title: 'Uploaded!', text: 'Image added to gallery',
            toast: true, position: 'top-end', showConfirmButton: false, timer: 2000
        });

        await fetchImages(); // রিফ্রেশ
    } catch (e) {
        Swal.fire('Error', 'Upload failed! Check file size.', 'error');
    } finally {
        uploading.value = false;
    }
};

const selectImage = (url) => {
    selectedImage.value = url;
};

const confirmSelection = () => {
    if (selectedImage.value) {
        emit('select', selectedImage.value);
        // মডাল বন্ধ করার আগে সিলেকশন ক্লিয়ার
        selectedImage.value = null;
    }
};

onMounted(() => fetchImages());
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm">
        <div class="bg-white w-full max-w-4xl rounded-xl shadow-2xl flex flex-col max-h-[85vh] overflow-hidden">

            <div class="p-4 border-b flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-lg text-gray-700">Media Gallery</h3>
                <button @click="$emit('close')" class="text-gray-500 hover:text-red-500 text-2xl">&times;</button>
            </div>

            <div class="p-4 border-b bg-white flex justify-between items-center">
                <label class="cursor-pointer bg-indigo-600 text-white px-5 py-2 rounded-lg hover:bg-indigo-700 transition shadow-md flex items-center gap-2">
                    <i class="bi bi-cloud-upload"></i>
                    <span v-if="uploading">Uploading...</span>
                    <span v-else>Upload New</span>
                    <input type="file" class="hidden" @change="handleFileUpload" accept="image/*">
                </label>
                <p class="text-sm text-gray-500">{{ images.length }} images found</p>
            </div>

            <div class="p-5 overflow-y-auto flex-1 bg-gray-100">
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-4">
                    <div
                        v-for="(img, index) in images"
                        :key="index"
                        @click="selectImage(img.path)"
                        :class="{'ring-4 ring-indigo-500 scale-95': selectedImage === img.path}"
                        class="cursor-pointer group relative aspect-square bg-white rounded-lg overflow-hidden border hover:shadow-lg transition duration-200"
                    >
                        <img :src="img.path" class="w-full h-full object-cover">
                        <div v-if="selectedImage === img.path" class="absolute inset-0 bg-indigo-500 bg-opacity-20 flex items-center justify-center">
                            <i class="bi bi-check-circle-fill text-white text-3xl drop-shadow-md"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-4 border-t bg-gray-50 flex justify-end space-x-3">
                <button @click="$emit('close')" class="px-5 py-2 text-gray-600 hover:bg-gray-200 rounded-lg font-medium">Cancel</button>
                <button
                    @click="confirmSelection"
                    :disabled="!selectedImage"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 font-bold shadow-sm"
                >
                    Use Selected Image
                </button>
            </div>
        </div>
    </div>
</template>
