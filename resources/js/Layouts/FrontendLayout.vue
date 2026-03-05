<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import { useCartStore } from '../../stores/cart';
import { useAuthStore } from '../../stores/auth';

const categories = ref([]);
const router = useRouter();
const isMenuOpen = ref(false);
const settings = ref({});
const backendUrl = 'http://127.0.0.1:73';

// 🔥 Settings Helper
const normalize = (str) => str ? String(str).replace(/[_\-\s]+/g, '').toLowerCase() : '';

const getSetting = (group, key, defaultValue = '') => {
    if (!settings.value) return defaultValue;
    const targetGroup = Object.keys(settings.value).find(k => normalize(k) === normalize(group));
    if (targetGroup && Array.isArray(settings.value[targetGroup])) {
        const item = settings.value[targetGroup].find(s =>
            normalize(s.key) === normalize(key) || normalize(s.name) === normalize(key)
        );
        if (item && item.value !== null && item.value !== '') {
            return item.type === 'image' ? item.value_url : item.value;
        }
    }
    return defaultValue;
};

const cartStore = useCartStore();
const authStore = useAuthStore();

onMounted(async () => {
    try {
        const [catRes, settingRes] = await Promise.all([
            axios.get(`${backendUrl}/api/public/categories`).catch(() => ({ data: [] })),
            axios.get(`${backendUrl}/api/public/settings`).catch(() => ({ data: {} }))
        ]);
        categories.value = catRes.data?.data || catRes.data || [];
        settings.value = settingRes.data?.data || {};
    } catch (error) {
        console.error("Layout fetch error:", error);
    }
});
</script>

<template>
    <div class="frontend-layout bg-[#080810] min-h-screen text-slate-200">

        <!-- ===== STICKY HEADER ===== -->
        <header class="sticky top-0 z-50 border-b border-white/5 bg-[#080810]/90 backdrop-blur-2xl">
            <nav class="max-w-7xl mx-auto px-5 h-16 flex items-center justify-between gap-6">

                <!-- Logo -->
                <div @click="router.push('/')" class="flex items-center gap-2.5 cursor-pointer flex-shrink-0 group">
                    <img
                        v-if="getSetting('general', 'site_logo')"
                        :src="getSetting('general', 'site_logo')"
                        class="h-8 w-auto object-contain"
                        alt="Logo"
                    />
                    <div v-else class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-violet-600 to-pink-600 flex items-center justify-center text-sm shadow-lg shadow-violet-500/30 group-hover:scale-110 transition-transform">
                            🛍
                        </div>
                        <span class="font-black text-lg tracking-tight text-white">
                            {{ getSetting('general', 'site_name', 'ESHOP') }}
                        </span>
                    </div>
                </div>

                <!-- Desktop Nav -->
                <ul class="hidden md:flex items-center gap-1 list-none m-0 p-0">
                    <li>
                        <router-link to="/" class="nav-link">Home</router-link>
                    </li>
                    <!-- Categories Dropdown -->
                    <li class="relative group/dropdown">
                        <button class="nav-link flex items-center gap-1">
                            Categories
                            <svg class="w-3 h-3 opacity-50 group-hover/dropdown:rotate-180 transition-transform" viewBox="0 0 12 12" fill="currentColor">
                                <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round"/>
                            </svg>
                        </button>
                        <div class="absolute top-full left-0 mt-2 w-52 bg-[#0f0f1e] border border-white/10 rounded-2xl shadow-2xl shadow-black/50 py-2 opacity-0 invisible group-hover/dropdown:opacity-100 group-hover/dropdown:visible transition-all duration-200 translate-y-1 group-hover/dropdown:translate-y-0">
                            <router-link
                                v-for="cat in categories"
                                :key="cat.id"
                                :to="`/category/${cat.slug}`"
                                class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-400 hover:text-white hover:bg-violet-500/10 transition-all"
                            >
                                <span class="w-1.5 h-1.5 bg-violet-500/50 rounded-full flex-shrink-0"></span>
                                {{ cat.name }}
                            </router-link>
                            <div v-if="!categories.length" class="px-4 py-3 text-xs text-slate-600">No categories</div>
                        </div>
                    </li>
                    <li><router-link to="/shop" class="nav-link">Shop</router-link></li>
                    <li v-if="getSetting('general', 'show_about_link', '1') !== '0'">
                        <router-link to="/about" class="nav-link">About</router-link>
                    </li>
                    <li v-if="getSetting('general', 'show_contact_link', '1') !== '0'">
                        <router-link to="/contact" class="nav-link">Contact</router-link>
                    </li>
                </ul>

                <!-- Actions -->
                <div class="flex items-center gap-2">
                    <!-- Auth -->
                    <template v-if="authStore.isAuthenticated">
                        <router-link to="/admin/dashboard" class="hidden sm:flex items-center gap-2 text-xs font-semibold text-slate-300 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 px-3 py-2 rounded-full transition-all">
                            👤 Dashboard
                        </router-link>
                    </template>
                    <template v-else>
                        <router-link to="/login" class="hidden sm:block">
                            <button class="text-xs font-semibold text-slate-300 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 px-4 py-2 rounded-full transition-all">
                                Sign In
                            </button>
                        </router-link>
                    </template>

                    <!-- Cart -->
                    <router-link to="/checkout" class="relative">
                        <button class="relative w-9 h-9 flex items-center justify-center rounded-full bg-white/5 hover:bg-white/10 border border-white/10 transition-all text-sm">
                            🛒
                            <span v-if="cartStore?.items?.length" class="absolute -top-1 -right-1 bg-gradient-to-br from-violet-500 to-pink-500 text-white text-[9px] font-bold rounded-full min-w-[16px] h-[16px] flex items-center justify-center border-2 border-[#080810]">
                                {{ cartStore.items.length }}
                            </span>
                        </button>
                    </router-link>

                    <!-- Mobile Menu Toggle -->
                    <button @click="isMenuOpen = !isMenuOpen" class="md:hidden w-9 h-9 flex items-center justify-center rounded-full bg-white/5 border border-white/10 text-slate-400 hover:text-white transition-all">
                        <span v-if="!isMenuOpen">☰</span>
                        <span v-else>✕</span>
                    </button>
                </div>
            </nav>

            <!-- Mobile Menu -->
            <div v-if="isMenuOpen" class="md:hidden border-t border-white/5 bg-[#0a0a14] px-5 py-4 space-y-1">
                <router-link to="/" @click="isMenuOpen=false" class="mobile-nav-link">Home</router-link>
                <router-link to="/shop" @click="isMenuOpen=false" class="mobile-nav-link">Shop</router-link>
                <div class="py-1 px-1">
                    <p class="text-xs text-slate-600 uppercase tracking-widest font-semibold mb-2 mt-2">Categories</p>
                    <router-link
                        v-for="cat in categories.slice(0, 8)" :key="cat.id"
                        :to="`/category/${cat.slug}`"
                        @click="isMenuOpen=false"
                        class="mobile-nav-link pl-4 text-slate-500"
                    >{{ cat.name }}</router-link>
                </div>
                <router-link to="/about" @click="isMenuOpen=false" class="mobile-nav-link">About</router-link>
                <router-link to="/contact" @click="isMenuOpen=false" class="mobile-nav-link">Contact</router-link>
                <router-link to="/login" @click="isMenuOpen=false" class="mobile-nav-link">Sign In</router-link>
            </div>
        </header>

        <!-- ===== MAIN CONTENT ===== -->
        <main>
            <slot />
        </main>

        <!-- ===== FOOTER ===== -->
        <footer class="border-t border-white/5 bg-[#050508] text-slate-500 pt-16 pb-10">
            <div class="max-w-7xl mx-auto px-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10 mb-14">

                    <!-- Brand -->
                    <div class="lg:col-span-2">
                        <div class="flex items-center gap-2 mb-5">
                            <img v-if="getSetting('general', 'site_logo')" :src="getSetting('general', 'site_logo')" class="h-7 w-auto filter brightness-0 invert opacity-60" alt="Logo" />
                            <span v-else class="font-black text-lg text-white">{{ getSetting('general', 'site_name', 'ESHOP') }}</span>
                        </div>
                        <p class="text-sm leading-relaxed mb-6 max-w-xs">
                            {{ getSetting('general', 'site_description', 'Premium shopping experience — curated, secure, delivered fast.') }}
                        </p>
                        <div class="flex gap-2.5">
                            <a v-if="getSetting('social', 'facebook')" :href="getSetting('social', 'facebook')" target="_blank" class="footer-social-btn">f</a>
                            <a v-if="getSetting('social', 'instagram')" :href="getSetting('social', 'instagram')" target="_blank" class="footer-social-btn">ig</a>
                            <a v-if="getSetting('social', 'youtube')" :href="getSetting('social', 'youtube')" target="_blank" class="footer-social-btn">yt</a>
                            <a v-if="getSetting('social', 'twitter')" :href="getSetting('social', 'twitter')" target="_blank" class="footer-social-btn">𝕏</a>
                            <a v-if="getSetting('social', 'tiktok')" :href="getSetting('social', 'tiktok')" target="_blank" class="footer-social-btn">tt</a>
                        </div>
                    </div>

                    <!-- Contact -->
                    <div>
                        <h4 class="footer-heading">Contact</h4>
                        <ul class="space-y-3 text-sm">
                            <li v-if="getSetting('general', 'phone')" class="flex items-start gap-2">
                                <span class="text-violet-500 mt-0.5 text-xs">📞</span>
                                <a :href="`tel:${getSetting('general', 'phone')}`" class="hover:text-violet-400 transition-colors">{{ getSetting('general', 'phone') }}</a>
                            </li>
                            <li v-if="getSetting('general', 'email')" class="flex items-start gap-2">
                                <span class="text-violet-500 mt-0.5 text-xs">✉</span>
                                <a :href="`mailto:${getSetting('general', 'email')}`" class="hover:text-violet-400 transition-colors">{{ getSetting('general', 'email') }}</a>
                            </li>
                            <li v-if="getSetting('general', 'address')" class="flex items-start gap-2">
                                <span class="text-violet-500 mt-0.5 text-xs">📍</span>
                                <span>{{ getSetting('general', 'address') }}</span>
                            </li>
                            <li v-if="getSetting('general', 'whatsapp')" class="flex items-start gap-2">
                                <span class="text-green-500 mt-0.5 text-xs">💬</span>
                                <a :href="`https://wa.me/${getSetting('general', 'whatsapp')}`" target="_blank" class="hover:text-green-400 transition-colors">WhatsApp</a>
                            </li>
                        </ul>
                    </div>

                    <!-- Quick Links -->
                    <div>
                        <h4 class="footer-heading">Quick Links</h4>
                        <ul class="space-y-2.5 text-sm">
                            <li><router-link to="/" class="footer-link">Home</router-link></li>
                            <li><router-link to="/shop" class="footer-link">Shop</router-link></li>
                            <li><router-link to="/about" class="footer-link">About Us</router-link></li>
                            <li><router-link to="/contact" class="footer-link">Contact</router-link></li>
                            <li v-if="getSetting('general', 'track_order_url')">
                                <a :href="getSetting('general', 'track_order_url')" class="footer-link">Track Order</a>
                            </li>
                        </ul>
                    </div>

                    <!-- Top Categories -->
                    <div>
                        <h4 class="footer-heading">Categories</h4>
                        <ul class="space-y-2.5 text-sm">
                            <li v-for="cat in categories.slice(0, 5)" :key="cat.id">
                                <router-link :to="`/category/${cat.slug}`" class="footer-link">{{ cat.name }}</router-link>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Bottom Bar -->
                <div class="border-t border-white/5 pt-8 flex flex-col sm:flex-row justify-between items-center gap-3 text-xs text-slate-600">
                    <span>{{ getSetting('general', 'copyright', '© 2026 E-Shop. Built with ❤️ in Bangladesh') }}</span>
                    <div class="flex items-center gap-2">
                        <span v-if="getSetting('payment', 'bkash_enabled')" class="payment-badge text-pink-400">bKash</span>
                        <span v-if="getSetting('payment', 'nagad_enabled')" class="payment-badge text-orange-400">Nagad</span>
                        <span v-if="getSetting('payment', 'rocket_enabled')" class="payment-badge text-purple-400">Rocket</span>
                        <span v-if="getSetting('payment', 'sslcommerz_enabled')" class="payment-badge text-green-400">SSL</span>
                        <span v-if="getSetting('payment', 'cod_enabled')" class="payment-badge text-blue-400">COD</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.nav-link {
    display: inline-flex;
    align-items: center;
    padding: 6px 14px;
    border-radius: 9999px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #94a3b8;
    text-decoration: none;
    transition: all 0.2s;
    white-space: nowrap;
}
.nav-link:hover, .nav-link.router-link-active {
    color: white;
    background: rgba(255,255,255,0.07);
}

.mobile-nav-link {
    display: block;
    padding: 10px 12px;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    color: #94a3b8;
    text-decoration: none;
    transition: all 0.2s;
}
.mobile-nav-link:hover {
    color: white;
    background: rgba(255,255,255,0.05);
}

.footer-heading {
    color: white;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    margin-bottom: 18px;
}

.footer-link {
    color: #64748b;
    text-decoration: none;
    transition: color 0.2s;
}
.footer-link:hover { color: #a78bfa; }

.footer-social-btn {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 8px;
    font-size: 0.7rem;
    font-weight: 700;
    color: #64748b;
    text-decoration: none;
    transition: all 0.2s;
}
.footer-social-btn:hover {
    background: rgba(124, 58, 237, 0.15);
    border-color: rgba(124, 58, 237, 0.3);
    color: #a78bfa;
}

.payment-badge {
    padding: 3px 8px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 6px;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.05em;
}

::-webkit-scrollbar { width: 5px; }
::-webkit-scrollbar-track { background: #080810; }
::-webkit-scrollbar-thumb { background: linear-gradient(to bottom, #7c3aed, #db2777); border-radius: 10px; }
</style>
