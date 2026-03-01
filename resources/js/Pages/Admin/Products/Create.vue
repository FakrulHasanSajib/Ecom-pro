<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

// --- Router Setup ---
const router = useRouter();
const route = useRoute();

const productId = computed(() => route.params.id);
const isEditMode = computed(() => !!productId.value);

const categories = ref([]);
const brands = ref([]);

// 🔥 Advanced Gallery States (আপনার ছবি অনুযায়ী)
const showMediaManager = ref(false);
const activeModalTab = ref('select'); // 'select' or 'upload'
const mediaTarget = ref('');
const testimonialIndex = ref(null);
const mediaFiles = ref([]);
const mediaLoading = ref(false);
const searchQuery = ref('');
const selectedMedia = ref([]);
const isUploading = ref(false);
const isDragging = ref(false);

const isSubmitting = ref(false);
const isGeneratingSeo = ref(false);
const tagInput = ref('');
const isLoadingData = ref(false);

// --- Form Initialization ---
const form = ref({
    name: '', slug: '', category_id: '', brand_id: '', unit: 'Piece',
    sku: '', stock: 100, purchase_price: '', wholesale_price: '',
    reseller_price: '', base_price: '', offer_price: '',
    enable_variants: false, colors: [], sizes: [], free_shipping: false,
    shipping_inside_dhaka: 80, shipping_outside_dhaka: 150,
    thumbnail: null, images: [], video_link: '', description: '',
    tags: [], meta_title: '', meta_description: '', testimonials: []
});

// --- Helpers ---
const safeJsonParse = (data) => {
    if (!data) return [];
    if (Array.isArray(data)) return data;
    try { return JSON.parse(data); } catch (e) { return []; }
};
const safeTagsParse = (data) => {
    if (!data) return [];
    if (Array.isArray(data)) return data;
    if (typeof data === 'string') return data.split(',').map(t => t.trim()).filter(t => t !== "");
    return [];
};

// --- API Calling ---
onMounted(async () => {
    const token = localStorage.getItem('token');
    const headers = { Authorization: `Bearer ${token}` };

    try {
        const [catRes, brandRes] = await Promise.all([
            axios.get('http://127.0.0.1:73/api/admin/list-categories', { headers }),
            axios.get('http://127.0.0.1:73/api/admin/list-brands', { headers })
        ]);
        categories.value = catRes.data;
        brands.value = brandRes.data;

        if (!isEditMode.value) {
            try {
                const shipRes = await axios.get('http://127.0.0.1:73/api/admin/shipping-defaults', { headers });
                form.value.shipping_inside_dhaka = parseInt(shipRes.data.inside) || 80;
                form.value.shipping_outside_dhaka = parseInt(shipRes.data.outside) || 150;
            } catch (e) {}
        }
    } catch (error) {
        if (error.response?.status === 401) router.push('/login');
    }

    if (isEditMode.value) {
        isLoadingData.value = true;
        try {
            const response = await axios.get(`http://127.0.0.1:73/api/admin/products/${productId.value}`, { headers });
            const p = response.data.data || response.data;
            form.value = {
                ...form.value, ...p,
                category_id: p.category_id || '', brand_id: p.brand_id || '',
                enable_variants: !!p.enable_variants, free_shipping: !!p.free_shipping,
                colors: safeJsonParse(p.colors), sizes: safeJsonParse(p.sizes),
                images: safeJsonParse(p.images), testimonials: safeJsonParse(p.testimonials),
                tags: safeTagsParse(p.tags), stock: p.total_stock || p.stock,
                purchase_price: p.purchase_price, base_price: p.base_price,
                shipping_inside_dhaka: p.shipping_inside_dhaka, shipping_outside_dhaka: p.shipping_outside_dhaka
            };
        } catch (error) {
            Swal.fire('Error', 'Failed to load product data', 'error');
            router.push('/admin/products');
        } finally { isLoadingData.value = false; }
    }
});

watch(() => form.value.name, (newVal) => {
    if (!isEditMode.value) {
        form.value.slug = newVal.toLowerCase().trim().replace(/[^\w\s-]/g, '').replace(/[\s_-]+/g, '-').replace(/^-+|-+$/g, '');
    }
});

const generateSeo = async () => {
    if (!form.value.name) return Swal.fire('Warning', 'Please enter Product Name first', 'warning');
    isGeneratingSeo.value = true;
    try {
        const token = localStorage.getItem('token');
        const res = await axios.post('http://127.0.0.1:73/api/admin/products/generate-seo', {
            name: form.value.name, description: form.value.description
        }, { headers: { Authorization: `Bearer ${token}` } });

        form.value.meta_title = res.data.meta_title || form.value.name;
        form.value.meta_description = res.data.meta_description || '';
        if (res.data.tags) form.value.tags = [...new Set([...form.value.tags, ...safeTagsParse(res.data.tags)])];
        Swal.fire({ icon: 'success', title: 'SEO Generated', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
    } catch (error) {} finally { isGeneratingSeo.value = false; }
};

// ==========================================
// 🔥 Advanced Media Gallery Logic 🔥
// ==========================================
const fetchMedia = async () => {
    mediaLoading.value = true;
    try {
        const token = localStorage.getItem('token');
        const res = await axios.get('http://127.0.0.1:73/api/admin/media', { headers: { Authorization: `Bearer ${token}` } });
        mediaFiles.value = res.data.data || res.data;
    } catch (error) { console.error("Gallery failed to load", error); } finally { mediaLoading.value = false; }
};

const filteredMedia = computed(() => {
    if (!searchQuery.value) return mediaFiles.value;
    return mediaFiles.value.filter(m => m.file_name?.toLowerCase().includes(searchQuery.value.toLowerCase()));
});

const openGallery = (target, index = null) => {
    mediaTarget.value = target;
    testimonialIndex.value = index;
    activeModalTab.value = 'select';
    selectedMedia.value = [];
    searchQuery.value = '';
    showMediaManager.value = true;
    fetchMedia();
};

const closeGallery = () => {
    showMediaManager.value = false;
    selectedMedia.value = [];
};

const toggleMediaSelection = (url) => {
    if (mediaTarget.value === 'thumbnail' || mediaTarget.value === 'testimonial') {
        selectedMedia.value = [url]; // Single Selection
    } else {
        // Multiple Selection for Gallery
        if (selectedMedia.value.includes(url)) {
            selectedMedia.value = selectedMedia.value.filter(u => u !== url);
        } else {
            selectedMedia.value.push(url);
        }
    }
};

const confirmSelection = () => {
    if (selectedMedia.value.length === 0) return Swal.fire('Warning', 'Please select an image', 'warning');

    if (mediaTarget.value === 'thumbnail') {
        form.value.thumbnail = selectedMedia.value[0];
    } else if (mediaTarget.value === 'testimonial' && testimonialIndex.value !== null) {
        form.value.testimonials[testimonialIndex.value].image = selectedMedia.value[0];
    } else if (mediaTarget.value === 'gallery') {
        selectedMedia.value.forEach(url => {
            if (!form.value.images.includes(url)) form.value.images.push(url);
        });
    }
    closeGallery();
};

const handleUpload = async (e) => {
    isDragging.value = false;
    const files = Array.from(e.target.files || e.dataTransfer.files);
    if (!files.length) return;

    isUploading.value = true;
    const token = localStorage.getItem('token');
    let successCount = 0;

    for (const file of files) {
        let formData = new FormData();
        formData.append('file', file);
        try {
            await axios.post('http://127.0.0.1:73/api/admin/media', formData, {
                headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'multipart/form-data' }
            });
            successCount++;
        } catch (error) { console.error('Upload Error:', error); }
    }

    if (successCount > 0) {
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: `${successCount} Image(s) Uploaded!`, showConfirmButton: false, timer: 1500 });
        await fetchMedia();
        activeModalTab.value = 'select'; // Switch to select tab to see uploaded images
    } else {
        Swal.fire('Error', 'Upload failed', 'error');
    }
    isUploading.value = false;
    if(e.target.type === 'file') e.target.value = '';
};

// ==========================================

const addTestimonial = () => form.value.testimonials.push({ type: 'text', content: '', image: '' });
const removeTestimonial = (index) => form.value.testimonials.splice(index, 1);
const addTag = () => { if(tagInput.value) { form.value.tags.push(tagInput.value); tagInput.value = ''; } };

const availableSizes = ['S', 'M', 'L', 'XL', 'XXL'];
const availableColors = [
    { name: 'Red', code: '#FF0000' }, { name: 'Blue', code: '#0000FF' },
    { name: 'Black', code: '#000000' }, { name: 'White', code: '#FFFFFF' },
    { name: 'Green', code: '#008000' }, { name: 'Yellow', code: '#FFFF00' }
];

const submitProduct = async () => {
    isSubmitting.value = true;
    const token = localStorage.getItem('token');
    const headers = { Authorization: `Bearer ${token}` };
    const url = isEditMode.value ? `http://127.0.0.1:73/api/admin/products/${productId.value}/update` : 'http://127.0.0.1:73/api/admin/products';

    try {
        await axios.post(url, form.value, { headers });
        Swal.fire('Success', isEditMode.value ? 'Product updated!' : 'Product created!', 'success');
        router.push('/admin/products');
    } catch (error) {
        Swal.fire('Error', error.response?.data?.message || 'Something went wrong!', 'error');
    } finally { isSubmitting.value = false; }
};
</script>

<template>
    <AdminLayout>

        <div v-if="showMediaManager" class="fixed inset-0 bg-slate-900/60 z-[100] flex items-center justify-center p-4 backdrop-blur-sm">
            <div class="bg-white w-full max-w-6xl h-[85vh] flex flex-col shadow-2xl overflow-hidden animate-up relative">

                <div class="flex items-center justify-between border-b px-6 pt-4 bg-white relative z-10">
                    <div class="flex gap-8">
                        <button @click="activeModalTab = 'select'" :class="activeModalTab === 'select' ? 'border-b-2 border-slate-800 font-bold text-slate-800' : 'text-slate-500 hover:text-slate-800'" class="pb-3 text-lg transition-colors">Select File</button>
                        <button @click="activeModalTab = 'upload'" :class="activeModalTab === 'upload' ? 'border-b-2 border-slate-800 font-bold text-slate-800' : 'text-slate-500 hover:text-slate-800'" class="pb-3 text-lg transition-colors">Upload New</button>
                    </div>
                    <button type="button" @click="closeGallery" class="text-3xl text-slate-400 hover:text-red-500 pb-3 leading-none transition-colors">&times;</button>
                </div>

                <div v-if="activeModalTab === 'select'" class="flex-1 flex flex-col overflow-hidden bg-[#f8f9fa]">
                    <div class="p-4 px-6 flex justify-between items-center bg-white border-b">
                        <h4 class="text-[#6366f1] font-semibold text-lg">Media Library</h4>
                        <input v-model="searchQuery" type="text" placeholder="Search files..." class="border border-slate-300 rounded px-4 py-2 w-64 text-sm focus:outline-none focus:border-[#6366f1] transition-colors">
                    </div>

                    <div class="flex-1 overflow-y-auto px-6 py-6 custom-scrollbar pb-24">
                        <div v-if="mediaLoading" class="flex justify-center items-center h-40">
                            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-[#6366f1]"></div>
                        </div>
                        <div v-else-if="filteredMedia.length === 0" class="text-center py-20 text-slate-500 font-medium">
                            No files found. Go to "Upload New" to add images.
                        </div>
                        <div v-else class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-5">
                            <div v-for="media in filteredMedia" :key="media.id"
                                @click="toggleMediaSelection(media.file_url)"
                                class="bg-white border rounded-lg overflow-hidden cursor-pointer flex flex-col relative transition-all group"
                                :class="selectedMedia.includes(media.file_url) ? 'border-[#6366f1] shadow-md ring-2 ring-[#6366f1]/30' : 'border-slate-200 hover:border-slate-400'">

                                <div class="absolute top-2 left-2 z-10" v-if="selectedMedia.includes(media.file_url)">
                                    <div class="bg-[#6366f1] text-white rounded w-6 h-6 flex items-center justify-center shadow">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                </div>

                                <div class="h-36 bg-slate-100 relative p-2">
                                    <img :src="media.file_url" class="w-full h-full object-contain drop-shadow-sm group-hover:scale-105 transition-transform duration-300">
                                </div>
                                <div class="p-2.5 text-xs text-slate-500 truncate text-center border-t bg-white font-medium">
                                    {{ media.file_name || 'image.jpg' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border-t p-4 px-6 flex justify-between items-center absolute bottom-0 w-full shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] z-20">
                        <div class="flex items-center gap-6">
                            <span class="font-bold text-slate-800 text-sm">{{ selectedMedia.length }} File(s) selected</span>
                            <button v-if="selectedMedia.length > 0" @click="selectedMedia = []" class="text-red-500 text-sm font-semibold hover:text-red-700 transition">Clear Selection</button>
                        </div>
                        <button @click="confirmSelection" class="bg-[#fbbf24] hover:bg-[#f59e0b] text-white px-10 py-2.5 rounded shadow text-sm font-bold transition-transform active:scale-95">Add Files</button>
                    </div>
                </div>

                <div v-if="activeModalTab === 'upload'" class="flex-1 overflow-y-auto p-8 bg-[#f8f9fa] flex items-center justify-center">
                    <div
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="handleUpload"
                        :class="isDragging ? 'bg-indigo-50 border-indigo-400' : 'bg-white border-slate-300'"
                        class="relative w-full max-w-3xl border-2 border-dashed rounded-xl p-20 flex flex-col items-center justify-center text-center transition-all shadow-sm">

                        <input type="file" multiple @change="handleUpload" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" :disabled="isUploading">

                        <div v-if="isUploading" class="text-indigo-600 flex flex-col items-center">
                            <div class="w-12 h-12 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin mb-4"></div>
                            <p class="font-bold text-lg">Uploading Images...</p>
                        </div>
                        <div v-else class="text-slate-500 pointer-events-none">
                            <div class="bg-slate-600 text-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 shadow">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                            </div>
                            <h3 class="text-xl font-bold text-slate-700 mb-2">Drag & Drop Images Here</h3>
                            <p class="text-sm text-slate-400">or click to browse from your computer</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="isLoadingData" class="flex justify-center items-center h-64">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
        </div>

        <div v-else class="product-form-container">
            <div class="header-section">
                <div class="header-text">
                    <h4>
                        <i :class="isEditMode ? 'bi-pencil-square' : 'bi-plus-circle-fill'" class="me-2"></i>
                        {{ isEditMode ? 'Edit Product' : 'Create New Product' }}
                    </h4>
                    <p>{{ isEditMode ? 'Update product information' : 'Fill all the information details correctly' }}</p>
                </div>
                <router-link to="/admin/products" class="btn-manage shadow-sm text-decoration-none">
                    <i class="bi bi-list-ul me-2"></i>Product List
                </router-link>
            </div>

            <form @submit.prevent="submitProduct">

                <div class="form-card animate-up">
                    <div class="card-header">
                        <h5><i class="bi bi-info-circle me-2 text-primary"></i>Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-4">
                            <label>Product Name <span class="required">*</span></label>
                            <input v-model="form.name" type="text" class="form-control-enhanced" placeholder="e.g. Premium Cotton Panjabi" required>
                        </div>

                        <div class="grid-3">
                            <div class="form-group">
                                <label>Category <span class="required">*</span></label>
                                <select v-model="form.category_id" class="form-control-enhanced" required>
                                    <option value="">Select Category</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Brand</label>
                                <select v-model="form.brand_id" class="form-control-enhanced">
                                    <option value="">Select Brand</option>
                                    <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Unit</label>
                                <input v-model="form.unit" type="text" class="form-control-enhanced" placeholder="e.g. Pcs, Kg">
                            </div>
                        </div>

                        <div class="form-group mt-3">
                            <label>SKU (Stock Keeping Unit)</label>
                            <input v-model="form.sku" type="text" class="form-control-enhanced" placeholder="Auto generated if empty">
                        </div>
                    </div>
                </div>

                <div class="form-card animate-up delay-1">
                    <div class="card-header">
                        <h5><i class="bi bi-tag me-2 text-success"></i>Pricing & Inventory</h5>
                    </div>
                    <div class="card-body">
                        <div class="price-grid">
                            <div class="price-card">
                                <span class="price-label">Purchase Price</span>
                                <div class="input-with-icon">
                                    <span>৳</span>
                                    <input v-model="form.purchase_price" type="number" placeholder="0.00">
                                </div>
                            </div>
                            <div class="price-card">
                                <span class="price-label">Wholesale Price</span>
                                <div class="input-with-icon">
                                    <span>৳</span>
                                    <input v-model="form.wholesale_price" type="number" placeholder="0.00">
                                </div>
                            </div>
                            <div class="price-card">
                                <span class="price-label">Reseller Price</span>
                                <div class="input-with-icon">
                                    <span>৳</span>
                                    <input v-model="form.reseller_price" type="number" placeholder="0.00">
                                </div>
                            </div>
                            <div class="price-card highlight-blue">
                                <span class="price-label text-blue">Regular Price <span class="required">*</span></span>
                                <div class="input-with-icon">
                                    <span class="text-blue">৳</span>
                                    <input v-model="form.base_price" type="number" placeholder="0.00" class="text-blue fw-bold" required>
                                </div>
                            </div>
                            <div class="price-card highlight-green">
                                <span class="price-label text-green">Offer Price</span>
                                <div class="input-with-icon">
                                    <span class="text-green">৳</span>
                                    <input v-model="form.offer_price" type="number" placeholder="0.00" class="text-green fw-bold">
                                </div>
                            </div>
                            <div class="price-card bg-dark text-white">
                                <span class="price-label text-white">Stock <span class="required">*</span></span>
                                <input v-model="form.stock" type="number" placeholder="100" class="bg-dark text-white border-white" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="card-body bg-light">
                        <div class="toggle-wrapper mb-3">
                            <input v-model="form.free_shipping" type="checkbox" id="freeShip" class="toggle-checkbox">
                            <label for="freeShip" class="toggle-label">Free Shipping</label>
                        </div>
                        <div v-if="!form.free_shipping" class="grid-2">
                            <div class="form-group">
                                <label>Inside Dhaka Charge</label>
                                <input v-model="form.shipping_inside_dhaka" type="number" class="form-control-enhanced">
                            </div>
                            <div class="form-group">
                                <label>Outside Dhaka Charge</label>
                                <input v-model="form.shipping_outside_dhaka" type="number" class="form-control-enhanced">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-card animate-up delay-2">
                    <div class="card-header flex-between">
                        <h5><i class="bi bi-palette2 me-2 text-warning"></i>Product Variations</h5>
                        <div class="toggle-wrapper">
                            <label for="variantToggle" class="toggle-label me-2">Enable</label>
                            <label class="switch">
                                <input v-model="form.enable_variants" type="checkbox" id="variantToggle">
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                    <div v-if="form.enable_variants" class="card-body bg-gray-50">
                        <div class="mb-4">
                            <label class="bold-label">Sizes</label>
                            <div class="chip-container">
                                <label v-for="size in availableSizes" :key="size" class="size-chip">
                                    <input type="checkbox" :value="size" v-model="form.sizes">
                                    <span>{{ size }}</span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="bold-label">Colors</label>
                            <div class="color-container">
                                <label v-for="color in availableColors" :key="color.name" class="color-circle" :style="{backgroundColor: color.code}">
                                    <input type="checkbox" :value="color.name" v-model="form.colors">
                                    <i v-if="form.colors.includes(color.name)" class="bi bi-check check-icon"></i>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-card animate-up delay-3">
                    <div class="card-header">
                        <h5><i class="bi bi-images me-2 text-info"></i>Product Images</h5>
                    </div>
                    <div class="card-body">
                        <div class="grid-2">
                            <div>
                                <label class="bold-label">Thumbnail Image (Single)</label>
                                <div class="thumbnail-uploader" @click="openGallery('thumbnail')">
                                    <div v-if="form.thumbnail" class="preview-full">
                                        <img :src="form.thumbnail">
                                        <button type="button" @click.stop="form.thumbnail = null" class="btn-remove-float">&times;</button>
                                    </div>
                                    <div v-else class="upload-placeholder">
                                        <i class="bi bi-cloud-upload text-3xl mb-2"></i>
                                        <span class="font-medium text-indigo-600">Browse Gallery</span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="bold-label">Gallery Images (Multiple)</label>
                                <div class="gallery-uploader">
                                    <div class="gallery-grid">
                                        <div class="upload-box-small" @click="openGallery('gallery')">
                                            <i class="bi bi-plus-lg"></i> Add
                                        </div>
                                        <div v-for="(img, idx) in form.images" :key="idx" class="preview-box">
                                            <img :src="img">
                                            <button type="button" @click="form.images.splice(idx, 1)" class="btn-remove-mini">&times;</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-card animate-up delay-4">
                    <div class="card-header">
                        <h5><i class="bi bi-text-paragraph me-2 text-dark"></i>Product Description</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-4">
                            <label>Video Link</label>
                            <input v-model="form.video_link" type="text" class="form-control-enhanced" placeholder="https://youtube.com/...">
                        </div>
                        <div class="editor-container">
                            <label class="bold-label">Full Description</label>
                            <div class="quill-wrapper">
                                <QuillEditor theme="snow" v-model:content="form.description" contentType="html" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-card animate-up delay-5 border-ai">
                    <div class="card-header flex-between bg-ai-light">
                        <h5><i class="bi bi-search me-2 text-primary"></i>SEO Settings</h5>
                        <button type="button" @click="generateSeo" :disabled="isGeneratingSeo" class="btn-ai-generate">
                            <span v-if="isGeneratingSeo" class="spinner-border spinner-border-sm me-2"></span>
                            <i v-else class="bi bi-stars me-2"></i>
                            {{ isGeneratingSeo ? 'Generating...' : 'AI Generate SEO' }}
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label>Slug <span class="required">*</span></label>
                            <input v-model="form.slug" type="text" class="form-control-enhanced bg-gray-50" placeholder="auto-generated-slug">
                            <small class="text-muted">Auto-generated from Product Name</small>
                        </div>
                        <div class="form-group mb-3">
                            <label>Meta Title</label>
                            <input v-model="form.meta_title" type="text" class="form-control-enhanced">
                        </div>
                        <div class="form-group mb-3">
                            <label>Tags</label>
                            <div class="tag-input-wrapper">
                                <span v-for="(t, i) in form.tags" :key="i" class="tag-badge">
                                    #{{ t }} <span @click="form.tags.splice(i,1)">&times;</span>
                                </span>
                                <input v-model="tagInput" @keydown.enter.prevent="addTag" type="text" placeholder="Type tag & Enter...">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Meta Description</label>
                            <textarea v-model="form.meta_description" class="form-control-enhanced" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-card animate-up delay-6">
                    <div class="card-header flex-between">
                        <h5><i class="bi bi-chat-quote me-2 text-danger"></i>Testimonials</h5>
                        <button type="button" @click="addTestimonial" class="btn-add-testi"><i class="bi bi-plus-circle me-1"></i> Add Review</button>
                    </div>
                    <div class="card-body bg-gray-50">
                        <div v-if="form.testimonials.length === 0" class="text-center text-muted py-4">No reviews added yet.</div>
                        <div v-for="(testi, idx) in form.testimonials" :key="idx" class="testimonial-row">
                            <div class="testi-header">
                                <span class="badge-count">#{{ idx + 1 }}</span>
                                <button type="button" @click="removeTestimonial(idx)" class="btn-trash"><i class="bi bi-trash"></i></button>
                            </div>
                            <div class="grid-testimonial">
                                <div class="form-group">
                                    <label>Type</label>
                                    <select v-model="testi.type" class="form-control-enhanced">
                                        <option value="text">Text</option>
                                        <option value="video">Video</option>
                                        <option value="image">Image</option>
                                    </select>
                                </div>
                                <div class="form-group full-col">
                                    <div v-if="testi.type === 'text'">
                                        <label>Customer Review</label>
                                        <textarea v-model="testi.content" class="form-control-enhanced" rows="2"></textarea>
                                    </div>
                                    <div v-else-if="testi.type === 'video'">
                                        <label>YouTube Video ID</label>
                                        <div class="flex gap-2">
                                            <input v-model="testi.content" class="form-control-enhanced" placeholder="Ex: dQw4w9WgXcQ">
                                            <img v-if="testi.content" :src="`https://img.youtube.com/vi/${testi.content}/mqdefault.jpg`" class="h-10 rounded">
                                        </div>
                                    </div>
                                    <div v-else>
                                        <label>Review Image</label>
                                        <div class="flex gap-3 items-center">
                                            <button type="button" @click="openGallery('testimonial', idx)" class="btn-browse-small">Select from Gallery</button>
                                            <img v-if="testi.image" :src="testi.image" class="h-10 rounded border">
                                            <input type="text" v-model="testi.image" class="form-control-enhanced" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="submit-section">
                    <button type="submit" :disabled="isSubmitting" class="btn-submit-final">
                        <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                        <span v-else>
                            <i :class="isEditMode ? 'bi-check2-circle' : 'bi-check-circle-fill'" class="me-2"></i>
                            {{ isEditMode ? 'Update Product' : 'Publish Product' }}
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* --- CORE & LAYOUT --- */
.product-form-container { background: #f3f4f6; padding: 30px; min-height: 100vh; font-family: 'Inter', sans-serif; }
.header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
.header-text h4 { font-weight: 800; color: #111827; margin: 0; font-size: 1.5rem; }
.header-text p { color: #6b7280; margin: 5px 0 0; font-size: 0.9rem; }
.btn-manage { background: white; border: 1px solid #e5e7eb; padding: 10px 20px; border-radius: 8px; font-weight: 600; color: #374151; transition: 0.2s; display: flex; align-items: center; }
.btn-manage:hover { background: #f9fafb; border-color: #d1d5db; }

/* --- CARDS --- */
.form-card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 25px; overflow: hidden; border: 1px solid #e5e7eb; }
.card-header { background: #ffffff; padding: 15px 25px; border-bottom: 1px solid #f3f4f6; }
.card-header h5 { margin: 0; font-size: 1.1rem; font-weight: 700; color: #1f2937; display: flex; align-items: center; }
.card-body { padding: 25px; }

/* --- INPUTS --- */
.form-control-enhanced { width: 100%; padding: 10px 15px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.95rem; color: #111827; transition: border-color 0.2s; background: #fff; }
.form-control-enhanced:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15); }
.bg-gray-50 { background-color: #f9fafb; }
label { display: block; margin-bottom: 8px; font-weight: 600; color: #374151; font-size: 0.9rem; }
.required { color: #ef4444; }
.text-muted { color: #6c757d; font-size: 0.8rem; }

/* --- GRIDS --- */
.grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

/* --- PRICING --- */
.price-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; }
.price-card { border: 1px solid #e5e7eb; padding: 15px; border-radius: 10px; background: #f9fafb; display: flex; flex-direction: column; }
.price-card:hover { border-color: #28c76f; transform: translateY(-3px); box-shadow: 0 5px 15px rgba(40,199,111,0.1); }
.price-label { font-size: 0.8rem; font-weight: 700; color: #6b7280; margin-bottom: 5px; text-transform: uppercase; }
.input-with-icon { position: relative; display: flex; align-items: center; }
.input-with-icon span { position: absolute; left: 10px; font-weight: bold; color: #9ca3af; }
.input-with-icon input { width: 100%; padding: 8px 10px 8px 25px; border: 1px solid #d1d5db; border-radius: 6px; font-weight: 700; }
.highlight-blue { background: #eff6ff; border-color: #bfdbfe; }
.highlight-green { background: #f0fdf4; border-color: #bbf7d0; }

/* --- SWITCH --- */
.switch { position: relative; display: inline-block; width: 44px; height: 24px; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; }
.slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; }
input:checked + .slider { background-color: #6366f1; }
input:checked + .slider:before { transform: translateX(20px); }
.slider.round { border-radius: 34px; }
.slider.round:before { border-radius: 50%; }
.flex-between { display: flex; justify-content: space-between; align-items: center; }

/* --- VARIATIONS --- */
.chip-container { display: flex; gap: 10px; flex-wrap: wrap; }
.size-chip { cursor: pointer; }
.size-chip input { display: none; }
.size-chip span { padding: 8px 16px; border: 1px solid #d1d5db; border-radius: 6px; font-weight: 600; color: #4b5563; display: inline-block; transition: 0.2s; }
.size-chip input:checked + span { background: #1f2937; color: white; border-color: #1f2937; }
.color-container { display: flex; gap: 10px; }
.color-circle { width: 40px; height: 40px; border-radius: 50%; border: 2px solid #e5e7eb; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.color-circle input { display: none; }
.check-icon { color: white; filter: drop-shadow(0 0 2px rgba(0,0,0,0.5)); }

/* --- IMAGES --- */
.thumbnail-uploader { height: 200px; border: 2px dashed #d1d5db; border-radius: 10px; display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; background: #f9fafb; transition: border-color 0.2s; }
.thumbnail-uploader:hover { border-color: #6366f1; }
.upload-placeholder { display: flex; flex-direction: column; align-items: center; color: #9ca3af; }
.preview-full { width: 100%; height: 100%; position: relative; }
.preview-full img { width: 100%; height: 100%; object-fit: contain; border-radius: 8px; }
.btn-remove-float { position: absolute; top: 10px; right: 10px; background: white; border: 1px solid #ef4444; color: #ef4444; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; z-index: 10; display: flex; justify-content: center; align-items: center; font-size: 1.2rem; }
.gallery-grid { display: flex; flex-wrap: wrap; gap: 10px; }
.upload-box-small { width: 80px; height: 80px; border: 2px dashed #d1d5db; border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; color: #6b7280; font-size: 0.8rem; transition: border-color 0.2s; }
.upload-box-small:hover { border-color: #6366f1; color: #6366f1; }
.preview-box { width: 80px; height: 80px; position: relative; border: 1px solid #e5e7eb; border-radius: 8px; }
.preview-box img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; }
.btn-remove-mini { position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; border: none; width: 20px; height: 20px; border-radius: 50%; font-size: 12px; cursor: pointer; display: flex; justify-content: center; align-items: center; }

/* --- QUILL --- */
.quill-wrapper { height: 250px; display: flex; flex-direction: column; border: 1px solid #d1d5db; border-radius: 8px; overflow: hidden; background: white; }

/* --- AI SEO SECTION --- */
.border-ai { border: 2px solid #a855f7; }
.bg-ai-light { background: #f3e8ff; }
.btn-ai-generate { background: linear-gradient(135deg, #a855f7, #ec4899); color: white; border: none; padding: 8px 16px; border-radius: 20px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; box-shadow: 0 4px 10px rgba(168, 85, 247, 0.3); transition: transform 0.2s; }
.btn-ai-generate:hover { transform: scale(1.05); }
.tag-input-wrapper { border: 1px solid #d1d5db; border-radius: 8px; padding: 5px; display: flex; flex-wrap: wrap; gap: 5px; background: white; }
.tag-badge { background: #e5e7eb; padding: 2px 10px; border-radius: 12px; font-size: 0.85rem; display: flex; align-items: center; gap: 5px; color: #374151; font-weight: 600; cursor: pointer; }
.tag-input-wrapper input { border: none; outline: none; padding: 5px; flex-grow: 1; }

/* --- TESTIMONIALS --- */
.btn-add-testi { background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.9rem; transition: background 0.2s; }
.btn-add-testi:hover { background: #059669; }
.testimonial-row { background: white; padding: 15px; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 10px; }
.testi-header { display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px dashed #e5e7eb; padding-bottom: 5px; }
.badge-count { font-size: 0.8rem; font-weight: bold; color: #9ca3af; }
.btn-trash { color: #ef4444; border: none; background: none; cursor: pointer; transition: color 0.2s; }
.btn-trash:hover { color: #dc2626; }
.grid-testimonial { display: grid; grid-template-columns: 1fr 3fr; gap: 15px; }
.btn-browse-small { border: 1px solid #d1d5db; background: white; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: 500; transition: background 0.2s; }
.btn-browse-small:hover { background: #f3f4f6; }

/* --- FOOTER --- */
.submit-section { text-align: center; margin: 40px 0 60px; }
.btn-submit-final { background: #111827; color: white; padding: 15px 50px; border: none; border-radius: 50px; font-size: 1.1rem; font-weight: 700; cursor: pointer; box-shadow: 0 10px 25px rgba(0,0,0,0.1); transition: all 0.2s; }
.btn-submit-final:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(0,0,0,0.15); }
.btn-submit-final:disabled { background: #6b7280; transform: none; box-shadow: none; cursor: not-allowed; }

/* Animations */
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.animate-up { animation: slideUp 0.4s ease forwards; }
.delay-1 { animation-delay: 0.1s; opacity: 0; }
.delay-2 { animation-delay: 0.2s; opacity: 0; }
.delay-3 { animation-delay: 0.3s; opacity: 0; }
.delay-4 { animation-delay: 0.4s; opacity: 0; }
.delay-5 { animation-delay: 0.5s; opacity: 0; }
.delay-6 { animation-delay: 0.6s; opacity: 0; }

.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }

@media (max-width: 768px) {
    .grid-3, .grid-2, .grid-testimonial { grid-template-columns: 1fr; }
    .header-section { flex-direction: column; align-items: flex-start; gap: 15px; }
}
</style>
