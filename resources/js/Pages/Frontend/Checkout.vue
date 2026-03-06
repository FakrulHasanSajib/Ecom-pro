<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useCartStore } from '../../stores/cart';
import axios from 'axios';
import Swal from 'sweetalert2';

const router = useRouter();
const cartStore = useCartStore();

const form = ref({ name: '', phone: '', address: '', area: 'inside_dhaka', payment_method: 'cod' });
const isSubmitting = ref(false);

// 🔥 ডাইনামিক ডেলিভারি চার্জের জন্য ভেরিয়েবল
const insideDhakaCharge = ref(70);  // ডিফল্ট মান
const outsideDhakaCharge = ref(130); // ডিফল্ট মান

// 🔥 ডাটাবেস থেকে সেটিংস (ডেলিভারি চার্জ) নিয়ে আসা
const fetchSettings = async () => {
    try {
        const res = await axios.get('http://127.0.0.1:73/api/public/settings');
        const data = res.data?.data || res.data || {};

        // ডাটাবেস থেকে shipping_inside_dhaka এবং shipping_outside_dhaka বের করা
        if (typeof data === 'object') {
            Object.keys(data).forEach(group => {
                const items = data[group];
                if (Array.isArray(items)) {
                    items.forEach(item => {
                        const key = item.key || item.name;
                        if (key === 'shipping_inside_dhaka' && item.value) {
                            insideDhakaCharge.value = Number(item.value);
                        }
                        if (key === 'shipping_outside_dhaka' && item.value) {
                            outsideDhakaCharge.value = Number(item.value);
                        }
                    });
                }
            });
        }
    } catch (error) {
        console.error("Failed to load delivery settings:", error);
    }
};

// 🔥 ডাইনামিক চার্জ ক্যালকুলেশন
const shippingCost = computed(() => form.value.area === 'inside_dhaka' ? insideDhakaCharge.value : outsideDhakaCharge.value);
const grandTotal = computed(() => cartStore.totalPrice + shippingCost.value);

onMounted(() => {
    if (cartStore.items.length === 0) {
        Swal.fire({ title: 'Empty Cart!', text: 'আপনার কার্ট খালি! অনুগ্রহ করে প্রোডাক্ট যুক্ত করুন।', icon: 'warning', confirmButtonColor: '#6366f1' }).then(() => router.push('/'));
    }
    // পেজ লোড হলে সেটিংস কল করবে
    fetchSettings();
});

const placeOrder = async () => {
    if (!form.value.name || !form.value.phone || !form.value.address) {
        Swal.fire({ title: 'তথ্য অসম্পূর্ণ!', text: 'দয়া করে নাম, ফোন ও ঠিকানা দিন।', icon: 'warning', confirmButtonColor: '#6366f1' }); return;
    }
    try {
        isSubmitting.value = true;
        const res = await axios.post('http://127.0.0.1:73/api/public/checkout', {
            name: form.value.name, phone: form.value.phone, address: form.value.address,
            area: form.value.area, payment_method: form.value.payment_method,
            shipping_charge: shippingCost.value, total_amount: grandTotal.value,
            items: cartStore.items.map(i => ({ product_id: i.id, quantity: i.quantity }))
        });
        if (form.value.payment_method === 'sslcommerz') {
            res.data.payment_url ? (window.location.href = res.data.payment_url) : (Swal.fire('Error!', 'Payment URL missing!', 'error'), isSubmitting.value = false);
        } else {
            Swal.fire({ title: 'Order Successful! 🎉', text: 'অর্ডার সফলভাবে রিসিভ হয়েছে।', icon: 'success', timer: 2000, showConfirmButton: false }).then(() => {
                cartStore.items = []; localStorage.removeItem('cart_items');
                router.push(`/order-success?order_number=${res.data.data.order_number}`);
            });
        }
    } catch (error) {
        isSubmitting.value = false;
        if (error.response) {
            if (error.response.status === 422) Swal.fire('Validation Error!', 'সব তথ্য সঠিকভাবে পূরণ করুন।', 'error');
            else if (error.response.status === 400) Swal.fire({ title: 'Server Error!', text: error.response.data.error_detail || error.response.data.message || 'Unknown error', icon: 'error' });
            else Swal.fire('Oops...', 'Server error: ' + error.response.status, 'error');
        } else Swal.fire('Network Error', 'সার্ভারের সাথে কানেক্ট হচ্ছে না।', 'error');
    }
};

const goBack = () => router.push('/');
const getImageUrl = (path) => { if (!path) return 'https://placehold.co/100x100?text=No+Image'; return path.startsWith('http') ? path : `http://127.0.0.1:73/storage/${path}`; };
</script>

<template>
    <div class="co-root">
        <div class="blob b1"></div><div class="blob b2"></div><div class="blob b3"></div>

        <header class="hdr">
            <div class="hdr-inner">
                <div @click="goBack" class="brand">
                    <span class="brand-emoji">🛍️</span>
                    <div><b class="bn">E-Shop</b><small class="bs">CHECKOUT</small></div>
                </div>
                <div class="steps">
                    <span class="sp sp-on"><em>1</em> Shipping</span>
                    <span class="sl"></span>
                    <span class="sp sp-on"><em>2</em> Payment</span>
                    <span class="sl"></span>
                    <span class="sp sp-dim"><em class="dim-n">3</em> Confirm</span>
                </div>
                <div class="ssl">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    SSL Secured
                </div>
            </div>
        </header>

        <main class="co-main">
            <button @click="goBack" class="back-btn">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7 7-7M3 12h18"/></svg>
                Continue Shopping
            </button>

            <div class="co-layout">
                <div class="co-left">

                    <div class="card">
                        <div class="card-hd">
                            <div class="nbadge">01</div>
                            <div><h2 class="ct">Shipping Details</h2><p class="cs">Where should we deliver?</p></div>
                        </div>
                        <div class="grid2 mb5">
                            <div class="fg">
                                <label class="fl">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg> Full Name
                                </label>
                                <input v-model="form.name" type="text" placeholder="e.g. Rahim Uddin" class="fi" />
                            </div>
                            <div class="fg">
                                <label class="fl">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg> Phone
                                </label>
                                <input v-model="form.phone" type="tel" placeholder="01XXXXXXXXX" class="fi" />
                            </div>
                        </div>
                        <div class="mb5">
                            <label class="fl mb3">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg> Delivery Area
                            </label>
                            <div class="grid2">
                                <label :class="['ac', form.area==='inside_dhaka' ? 'ac-on' : 'ac-off']">
                                    <input type="radio" v-model="form.area" value="inside_dhaka" class="hidden" />
                                    <span class="ai">🏙️</span>
                                    <div class="flex-1">
                                        <p class="at">Inside Dhaka</p>
                                        <p class="ach">৳{{ insideDhakaCharge }} · 1–2 days</p>
                                    </div>
                                    <svg v-if="form.area==='inside_dhaka'" class="ck" fill="currentColor" width="18" height="18" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                </label>
                                <label :class="['ac', form.area==='outside_dhaka' ? 'ac-on' : 'ac-off']">
                                    <input type="radio" v-model="form.area" value="outside_dhaka" class="hidden" />
                                    <span class="ai">🚚</span>
                                    <div class="flex-1">
                                        <p class="at">Outside Dhaka</p>
                                        <p class="ach">৳{{ outsideDhakaCharge }} · 3–5 days</p>
                                    </div>
                                    <svg v-if="form.area==='outside_dhaka'" class="ck" fill="currentColor" width="18" height="18" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                </label>
                            </div>
                        </div>
                        <div class="fg">
                            <label class="fl"><svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg> Detailed Address</label>
                            <textarea v-model="form.address" rows="3" placeholder="House no., Road, Thana, District..." class="fi" style="resize:none"></textarea>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-hd">
                            <div class="nbadge">02</div>
                            <div><h2 class="ct">Payment Method</h2><p class="cs">How would you like to pay?</p></div>
                        </div>
                        <div class="grid2">
                            <label :class="['pc', form.payment_method==='cod' ? 'pc-cod' : 'pc-off']">
                                <input type="radio" v-model="form.payment_method" value="cod" class="hidden" />
                                <div class="pi pi-cod"><svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg></div>
                                <p class="ptt">Cash on Delivery</p>
                                <p class="pds">Pay when your order arrives.</p>
                                <span class="pb pb-cod">Most Popular</span>
                                <div v-if="form.payment_method==='cod'" class="pchk pchk-cod">✓</div>
                            </label>
                            <label :class="['pc', form.payment_method==='sslcommerz' ? 'pc-ssl' : 'pc-off']">
                                <input type="radio" v-model="form.payment_method" value="sslcommerz" class="hidden" />
                                <div class="pi pi-ssl"><svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg></div>
                                <p class="ptt">SSLCommerz</p>
                                <p class="pds">bKash, Nagad, Cards & more.</p>
                                <span class="pb pb-ssl">Instant</span>
                                <div v-if="form.payment_method==='sslcommerz'" class="pchk pchk-ssl">✓</div>
                            </label>
                        </div>
                    </div>

                    <div class="trust">
                        <span>🔒 Secure</span><span class="td"></span>
                        <span>↩️ Easy Returns</span><span class="td"></span>
                        <span>🚚 Fast Delivery</span><span class="td"></span>
                        <span>🎁 Originals</span>
                    </div>

                </div>

                <div class="co-right">
                    <div class="summary sticky top-24">
                        <div class="sm-rainbow"></div>

                        <div class="sm-top">
                            <span class="sm-title">Order Summary</span>
                            <span class="sm-cnt">{{ cartStore.items.length }} item{{ cartStore.items.length!==1?'s':'' }}</span>
                        </div>

                        <div class="sm-items">
                            <div v-for="item in cartStore.items" :key="item.id" class="sm-item">
                                <div class="sm-iw">
                                    <img :src="getImageUrl(item.thumbnail)" class="sm-img" />
                                    <span class="sm-qty">{{ item.quantity }}</span>
                                </div>
                                <div class="sm-info">
                                    <p class="sm-name">{{ item.name }}</p>
                                    <p class="sm-up">৳{{ item.price }} × {{ item.quantity }}</p>
                                </div>
                                <span class="sm-tot">৳{{ item.price * item.quantity }}</span>
                            </div>
                        </div>

                        <div class="sm-pricing">
                            <div class="sm-row"><span class="sm-l">Subtotal</span><span class="sm-v">৳{{ cartStore.totalPrice }}</span></div>
                            <div class="sm-row">
                                <span class="sm-l">Shipping <span class="sm-tag">{{ form.area==='inside_dhaka'?'Inside Dhaka':'Outside Dhaka' }}</span></span>
                                <span class="sm-v">৳{{ shippingCost }}</span>
                            </div>
                            <div class="sm-row sm-save"><span>🎉 Free packaging</span><span>Included!</span></div>
                        </div>

                        <div class="sm-grand">
                            <span class="sg-lbl">Total Payable</span>
                            <span class="sg-amt">৳{{ grandTotal }}</span>
                        </div>

                        <div class="sm-cta">
                            <button @click="placeOrder" :disabled="isSubmitting" class="cta">
                                <span v-if="isSubmitting" style="display:flex;align-items:center;justify-content:center;gap:8px">
                                    <svg style="animation:spin 1s linear infinite" width="20" height="20" fill="none" viewBox="0 0 24 24"><circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    Processing...
                                </span>
                                <span v-else style="display:flex;align-items:center;justify-content:center;gap:8px">
                                    <template v-if="form.payment_method==='cod'">✅ Confirm Order — ৳{{ grandTotal }}</template>
                                    <template v-else>🔐 Pay ৳{{ grandTotal }} via SSLCommerz</template>
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7-7 7M3 12h18"/></svg>
                                </span>
                            </button>
                            <p class="cta-note">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                256-bit SSL encrypted. Your data is 100% safe.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</template>

<style scoped>
@keyframes spin { to { transform: rotate(360deg); } }

* { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; box-sizing: border-box; }

.co-root { background: #f4f3ff; position: relative; overflow-x: hidden; min-height: 100vh; }

/* --- Blobs --- */
.blob { position: fixed; border-radius: 50%; filter: blur(90px); opacity: 0.18; pointer-events: none; z-index: 0; }
.b1 { width: 520px; height: 520px; background: radial-gradient(#a78bfa, #818cf8); top: -200px; left: -150px; }
.b2 { width: 420px; height: 420px; background: radial-gradient(#34d399, #6ee7b7); bottom: -130px; right: -110px; }
.b3 { width: 300px; height: 300px; background: radial-gradient(#fbbf24, #f9a8d4); top: 42%; left: 42%; }

/* --- Header --- */
.hdr { background: rgba(255,255,255,0.92); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); border-bottom: 1px solid rgba(99,102,241,0.1); box-shadow: 0 2px 24px rgba(99,102,241,0.07); position: sticky; top: 0; z-index: 50; }
.hdr-inner { max-width: 1280px; margin: 0 auto; padding: 0 24px; height: 72px; display: flex; align-items: center; justify-content: space-between; }
.brand { display: flex; align-items: center; gap: 12px; cursor: pointer; }
.brand-emoji { font-size: 1.8rem; transition: transform 0.3s; }
.brand:hover .brand-emoji { transform: scale(1.1); }
.bn { display: block; font-size: 1.18rem; font-weight: 900; background: linear-gradient(135deg, #4f46e5, #7c3aed); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.bs { display: block; font-size: 0.58rem; font-weight: 700; color: #94a3b8; letter-spacing: 0.14em; text-transform: uppercase; }

.steps { display: flex; align-items: center; gap: 8px; }
.sp { display: flex; align-items: center; gap: 7px; padding: 6px 14px; border-radius: 100px; font-size: 0.77rem; font-weight: 700; }
.sp em { width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: 900; font-style: normal; background: #4f46e5; color: white; }
.sp-on { background: #eef2ff; color: #4f46e5; }
.sp-dim { background: #f1f5f9; color: #94a3b8; }
.dim-n { background: #cbd5e1 !important; }
.sl { width: 22px; height: 2px; background: linear-gradient(90deg, #c7d2fe, #e0e7ff); border-radius: 2px; }

.ssl { display: flex; align-items: center; gap: 6px; padding: 7px 16px; background: linear-gradient(135deg, #f0fdf4, #dcfce7); border: 1px solid #bbf7d0; border-radius: 100px; color: #16a34a; font-size: 0.77rem; font-weight: 700; }

/* --- Main --- */
.co-main { max-width: 1280px; margin: 0 auto; padding: 40px 24px; position: relative; z-index: 1; }

.back-btn { display: inline-flex; align-items: center; gap: 7px; color: #64748b; font-size: 0.86rem; font-weight: 600; padding: 8px 16px; border-radius: 12px; background: white; border: 1px solid #e2e8f0; box-shadow: 0 1px 4px rgba(0,0,0,0.04); cursor: pointer; transition: all 0.2s; margin-bottom: 32px; }
.back-btn:hover { color: #4f46e5; border-color: #c7d2fe; background: #eef2ff; transform: translateX(-3px); }

.co-layout { display: flex; gap: 32px; flex-direction: column; }
@media (min-width: 1024px) { .co-layout { flex-direction: row; } .co-left { width: 58%; } .co-right { width: 42%; } }

.co-left { display: flex; flex-direction: column; gap: 24px; }

/* --- Card --- */
.card { background: white; border-radius: 24px; padding: 28px; border: 1px solid rgba(99,102,241,0.08); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03), 0 20px 40px -10px rgba(99,102,241,0.08), 0 0 0 1px rgba(255,255,255,0.9) inset; }
.card-hd { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 24px; padding-bottom: 18px; border-bottom: 1px solid #f1f5f9; }
.nbadge { width: 40px; height: 40px; border-radius: 14px; background: linear-gradient(135deg, #4f46e5, #7c3aed); display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 4px 12px rgba(99,102,241,0.3); color: white; font-weight: 900; font-size: 0.8rem; }
.ct { font-size: 1.1rem; font-weight: 800; color: #1e293b; }
.cs { font-size: 0.77rem; color: #94a3b8; font-weight: 500; margin-top: 2px; }

/* --- Form --- */
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 640px) { .grid2 { grid-template-columns: 1fr; } }
.mb5 { margin-bottom: 20px; }
.mb3 { margin-bottom: 12px; }
.fg { display: flex; flex-direction: column; gap: 7px; }
.fl { display: flex; align-items: center; gap: 6px; font-size: 0.77rem; font-weight: 700; color: #475569; }
.fi { background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 13px 16px; font-size: 0.93rem; color: #1e293b; outline: none; transition: all 0.2s; width: 100%; font-family: inherit; }
.fi:focus { border-color: #6366f1; background: white; box-shadow: 0 0 0 4px rgba(99,102,241,0.1); }
.fi::placeholder { color: #c8d2de; }

/* --- Area cards --- */
.ac { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 16px; border: 2px solid; cursor: pointer; transition: all 0.22s; }
.ac-off { border-color: #e2e8f0; background: #f8fafc; }
.ac-off:hover { border-color: #c7d2fe; background: #f5f3ff; }
.ac-on { border-color: #6366f1; background: linear-gradient(135deg, #f5f3ff, #eef2ff); }
.ai { font-size: 1.8rem; }
.at { font-weight: 800; color: #1e293b; font-size: 0.88rem; }
.ach { font-size: 0.72rem; font-weight: 700; color: #4f46e5; margin-top: 2px; }
.ck { color: #4f46e5; flex-shrink: 0; }

/* --- Payment cards --- */
.pc { position: relative; display: flex; flex-direction: column; align-items: center; text-align: center; padding: 26px 18px; border-radius: 20px; border: 2px solid; cursor: pointer; transition: all 0.25s; }
.pc-off { border-color: #e2e8f0; background: #f8fafc; }
.pc-off:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
.pc-cod { border-color: #10b981; background: linear-gradient(135deg, #f0fdf4, #ecfdf5); box-shadow: 0 0 0 4px rgba(16,185,129,0.1); transform: translateY(-2px); }
.pc-ssl { border-color: #3b82f6; background: linear-gradient(135deg, #eff6ff, #dbeafe); box-shadow: 0 0 0 4px rgba(59,130,246,0.1); transform: translateY(-2px); }
.pi { width: 62px; height: 62px; border-radius: 18px; display: flex; align-items: center; justify-content: center; margin-bottom: 11px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.pi-cod { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669; }
.pi-ssl { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb; }
.ptt { font-weight: 800; color: #1e293b; font-size: 0.95rem; margin-bottom: 5px; }
.pds { font-size: 0.73rem; color: #94a3b8; font-weight: 500; line-height: 1.4; }
.pb { display: inline-block; margin-top: 9px; padding: 3px 10px; border-radius: 100px; font-size: 0.65rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.pb-cod { background: #dcfce7; color: #15803d; }
.pb-ssl { background: #dbeafe; color: #1d4ed8; }
.pchk { position: absolute; top: 11px; right: 11px; width: 23px; height: 23px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 900; color: white; }
.pchk-cod { background: #10b981; }
.pchk-ssl { background: #3b82f6; }

/* --- Trust bar --- */
.trust { display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: 6px; background: white; border-radius: 16px; padding: 13px 20px; border: 1px solid #f1f5f9; box-shadow: 0 2px 8px rgba(0,0,0,0.03); font-size: 0.74rem; font-weight: 600; color: #64748b; }
.td { width: 1px; height: 16px; background: #e2e8f0; }

/* --- Order Summary --- */
.summary { background: #0f172a; border-radius: 28px; overflow: hidden; box-shadow: 0 25px 60px rgba(15,23,42,0.28), 0 0 0 1px rgba(255,255,255,0.04) inset; }
.sm-rainbow { height: 4px; background: linear-gradient(90deg, #6366f1, #8b5cf6, #ec4899, #f59e0b, #10b981); }
.sm-top { padding: 22px 26px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
.sm-title { font-size: 1.15rem; font-weight: 900; color: white; }
.sm-cnt { background: rgba(99,102,241,0.2); color: #a5b4fc; border: 1px solid rgba(99,102,241,0.3); border-radius: 100px; padding: 4px 12px; font-size: 0.7rem; font-weight: 700; }

.sm-items { padding: 0 26px; max-height: 280px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; scrollbar-width: thin; scrollbar-color: #334155 transparent; }
.sm-items::-webkit-scrollbar { width: 4px; }
.sm-items::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
.sm-item { display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06); border-radius: 14px; padding: 10px 12px; transition: background 0.2s; }
.sm-item:hover { background: rgba(255,255,255,0.07); }
.sm-iw { position: relative; flex-shrink: 0; }
.sm-img { width: 50px; height: 50px; border-radius: 12px; object-fit: cover; background: white; border: 1px solid rgba(255,255,255,0.08); }
.sm-qty { position: absolute; top: -6px; right: -6px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; font-size: 0.58rem; font-weight: 900; width: 19px; height: 19px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #0f172a; }
.sm-info { flex: 1; min-width: 0; }
.sm-name { font-size: 0.81rem; font-weight: 700; color: #e2e8f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sm-up { font-size: 0.67rem; color: #64748b; margin-top: 3px; }
.sm-tot { font-weight: 800; color: white; font-size: 0.85rem; flex-shrink: 0; }

.sm-pricing { margin: 18px 26px 0; padding: 16px 18px; background: rgba(255,255,255,0.03); border-radius: 14px; border: 1px solid rgba(255,255,255,0.05); display: flex; flex-direction: column; gap: 11px; }
.sm-row { display: flex; justify-content: space-between; align-items: center; }
.sm-l { font-size: 0.81rem; color: #64748b; font-weight: 500; display: flex; align-items: center; gap: 7px; }
.sm-tag { background: rgba(99,102,241,0.15); color: #a5b4fc; font-size: 0.59rem; font-weight: 700; padding: 2px 7px; border-radius: 100px; text-transform: uppercase; }
.sm-v { font-size: 0.85rem; color: #e2e8f0; font-weight: 700; }
.sm-save { padding-top: 8px; border-top: 1px dashed rgba(255,255,255,0.07); font-size: 0.75rem; color: #34d399; font-weight: 600; }

.sm-grand { margin: 14px 26px; padding: 18px; background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(139,92,246,0.1)); border-radius: 14px; border: 1px solid rgba(99,102,241,0.2); display: flex; align-items: center; justify-content: space-between; }
.sg-lbl { font-size: 0.86rem; color: #94a3b8; font-weight: 600; }
.sg-amt { font-size: 1.85rem; font-weight: 900; background: linear-gradient(135deg, #a5b4fc, #34d399); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; letter-spacing: -0.03em; }

.sm-cta { padding: 0 26px 26px; }
.cta { width: 100%; padding: 17px 24px; border-radius: 16px; font-weight: 900; font-size: 0.97rem; color: white; border: none; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #9333ea 100%); box-shadow: 0 0 28px rgba(99,102,241,0.35), 0 4px 14px rgba(99,102,241,0.2); cursor: pointer; transition: all 0.3s; font-family: inherit; }
.cta:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 0 40px rgba(99,102,241,0.5), 0 8px 24px rgba(99,102,241,0.3); }
.cta:active:not(:disabled) { transform: translateY(0); }
.cta:disabled { opacity: 0.65; cursor: not-allowed; }
.cta-note { display: flex; align-items: center; justify-content: center; gap: 5px; margin-top: 12px; font-size: 0.71rem; color: #475569; font-weight: 500; }

@media (max-width: 640px) {
    .card { padding: 18px; }
    .hdr-inner { padding: 0 16px; }
    .steps { display: none; }
    .sm-top, .sm-items { padding-left: 18px; padding-right: 18px; }
    .sm-pricing, .sm-grand { margin-left: 18px; margin-right: 18px; }
    .sm-cta { padding: 0 18px 20px; }
    .td { display: none; }
    .sg-amt { font-size: 1.55rem; }
    .trust span:not(.td) { padding: 2px 4px; }
}
</style>
