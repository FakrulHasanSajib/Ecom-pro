<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const API_URL = 'http://127.0.0.1:73/api/admin/sliders';
const MEDIA_API_URL = 'http://127.0.0.1:73/api/admin/media'; // আপনার গ্যালারি এপিআই

const sliders = ref([]);
const mediaFiles = ref([]);
const loading = ref(true);
const mediaLoading = ref(false);
const searchQuery = ref('');

// Modal States
const showModal = ref(false);
const showGalleryModal = ref(false);

const form = ref({ category_name: '', title: '', link: '', image_path: '', status: true });
const imagePreview = ref(null);

const bannerCategories = ['Free Shipping', 'Home 4 Banner', 'Home Banner', 'Footer Top Ads', 'Slider Bottom Ads', 'Slider'];

const fetchSliders = async () => {
    try {
        const token = localStorage.getItem('token');
        const res = await axios.get(API_URL, { headers: { Authorization: `Bearer ${token}` } });
        sliders.value = res.data;
    } catch (error) { console.error(error); } finally { loading.value = false; }
};

const fetchMedia = async () => {
    mediaLoading.value = true;
    try {
        const token = localStorage.getItem('token');
        const res = await axios.get(MEDIA_API_URL, { headers: { Authorization: `Bearer ${token}` } });
        mediaFiles.value = res.data.data || res.data;
    } catch (error) { console.error("Failed to load media"); } finally { mediaLoading.value = false; }
};

const filteredSliders = computed(() => {
    if (!searchQuery.value) return sliders.value;
    return sliders.value.filter(s => s.category_name?.toLowerCase().includes(searchQuery.value.toLowerCase()));
});

const openCreateModal = () => {
    form.value = { category_name: '', title: '', link: '', image_path: '', status: true };
    imagePreview.value = null;
    showModal.value = true;
};

const openGallery = () => {
    fetchMedia();
    showGalleryModal.value = true;
};

const selectImage = (media) => {
    form.value.image_path = media.path || media.file_path; // আপনার মিডিয়া টেবিলের পাথ কলামের নাম
    imagePreview.value = media.url || media.file_url || `http://127.0.0.1:73/storage/${form.value.image_path}`;
    showGalleryModal.value = false;
};

const saveSlider = async () => {
    if (!form.value.image_path || !form.value.category_name) {
        return Swal.fire('Error', 'Image and Category are required!', 'warning');
    }

    // যেহেতু এখন শুধু টেক্সট পাঠাচ্ছি, FormData এর বদলে সরাসরি JSON পাঠাব
    const payload = {
        category_name: form.value.category_name,
        title: form.value.title,
        link: form.value.link,
        image: form.value.image_path,
        status: form.value.status ? 1 : 0
    };

    try {
        const token = localStorage.getItem('token');
        await axios.post(API_URL, payload, { headers: { Authorization: `Bearer ${token}` } });
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Banner Saved!', showConfirmButton: false, timer: 1500 });
        showModal.value = false;
        fetchSliders();
    } catch (error) { Swal.fire('Error', 'Failed to save banner!', 'error'); }
};

const toggleStatus = async (id) => {
    try {
        const token = localStorage.getItem('token');
        await axios.post(`${API_URL}/${id}/toggle-status`, {}, { headers: { Authorization: `Bearer ${token}` } });
        fetchSliders();
    } catch (error) { Swal.fire('Error', 'Failed to update status', 'error'); }
};

const deleteSlider = async (id) => {
    const result = await Swal.fire({ title: 'Are you sure?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Yes, delete it!' });
    if (result.isConfirmed) {
        try {
            const token = localStorage.getItem('token');
            await axios.delete(`${API_URL}/${id}`, { headers: { Authorization: `Bearer ${token}` } });
            fetchSliders();
        } catch (error) { Swal.fire('Error', 'Failed to delete', 'error'); }
    }
};

onMounted(() => fetchSliders());
</script>

<template>
    <AdminLayout>
        <div class="p-6 bg-[#f8f9fa] min-h-screen">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-slate-800">Banner Manage</h2>
                <button @click="openCreateModal" class="bg-[#6366f1] hover:bg-[#4f46e5] text-white px-6 py-2 rounded-full font-medium transition shadow-sm">
                    Create
                </button>
            </div>

            <div class="bg-white border border-slate-200 rounded-lg shadow-sm">
                <div class="flex justify-between items-center p-4 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-slate-500 font-medium">Search:</span>
                        <input v-model="searchQuery" type="text" class="border border-slate-300 rounded px-3 py-1.5 outline-none focus:border-indigo-400 text-sm w-48">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[#f8f9fa] text-slate-600 text-sm font-semibold border-b border-slate-200">
                                <th class="p-4 w-16">SL</th>
                                <th class="p-4">Category</th>
                                <th class="p-4 text-center">Image</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <tr v-if="loading"><td colspan="5" class="p-8 text-center text-slate-500">Loading...</td></tr>
                            <tr v-else-if="filteredSliders.length === 0"><td colspan="5" class="p-8 text-center text-slate-500">No banners found</td></tr>

                            <tr v-for="(slider, index) in filteredSliders" :key="slider.id" class="border-b border-slate-100 hover:bg-slate-50 transition">
                                <td class="p-4 text-slate-600">{{ index + 1 }}</td>
                                <td class="p-4 text-slate-600 font-medium">{{ slider.category_name }}</td>
                                <td class="p-4 flex justify-center">
                                    <div class="w-16 h-10 border border-slate-200 overflow-hidden bg-white flex items-center justify-center p-0.5 shadow-sm rounded">
                                        <img :src="slider.image_url" alt="Banner" class="w-full h-full object-cover rounded">
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span :class="slider.status ? 'bg-[#a7f3d0] text-[#065f46]' : 'bg-red-100 text-red-700'" class="px-2.5 py-1 rounded text-xs font-semibold">
                                        {{ slider.status ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <button @click="toggleStatus(slider.id)" class="bg-gray-500 hover:bg-gray-600 text-white p-1.5 rounded transition">
                                            <svg v-if="slider.status" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018a2 2 0 01.485.06l3.76.94m-7 10v5a2 2 0 002 2h.096c.5 0 .905-.405.905-.904 0-.715.211-1.413.608-2.008L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5"></path></svg>
                                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path></svg>
                                        </button>
                                        <button @click="deleteSlider(slider.id)" class="bg-[#f43f5e] hover:bg-[#e11d48] text-white p-1.5 rounded transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 bg-slate-900/50 flex items-center justify-center z-[50]">
            <div class="bg-white rounded-lg w-full max-w-md shadow-2xl overflow-hidden">
                <div class="flex justify-between items-center p-4 border-b">
                    <h3 class="text-lg font-bold text-slate-800">Add New Banner</h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm text-slate-600 mb-2">Category *</label>
                        <select v-model="form.category_name" class="w-full px-3 py-2 border border-slate-300 rounded outline-none focus:border-indigo-500 text-sm">
                            <option value="">Select Category</option>
                            <option v-for="cat in bannerCategories" :key="cat" :value="cat">{{ cat }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-600 mb-2">Banner Image *</label>
                        <div @click="openGallery" class="border-2 border-dashed border-slate-300 rounded-lg p-4 text-center hover:bg-slate-50 transition cursor-pointer">
                            <div v-if="!imagePreview" class="text-slate-400 py-4">
                                <span class="text-3xl">🖼️</span>
                                <p class="text-sm mt-2 font-medium">Click to select from Gallery</p>
                            </div>
                            <img v-else :src="imagePreview" class="w-full h-auto rounded-lg max-h-32 object-cover">
                        </div>
                    </div>

                    <div><label class="block text-sm text-slate-600 mb-2">Target Link</label><input v-model="form.link" type="url" placeholder="https://..." class="w-full px-3 py-2 border border-slate-300 rounded outline-none text-sm"></div>
                </div>
                <div class="p-4 border-t bg-slate-50 flex justify-end gap-3">
                    <button @click="showModal = false" class="px-5 py-2 bg-slate-200 text-slate-700 rounded text-sm font-medium hover:bg-slate-300 transition">Close</button>
                    <button @click="saveSlider" class="px-5 py-2 bg-[#6366f1] text-white rounded text-sm font-medium hover:bg-[#4f46e5] transition">Save Banner</button>
                </div>
            </div>
        </div>

        <div v-if="showGalleryModal" class="fixed inset-0 bg-slate-900/70 flex items-center justify-center z-[60]">
            <div class="bg-white rounded-lg w-full max-w-4xl h-[80vh] flex flex-col shadow-2xl overflow-hidden">
                <div class="flex justify-between items-center p-4 border-b bg-slate-50">
                    <h3 class="text-lg font-bold text-slate-800">Media Gallery</h3>
                    <button @click="showGalleryModal = false" class="text-slate-400 hover:text-red-500 font-bold text-xl">✕</button>
                </div>

                <div class="p-6 flex-1 overflow-y-auto">
                    <div v-if="mediaLoading" class="text-center py-20 text-slate-500 font-bold">Loading media...</div>
                    <div v-else-if="mediaFiles.length === 0" class="text-center py-20 text-slate-500">No images found in gallery!</div>

                    <div v-else class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <div v-for="media in mediaFiles" :key="media.id" @click="selectImage(media)"
                            class="relative aspect-square border-2 border-transparent hover:border-indigo-500 rounded-lg overflow-hidden cursor-pointer group shadow-sm">
                            <img :src="media.url || media.file_url || ('http://127.0.0.1:73/storage/' + (media.path || media.file_path))"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            <div class="absolute inset-0 bg-indigo-500/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </AdminLayout>
</template>
