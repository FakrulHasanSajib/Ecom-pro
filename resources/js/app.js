import './bootstrap';
import '../css/app.css';

import { createApp } from 'vue';
import router from './router'; // আমরা যে router.js বানিয়েছি সেটি ইমপোর্ট

// আপনি যদি কোনো গ্লোবাল লেআউট ব্যবহার করেন
import AdminLayout from '@/Layouts/AdminLayout.vue';

const app = createApp({
    // মেইন অ্যাপ মাউন্ট হওয়ার পর router-view অটোমেটিক কাজ করবে
});

// গ্লোবাল কম্পোনেন্ট রেজিস্টার (অপশনাল)
app.component('AdminLayout', AdminLayout);

// অ্যাপে রাউটার যুক্ত করা
app.use(router);

// অ্যাপ মাউন্ট করা (app.blade.php এর <div id="app"> এর সাথে কানেক্ট হবে)
app.mount('#app');
