<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const router = useRouter();
const API_URL = 'http://127.0.0.1:73/api/admin';

// States
const products = ref([]);
const cart = ref([]);
const loading = ref(false);
const searchQuery = ref('');

// Form Data (Discount removed)
const form = ref({
  name: '',
  phone: '',
  address: '',
  area: '',
  district: '',
  order_source: 'Phone',
  payment_method: 'COD',
  status: 'Pending',
  shipping_charge: 60
});

// Load Products for selection
const fetchProducts = async () => {
  try {
    const token = localStorage.getItem('token');
    const res = await axios.get(`${API_URL}/products`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    products.value = res.data.data || res.data || [];
  } catch (error) {
    console.error("Failed to fetch products");
  }
};

// Filter Products based on search
const filteredProducts = computed(() => {
  if (!searchQuery.value) return products.value.slice(0, 10);
  return products.value.filter(p => p.name.toLowerCase().includes(searchQuery.value.toLowerCase()));
});

// Cart Functions
const addToCart = (product) => {
  const existing = cart.value.find(item => item.product_id === product.id);
  if (existing) {
    existing.quantity++;
  } else {
    const price = product.sale_price || product.base_price || product.price || 0;
    cart.value.push({
      product_id: product.id,
      name: product.name,
      price: price,
      quantity: 1
    });
  }
};

const removeFromCart = (index) => {
  cart.value.splice(index, 1);
};

// Calculations
const subTotal = computed(() => {
  return cart.value.reduce((sum, item) => sum + (item.price * item.quantity), 0);
});

const grandTotal = computed(() => {
  return subTotal.value + Number(form.value.shipping_charge); // Discount minus kora bad dewa hoyeche
});

// Submit Order
const submitOrder = async () => {
  if (cart.value.length === 0) return Swal.fire('Warning', 'Please add at least one product!', 'warning');
  if (!form.value.name || !form.value.phone) return Swal.fire('Warning', 'Customer name and phone are required!', 'warning');

  loading.value = true;
  try {
    const token = localStorage.getItem('token');

    const payload = {
      ...form.value,
      sub_total: subTotal.value,
      total_amount: grandTotal.value,
      items: cart.value
    };

    await axios.post(`${API_URL}/orders`, payload, {
      headers: { Authorization: `Bearer ${token}` }
    });

    Swal.fire('Success', 'Order Created Successfully!', 'success');
    router.push('/admin/orders');
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to create order', 'error');
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchProducts();
});
</script>

<template>
  <AdminLayout>
    <div class="max-w-6xl mx-auto pb-10">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-black text-slate-800">🛍️ Create New Order</h2>
        <button @click="router.push('/admin/orders')" class="text-slate-500 font-bold hover:text-indigo-600 transition">
          ← Back to Orders
        </button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <div class="lg:col-span-7 space-y-6">
          <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
            <h3 class="text-lg font-bold text-slate-800 border-b pb-3 mb-4">Customer Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Customer Name *</label>
                <input v-model="form.name" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="e.g. Rahim Uddin">
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Phone Number *</label>
                <input v-model="form.phone" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="01XXXXXXXXX">
              </div>
              <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-600 mb-1">Full Address</label>
                <textarea v-model="form.address" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="House, Road, Thana"></textarea>
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">District</label>
                <input v-model="form.district" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="e.g. Dhaka">
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Area / Thana</label>
                <input v-model="form.area" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="e.g. Dhanmondi">
              </div>
            </div>
          </div>

          <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
            <h3 class="text-lg font-bold text-slate-800 border-b pb-3 mb-4">Order Settings</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Order Source</label>
                <select v-model="form.order_source" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-indigo-50 font-bold text-indigo-800">
                  <option value="Website">Website</option>
                  <option value="Phone">Phone Call</option>
                  <option value="Facebook">Facebook Page</option>
                  <option value="WhatsApp">WhatsApp</option>
                  <option value="Instagram">Instagram</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Payment Method</label>
                <select v-model="form.payment_method" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                  <option value="COD">Cash on Delivery (COD)</option>
                  <option value="bKash">bKash</option>
                  <option value="Nagad">Nagad</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Initial Status</label>
                <select v-model="form.status" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                  <option value="Pending">Pending</option>
                  <option value="Confirmed">Confirmed</option>
                  <option value="Processing">Processing</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <div class="lg:col-span-5 space-y-6">
          <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
            <h3 class="text-lg font-bold text-slate-800 mb-3">Add Products</h3>
            <input v-model="searchQuery" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none mb-3" placeholder="🔍 Search product by name...">

            <div class="max-h-40 overflow-y-auto border border-slate-100 rounded-lg divide-y custom-scrollbar">
              <div v-for="product in filteredProducts" :key="product.id" class="p-2 flex justify-between items-center hover:bg-slate-50">
                <div class="text-sm font-bold text-slate-700 truncate w-3/4">{{ product.name }}</div>
                <button @click="addToCart(product)" class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded text-xs font-black hover:bg-indigo-600 hover:text-white transition">
                  + Add
                </button>
              </div>
              <div v-if="filteredProducts.length === 0" class="p-4 text-center text-sm text-slate-500">No products found.</div>
            </div>
          </div>

          <div class="bg-slate-800 p-6 rounded-xl shadow-sm text-white">
            <h3 class="text-lg font-bold border-b border-slate-700 pb-3 mb-4">Cart Summary</h3>

            <div class="space-y-3 mb-6 max-h-48 overflow-y-auto custom-scrollbar pr-2">
              <div v-for="(item, index) in cart" :key="index" class="flex justify-between items-center bg-slate-700 p-3 rounded-lg border border-slate-600">
                <div class="w-1/2">
                  <div class="text-sm font-bold truncate" :title="item.name">{{ item.name }}</div>
                  <div class="text-xs text-slate-400">৳{{ item.price }}</div>
                </div>
                <div class="flex items-center gap-2">
                  <input type="number" v-model.number="item.quantity" min="1" class="w-12 px-1 py-1 text-center text-slate-900 rounded text-sm font-bold outline-none">
                  <button @click="removeFromCart(index)" class="text-red-400 hover:text-red-300 text-xl font-black ml-1">×</button>
                </div>
              </div>
              <div v-if="cart.length === 0" class="text-center text-red-400 font-bold text-sm py-4">Cart is empty!</div>
            </div>

            <div class="space-y-2 text-sm font-medium border-t border-slate-700 pt-4">
              <div class="flex justify-between">
                <span class="text-slate-300">Subtotal:</span>
                <span>৳{{ subTotal }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-slate-300">Shipping:</span>
                <input type="number" v-model.number="form.shipping_charge" class="w-20 px-2 py-1 text-right text-slate-900 rounded text-sm outline-none">
              </div>
              <div class="flex justify-between text-lg font-black text-emerald-400 pt-2 border-t border-slate-700 mt-2">
                <span>Total Amount:</span>
                <span>৳{{ grandTotal }}</span>
              </div>
            </div>

            <button @click="submitOrder" :disabled="loading" class="w-full mt-6 bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white font-black py-3 rounded-lg shadow-lg transition-all">
              {{ loading ? 'Processing...' : 'Confirm Order 🚀' }}
            </button>
          </div>

        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #64748b; border-radius: 10px; }
</style>
