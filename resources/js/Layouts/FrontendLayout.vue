<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const categories = ref([]);
const router = useRouter();
const isMenuOpen = ref(false);

// ডায়নামিক ক্যাটাগরি আনছি হেডারের জন্য
onMounted(async () => {
    try {
        const response = await axios.get('/api/public/categories'); // নিশ্চিত করুন এই API টি আপনার আছে
        categories.value = response.data;
    } catch (error) {
        console.error("Error fetching categories:", error);
    }
});
</script>

<template>
    <div class="frontend-layout">
        <header class="main-header">
            <nav class="nav-container">
                <div class="nav-logo" @click="router.push('/')">
                    <span class="logo-icon">🚀</span>
                    <span class="logo-text">ECO<span class="text-gradient">PRO</span></span>
                </div>

                <ul class="nav-links" :class="{ 'active': isMenuOpen }">
                    <li><router-link to="/">Home</router-link></li>
                    <li class="dropdown">
                        <a href="#">Categories <i class="bi bi-chevron-down"></i></a>
                        <ul class="dropdown-menu">
                            <li v-for="cat in categories" :key="cat.id">
                                <router-link :to="`/category/${cat.slug}`">{{ cat.name }}</router-link>
                            </li>
                        </ul>
                    </li>
                    <li><router-link to="/shop">Shop</router-link></li>
                </ul>

                <div class="nav-actions">
                    <button class="search-btn"><i class="bi bi-search"></i></button>
                    <div class="cart-icon">
                        <i class="bi bi-bag-heart"></i>
                        <span class="cart-count">0</span>
                    </div>
                    <router-link to="/login" class="login-btn">Login</router-link>
                    <button class="mobile-menu-btn" @click="isMenuOpen = !isMenuOpen">
                        <i class="bi" :class="isMenuOpen ? 'bi-x-lg' : 'bi-list'"></i>
                    </button>
                </div>
            </nav>
        </header>

        <main class="content-area">
            <slot />
        </main>

        <footer class="main-footer">
            <div class="footer-grid">
                <div class="footer-info">
                    <h3>ECOPRO</h3>
                    <p>Experience the future of shopping with our premium collection and lightning-fast delivery.</p>
                    <div class="social-links">
                        <i class="bi bi-facebook"></i>
                        <i class="bi bi-instagram"></i>
                        <i class="bi bi-twitter-x"></i>
                    </div>
                </div>
                <div class="footer-links">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><router-link to="/">Home</router-link></li>
                        <li><router-link to="/about">About Us</router-link></li>
                        <li><router-link to="/contact">Contact</router-link></li>
                    </ul>
                </div>
                <div class="footer-cats">
                    <h4>Top Categories</h4>
                    <ul>
                        <li v-for="cat in categories.slice(0, 5)" :key="cat.id">
                            {{ cat.name }}
                        </li>
                    </ul>
                </div>
                <div class="footer-contact">
                    <h4>Newsletter</h4>
                    <p>Get updates on new arrivals!</p>
                    <div class="footer-subscribe">
                        <input type="email" placeholder="Email...">
                        <button>Go</button>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 EcoPro. All Rights Reserved. Built with ❤️</p>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.main-header {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(15px);
    position: sticky; /* এখানে position শব্দটা যোগ হবে */
    top: 0;
    z-index: 1000;
    border-bottom: 1px solid #f1f5f9;
    padding: 15px 5%;
}
.nav-container { display: flex; justify-content: space-between; align-items: center; max-width: 1400px; margin: 0 auto; }
.nav-logo { cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 1.5rem; font-weight: 800; }
.text-gradient { background: linear-gradient(to right, #6366f1, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.nav-links { display: flex; gap: 30px; list-style: none; margin: 0; }
.nav-links a { text-decoration: none; color: #475569; font-weight: 600; transition: 0.3s; }
.nav-links a:hover { color: #6366f1; }

/* Dropdown */
.dropdown { position: relative; }
.dropdown-menu { position: absolute; top: 100%; left: 0; background: white; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border-radius: 12px; min-width: 200px; padding: 15px; display: none; list-style: none; }
.dropdown:hover .dropdown-menu { display: block; }
.dropdown-menu li { padding: 8px 0; border-bottom: 1px solid #f8fafc; }

.nav-actions { display: flex; align-items: center; gap: 20px; }
.cart-icon { position: relative; font-size: 1.4rem; cursor: pointer; }
.cart-count { position: absolute; top: -5px; right: -10px; background: #f43f5e; color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 50%; }
.login-btn { background: #0f172a; color: white; padding: 8px 20px; border-radius: 50px; text-decoration: none; font-weight: 600; }

.main-footer { background: #0f172a; color: #94a3b8; padding: 80px 5% 30px; margin-top: 100px; }
.footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1.5fr; gap: 40px; max-width: 1400px; margin: 0 auto; }
.footer-info h3 { color: white; margin-bottom: 20px; }
.footer-links h4, .footer-cats h4, .footer-contact h4 { color: white; margin-bottom: 20px; }
.footer-links ul, .footer-cats ul { list-style: none; padding: 0; }
.footer-links li, .footer-cats li { margin-bottom: 10px; cursor: pointer; transition: 0.3s; }
.footer-links li:hover { color: #6366f1; }
.footer-bottom { text-align: center; margin-top: 60px; padding-top: 30px; border-top: 1px solid rgba(255,255,255,0.1); }
</style>
