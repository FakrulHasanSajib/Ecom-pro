import { createRouter, createWebHistory } from 'vue-router';

// ১. কম্পোনেন্ট ইমপোর্ট
import Dashboard from './Pages/Admin/Dashboard.vue';
import CategoryIndex from './Pages/Admin/Categories/Index.vue';
import ProductIndex from './Pages/Admin/Products/Index.vue';
import ProductCreate from './Pages/Admin/Products/Create.vue';

// ২. আসল লগইন পেজ ইমপোর্ট করুন (আগের টেম্পোরারি লাইন মুছে ফেলুন)
import Login from './Pages/Auth/Login.vue';
import Home from './Pages/Frontend/Home.vue';
import ProductDetails from './Pages/Frontend/ProductDetails.vue'; // পাথ ঠিক করা হয়েছে

const routes = [
    // --- Public Routes ---
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
        path: '/login',
        name: 'login',
        component: Login
    },

    // --- Admin Routes ---
    {
        path: '/admin/dashboard',
        name: 'admin.dashboard',
        component: Dashboard
    },

    // --- Category Routes ---
    {
        path: '/admin/categories',
        name: 'admin.categories.index',
        component: CategoryIndex
    },

    // --- Product Routes ---
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

    // --- 404 Not Found (এটি সবসময় একদম শেষে থাকতে হয়) ---
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
