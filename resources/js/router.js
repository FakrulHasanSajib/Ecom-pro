import { createRouter, createWebHistory } from 'vue-router';

// ==========================================
// ১. অ্যাডমিন পেজ ইমপোর্টস
// ==========================================
import Dashboard from './Pages/Admin/Dashboard.vue';
import CategoryIndex from './Pages/Admin/Categories/Index.vue';
import ProductIndex from './Pages/Admin/Products/Index.vue';
import ProductCreate from './Pages/Admin/Products/Create.vue';
import AdminLogin from './Pages/Auth/Login.vue';
import AdminOrderIndex from './Pages/Admin/Orders/Index.vue';
import AdminOrderStatus from './Pages/Admin/OrderStatuses/Index.vue';
import AdminOrderCreate from './Pages/Admin/Orders/Create.vue';
import AdminOrderEdit from './Pages/Admin/Orders/Edit.vue';
import AdminOrderShow from './Pages/Admin/Orders/Show.vue';
import AdminSettings from './Pages/Admin/Settings/Index.vue'; //

// ==========================================
// ২. ফ্রন্টএন্ড/কাস্টমার পেজ ইমপোর্টস
// ==========================================
import Home from './Pages/Frontend/Home.vue';
import ProductDetails from './Pages/Frontend/ProductDetails.vue';
import Checkout from './Pages/Frontend/Checkout.vue';
import OrderSuccess from './Pages/Frontend/OrderSuccess.vue';
import Invoice from './Pages/Frontend/Invoice.vue';
import CustomerLogin from './Pages/Frontend/Login.vue';
import CustomerDashboard from './Pages/Frontend/Dashboard.vue';

const routes = [
    // ==========================================
    // --- পাবলিক এবং কাস্টমার রাউটস ---
    // ==========================================
    {
        path: '/',
        name: 'Home',
        component: Home
    },
    {
        path: '/product/:id',
        name: 'ProductDetails',
        component: ProductDetails
    },
    {
        path: '/checkout',
        name: 'Checkout',
        component: Checkout
    },
    {
        path: '/order-success',
        name: 'OrderSuccess',
        component: OrderSuccess
    },
    {
        path: '/invoice/:order_number',
        name: 'Invoice',
        component: Invoice
    },
    {
        path: '/login',
        name: 'CustomerLogin',
        component: CustomerLogin
    },
    {
        path: '/dashboard',
        name: 'CustomerDashboard',
        component: CustomerDashboard
    },

    // ==========================================
    // --- অ্যাডমিন রাউটস ---
    // ==========================================
    {
        path: '/admin/login',
        name: 'AdminLogin',
        component: AdminLogin
    },
    {
        path: '/admin/dashboard',
        name: 'admin.dashboard',
        component: Dashboard
    },
    {
        path: '/admin/categories',
        name: 'admin.categories.index',
        component: CategoryIndex
    },
    {
        path: '/admin/products',
        name: 'admin.products.index',
        component: ProductIndex
    },
    {
        path: '/admin/products/create',
        name: 'admin.products.create',
        component: ProductCreate
    },
    {
        path: '/admin/products/:id/edit',
        name: 'admin.products.edit',
        component: ProductCreate,
        props: true
    },
    {
        path: '/admin/settings',
        name: 'admin.settings',
        component: AdminSettings
    },
    {
        path: '/admin/orders',
        name: 'admin.orders.index',
        component: AdminOrderIndex
    },
    {
        path: '/admin/orders/create',
        name: 'admin.orders.create',
        component: AdminOrderCreate
    },
    {
        path: '/admin/orders/:id',
        name: 'admin.orders.show',
        component: AdminOrderShow,
        props: true
    },
    {
        path: '/admin/orders/:id/edit',
        name: 'admin.orders.edit',
        component: AdminOrderEdit,
        props: true
    },
    {
        path: '/admin/order-statuses',
        name: 'admin.order-statuses.index',
        component: AdminOrderStatus
    },

    // ==========================================
    // --- 404 Not Found (এটি সবসময় একদম শেষে থাকতে হবে) ---
    // ==========================================
    {
        path: '/:pathMatch(.*)*',
        name: 'NotFound',
        component: { template: '<div class="p-10 text-center text-red-500"><h1>404 - Page Not Found</h1></div>' }
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

export default router;
