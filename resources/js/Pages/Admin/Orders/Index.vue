<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const route = useRoute();
const router = useRouter();

// ডাটা স্টেট
const rawOrders = ref([]);
const availableStatuses = ref([]);
const users = ref([]);
const loading = ref(true);
const selectedOrders = ref([]);
const selectAll = ref(false);

// ফিল্টার স্টেট
const searchQuery = ref('');
const perPage = ref(30);
const dateFilter = ref('');
const orderSource = ref('');
const statusFilter = ref('');
const assignFilter = ref('');

// API বেস URL
const API_URL = 'http://127.0.0.1:73/api/admin';

// ফিল্টার করা অর্ডার
const filteredOrders = computed(() => {
  let result = rawOrders.value;

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    result = result.filter(order =>
      order.order_number?.toLowerCase().includes(q) ||
      order.shipping?.name?.toLowerCase().includes(q) ||
      order.shipping?.phone?.includes(q)
    );
  }

  if (orderSource.value) {
    result = result.filter(order => order.order_source === orderSource.value);
  }

  if (statusFilter.value) {
    result = result.filter(order => order.order_status_id == statusFilter.value);
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
    const params = {
      per_page: perPage.value,
      date_filter: dateFilter.value,
      search: searchQuery.value,
      order_source: orderSource.value,
      order_status: statusFilter.value,
      user_id: assignFilter.value
    };

    const [ordersRes, statusesRes, usersRes] = await Promise.all([
      axios.get(`${API_URL}/orders`, {
        headers: { Authorization: `Bearer ${token}` },
        params
      }),
      axios.get(`${API_URL}/order-statuses`, {
        headers: { Authorization: `Bearer ${token}` }
      }),
      axios.get(`${API_URL}/users`, {
        headers: { Authorization: `Bearer ${token}` }
      })
    ]);

    rawOrders.value = ordersRes.data.data || [];
    availableStatuses.value = statusesRes.data.data || [];
    users.value = usersRes.data.data || [];

    selectedOrders.value = [];
    selectAll.value = false;

  } catch (error) {
    console.error('Fetch error:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Failed to load orders',
      background: '#1e1e2f',
      color: '#fff'
    });
  } finally {
    loading.value = false;
  }
};

// সিঙ্গেল স্ট্যাটাস আপডেট
const updateStatus = async (orderId, statusId) => {
  try {
    const token = localStorage.getItem('token');
    await axios.post(`${API_URL}/orders/${orderId}/status`,
      { status_id: statusId },
      { headers: { Authorization: `Bearer ${token}` } }
    );

    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Status Updated!',
      showConfirmButton: false,
      timer: 1500,
      background: '#1e1e2f',
      color: '#fff'
    });

    fetchOrders();
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Failed to update status',
      background: '#1e1e2f',
      color: '#fff'
    });
  }
};

// বাল্ক স্ট্যাটাস আপডেট
const bulkStatusUpdate = async () => {
  if (selectedOrders.value.length === 0) {
    Swal.fire('Warning', 'Please select at least one order', 'warning');
    return;
  }

  const { value: statusId } = await Swal.fire({
    title: 'Select Status',
    input: 'select',
    inputOptions: availableStatuses.value.reduce((acc, status) => {
      acc[status.id] = status.name;
      return acc;
    }, {}),
    showCancelButton: true,
    background: '#1e1e2f',
    color: '#fff'
  });

  if (statusId) {
    try {
      const token = localStorage.getItem('token');
      await axios.post(`${API_URL}/orders/bulk-status`, {
        order_ids: selectedOrders.value,
        status_id: statusId
      }, {
        headers: { Authorization: `Bearer ${token}` }
      });

      Swal.fire('Success', 'Status updated successfully', 'success');
      fetchOrders();
    } catch (error) {
      Swal.fire('Error', 'Failed to update status', 'error');
    }
  }
};

// বাল্ক অ্যাসাইন আপডেট
const bulkAssignUpdate = async () => {
  if (selectedOrders.value.length === 0) {
    Swal.fire('Warning', 'Please select at least one order', 'warning');
    return;
  }

  const { value: userId } = await Swal.fire({
    title: 'Select User',
    input: 'select',
    inputOptions: users.value.reduce((acc, user) => {
      acc[user.id] = user.name;
      return acc;
    }, {}),
    showCancelButton: true,
    background: '#1e1e2f',
    color: '#fff'
  });

  if (userId) {
    try {
      const token = localStorage.getItem('token');
      await axios.post(`${API_URL}/orders/bulk-assign`, {
        order_ids: selectedOrders.value,
        user_id: userId
      }, {
        headers: { Authorization: `Bearer ${token}` }
      });

      Swal.fire('Success', 'Orders assigned successfully', 'success');
      fetchOrders();
    } catch (error) {
      Swal.fire('Error', 'Failed to assign orders', 'error');
    }
  }
};

// প্রিন্ট অর্ডার
const printOrders = async () => {
  if (selectedOrders.value.length === 0) {
    Swal.fire('Warning', 'Please select at least one order', 'warning');
    return;
  }

  try {
    const token = localStorage.getItem('token');
    const response = await axios.post(`${API_URL}/orders/print`, {
      order_ids: selectedOrders.value
    }, {
      headers: { Authorization: `Bearer ${token}` }
    });

    const printWindow = window.open('', '_blank');
    printWindow.document.write(response.data.view);
    printWindow.document.close();
    printWindow.print();
  } catch (error) {
    Swal.fire('Error', 'Failed to print orders', 'error');
  }
};

// এক্সপোর্ট CSV
const exportOrders = async () => {
  if (selectedOrders.value.length === 0) {
    Swal.fire('Warning', 'Please select at least one order', 'warning');
    return;
  }
  window.location.href = `${API_URL}/orders/export?ids=${selectedOrders.value.join(',')}&token=${localStorage.getItem('token')}`;
};

// সিলেক্ট অল টগল
const toggleSelectAll = () => {
  if (selectAll.value) {
    selectedOrders.value = filteredOrders.value.map(order => order.id);
  } else {
    selectedOrders.value = [];
  }
};

// ইন্ডিভিজুয়াল সিলেক্ট
const toggleSelect = (orderId) => {
  const index = selectedOrders.value.indexOf(orderId);
  if (index === -1) {
    selectedOrders.value.push(orderId);
  } else {
    selectedOrders.value.splice(index, 1);
  }
  selectAll.value = filteredOrders.value.length === selectedOrders.value.length;
};

// ফিল্টার অ্যাপ্লাই
const applyFilters = () => {
  router.push({
    query: {
      status: statusFilter.value || undefined,
      source: orderSource.value || undefined,
      assign: assignFilter.value || undefined
    }
  });
  fetchOrders();
};

// রিসেট ফিল্টার
const resetFilters = () => {
  searchQuery.value = '';
  dateFilter.value = '';
  orderSource.value = '';
  statusFilter.value = '';
  assignFilter.value = '';
  perPage.value = 30;
  router.push({ query: {} });
  fetchOrders();
};

// স্ট্যাটাস ব্যাজের জন্য ক্লাস (কালার ক্লাস ব্যবহার)
const getStatusBadgeClass = (status) => {
  if (!status) return 'bg-slate-100 text-slate-700 border-slate-200';
  return status.color_class || 'bg-slate-100 text-slate-700 border-slate-200';
};

// ফরম্যাট ডেট
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-GB', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  }) + ' ' + date.toLocaleTimeString('en-GB', {
    hour: '2-digit',
    minute: '2-digit'
  });
};

// ফরম্যাট কারেন্সি
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('bn-BD', { style: 'currency', currency: 'BDT' }).format(amount || 0);
};

// প্রোডাক্টের নাম সংক্ষেপ
const getProductInitials = (name) => {
  return name ? name.charAt(0).toUpperCase() : 'P';
};

onMounted(() => {
  fetchOrders();
  if (route.query.status) statusFilter.value = route.query.status;
  if (route.query.source) orderSource.value = route.query.source;
  if (route.query.assign) assignFilter.value = route.query.assign;
});

watch([perPage, dateFilter], () => {
  fetchOrders();
});
</script>

<template>
  <AdminLayout>
    <div class="bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden">
      <!-- Header Section -->
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
            <button @click="fetchOrders"
              class="bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-lg font-semibold transition-all flex items-center gap-2 border border-white/20">
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

      <!-- Action Buttons Bar -->
      <div class="bg-slate-50 border-b border-slate-200 p-4">
        <div class="flex flex-wrap gap-2">
          <button @click="bulkAssignUpdate"
            :disabled="selectedOrders.length === 0"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-semibold transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
            <span>👥</span> Assign
          </button>
          <button @click="bulkStatusUpdate"
            :disabled="selectedOrders.length === 0"
            class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-semibold transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
            <span>🔄</span> Status
          </button>
          <button @click="printOrders"
            :disabled="selectedOrders.length === 0"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
            <span>🖨️</span> Print
          </button>
          <button @click="exportOrders"
            :disabled="selectedOrders.length === 0"
            class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-semibold transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
            <span>📥</span> Export
          </button>
          <button @click="resetFilters"
            class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-4 py-2 rounded-lg font-semibold transition-all flex items-center gap-2">
            <span>🗑️</span> Reset
          </button>
        </div>
      </div>

      <!-- Filters Section -->
      <div class="p-4 border-b border-slate-200 bg-white">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-7 gap-3">
          <!-- Search -->
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🔍</span>
            <input type="text" v-model="searchQuery" @keyup.enter="fetchOrders"
              placeholder="Search orders..."
              class="w-full pl-10 pr-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
          </div>
          <!-- Per Page -->
          <select v-model="perPage" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="30">30 Per Page</option>
            <option value="50">50 Per Page</option>
            <option value="100">100 Per Page</option>
            <option value="150">150 Per Page</option>
          </select>
          <!-- Date Filter -->
          <select v-model="dateFilter" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">Select Date</option>
            <option value="today">Today</option>
            <option value="this_week">This Week</option>
            <option value="this_month">This Month</option>
          </select>
          <!-- Order Source -->
          <select v-model="orderSource" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Sources</option>
            <option value="website">Website</option>
            <option value="landing page">Landing Page</option>
            <option value="phone">Phone</option>
            <option value="whatsapp">WhatsApp</option>
            <option value="call center">Call Center</option>
          </select>
          <!-- Status Filter -->
          <select v-model="statusFilter" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Status</option>
            <option v-for="status in availableStatuses" :key="status.id" :value="status.id">
              {{ status.name }}
            </option>
          </select>
          <!-- Assign Filter -->
          <select v-model="assignFilter" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Assignee</option>
            <option v-for="user in users" :key="user.id" :value="user.id">
              {{ user.name }}
            </option>
          </select>
          <!-- Filter Button -->
          <button @click="applyFilters"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-semibold transition-all flex items-center justify-center gap-2">
            <span>🔍</span> Filter
          </button>
        </div>
      </div>

      <!-- Orders Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[1200px]">
          <thead>
            <tr class="bg-slate-100 text-slate-600 text-xs font-black uppercase tracking-wider border-b border-slate-200">
              <th class="p-4 w-12">
                <input type="checkbox" v-model="selectAll" @change="toggleSelectAll"
                  class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
              </th>
              <th class="p-4">Order Details</th>
              <th class="p-4">Customer Info</th>
              <th class="p-4">Shipping Address</th>
              <th class="p-4">Products</th>
              <th class="p-4">Payment</th>
              <th class="p-4">Assign / Courier</th>
              <th class="p-4 text-center">Status</th>
              <th class="p-4 text-center">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <!-- Loading State -->
            <tr v-if="loading">
              <td colspan="9" class="p-12 text-center">
                <div class="flex flex-col items-center gap-3">
                  <div class="w-12 h-12 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
                  <span class="text-slate-500 font-medium">Loading orders...</span>
                </div>
              </td>
            </tr>

            <!-- Empty State -->
            <tr v-else-if="filteredOrders.length === 0">
              <td colspan="9" class="p-12 text-center">
                <div class="text-6xl mb-4">📭</div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">No Orders Found</h3>
                <p class="text-slate-500">Try adjusting your filters or create a new order</p>
              </td>
            </tr>

            <!-- Order Rows -->
            <tr v-for="order in filteredOrders" :key="order.id"
              class="hover:bg-indigo-50/30 transition-colors group">
              <td class="p-4">
                <input type="checkbox" :checked="selectedOrders.includes(order.id)"
                  @change="toggleSelect(order.id)"
                  class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
              </td>

              <!-- Order Details -->
              <td class="p-4">
                <div class="font-black text-indigo-600 text-sm">#{{ order.order_number || order.id }}</div>
                <div class="text-xs text-slate-400 font-medium mt-1">
                  {{ formatDate(order.created_at) }}
                </div>
                <div class="mt-2 flex flex-wrap gap-1">
                  <span v-if="order.order_source"
                    class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded-full text-[10px] font-bold">
                    {{ order.order_source }}
                  </span>
                  <span v-if="order.utm_source"
                    class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-[10px] font-bold">
                    {{ order.utm_source }}
                  </span>
                </div>
              </td>

              <!-- Customer Info -->
              <td class="p-4">
                <div class="font-bold text-slate-800">{{ order.shipping?.name || 'N/A' }}</div>
                <div class="flex items-center gap-1 mt-1">
                  <a :href="'tel:' + order.shipping?.phone"
                    class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                    {{ order.shipping?.phone || 'No Phone' }}
                  </a>
                </div>
                <div class="text-xs text-slate-500 mt-1">{{ order.user?.name || 'Guest' }}</div>
              </td>

              <!-- Shipping Address -->
              <td class="p-4">
                <div class="text-sm font-medium text-slate-700">{{ order.shipping?.address || 'N/A' }}</div>
                <div class="text-xs text-slate-500 mt-1">
                  {{ order.shipping?.area || '' }}
                  <span v-if="order.shipping?.district">, {{ order.shipping.district }}</span>
                </div>
                <div class="text-xs text-slate-500">{{ order.shipping?.thana || '' }}</div>
              </td>

              <!-- Products -->
              <td class="p-4">
                <div class="flex -space-x-2 mb-2">
                  <div v-for="(item, idx) in order.order_items?.slice(0, 3)" :key="idx"
                    class="w-8 h-8 rounded-full bg-indigo-100 border-2 border-white flex items-center justify-center text-xs font-bold text-indigo-600"
                    :title="item.product_name">
                    {{ getProductInitials(item.product_name) }}
                  </div>
                  <div v-if="order.order_items?.length > 3"
                    class="w-8 h-8 rounded-full bg-slate-200 border-2 border-white flex items-center justify-center text-xs font-bold text-slate-600">
                    +{{ order.order_items.length - 3 }}
                  </div>
                </div>
                <div class="text-xs text-slate-600">
                  <span v-for="(item, idx) in order.order_items?.slice(0,2)" :key="idx" class="block">
                    {{ item.product_name }} (x{{ item.quantity }})
                  </span>
                  <span v-if="order.order_items?.length > 2" class="text-slate-400">...</span>
                </div>
              </td>

              <!-- Payment -->
              <td class="p-4">
                <div class="font-black text-emerald-600 text-lg">{{ formatCurrency(order.grand_total) }}</div>
                <div class="text-xs text-slate-500">
                  Sub: {{ formatCurrency(order.sub_total) }} + Shipping: {{ formatCurrency(order.shipping_charge) }}
                </div>
                <div class="flex items-center gap-1 mt-1">
                  <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">
                    {{ order.payment_method || 'COD' }}
                  </span>
                  <span :class="order.payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'"
                    class="px-2 py-1 rounded-full text-[10px] font-bold">
                    {{ order.payment_status }}
                  </span>
                </div>
              </td>

              <!-- Assign / Courier -->
              <td class="p-4">
                <div class="text-xs font-bold text-indigo-600">{{ order.user?.name || 'Unassigned' }}</div>
                <div class="text-[10px] font-medium text-slate-500 mt-1 uppercase">{{ order.courier || 'N/A' }}</div>
                <div v-if="order.tracking_id" class="mt-1">
                  <a :href="'https://steadfast.com.bd/t/' + order.tracking_id"
                    target="_blank"
                    class="text-[10px] bg-blue-100 text-blue-700 px-2 py-1 rounded-full font-bold hover:bg-blue-200">
                    Track
                  </a>
                </div>
              </td>

              <!-- Status -->
              <td class="p-4">
                <div class="flex justify-center">
                  <select
                    :value="order.order_status_id"
                    @change="updateStatus(order.id, $event.target.value)"
                    :class="getStatusBadgeClass(order.status)"
                    class="appearance-none border text-xs font-bold px-3 py-1.5 rounded-full cursor-pointer focus:ring-2 focus:ring-indigo-400 outline-none transition-all shadow-sm min-w-[110px] text-center"
                  >
                    <option v-for="status in availableStatuses"
                      :key="status.id"
                      :value="status.id"
                      :selected="order.order_status_id == status.id"
                      class="bg-white text-slate-800 font-bold">
                      {{ status.name }}
                    </option>
                  </select>
                </div>
              </td>

              <!-- Actions -->
              <td class="p-4">
                <div class="flex items-center justify-center gap-2">
                  <router-link :to="`/admin/orders/${order.id}`"
                    class="w-8 h-8 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center hover:bg-indigo-200 transition-colors"
                    title="View">
                    👁️
                  </router-link>
                  <router-link :to="`/admin/orders/${order.id}/edit`"
                    class="w-8 h-8 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center hover:bg-amber-200 transition-colors"
                    title="Edit">
                    ✏️
                  </router-link>
                  <button @click="updateStatus(order.id, 'cancelled')"
                    class="w-8 h-8 bg-red-100 text-red-600 rounded-lg flex items-center justify-center hover:bg-red-200 transition-colors"
                    title="Cancel">
                    ❌
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="bg-slate-50 p-4 border-t border-slate-200">
        <div class="flex justify-between items-center">
          <span class="text-xs font-medium text-slate-600">
            Showing {{ filteredOrders.length }} of {{ rawOrders.length }} orders
          </span>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
/* Custom Scrollbar */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}
::-webkit-scrollbar-track {
  background: #f1f5f9;
}
::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Table Styles */
table {
  border-collapse: separate;
  border-spacing: 0;
}
th {
  position: sticky;
  top: 0;
  background: #f8fafc;
  z-index: 10;
}
tr {
  animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
