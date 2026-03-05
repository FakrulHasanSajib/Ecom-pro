<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';
import {
    SfButton, SfIconShoppingCart, SfIconArrowBack, SfLoaderCircular,
    SfRating, SfIconFavorite
} from '@storefront-ui/vue';

import { useCartStore } from '../../stores/cart';
import CartSidebar from '../../Components/CartSidebar.vue';

const route = useRoute();
const router = useRouter();
const cartStore = useCartStore();

const product = ref(null);
const relatedProducts = ref([]);
const loading = ref(true);
const activeImage = ref('');
const quantity = ref(1);
const isCartSidebarOpen = ref(false);
const isWishlisted = ref(false);
const activeTab = ref('description');

const backendUrl = 'http://127.0.0.1:73';

const getImageUrl = (path) => {
    if (!path) return 'https://placehold.co/600x600?text=No+Image';
    return path.startsWith('http') ? path : `${backendUrl}/storage/${path}`;
};

const zoomStyle = ref({ transformOrigin: 'center center' });
const isZooming = ref(false);
const handleZoom = (e) => {
    const { left, top, width, height } = e.target.getBoundingClientRect();
    const x = ((e.clientX - left) / width) * 100;
    const y = ((e.clientY - top) / height) * 100;
    zoomStyle.value.transformOrigin = `${x}% ${y}%`;
};

const reviewForm = ref({ rating: 5, comment: '', name: '' });

const fetchProduct = async () => {
    try {
        loading.value = true;
        window.scrollTo({ top: 0, behavior: 'smooth' });
        quantity.value = 1;

        const res = await axios.get(`${backendUrl}/api/public/products/${route.params.id}`);
        product.value = res.data.data;
        activeImage.value = getImageUrl(product.value.thumbnail);

        const relatedRes = await axios.get(`${backendUrl}/api/public/products`);
        relatedProducts.value = (relatedRes.data.data || []).filter(p => p.id !== product.value.id).slice(0, 4);

    } catch (error) {
        console.error("Failed to load product", error);
    } finally {
        loading.value = false;
    }
};

watch(() => route.params.id, (newId) => { if (newId) fetchProduct(); });

const showToast = (message) => {
    Swal.fire({
        toast: true,
        position: 'bottom-end',
        icon: 'success',
        title: message,
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true,
        background: '#0f172a',
        color: '#f8fafc',
        iconColor: '#10b981'
    });
};

const handleAddToCart = () => {
    if (product.value) {
        cartStore.addToCart(product.value, quantity.value);
        showToast('Item added to cart');
        isCartSidebarOpen.value = true;
    }
};

const handleRelatedAddToCart = (relProduct) => {
    cartStore.addToCart(relProduct, 1);
    showToast('Item added to cart');
    isCartSidebarOpen.value = true;
};

const submitReview = () => {
    showToast(`Review submitted — thank you, ${reviewForm.value.name}!`);
    reviewForm.value = { rating: 5, comment: '', name: '' };
};

const toggleWishlist = () => { isWishlisted.value = !isWishlisted.value; };

const discountPercent = computed(() => {
    if (!product.value?.discount_price || !product.value?.base_price) return null;
    return Math.round((1 - product.value.sale_price / product.value.base_price) * 100);
});

const embedVideoUrl = computed(() => {
    if (!product.value?.video_link) return null;
    let url = product.value.video_link;
    if (url.includes('watch?v=')) return url.replace('watch?v=', 'embed/');
    return url;
});

const getGalleryImages = computed(() => {
    if (!product.value?.images) return [];
    try {
        return typeof product.value.images === 'string' ? JSON.parse(product.value.images) : product.value.images;
    } catch(e) { return []; }
});

const getTestimonials = computed(() => {
    if (!product.value?.testimonials) return [];
    try {
        let items = typeof product.value.testimonials === 'string' ? JSON.parse(product.value.testimonials) : product.value.testimonials;
        if (Array.isArray(items)) {
            return items.map(item => {
                if (typeof item === 'string' && item.trim().startsWith('{')) {
                    try { return JSON.parse(item); } catch (e) { return item; }
                }
                return item;
            });
        }
        return items;
    } catch(e) { return []; }
});

const goBack = () => router.push('/');

onMounted(() => { fetchProduct(); });
</script>

<template>
    <div class="eshop-root">

        <!-- ══════════════════ HEADER ══════════════════ -->
        <header class="eshop-header">
            <div class="header-inner">
                <div @click="goBack" class="brand-logo">
                    <div class="brand-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                    </div>
                    <span class="brand-name">ESHOP</span>
                </div>
                <nav class="header-nav">
                    <button @click="goBack" class="nav-link">Home</button>
                    <button class="nav-link">Collections</button>
                    <button class="nav-link">Deals</button>
                </nav>
                <div class="header-actions">
                    <button @click="isCartSidebarOpen = true" class="cart-btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 001.99-1.85l1.13-8.54H6"/></svg>
                        <span v-if="cartStore.totalItems > 0" class="cart-badge">{{ cartStore.totalItems }}</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- ══════════════════ LOADING ══════════════════ -->
        <div v-if="loading" class="loading-screen">
            <div class="loader-ring"></div>
            <p class="loader-text">Loading product...</p>
        </div>

        <!-- ══════════════════ MAIN ══════════════════ -->
        <main v-else-if="product" class="main-content">

            <!-- Breadcrumb -->
            <nav class="breadcrumb">
                <button @click="goBack" class="breadcrumb-link">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    Home
                </button>
                <span class="breadcrumb-sep">›</span>
                <span class="breadcrumb-cat">{{ product.category?.name || 'Products' }}</span>
                <span class="breadcrumb-sep">›</span>
                <span class="breadcrumb-current">{{ product.name }}</span>
            </nav>

            <!-- ── Product Hero ── -->
            <section class="product-hero">

                <!-- Image Panel -->
                <div class="image-panel">
                    <div v-if="getGalleryImages.length > 0" class="thumb-strip">
                        <button
                            @click="activeImage = getImageUrl(product.thumbnail)"
                            :class="['thumb-item', { 'thumb-active': activeImage === getImageUrl(product.thumbnail) }]">
                            <img :src="getImageUrl(product.thumbnail)" alt="thumb" />
                        </button>
                        <button
                            v-for="(img, idx) in getGalleryImages" :key="idx"
                            @click="activeImage = getImageUrl(img)"
                            :class="['thumb-item', { 'thumb-active': activeImage === getImageUrl(img) }]">
                            <img :src="getImageUrl(img)" alt="thumb" />
                        </button>
                    </div>

                    <div class="main-image-wrap"
                         @mousemove="handleZoom"
                         @mouseenter="isZooming = true"
                         @mouseleave="isZooming = false">
                        <img
                            :src="activeImage"
                            class="main-image"
                            :class="{ 'is-zooming': isZooming }"
                            :style="isZooming ? zoomStyle : {}"
                            alt="Product" />

                        <div v-if="discountPercent" class="discount-badge">-{{ discountPercent }}%</div>

                        <button @click="toggleWishlist" :class="['wishlist-btn', { 'wishlisted': isWishlisted }]">
                            <svg width="18" height="18" viewBox="0 0 24 24" :fill="isWishlisted ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Info Panel -->
                <div class="info-panel">
                    <div class="product-meta-top">
                        <span class="category-pill">{{ product.category?.name || 'General' }}</span>
                        <div class="rating-chip">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="#f59e0b" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            <span>4.8</span>
                            <span class="rating-count">(124)</span>
                        </div>
                    </div>

                    <h1 class="product-title">{{ product.name }}</h1>

                    <div class="price-block">
                        <span class="price-current">৳{{ product.sale_price || product.base_price }}</span>
                        <span v-if="product.discount_price" class="price-original">৳{{ product.discount_price }}</span>
                        <span v-if="discountPercent" class="price-save">Save {{ discountPercent }}%</span>
                    </div>

                    <div class="divider"></div>

                    <div class="tab-bar">
                        <button :class="['tab-btn', { 'tab-active': activeTab === 'description' }]" @click="activeTab = 'description'">Description</button>
                        <button :class="['tab-btn', { 'tab-active': activeTab === 'specs' }]" @click="activeTab = 'specs'">Specifications</button>
                    </div>
                    <div class="tab-content" v-show="activeTab === 'description'">
                        <div v-html="product.description || '<p>Premium quality product crafted with excellence for your everyday needs.</p>'" class="prose-content"></div>
                    </div>
                    <div class="tab-content" v-show="activeTab === 'specs'">
                        <div class="specs-grid">
                            <div class="spec-row"><span>Category</span><span>{{ product.category?.name || '—' }}</span></div>
                            <div class="spec-row"><span>SKU</span><span>#{{ product.id }}</span></div>
                            <div class="spec-row"><span>Availability</span><span class="in-stock">In Stock</span></div>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <div class="purchase-row">
                        <div class="qty-control">
                            <button @click="quantity > 1 ? quantity-- : null" class="qty-btn">−</button>
                            <span class="qty-value">{{ quantity }}</span>
                            <button @click="quantity++" class="qty-btn">+</button>
                        </div>
                        <button @click="handleAddToCart" class="add-to-cart-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 001.99-1.85l1.13-8.54H6"/></svg>
                            Add to Cart
                        </button>
                    </div>

                    <div class="trust-row">
                        <div class="trust-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            <span>Secure Payment</span>
                        </div>
                        <div class="trust-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 3h15v13H1z"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                            <span>Free Delivery</span>
                        </div>
                        <div class="trust-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
                            <span>Easy Returns</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ── Video Section ── -->
            <section v-if="embedVideoUrl" class="video-section">
                <div class="section-header">
                    <h2 class="section-title">Product Video</h2>
                    <div class="title-line"></div>
                </div>
                <div class="video-frame">
                    <iframe :src="embedVideoUrl" frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen class="video-iframe"></iframe>
                </div>
            </section>

            <!-- ── Reviews Section ── -->
            <section class="reviews-section">
                <div class="section-header">
                    <h2 class="section-title">Customer Reviews</h2>
                    <div class="title-line"></div>
                </div>

                <div class="reviews-layout">
                    <div class="reviews-list">
                        <div v-if="getTestimonials.length === 0" class="no-reviews">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                            <p>No reviews yet. Be the first!</p>
                        </div>
                        <div v-else v-for="(review, idx) in getTestimonials" :key="idx" class="review-card">
                            <div class="review-header">
                                <div class="reviewer-avatar">{{ review.name ? review.name.charAt(0).toUpperCase() : 'U' }}</div>
                                <div class="reviewer-meta">
                                    <h4>{{ review.name || 'Anonymous' }}</h4>
                                    <div class="stars">
                                        <svg v-for="i in 5" :key="i" width="12" height="12" viewBox="0 0 24 24" fill="#f59e0b" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                    </div>
                                </div>
                            </div>
                            <p class="review-body">{{ review.comment || review.content || (typeof review === 'string' ? review : '') }}</p>
                        </div>
                    </div>

                    <div class="review-form-panel">
                        <h3 class="form-title">Write a Review</h3>
                        <form @submit.prevent="submitReview" class="review-form">
                            <div class="form-group">
                                <label>Rating</label>
                                <select v-model="reviewForm.rating">
                                    <option value="5">★★★★★ Excellent</option>
                                    <option value="4">★★★★☆ Good</option>
                                    <option value="3">★★★☆☆ Average</option>
                                    <option value="2">★★☆☆☆ Poor</option>
                                    <option value="1">★☆☆☆☆ Terrible</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Full Name</label>
                                <input v-model="reviewForm.name" type="text" required placeholder="Your name" />
                            </div>
                            <div class="form-group">
                                <label>Your Review</label>
                                <textarea v-model="reviewForm.comment" required rows="4" placeholder="Share your experience..."></textarea>
                            </div>
                            <button type="submit" class="submit-review-btn">Submit Review</button>
                        </form>
                    </div>
                </div>
            </section>

            <!-- ── Related Products ── -->
            <section v-if="relatedProducts.length > 0" class="related-section">
                <div class="section-header">
                    <h2 class="section-title">You May Also Like</h2>
                    <div class="title-line"></div>
                </div>
                <div class="related-grid">
                    <div v-for="rel in relatedProducts" :key="rel.id" class="related-card">
                        <router-link :to="'/product/' + rel.id" class="related-img-wrap">
                            <img :src="getImageUrl(rel.thumbnail)" :alt="rel.name" class="related-img" />
                            <div class="related-overlay"><span>View Product</span></div>
                        </router-link>
                        <div class="related-info">
                            <span class="related-cat">{{ rel.category?.name || 'General' }}</span>
                            <router-link :to="'/product/' + rel.id">
                                <h3 class="related-name">{{ rel.name }}</h3>
                            </router-link>
                            <div class="related-footer">
                                <span class="related-price">৳{{ rel.sale_price || rel.base_price }}</span>
                                <button @click.prevent="handleRelatedAddToCart(rel)" class="related-cart-btn">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 001.99-1.85l1.13-8.54H6"/></svg>
                                    Add
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </main>

        <!-- 404 -->
        <div v-else class="not-found">
            <span class="not-found-code">404</span>
            <p>Product not found.</p>
            <button @click="goBack" class="back-home-btn">← Back to Home</button>
        </div>

        <footer class="site-footer">
            <div class="footer-inner">
                <span class="footer-brand">ESHOP</span>
                <span class="footer-copy">© 2025 All rights reserved</span>
            </div>
        </footer>

        <CartSidebar :isOpen="isCartSidebarOpen" @close="isCartSidebarOpen = false" />
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;900&family=DM+Sans:wght@300;400;500;600&display=swap');

.eshop-root {
    --c-bg: #fafaf9;
    --c-surface: #ffffff;
    --c-border: #e8e5e0;
    --c-border-light: #f0ede8;
    --c-text: #1a1714;
    --c-text-sub: #6b6560;
    --c-text-muted: #a09890;
    --c-accent: #c9622c;
    --c-accent-dark: #a84e22;
    --c-accent-light: #fdf0eb;
    --c-dark: #1a1714;
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
    --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
    --radius: 12px;
    --radius-lg: 20px;
    font-family: 'DM Sans', sans-serif;
    background: var(--c-bg);
    min-height: 100vh;
    color: var(--c-text);
    display: flex;
    flex-direction: column;
}

/* HEADER */
.eshop-header {
    background: var(--c-surface);
    border-bottom: 1px solid var(--c-border);
    position: sticky; top: 0; z-index: 100;
}
.header-inner {
    max-width: 1280px; margin: 0 auto;
    padding: 0 32px; height: 64px;
    display: flex; align-items: center; justify-content: space-between; gap: 32px;
}
.brand-logo { display: flex; align-items: center; gap: 10px; cursor: pointer; }
.brand-icon {
    width: 36px; height: 36px;
    background: var(--c-dark); color: white;
    border-radius: 8px; display: flex; align-items: center; justify-content: center;
}
.brand-name {
    font-family: 'Playfair Display', serif;
    font-weight: 700; font-size: 1.25rem;
    letter-spacing: 0.08em; color: var(--c-dark);
}
.header-nav { display: flex; gap: 4px; }
.nav-link {
    padding: 6px 16px; border: none; background: none;
    font-family: 'DM Sans', sans-serif; font-size: 0.875rem; font-weight: 500;
    color: var(--c-text-sub); cursor: pointer; border-radius: 8px; transition: all 0.2s;
}
.nav-link:hover { background: var(--c-accent-light); color: var(--c-accent); }
.cart-btn {
    width: 40px; height: 40px;
    border: 1.5px solid var(--c-border); background: var(--c-surface);
    border-radius: 10px; display: flex; align-items: center; justify-content: center;
    cursor: pointer; position: relative; color: var(--c-text-sub); transition: all 0.2s;
}
.cart-btn:hover { border-color: var(--c-accent); color: var(--c-accent); }
.cart-badge {
    position: absolute; top: -6px; right: -6px;
    background: var(--c-accent); color: white;
    font-size: 10px; font-weight: 700;
    width: 18px; height: 18px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    border: 2px solid var(--c-surface);
}

/* LOADING */
.loading-screen {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    min-height: 60vh; gap: 16px;
}
.loader-ring {
    width: 48px; height: 48px;
    border: 3px solid var(--c-border);
    border-top-color: var(--c-accent);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.loader-text { color: var(--c-text-muted); font-size: 0.875rem; font-weight: 500; }

/* MAIN */
.main-content { max-width: 1280px; margin: 0 auto; padding: 32px; flex: 1; width: 100%; }

/* BREADCRUMB */
.breadcrumb {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.8125rem; color: var(--c-text-muted); margin-bottom: 28px;
}
.breadcrumb-link {
    display: flex; align-items: center; gap: 4px;
    background: none; border: none; color: var(--c-text-muted);
    font-family: inherit; font-size: 0.8125rem; cursor: pointer; transition: color 0.2s;
}
.breadcrumb-link:hover { color: var(--c-accent); }
.breadcrumb-sep { color: var(--c-border); }
.breadcrumb-cat { color: var(--c-text-sub); }
.breadcrumb-current { color: var(--c-text); font-weight: 500; }

/* PRODUCT HERO */
.product-hero {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 0;
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: var(--radius-lg);
    overflow: hidden; margin-bottom: 48px;
}
.image-panel {
    display: flex; gap: 12px; padding: 32px;
    background: #f7f5f2;
    border-right: 1px solid var(--c-border);
}
.thumb-strip { display: flex; flex-direction: column; gap: 8px; flex-shrink: 0; }
.thumb-item {
    width: 60px; height: 60px;
    border: 1.5px solid var(--c-border);
    border-radius: 8px; background: white;
    padding: 5px; cursor: pointer; overflow: hidden; transition: all 0.2s;
}
.thumb-item img { width: 100%; height: 100%; object-fit: contain; }
.thumb-item:hover { border-color: var(--c-accent); }
.thumb-active { border-color: var(--c-accent) !important; box-shadow: 0 0 0 3px rgba(201,98,44,0.12); }

.main-image-wrap {
    flex: 1; position: relative;
    background: white; border: 1px solid var(--c-border);
    border-radius: var(--radius); overflow: hidden;
    cursor: crosshair; aspect-ratio: 1;
}
.main-image {
    position: absolute; inset: 0; width: 100%; height: 100%;
    object-fit: contain; padding: 24px;
    transition: transform 0.1s ease-out; transform-origin: center;
}
.main-image.is-zooming { transform: scale(2.2); }
.discount-badge {
    position: absolute; top: 14px; left: 14px;
    background: var(--c-accent); color: white;
    font-size: 0.7rem; font-weight: 700;
    padding: 4px 10px; border-radius: 6px; letter-spacing: 0.04em;
}
.wishlist-btn {
    position: absolute; top: 14px; right: 14px;
    width: 36px; height: 36px;
    background: white; border: 1.5px solid var(--c-border);
    border-radius: 8px; display: flex; align-items: center; justify-content: center;
    cursor: pointer; color: var(--c-text-muted); transition: all 0.2s;
}
.wishlist-btn:hover { border-color: #ef4444; color: #ef4444; }
.wishlisted { color: #ef4444 !important; border-color: #ef4444 !important; background: #fff5f5 !important; }

/* INFO PANEL */
.info-panel { padding: 40px; display: flex; flex-direction: column; }
.product-meta-top { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
.category-pill {
    font-size: 0.7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.1em;
    color: var(--c-accent); background: var(--c-accent-light);
    border: 1px solid #f5cbb0; padding: 4px 12px; border-radius: 20px;
}
.rating-chip {
    display: flex; align-items: center; gap: 4px;
    font-size: 0.8125rem; font-weight: 600; color: #92400e;
    background: #fffbeb; border: 1px solid #fde68a;
    padding: 4px 10px; border-radius: 20px;
}
.rating-count { font-weight: 400; color: var(--c-text-muted); }
.product-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.75rem, 3vw, 2.25rem);
    font-weight: 700; line-height: 1.25; color: var(--c-dark); margin-bottom: 20px;
}
.price-block { display: flex; align-items: baseline; gap: 12px; margin-bottom: 24px; }
.price-current {
    font-family: 'Playfair Display', serif;
    font-size: 2rem; font-weight: 700; color: var(--c-accent);
}
.price-original { font-size: 1rem; font-weight: 500; color: var(--c-text-muted); text-decoration: line-through; }
.price-save {
    font-size: 0.75rem; font-weight: 700; color: #059669;
    background: #ecfdf5; border: 1px solid #a7f3d0;
    padding: 3px 10px; border-radius: 20px;
}
.divider { height: 1px; background: var(--c-border-light); margin: 20px 0; }

/* TABS */
.tab-bar {
    display: flex; gap: 4px;
    background: var(--c-bg); border: 1px solid var(--c-border);
    border-radius: 10px; padding: 4px; margin-bottom: 16px; width: fit-content;
}
.tab-btn {
    padding: 7px 18px; border: none; background: none;
    font-family: 'DM Sans', sans-serif; font-size: 0.8125rem; font-weight: 500;
    color: var(--c-text-sub); cursor: pointer; border-radius: 7px; transition: all 0.2s;
}
.tab-active { background: var(--c-surface); color: var(--c-dark); font-weight: 600; box-shadow: var(--shadow-sm); }
.tab-content { min-height: 60px; margin-bottom: 4px; }
.prose-content { font-size: 0.9rem; line-height: 1.7; color: var(--c-text-sub); }
.prose-content :deep(p) { margin-bottom: 8px; }
.specs-grid { display: flex; flex-direction: column; }
.spec-row {
    display: flex; justify-content: space-between;
    padding: 9px 0; font-size: 0.875rem;
    border-bottom: 1px solid var(--c-border-light);
}
.spec-row span:first-child { color: var(--c-text-muted); font-weight: 500; }
.spec-row span:last-child { color: var(--c-text); font-weight: 600; }
.in-stock { color: #059669; }

/* PURCHASE */
.purchase-row { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
.qty-control {
    display: flex; align-items: center;
    border: 1.5px solid var(--c-border); border-radius: 10px;
    overflow: hidden; background: var(--c-surface);
}
.qty-btn {
    width: 42px; height: 48px;
    border: none; background: none;
    font-size: 1.25rem; font-weight: 400;
    cursor: pointer; color: var(--c-text-sub); transition: background 0.15s; flex-shrink: 0;
}
.qty-btn:hover { background: var(--c-bg); }
.qty-value {
    width: 44px; text-align: center;
    font-weight: 700; font-size: 1rem; color: var(--c-dark);
    border-left: 1px solid var(--c-border); border-right: 1px solid var(--c-border);
    height: 48px; display: flex; align-items: center; justify-content: center;
}
.add-to-cart-btn {
    flex: 1; height: 48px;
    background: var(--c-dark); color: white;
    border: none; border-radius: 10px;
    font-family: 'DM Sans', sans-serif; font-size: 0.9375rem; font-weight: 600;
    letter-spacing: 0.02em; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: all 0.2s;
}
.add-to-cart-btn:hover { background: var(--c-accent); transform: translateY(-1px); box-shadow: 0 4px 16px rgba(201,98,44,0.3); }
.add-to-cart-btn:active { transform: translateY(0); }

/* TRUST BADGES */
.trust-row {
    display: flex; gap: 16px; flex-wrap: wrap;
    padding: 14px 16px; background: var(--c-bg);
    border-radius: 10px; border: 1px solid var(--c-border-light);
}
.trust-item {
    display: flex; align-items: center; gap: 6px;
    font-size: 0.75rem; font-weight: 500; color: var(--c-text-sub);
}
.trust-item svg { color: var(--c-text-muted); flex-shrink: 0; }

/* SECTIONS */
.section-header { display: flex; align-items: center; gap: 16px; margin-bottom: 28px; }
.section-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.5rem; font-weight: 700; color: var(--c-dark); white-space: nowrap;
}
.title-line { flex: 1; height: 1px; background: var(--c-border); }

/* VIDEO */
.video-section { margin-bottom: 48px; }
.video-frame {
    position: relative; aspect-ratio: 16/9;
    border-radius: var(--radius-lg); overflow: hidden;
    border: 1px solid var(--c-border); box-shadow: var(--shadow-md);
}
.video-iframe { position: absolute; inset: 0; width: 100%; height: 100%; }

/* REVIEWS */
.reviews-section {
    background: var(--c-surface);
    border: 1px solid var(--c-border); border-radius: var(--radius-lg);
    padding: 40px; margin-bottom: 48px;
}
.reviews-layout { display: grid; grid-template-columns: 3fr 2fr; gap: 48px; }
.no-reviews {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; gap: 12px; padding: 48px;
    border: 1.5px dashed var(--c-border); border-radius: var(--radius);
    color: var(--c-text-muted); text-align: center; font-size: 0.9rem;
}
.review-card { padding: 20px 0; border-bottom: 1px solid var(--c-border-light); }
.review-card:last-child { border-bottom: none; }
.review-header { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 12px; }
.reviewer-avatar {
    width: 40px; height: 40px;
    background: var(--c-dark); color: white;
    border-radius: 10px; display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 1rem; flex-shrink: 0;
}
.reviewer-meta h4 { font-weight: 600; font-size: 0.9375rem; margin-bottom: 4px; color: var(--c-dark); }
.stars { display: flex; gap: 2px; }
.review-body {
    font-size: 0.875rem; line-height: 1.7; color: var(--c-text-sub);
    padding: 14px 16px; background: var(--c-bg);
    border-radius: 10px; border: 1px solid var(--c-border-light);
}
.review-form-panel {
    background: var(--c-bg); border: 1px solid var(--c-border);
    border-radius: var(--radius); padding: 28px; height: fit-content;
}
.form-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.1rem; font-weight: 700; margin-bottom: 20px; color: var(--c-dark);
}
.review-form { display: flex; flex-direction: column; gap: 16px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label {
    font-size: 0.75rem; font-weight: 700; color: var(--c-text-sub);
    text-transform: uppercase; letter-spacing: 0.06em;
}
.form-group input,
.form-group select,
.form-group textarea {
    padding: 10px 14px;
    border: 1.5px solid var(--c-border); border-radius: 8px;
    background: var(--c-surface);
    font-family: 'DM Sans', sans-serif; font-size: 0.875rem; color: var(--c-text);
    outline: none; transition: border-color 0.2s;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus { border-color: var(--c-accent); }
.form-group textarea { resize: none; }
.submit-review-btn {
    padding: 12px; background: var(--c-dark); color: white;
    border: none; border-radius: 8px;
    font-family: 'DM Sans', sans-serif; font-size: 0.875rem; font-weight: 600;
    cursor: pointer; transition: all 0.2s; letter-spacing: 0.02em;
}
.submit-review-btn:hover { background: var(--c-accent); }

/* RELATED */
.related-section { margin-bottom: 48px; }
.related-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
.related-card {
    background: var(--c-surface); border: 1px solid var(--c-border);
    border-radius: var(--radius); overflow: hidden; transition: all 0.25s;
}
.related-card:hover { box-shadow: var(--shadow-md); transform: translateY(-3px); }
.related-img-wrap {
    display: block; position: relative;
    aspect-ratio: 1; background: #f7f5f2; overflow: hidden;
}
.related-img { width: 100%; height: 100%; object-fit: contain; padding: 20px; transition: transform 0.4s; }
.related-card:hover .related-img { transform: scale(1.06); }
.related-overlay {
    position: absolute; inset: 0;
    background: rgba(26,23,20,0.5);
    display: flex; align-items: center; justify-content: center;
    opacity: 0; transition: opacity 0.25s;
}
.related-overlay span {
    color: white; font-size: 0.8125rem; font-weight: 600;
    border: 1.5px solid white; padding: 6px 16px; border-radius: 20px; letter-spacing: 0.04em;
}
.related-card:hover .related-overlay { opacity: 1; }
.related-info { padding: 16px; }
.related-cat { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--c-text-muted); }
.related-name {
    font-size: 0.9rem; font-weight: 600; color: var(--c-dark);
    margin: 6px 0 12px; line-height: 1.4;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    transition: color 0.2s;
}
.related-name:hover { color: var(--c-accent); }
.related-footer {
    display: flex; align-items: center; justify-content: space-between;
    border-top: 1px solid var(--c-border-light); padding-top: 12px;
}
.related-price { font-weight: 700; font-size: 1rem; color: var(--c-dark); }
.related-cart-btn {
    display: flex; align-items: center; gap: 6px;
    padding: 7px 14px; background: var(--c-bg);
    border: 1.5px solid var(--c-border); border-radius: 7px;
    font-family: 'DM Sans', sans-serif; font-size: 0.8125rem; font-weight: 600;
    color: var(--c-text-sub); cursor: pointer; transition: all 0.2s;
}
.related-cart-btn:hover { background: var(--c-dark); color: white; border-color: var(--c-dark); }

/* 404 */
.not-found {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    min-height: 60vh; gap: 16px; flex: 1;
}
.not-found-code { font-family: 'Playfair Display', serif; font-size: 7rem; font-weight: 900; color: var(--c-border); line-height: 1; }
.not-found p { color: var(--c-text-sub); font-size: 1rem; font-weight: 500; }
.back-home-btn {
    padding: 12px 28px; background: var(--c-dark); color: white;
    border: none; border-radius: 10px;
    font-family: 'DM Sans', sans-serif; font-size: 0.9375rem; font-weight: 600;
    cursor: pointer; transition: background 0.2s;
}
.back-home-btn:hover { background: var(--c-accent); }

/* FOOTER */
.site-footer { border-top: 1px solid var(--c-border); background: var(--c-surface); }
.footer-inner {
    max-width: 1280px; margin: 0 auto; padding: 20px 32px;
    display: flex; align-items: center; justify-content: space-between;
}
.footer-brand { font-family: 'Playfair Display', serif; font-weight: 700; font-size: 1rem; letter-spacing: 0.08em; }
.footer-copy { font-size: 0.8rem; color: var(--c-text-muted); }

/* RESPONSIVE */
@media (max-width: 1024px) {
    .related-grid { grid-template-columns: repeat(2, 1fr); }
    .reviews-layout { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .main-content { padding: 20px 16px; }
    .header-inner { padding: 0 16px; }
    .header-nav { display: none; }
    .product-hero { grid-template-columns: 1fr; }
    .image-panel { padding: 20px; border-right: none; border-bottom: 1px solid var(--c-border); }
    .thumb-strip { flex-direction: row; }
    .info-panel { padding: 24px; }
    .reviews-section { padding: 24px; }
    .trust-row { gap: 10px; }
}
@media (max-width: 480px) {
    .related-grid { grid-template-columns: 1fr 1fr; }
    .related-info { padding: 12px; }
}
</style>
