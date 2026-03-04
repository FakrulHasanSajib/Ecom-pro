<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const route = useRoute();
const router = useRouter();

// ডাটা স্টেট
const rawOrders = ref([]);
const availableStatuses = ref([]);
const users = ref([]);
const loading = ref(true);
const selectedOrders = ref([]);
const selectAll = ref(false);

// 🔥 URL এর সাথে সরাসরি সংযুক্ত ফিল্টার
const statusFilter = computed({
  get: () => route.query.status || '',
  set: (val) => router.push({ query: { ...route.query, status: val || undefined } })
});

const searchQuery = ref(route.query.search || '');
const perPage = ref(30);
const dateFilter = ref('');
const orderSource = ref(route.query.source || '');
const assignFilter = ref(route.query.assign || '');

// API বেস URL
const API_URL = 'http://127.0.0.1:73/api/admin';
const ASSET_URL = 'http://127.0.0.1:73/storage'; // ইমেজের জন্য স্টোরেজ লিংক

// ফিল্টার করা অর্ডার
const filteredOrders = computed(() => {
  let result = rawOrders.value;

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    result = result.filter(order =>
      order.order_number?.toLowerCase().includes(q) ||
      order.name?.toLowerCase().includes(q) ||
      order.phone?.includes(q)
    );
  }

  if (orderSource.value) {
    result = result.filter(order => order.order_source === orderSource.value);
  }

  if (statusFilter.value) {
    const filterStatus = statusFilter.value.trim().toLowerCase();
    result = result.filter(order => {
      const dbStatus = (order.status || 'pending').trim().toLowerCase();
      if (filterStatus === 'canceled' || filterStatus === 'cancelled') {
         return dbStatus === 'canceled' || dbStatus === 'cancelled';
      }
      return dbStatus === filterStatus;
    });
  }

  if (assignFilter.value) {
    result = result.filter(order => order.user_id == assignFilter.value);
  }

  return result;
});

// অর্ডার লোড
const fetchOrders = async () => {
  loading.value = true;
  try {
    const token = localStorage.getItem('token');
    const headers = { Authorization: `Bearer ${token}` };

    const [ordersRes, statusesRes, usersRes] = await Promise.all([
      axios.get(`${API_URL}/orders`, { headers }).catch(() => ({ data: { data: [] } })),
      axios.get(`${API_URL}/order-statuses`, { headers }).catch(() => ({ data: { data: [] } })),
      axios.get(`${API_URL}/users`, { headers }).catch(() => ({ data: { data: [] } }))
    ]);

    rawOrders.value = ordersRes.data.data || [];

    let fetchedStatuses = statusesRes.data.data || [];
    if(fetchedStatuses.length === 0) {
        fetchedStatuses = [
            { id: 1, name: 'Pending' }, { id: 2, name: 'Processing' },
            { id: 3, name: 'Shipped' }, { id: 4, name: 'Delivered' }, { id: 5, name: 'Cancelled' }
        ];
    }
    availableStatuses.value = fetchedStatuses;
    users.value = usersRes.data.data || [];

    selectedOrders.value = [];
    selectAll.value = false;

  } catch (error) {
    console.error('Fetch error:', error);
    Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load orders' });
  } finally {
    loading.value = false;
  }
};

// সিঙ্গেল স্ট্যাটাস আপডেট
const updateStatus = async (orderId, newStatus) => {
  try {
    const token = localStorage.getItem('token');
    await axios.post(`${API_URL}/orders/${orderId}/status`,
      { status: newStatus },
      { headers: { Authorization: `Bearer ${token}` } }
    );

    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Status Updated!', showConfirmButton: false, timer: 1500 });
    fetchOrders();
  } catch (error) {
    Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to update status' });
  }
};

// অর্ডার ডিলিট ফাংশন
const deleteOrder = async (orderId) => {
  const result = await Swal.fire({
    title: 'Are you sure?', text: "You won't be able to revert this!", icon: 'warning',
    showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#3b82f6', confirmButtonText: 'Yes, delete it!'
  });

  if (result.isConfirmed) {
    try {
      const token = localStorage.getItem('token');
      await axios.delete(`${API_URL}/orders/${orderId}`, { headers: { Authorization: `Bearer ${token}` } });
      Swal.fire('Deleted!', 'Order has been deleted.', 'success');
      fetchOrders();
    } catch (error) {
      Swal.fire('Error', 'Failed to delete order', 'error');
    }
  }
};

// 🔥 ১. বাল্ক স্ট্যাটাস আপডেট
const bulkStatusUpdate = async () => {
  if (selectedOrders.value.length === 0) return Swal.fire('Warning', 'Please select at least one order', 'warning');

  const { value: statusName } = await Swal.fire({
    title: 'Select Status', input: 'select',
    inputOptions: availableStatuses.value.reduce((acc, status) => { acc[status.name] = status.name; return acc; }, {}),
    showCancelButton: true
  });

  if (statusName) {
    try {
      const token = localStorage.getItem('token');
      await axios.post(`${API_URL}/orders/bulk-status`, { order_ids: selectedOrders.value, status: statusName }, { headers: { Authorization: `Bearer ${token}` } });
      Swal.fire('Success', 'Status updated successfully', 'success');
      fetchOrders();
    } catch (error) {
      Swal.fire('Error', 'Failed to update status', 'error');
    }
  }
};

// 🔥 ২. বাল্ক অ্যাসাইন আপডেট
const bulkAssignUpdate = async () => {
  if (selectedOrders.value.length === 0) return Swal.fire('Warning', 'Please select at least one order', 'warning');

  const { value: userId } = await Swal.fire({
    title: 'Assign to User',
    input: 'select',
    inputOptions: users.value.reduce((acc, user) => { acc[user.id] = user.name; return acc; }, {}),
    showCancelButton: true,
    inputPlaceholder: 'Select an Assignee'
  });

  if (userId) {
    try {
      const token = localStorage.getItem('token');
      await axios.post(`${API_URL}/orders/bulk-assign`, { order_ids: selectedOrders.value, user_id: userId }, { headers: { Authorization: `Bearer ${token}` } });
      Swal.fire('Success', 'Orders assigned successfully', 'success');
      fetchOrders();
    } catch (error) {
      Swal.fire('Error', 'Failed to assign orders', 'error');
    }
  }
};

// 🔥 ৩. প্রিন্ট অর্ডার
const printOrders = async () => {
  if (selectedOrders.value.length === 0) return Swal.fire('Warning', 'Please select at least one order', 'warning');

  try {
    const token = localStorage.getItem('token');
    const response = await axios.post(`${API_URL}/orders/print`, { order_ids: selectedOrders.value }, { headers: { Authorization: `Bearer ${token}` } });

    const printWindow = window.open('', '_blank');
    printWindow.document.write(response.data.view);
    printWindow.document.close();
  } catch (error) {
    Swal.fire('Error', 'Failed to generate print view', 'error');
  }
};

// 🔥 ৪. এক্সপোর্ট CSV
const exportOrders = async () => {
  if (selectedOrders.value.length === 0) return Swal.fire('Warning', 'Please select at least one order', 'warning');

  try {
    const token = localStorage.getItem('token');
    const response = await axios.get(`${API_URL}/orders/export`, {
      headers: { Authorization: `Bearer ${token}` },
      params: { ids: selectedOrders.value.join(',') },
      responseType: 'blob'
    });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `orders_export_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

  } catch (error) {
    Swal.fire('Error', 'Failed to export CSV file', 'error');
  }
};

// সিলেক্ট অল টগল
const toggleSelectAll = () => {
  if (selectAll.value) selectedOrders.value = filteredOrders.value.map(order => order.id);
  else selectedOrders.value = [];
};

// ইন্ডিভিজুয়াল সিলেক্ট
const toggleSelect = (orderId) => {
  const index = selectedOrders.value.indexOf(orderId);
  if (index === -1) selectedOrders.value.push(orderId);
  else selectedOrders.value.splice(index, 1);
  selectAll.value = filteredOrders.value.length === selectedOrders.value.length;
};

// ফিল্টার অ্যাপ্লাই
const applyFilters = () => {
  router.push({
    query: { ...route.query, search: searchQuery.value || undefined, source: orderSource.value || undefined, assign: assignFilter.value || undefined }
  });
};

// রিসেট ফিল্টার
const resetFilters = () => {
  searchQuery.value = ''; dateFilter.value = ''; orderSource.value = ''; assignFilter.value = '';
  router.push({ query: {} });
  fetchOrders();
};

// Image Helper Function
const getProductImage = (imagePath) => {
    if (!imagePath) return 'https://placehold.co/100x100?text=No+Image';
    if (imagePath.startsWith('http')) return imagePath;
    return `${ASSET_URL}/${imagePath}`;
};

// স্ট্যাটাস ব্যাজের জন্য ক্লাস
const getStatusBadgeClass = (status) => {
  if (!status) return 'bg-slate-100 text-slate-700 border-slate-200';
  const s = status.toLowerCase();
  if (s === 'pending') return 'bg-amber-100 text-amber-700 border-amber-200';
  if (s === 'processing') return 'bg-blue-100 text-blue-700 border-blue-200';
  if (s === 'shipped') return 'bg-indigo-100 text-indigo-700 border-indigo-200';
  if (s === 'delivered') return 'bg-emerald-100 text-emerald-700 border-emerald-200';
  if (s === 'cancelled' || s === 'canceled') return 'bg-red-100 text-red-700 border-red-200';
  return 'bg-slate-100 text-slate-700 border-slate-200';
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-GB') + ' ' + date.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('bn-BD', { style: 'currency', currency: 'BDT' }).format(amount || 0);
};

onMounted(() => {
  fetchOrders();
});
</script>

<template>
  <AdminLayout>
    <div class="bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden">
      <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div>
            <h2 class="text-2xl md:text-3xl font-black text-white flex items-center gap-3">
              <span>📦</span>
              {{ route.query.status ? `${route.query.status} Orders` : 'All Orders' }}
            </h2>
            <p class="text-indigo-100 text-sm mt-1 font-medium">
              Total Orders: {{ filteredOrders.length }} | Selected: {{ selectedOrders.length }}
            </p>
          </div>
          <div class="flex flex-wrap gap-2">
            <button @click="fetchOrders" class="bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-lg font-semibold transition-all flex items-center gap-2 border border-white/20">
              <span>🔄</span> Refresh
            </button>
            <router-link to="/admin/orders/create">
              <button class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-lg font-semibold transition-all flex items-center gap-2 shadow-lg">
                <span>➕</span> Add New
              </button>
            </router-link>
          </div>
        </div>
      </div>

      <div class="bg-slate-50 border-b border-slate-200 p-4">
        <div class="flex flex-wrap gap-2">
          <button @click="bulkAssignUpdate" :disabled="selectedOrders.length === 0" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-semibold transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
            <span>👥</span> Assign
          </button>
          <button @click="bulkStatusUpdate" :disabled="selectedOrders.length === 0" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-semibold transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
            <span>🔄</span> Status
          </button>
          <button @click="printOrders" :disabled="selectedOrders.length === 0" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
            <span>🖨️</span> Print
          </button>
          <button @click="exportOrders" :disabled="selectedOrders.length === 0" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-semibold transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
            <span>📥</span> Export
          </button>
          <button @click="resetFilters" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-4 py-2 rounded-lg font-semibold transition-all flex items-center gap-2">
            <span>🗑️</span> Reset
          </button>
        </div>
      </div>

      <div class="p-4 border-b border-slate-200 bg-white">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-7 gap-3">
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🔍</span>
            <input type="text" v-model="searchQuery" @keyup.enter="applyFilters" placeholder="Search Name/Phone..."
              class="w-full pl-10 pr-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-medium text-sm">
          </div>
          <select v-model="perPage" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium text-sm">
            <option value="30">30 Per Page</option>
            <option value="50">50 Per Page</option>
            <option value="100">100 Per Page</option>
          </select>
          <select v-model="dateFilter" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium text-sm">
            <option value="">All Time</option>
            <option value="today">Today</option>
            <option value="this_week">This Week</option>
            <option value="this_month">This Month</option>
          </select>
          <select v-model="orderSource" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium text-sm">
            <option value="">All Sources</option>
            <option value="website">Website</option>
            <option value="facebook">Facebook</option>
          </select>
          <select v-model="statusFilter" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium text-sm">
            <option value="">All Status</option>
            <option v-for="status in availableStatuses" :key="status.id" :value="status.name">
              {{ status.name }}
            </option>
          </select>
          <select v-model="assignFilter" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium text-sm">
            <option value="">All Assignee</option>
            <option v-for="user in users" :key="user.id" :value="user.id">
              {{ user.name }}
            </option>
          </select>
          <button @click="applyFilters" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-semibold transition-all flex items-center justify-center gap-2">
            <span>🔍</span> Filter
          </button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[1200px]">
          <thead>
            <tr class="bg-slate-100 text-slate-600 text-xs font-black uppercase tracking-wider border-b border-slate-200">
              <th class="p-4 w-12">
                <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
              </th>
              <th class="p-4">Order Details</th>
              <th class="p-4">Customer Info</th>
              <th class="p-4 w-1/4">Products</th>
              <th class="p-4">Payment</th>
              <th class="p-4 text-center">Status</th>
              <th class="p-4 text-center">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="loading">
              <td colspan="7" class="p-12 text-center">
                <div class="flex flex-col items-center gap-3">
                  <div class="w-12 h-12 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
                  <span class="text-slate-500 font-medium">Loading orders...</span>
                </div>
              </td>
            </tr>

            <tr v-else-if="filteredOrders.length === 0">
              <td colspan="7" class="p-12 text-center">
                <div class="text-6xl mb-4">📭</div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">No Orders Found</h3>
                <p class="text-slate-500">Try adjusting your filters or create a new order</p>
              </td>
            </tr>

            <tr v-for="order in filteredOrders" :key="order.id" class="hover:bg-indigo-50/30 transition-colors group">
              <td class="p-4">
                <input type="checkbox" :checked="selectedOrders.includes(order.id)" @change="toggleSelect(order.id)" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
              </td>

              <td class="p-4">
                <div class="font-black text-indigo-600 text-sm">#{{ order.order_number || order.id }}</div>
                <div class="text-xs text-slate-400 font-medium mt-1">{{ formatDate(order.created_at) }}</div>
                <div class="mt-2 flex flex-wrap gap-1">
                  <span class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded-full text-[10px] font-bold uppercase">{{ order.order_source || 'WEB' }}</span>
                </div>
              </td>

              <td class="p-4">
                <div class="font-bold text-slate-800">{{ order.name || 'N/A' }}</div>
                <a :href="'tel:' + order.phone" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 mt-1 inline-block">
                  <i class="bi bi-telephone"></i> {{ order.phone || 'No Phone' }}
                </a>
                <div class="text-xs text-slate-500 mt-2 truncate max-w-[200px] hover:whitespace-normal hover:bg-white hover:shadow-lg hover:absolute hover:z-10 hover:p-2 hover:rounded border hover:border-gray-200" title="Full Address">
                    <i class="bi bi-geo-alt"></i> {{ order.address || 'N/A' }}, {{ order.area || '' }}
                </div>
              </td>

              <td class="p-4">
                <div class="space-y-2">
                  <div v-for="(item, idx) in (order.items || order.order_items || []).slice(0, 2)" :key="idx"
                       class="flex items-center gap-3 bg-white p-2 rounded-lg border border-slate-100 shadow-sm">
                    <img :src="getProductImage(item.product?.thumbnail || item.product_image)"
                         class="w-10 h-10 object-cover rounded bg-slate-50"
                         alt="Product">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-slate-800 truncate" :title="item.product_name || item.product?.name">
                            {{ item.product_name || item.product?.name || 'Unknown Product' }}
                        </p>
                        <p class="text-[10px] text-slate-500 mt-0.5">
                            Qty: <span class="font-bold text-indigo-600">{{ item.quantity || 1 }}</span>
                            <span v-if="item.price"> × ৳{{ item.price }}</span>
                        </p>
                    </div>
                  </div>
                  <div v-if="(order.items || order.order_items || []).length > 2" class="text-xs text-center text-indigo-600 font-bold bg-indigo-50 py-1 rounded-md cursor-pointer hover:bg-indigo-100">
                    + {{ (order.items || order.order_items).length - 2 }} more items
                  </div>
                  <div v-if="!(order.items || order.order_items) || (order.items || order.order_items).length === 0" class="text-xs text-rose-500 font-medium italic">
                    No items found
                  </div>
                </div>
              </td>

              <td class="p-4">
                <div class="font-black text-emerald-600 text-base">{{ formatCurrency(order.grand_total || order.total_amount) }}</div>
                <div class="mt-1">
                  <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-[10px] font-bold uppercase tracking-wider">
                    {{ order.payment_method || 'COD' }}
                  </span>
                </div>
              </td>

              <td class="p-4">
                <div class="flex justify-center">
                  <select
                    v-model="order.status"
                    @change="updateStatus(order.id, $event.target.value)"
                    :class="getStatusBadgeClass(order.status)"
                    class="appearance-none border text-xs font-bold px-3 py-1.5 rounded-full cursor-pointer focus:ring-2 focus:ring-indigo-400 outline-none transition-all shadow-sm min-w-[110px] text-center"
                  >
                    <option v-for="status in availableStatuses" :key="status.id" :value="status.name" class="bg-white text-slate-800 font-bold">
                      {{ status.name }}
                    </option>
                  </select>
                </div>
              </td>

            <td class="p-4">
                <div class="flex items-center justify-center gap-2">
                  <router-link :to="`/admin/orders/${order.id}`" class="w-8 h-8 bg-indigo-50 text-indigo-600 rounded flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-colors" title="View Details">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                  </router-link>

                  <router-link :to="`/admin/orders/${order.id}/edit`" class="w-8 h-8 bg-amber-50 text-amber-600 rounded flex items-center justify-center hover:bg-amber-500 hover:text-white transition-colors" title="Edit Order">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                      </svg>
                  </router-link>

                  <button @click="deleteOrder(order.id)" class="w-8 h-8 bg-rose-50 text-rose-600 rounded flex items-center justify-center hover:bg-rose-500 hover:text-white transition-colors" title="Delete Order">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="bg-slate-50 p-4 border-t border-slate-200">
        <div class="flex justify-between items-center">
          <span class="text-xs font-bold text-slate-600 uppercase tracking-widest">
            Showing {{ filteredOrders.length }} of {{ rawOrders.length }} orders
          </span>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
::-webkit-scrollbar { width: 8px; height: 8px; }
::-webkit-scrollbar-track { background: #f8fafc; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
table { border-collapse: separate; border-spacing: 0; }
th { position: sticky; top: 0; background: #f8fafc; z-index: 10; }
tr { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
