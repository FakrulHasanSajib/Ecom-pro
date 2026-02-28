<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const API_URL = 'http://127.0.0.1:73/api/admin/media';
const mediaFiles = ref([]);
const loading = ref(true);
const isUploading = ref(false);
const isDragging = ref(false);
const selectedFiles = ref([]);

const fetchMedia = async () => {
    loading.value = true;
    try {
        const token = localStorage.getItem('token');
        const res = await axios.get(API_URL, { headers: { Authorization: `Bearer ${token}` } });
        mediaFiles.value = res.data.data;
        selectedFiles.value = []; // Reset selection after reload
    } catch (error) { console.error(error); } finally { loading.value = false; }
};

// একসাথে একাধিক ছবি আপলোডের লজিক
// একসাথে একাধিক ছবি আপলোডের লজিক (আপডেট করা হয়েছে)
const handleUpload = async (e) => {
    isDragging.value = false;
    const files = Array.from(e.target.files || e.dataTransfer.files);
    if (!files.length) return;

    isUploading.value = true;
    const token = localStorage.getItem('token');

    for (const file of files) {
        let formData = new FormData();

        // 🔥 এই লাইনটি সবচেয়ে গুরুত্বপূর্ণ: নাম অবশ্যই 'file' হবে
        formData.append('file', file);

        try {
            await axios.post(API_URL, formData, {
                headers: {
                    Authorization: `Bearer ${token}`,
                    'Content-Type': 'multipart/form-data'
                }
            });
        } catch (error) {
            console.error('Upload error:', error.response?.data);
        }
    }
    // ... বাকি কোড
    fetchMedia();
    isUploading.value = false;
};

// ছবি ডিলিট
const deleteMedia = async (id) => {
    const result = await Swal.fire({ title: 'Are you sure?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Yes, delete it!' });
    if (result.isConfirmed) {
        try {
            const token = localStorage.getItem('token');
            await axios.delete(`${API_URL}/${id}`, { headers: { Authorization: `Bearer ${token}` } });
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Deleted!', showConfirmButton: false, timer: 1000 });
            fetchMedia();
        } catch (error) { Swal.fire('Error', 'Failed to delete', 'error'); }
    }
};

// সিঙ্গল ইমেজের লিংক কপি করা
const copyUrl = (url) => {
    navigator.clipboard.writeText(url);
    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Link Copied!', showConfirmButton: false, timer: 1000 });
};

// সিলেক্ট করা সবগুলো ইমেজের লিংক কপি করা
const copySelected = () => {
    if (selectedFiles.value.length === 0) {
        return Swal.fire('Warning', 'Please select at least one image!', 'warning');
    }

    const urls = mediaFiles.value
        .filter(m => selectedFiles.value.includes(m.id))
        .map(m => m.file_url)
        .join('\n');

    navigator.clipboard.writeText(urls);
    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: `${selectedFiles.value.length} Links Copied!`, showConfirmButton: false, timer: 1500 });
};

// চেক বক্স টগল
const toggleSelect = (id) => {
    if (selectedFiles.value.includes(id)) {
        selectedFiles.value = selectedFiles.value.filter(fileId => fileId !== id);
    } else {
        selectedFiles.value.push(id);
    }
};

onMounted(() => fetchMedia());
</script>

<template>
    <AdminLayout>
        <div class="p-4 md:p-8 bg-[#f8f9fa] min-h-screen">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <h2 class="text-2xl font-semibold text-slate-800">Media Gallery</h2>

                <div class="flex items-center gap-3">
                    <button @click="fetchMedia" class="bg-[#00b4d8] hover:bg-[#0096c7] text-white px-5 py-2 rounded font-medium transition shadow-sm flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Sync Files
                    </button>
                    <button @click="copySelected" class="bg-slate-500 hover:bg-slate-600 text-white px-5 py-2 rounded font-medium transition shadow-sm flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        Copy Selected
                    </button>
                </div>
            </div>

            <div
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleUpload"
                :class="isDragging ? 'bg-indigo-50 border-indigo-400' : 'bg-white border-[#8b5cf6]/50'"
                class="relative border-2 border-dashed rounded-lg p-12 flex flex-col items-center justify-center text-center transition-all mb-8 shadow-sm"
            >
                <input type="file" multiple @change="handleUpload" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" :disabled="isUploading">

                <div v-if="isUploading" class="text-indigo-600 flex flex-col items-center">
                    <div class="w-10 h-10 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin mb-3"></div>
                    <p class="font-bold">Uploading Images...</p>
                </div>
                <div v-else class="text-slate-500 pointer-events-none">
                    <div class="bg-slate-600 text-white w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4 shadow">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-700 mb-1">Drag & Drop Images Here</h3>
                    <p class="text-xs text-slate-400">Max size: 2MB | Types: jpg, png, webp</p>
                </div>
            </div>

            <div v-if="loading" class="text-center py-10 text-slate-500">Loading gallery...</div>
            <div v-else-if="mediaFiles.length === 0" class="text-center py-10 text-slate-500 bg-white rounded-lg border border-slate-200">No images found in gallery!</div>

            <div v-else class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">

                <div v-for="media in mediaFiles" :key="media.id" class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden flex flex-col group">
                    <div class="absolute p-2 z-10">
                        <input type="checkbox" :checked="selectedFiles.includes(media.id)" @change="toggleSelect(media.id)" class="w-5 h-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer shadow-sm">
                    </div>

                    <div class="w-full aspect-square bg-slate-100 relative">
                        <img :src="media.file_url" class="w-full h-full object-cover">
                    </div>

                    <div class="flex items-center justify-between p-2 border-t border-slate-100 bg-white">
                        <button @click="copyUrl(media.file_url)" class="text-[#6366f1] hover:text-[#4f46e5] bg-indigo-50 hover:bg-indigo-100 p-2 rounded transition flex-1 flex justify-center mr-1" title="Copy Link">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        </button>
                        <button @click="deleteMedia(media.id)" class="text-[#f43f5e] hover:text-[#e11d48] bg-rose-50 hover:bg-rose-100 p-2 rounded transition flex-1 flex justify-center ml-1" title="Delete">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </AdminLayout>
</template>
