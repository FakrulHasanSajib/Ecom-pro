<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const router = useRouter();
const route = useRoute();
const API_URL = 'http://127.0.0.1:73/api/admin';
const orderId = route.params.id; // URL theke ID neya

// States
const products = ref([]);
const cart = ref([]);
const loading = ref(true);
const saving = ref(false);
const searchQuery = ref('');
const availableStatuses = ref([]);

// Form Data (Discount removed)
const form = ref({
  name: '',
  phone: '',
  address: '',
  area: '',
  district: '',
  payment_method: 'COD',
  status: 'Pending',
  shipping_charge: 0
});

// Load Order Details
const fetchOrderDetails = async () => {
  try {
    const token = localStorage.getItem('token');
    const res = await axios.get(`${API_URL}/orders/${orderId}`, {
      headers: { Authorization: `Bearer ${token}` }
    });

    const order = res.data.data;

    // Form fill kora (Discount removed)
    form.value = {
      name: order.name || '',
      phone: order.phone || '',
      address: order.address || '',
      area: order.area || '',
      district: order.district || '',
      payment_method: order.payment_method || 'COD',
      status: order.status || 'Pending',
      shipping_charge: Number(order.shipping_charge) || 0
    };

    // Cart e items add kora (Backend er response onujayi)
    const items = order.items || order.order_items || [];
    cart.value = items.map(item => ({
      product_id: item.product_id,
      name: item.product_name || item.name || 'Unknown Product',
      price: Number(item.price) || 0,
      quantity: Number(item.quantity) || 1
    }));

  } catch (error) {
    console.error("Failed to load order:", error);
    Swal.fire('Error', 'Failed to load order details', 'error');
  } finally {
    loading.value = false;
  }
};

// Load Products for selection & Statuses
const fetchDependencies = async () => {
  try {
    const token = localStorage.getItem('token');
    const [prodRes, statRes] = await Promise.all([
        axios.get(`${API_URL}/products`, { headers: { Authorization: `Bearer ${token}` } }),
        axios.get(`${API_URL}/order-statuses`, { headers: { Authorization: `Bearer ${token}` } })
    ]);

    products.value = prodRes.data.data || prodRes.data || [];

    let dbStatuses = statRes.data.data || [];
    if(dbStatuses.length === 0) {
        dbStatuses = [{ name: 'Pending' }, { name: 'Processing' }, { name: 'Shipped' }, { name: 'Delivered' }, { name: 'Cancelled' }];
    }
    availableStatuses.value = dbStatuses;
  } catch (error) {
    console.error("Failed to fetch dependencies");
  }
};

// Filter Products
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

// Discount kete dewa hoyeche total calculation theke
const grandTotal = computed(() => {
  return subTotal.value + Number(form.value.shipping_charge);
});

// Update Order
const updateOrder = async () => {
  if (cart.value.length === 0) return Swal.fire('Warning', 'Order must have at least one product!', 'warning');
  if (!form.value.name || !form.value.phone) return Swal.fire('Warning', 'Customer name and phone are required!', 'warning');

  saving.value = true;
  try {
    const token = localStorage.getItem('token');
    const payload = {
      ...form.value,
      sub_total: subTotal.value,
      total_amount: grandTotal.value,
      items: cart.value
    };

    await axios.put(`${API_URL}/orders/${orderId}`, payload, {
      headers: { Authorization: `Bearer ${token}` }
    });

    Swal.fire('Success', 'Order Updated Successfully!', 'success');
    router.push('/admin/orders');
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to update order', 'error');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  fetchDependencies();
  fetchOrderDetails();
});
</script>

<template>
  <AdminLayout>
    <div class="max-w-6xl mx-auto pb-10">

      <div v-if="loading" class="flex flex-col items-center justify-center py-20">
          <div class="w-12 h-12 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
          <span class="text-slate-500 font-bold mt-4 animate-pulse">Loading order details...</span>
      </div>

      <div v-else>
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-black text-slate-800">✏️ Edit Order #{{ orderId }}</h2>
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
                  <input v-model="form.name" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-600 mb-1">Phone Number *</label>
                  <input v-model="form.phone" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div class="md:col-span-2">
                  <label class="block text-xs font-bold text-slate-600 mb-1">Full Address</label>
                  <textarea v-model="form.address" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-600 mb-1">District</label>
                  <input v-model="form.district" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-600 mb-1">Area / Thana</label>
                  <input v-model="form.area" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
              </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
              <h3 class="text-lg font-bold text-slate-800 border-b pb-3 mb-4">Order Settings</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-600 mb-1">Payment Method</label>
                  <select v-model="form.payment_method" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="COD">Cash on Delivery (COD)</option>
                    <option value="bKash">bKash</option>
                    <option value="Nagad">Nagad</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-600 mb-1">Order Status</label>
                  <select v-model="form.status" class="w-full px-3 py-2 border border-indigo-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-indigo-50 font-bold text-indigo-800">
                    <option v-for="st in availableStatuses" :key="st.name" :value="st.name">{{ st.name }}</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <div class="lg:col-span-5 space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
              <h3 class="text-lg font-bold text-slate-800 mb-3">Add More Products</h3>
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
              <h3 class="text-lg font-bold border-b border-slate-700 pb-3 mb-4">Edit Cart Items</h3>

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
                <div v-if="cart.length === 0" class="text-center text-red-400 font-bold text-sm py-4">Cart is empty! Order must have items.</div>
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

              <button @click="updateOrder" :disabled="saving" class="w-full mt-6 bg-indigo-500 hover:bg-indigo-600 disabled:opacity-50 text-white font-black py-3 rounded-lg shadow-lg transition-all">
                {{ saving ? 'Saving Changes...' : 'Update Order 💾' }}
              </button>
            </div>

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
