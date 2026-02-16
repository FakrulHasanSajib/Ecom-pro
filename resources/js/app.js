import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Swal from 'sweetalert2';
import { QuillEditor } from '@vueup/vue-quill'; // এই লাইনটি নিশ্চিত করুন
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import axios from 'axios';


window.Swal = Swal;
window.axios = axios;
window.axios.defaults.withCredentials = true;
// এই লাইনটি সবকিছুর আগে থাকা জরুরি
window.axios.defaults.withCredentials = true;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'; // কুকি সেশন ঠিক রাখার জন্য

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .component('QuillEditor', QuillEditor) // এখানে একবারই রেজিস্টার করুন
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
