<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router'; // Inertia বাদ দিয়ে Vue Router
import axios from 'axios';
import Swal from 'sweetalert2';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import MediaManager from '@/Components/MediaManager.vue';

// --- Router Setup ---
const router = useRouter();
const route = useRoute();

// --- State Variables ---
// URL থেকে ID চেক করা হচ্ছে (Edit Mode এর জন্য)
const productId = computed(() => route.params.id);
const isEditMode = computed(() => !!productId.value);

const categories = ref([]);
const brands = ref([]);
const showMediaManager = ref(false);
const mediaTarget = ref('');
const testimonialIndex = ref(null);
const isSubmitting = ref(false);
const isGeneratingSeo = ref(false);
const tagInput = ref('');
const isLoadingData = ref(false); // ডাটা লোডিং এর জন্য

// --- Form Initialization (Reactive Object) ---
const form = ref({
    name: '',
    slug: '',
    category_id: '',
    brand_id: '',
    unit: 'Piece',
    sku: '',
    stock: 100,
    purchase_price: '',
    wholesale_price: '',
    reseller_price: '',
    base_price: '',
    offer_price: '',
    enable_variants: false,
    colors: [],
    sizes: [],
    free_shipping: false,
    shipping_inside_dhaka: 80, // ডিফল্ট (API কল হওয়ার পর আপডেট হবে)
    shipping_outside_dhaka: 150, // ডিফল্ট (API কল হওয়ার পর আপডেট হবে)
    thumbnail: null,
    images: [],
    video_link: '',
    description: '',
    tags: [],
    meta_title: '',
    meta_description: '',
    testimonials: []
});

// --- Helpers: Safe Parsers ---
const safeJsonParse = (data) => {
    if (!data) return [];
    if (Array.isArray(data)) return data;
    try { return JSON.parse(data); } catch (e) { return []; }
};

const safeTagsParse = (data) => {
    if (!data) return [];
    if (Array.isArray(data)) return data;
    if (typeof data === 'string') return data.split(',').map(tag => tag.trim()).filter(tag => tag !== "");
    return [];
};

// --- Initialization (API Calling) ---
onMounted(async () => {
    // ১. ক্যাটাগরি, ব্র্যান্ড এবং ডায়নামিক শিপিং ডাটা লোড করা
    try {
        const [catRes, brandRes] = await Promise.all([
            axios.get('/api/admin/list-categories'),
            axios.get('/api/admin/list-brands')
        ]);
        categories.value = catRes.data;
        brands.value = brandRes.data;

        // ✅ Create Mode হলে Settings টেবিল থেকে ডায়নামিক শিপিং চার্জ আনা
        if (!isEditMode.value) {
            try {
                const shipRes = await axios.get('/api/admin/shipping-defaults');
                form.value.shipping_inside_dhaka = parseInt(shipRes.data.inside) || 80;
                form.value.shipping_outside_dhaka = parseInt(shipRes.data.outside) || 150;
            } catch (shipErr) {
                console.warn("Shipping defaults could not be loaded, using fallbacks.");
            }
        }

    } catch (error) {
        console.error("Dependency loading failed:", error);
    }

    // ২. যদি এডিট মোড হয়, তাহলে প্রোডাক্টের নিজস্ব ডাটা আনা
    if (isEditMode.value) {
        isLoadingData.value = true;
        try {
            const response = await axios.get(`/api/admin/products/${productId.value}`);
            const p = response.data.data || response.data; // ডাটা স্ট্রাকচার হ্যান্ডলিং

            // ফর্মে ডাটা সেট করা
            form.value = {
                ...form.value, // ডিফল্ট ভ্যালু রাখা
                ...p, // API থেকে আসা ডাটা ওভাররাইড করা
                category_id: p.category_id || '',
                brand_id: p.brand_id || '',
                enable_variants: !!p.enable_variants, // 1/0 কে boolean এ কনভার্ট
                free_shipping: !!p.free_shipping,

                // JSON ডাটাগুলো পার্স করা
                colors: safeJsonParse(p.colors),
                sizes: safeJsonParse(p.sizes),
                images: safeJsonParse(p.images),
                testimonials: safeJsonParse(p.testimonials),
                tags: safeTagsParse(p.tags),

                // নাম্বার ফরম্যাট নিশ্চিত করা
                stock: p.total_stock || p.stock,
                purchase_price: p.purchase_price,
                base_price: p.base_price,
                shipping_inside_dhaka: p.shipping_inside_dhaka,
                shipping_outside_dhaka: p.shipping_outside_dhaka
            };
        } catch (error) {
            Swal.fire('Error', 'Failed to load product data', 'error');
            router.push('/admin/products');
        } finally {
            isLoadingData.value = false;
        }
    }
});

// --- Auto Slug (Only for Create Mode) ---
watch(() => form.value.name, (newVal) => {
    if (!isEditMode.value) {
        form.value.slug = newVal.toLowerCase().trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
});

// --- AI SEO Logic ---
const generateSeo = async () => {
    if (!form.value.name) return Swal.fire('Warning', 'Please enter Product Name first', 'warning');

    isGeneratingSeo.value = true;
    try {
        const response = await axios.post('/api/admin/products/generate-seo', {
            name: form.value.name,
            description: form.value.description
        });
        const data = response.data;

        form.value.meta_title = data.meta_title || form.value.name;
        form.value.meta_description = data.meta_description || '';

        if (data.tags) {
            const newTags = safeTagsParse(data.tags);
            form.value.tags = [...new Set([...form.value.tags, ...newTags])];
        }

        Swal.fire({
            icon: 'success', title: 'SEO Generated',
            toast: true, position: 'top-end', showConfirmButton: false, timer: 2000
        });
    } catch (error) {
        Swal.fire('Error', 'AI generation failed. Check API Key.', 'error');
    } finally {
        isGeneratingSeo.value = false;
    }
};

// --- Gallery & Media Logic ---
const openGallery = (target, index = null) => {
    mediaTarget.value = target;
    testimonialIndex.value = index;
    showMediaManager.value = true;
};

const mediaFiles = ref([]);
const fetchMedia = async () => {
    const token = localStorage.getItem('token');
    const res = await axios.get('http://127.0.0.1:73/api/admin/media', {
        headers: { Authorization: `Bearer ${token}` }
    });
    mediaFiles.value = res.data.data;
};

const handleMediaSelect = (url) => {
    if (mediaTarget.value === 'thumbnail') {
        form.value.thumbnail = url;
    } else if (mediaTarget.value === 'gallery') {
        if (!form.value.images.includes(url)) form.value.images.push(url);
    } else if (mediaTarget.value === 'testimonial' && testimonialIndex.value !== null) {
        form.value.testimonials[testimonialIndex.value].image = url;
    }
    showMediaManager.value = false;
};

// --- Array Manipulations ---
const addTestimonial = () => form.value.testimonials.push({ type: 'text', content: '', image: '' });
const removeTestimonial = (index) => form.value.testimonials.splice(index, 1);
const addTag = () => {
    if(tagInput.value) {
        form.value.tags.push(tagInput.value);
        tagInput.value = '';
    }
};

const availableSizes = ['S', 'M', 'L', 'XL', 'XXL'];
const availableColors = [
    { name: 'Red', code: '#FF0000' }, { name: 'Blue', code: '#0000FF' },
    { name: 'Black', code: '#000000' }, { name: 'White', code: '#FFFFFF' },
    { name: 'Green', code: '#008000' }, { name: 'Yellow', code: '#FFFF00' }
];

// --- Submit Logic (Unified) ---
const submitProduct = async () => {
    isSubmitting.value = true;
    try {
        if (isEditMode.value) {
            // Update Request
            await axios.post(`/api/admin/products/${productId.value}/update`, form.value);
            Swal.fire('Success', 'Product updated successfully!', 'success');
        } else {
            // Create Request
            await axios.post('/api/admin/products', form.value);
            Swal.fire('Success', 'Product created successfully!', 'success');
        }

        // সফল হলে প্রোডাক্ট লিস্টে রিডাইরেক্ট
        router.push('/admin/products');

    } catch (error) {
        console.error(error);
        const msg = error.response?.data?.message || 'Something went wrong!';
        Swal.fire('Error', msg, 'error');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <AdminLayout>
        <MediaManager v-if="showMediaManager" @close="showMediaManager = false" @select="handleMediaSelect" />

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
                                        <i class="bi bi-cloud-upload text-3xl"></i>
                                        <span>Select Thumbnail</span>
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
                                            <button type="button" @click="openGallery('testimonial', idx)" class="btn-browse-small">Select</button>
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
.thumbnail-uploader { height: 200px; border: 2px dashed #d1d5db; border-radius: 10px; display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; background: #f9fafb; }
.upload-placeholder { display: flex; flex-direction: column; align-items: center; color: #9ca3af; }
.preview-full { width: 100%; height: 100%; }
.preview-full img { width: 100%; height: 100%; object-fit: contain; border-radius: 8px; }
.btn-remove-float { position: absolute; top: 10px; right: 10px; background: white; border: 1px solid #ef4444; color: #ef4444; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; }
.gallery-grid { display: flex; flex-wrap: wrap; gap: 10px; }
.upload-box-small { width: 80px; height: 80px; border: 2px dashed #d1d5db; border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; color: #6b7280; font-size: 0.8rem; }
.preview-box { width: 80px; height: 80px; position: relative; border: 1px solid #e5e7eb; border-radius: 8px; }
.preview-box img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; }
.btn-remove-mini { position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; border: none; width: 20px; height: 20px; border-radius: 50%; font-size: 12px; cursor: pointer; }

/* --- QUILL --- */
.quill-wrapper { height: 250px; display: flex; flex-direction: column; border: 1px solid #d1d5db; border-radius: 8px; overflow: hidden; }

/* --- AI SEO SECTION --- */
.border-ai { border: 2px solid #a855f7; }
.bg-ai-light { background: #f3e8ff; }
.btn-ai-generate { background: linear-gradient(135deg, #a855f7, #ec4899); color: white; border: none; padding: 8px 16px; border-radius: 20px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; box-shadow: 0 4px 10px rgba(168, 85, 247, 0.3); transition: transform 0.2s; }
.btn-ai-generate:hover { transform: scale(1.05); }
.tag-input-wrapper { border: 1px solid #d1d5db; border-radius: 8px; padding: 5px; display: flex; flex-wrap: wrap; gap: 5px; background: white; }
.tag-badge { background: #e5e7eb; padding: 2px 10px; border-radius: 12px; font-size: 0.85rem; display: flex; align-items: center; gap: 5px; color: #374151; font-weight: 600; }
.tag-input-wrapper input { border: none; outline: none; padding: 5px; flex-grow: 1; }

/* --- TESTIMONIALS --- */
.btn-add-testi { background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.9rem; }
.testimonial-row { background: white; padding: 15px; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 10px; }
.testi-header { display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px dashed #e5e7eb; padding-bottom: 5px; }
.badge-count { font-size: 0.8rem; font-weight: bold; color: #9ca3af; }
.btn-trash { color: #ef4444; border: none; background: none; cursor: pointer; }
.grid-testimonial { display: grid; grid-template-columns: 1fr 3fr; gap: 15px; }
.btn-browse-small { border: 1px solid #d1d5db; background: white; padding: 5px 10px; border-radius: 4px; cursor: pointer; }

/* --- FOOTER --- */
.submit-section { text-align: center; margin: 40px 0 60px; }
.btn-submit-final { background: #111827; color: white; padding: 15px 50px; border: none; border-radius: 50px; font-size: 1.1rem; font-weight: 700; cursor: pointer; box-shadow: 0 10px 25px rgba(0,0,0,0.1); transition: 0.2s; }
.btn-submit-final:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(0,0,0,0.15); }

@media (max-width: 768px) {
    .grid-3, .grid-2, .grid-testimonial { grid-template-columns: 1fr; }
    .header-section { flex-direction: column; align-items: flex-start; gap: 15px; }
}
</style>
