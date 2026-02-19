<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import FrontendLayout from '@/Layouts/FrontendLayout.vue';

const products = ref([]);
const categories = ref([]);
const isLoading = ref(true);

// API থেকে ডাটা ফেচ করা
onMounted(async () => {
    try {
        // প্রোডাক্ট এবং ক্যাটাগরি একসাথে কল করা
        const [prodRes, catRes] = await Promise.all([
            axios.get('/api/public/products'),
            axios.get('/api/public/categories')
        ]);

        products.value = prodRes.data.data || prodRes.data;
        categories.value = catRes.data;
    } catch (error) {
        console.error("Error loading home data:", error);
    } finally {
        isLoading.value = false;
    }
});

// সোশ্যাল লিঙ্ক ও কন্টাক্ট ইনফো (এগুলো চাইলে পরে সেটিংস টেবিল থেকেও আনতে পারেন)
const siteSettings = ref({
    fb: "https://facebook.com/yourstore",
    insta: "https://instagram.com/yourstore",
    twitter: "https://twitter.com/yourstore",
    phone: "+880 1234 567 890",
    email: "support@ecopro.com"
});
</script>

<template>
    <FrontendLayout>
        <div class="home-wrapper">

            <section class="hero-section">
                <div class="hero-content">
                    <span class="badge-new">New Collection 2026</span>
                    <h1 class="hero-title">Elevate Your Lifestyle with <span class="text-gradient">Premium</span> Choices</h1>
                    <p class="hero-subtitle">Experience seamless shopping. Discover the best quality products at unbeatable prices, delivered right to your door.</p>
                    <div class="hero-buttons">
                        <button class="btn-primary-gradient">Shop Now <i class="bi bi-arrow-right"></i></button>
                        <button class="btn-outline-glass">Contact Us</button>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="glass-card">
                        <i class="bi bi-patch-check-fill text-5xl text-blue-400"></i>
                        <div class="glass-text">
                            <h4>Genuine Products</h4>
                            <p>100% Quality Assurance</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="trust-section">
                <div class="trust-container">
                    <div class="trust-item">
                        <div class="icon-box"><i class="bi bi-truck"></i></div>
                        <div><h5>Free Shipping</h5><p>On orders over ৳5000</p></div>
                    </div>
                    <div class="trust-item">
                        <div class="icon-box"><i class="bi bi-shield-lock"></i></div>
                        <div><h5>Secure Payment</h5><p>SSL Protected Checkout</p></div>
                    </div>
                    <div class="trust-item">
                        <div class="icon-box"><i class="bi bi-arrow-repeat"></i></div>
                        <div><h5>7 Days Return</h5><p>No questions asked</p></div>
                    </div>
                    <div class="trust-item">
                        <div class="icon-box"><i class="bi bi-telephone-outbound"></i></div>
                        <div><h5>24/7 Support</h5><p>{{ siteSettings.phone }}</p></div>
                    </div>
                </div>
            </section>

            <section class="category-section" v-if="categories.length > 0">
                <div class="section-header">
                    <h2>Explore Categories</h2>
                    <div class="header-line"></div>
                </div>
                <div class="category-grid">
                    <router-link
                        v-for="cat in categories"
                        :key="cat.id"
                        :to="`/category/${cat.slug}`"
                        class="category-card"
                    >
                        <div class="cat-icon">
                            <i :class="cat.icon ? `bi ${cat.icon}` : 'bi-grid-fill'"></i>
                        </div>
                        <span class="cat-name">{{ cat.name }}</span>
                    </router-link>
                </div>
            </section>

            <section class="product-section">
                <div class="section-header flex-between">
                    <div>
                        <h2>Trending Products</h2>
                        <p class="section-subtitle">Top picks from our collection</p>
                    </div>
                    <button class="btn-view-all">See All Products <i class="bi bi-chevron-right"></i></button>
                </div>

                <div v-if="isLoading" class="loader-container">
                    <div class="modern-spinner"></div>
                    <p>Loading Products...</p>
                </div>

                <div v-else class="product-grid">
                    <div v-for="product in products" :key="product.id" class="product-card">
                        <div class="product-image-wrapper">
                            <img :src="product.thumbnail ? `/${product.thumbnail}` : 'https://via.placeholder.com/400'" :alt="product.name" class="product-img">
                            <div v-if="product.offer_price" class="discount-badge">Sale</div>

                            <div class="hover-actions">
                                <button class="action-btn"><i class="bi bi-heart"></i></button>
                                <button class="action-btn"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>

                        <div class="product-info">
                            <span class="cat-label" v-if="product.category">{{ product.category.name }}</span>
                            <h3 class="product-title">{{ product.name }}</h3>

                            <div class="price-box">
                                <span v-if="product.offer_price" class="price-new">৳{{ product.offer_price }}</span>
                                <span :class="product.offer_price ? 'price-old' : 'price-new'">৳{{ product.base_price }}</span>
                            </div>

                            <button class="btn-add-cart">
                                <i class="bi bi-cart-plus me-2"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="!isLoading && products.length === 0" class="empty-state">
                    <p>No products found in the store.</p>
                </div>
            </section>

            <section class="promo-banner">
                <div class="promo-content">
                    <h2>Don't Miss Out!</h2>
                    <p>Subscribe to get exclusive updates and offer alerts.</p>
                    <div class="subscribe-form">
                        <input type="email" placeholder="Enter your email">
                        <button>Join Now</button>
                    </div>
                    <div class="social-icons-footer">
                        <a :href="siteSettings.fb" target="_blank"><i class="bi bi-facebook"></i></a>
                        <a :href="siteSettings.insta" target="_blank"><i class="bi bi-instagram"></i></a>
                        <a :href="siteSettings.twitter" target="_blank"><i class="bi bi-twitter-x"></i></a>
                    </div>
                </div>
            </section>

        </div>
    </FrontendLayout>
</template>

<style scoped>
/* সব CSS আগের মতোই থাকবে, শুধু সোশ্যাল আইকনের জন্য নিচে কয়েকটা লাইন যোগ করলাম */
.social-icons-footer {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 30px;
}
.social-icons-footer a {
    color: white;
    font-size: 1.5rem;
    transition: 0.3s;
    background: rgba(255,255,255,0.1);
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}
.social-icons-footer a:hover {
    background: white;
    color: #ec4899;
    transform: scale(1.1);
}

/* আপনার আগের স্টাইলগুলো এখানে কপি-পেস্ট করুন... */
/* (আমি আগের স্টাইল কোড এখানে সংক্ষেপ করলাম, আপনি আপনার ফাইলের পুরো স্টাইল অংশটুকু রাখবেন) */
.home-wrapper { font-family: 'Inter', sans-serif; background-color: #f8fafc; overflow-x: hidden; color: #0f172a; }
.hero-section { position: relative; min-height: 80vh; background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); display: flex; align-items: center; justify-content: space-between; padding: 0 10%; overflow: hidden;}
.hero-title { font-size: 4rem; font-weight: 900; line-height: 1.1; margin-bottom: 20px; color: white; }
.text-gradient { background: linear-gradient(to right, #a855f7, #ec4899, #f43f5e); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.category-card { text-decoration: none; transition: 0.3s; }
.product-card { background: white; border-radius: 24px; padding: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid #f1f5f9; }
.btn-primary-gradient { background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); color: white; border: none; padding: 15px 35px; border-radius: 50px; font-weight: 700; cursor: pointer; }
/* ... বাকি সব CSS আগের মতোই ... */
</style>
