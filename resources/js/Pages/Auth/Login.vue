<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';

const router = useRouter();

// ফর্ম ডাটা
const form = ref({
    email: '',
    password: '',
    remember: false
});

const loading = ref(false);
const errors = ref({});

// লগইন ফাংশন
const login = async () => {
    loading.value = true;
    errors.value = {};

    try {
        // ১. API লগইন রিকোয়েস্ট (Token Based)
        const response = await axios.post('/api/login', form.value);

        // ২. সফল হলে টোকেন সেভ করা
        if (response.status === 200 && response.data.token) {

            // টোকেন লোকাল স্টোরেজে রাখা (যাতে রিফ্রেশ দিলেও লগইন থাকে)
            const token = response.data.token;
            localStorage.setItem('token', token);

            // Axios এর ডিফল্ট হেডারে টোকেন সেট করা
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

            // সাকসেস মেসেজ
            Swal.fire({
                icon: 'success',
                title: 'Welcome Back!',
                text: 'Logged in successfully.',
                timer: 1500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });

            // ড্যাশবোর্ডে রিডাইরেক্ট
            router.push('/admin/dashboard');
        }

    } catch (error) {
        // ৩. এরর হ্যান্ডলিং
        if (error.response) {
            if (error.response.status === 422) {
                // ভ্যালিডেশন এরর (Email/Password format issue)
                errors.value = error.response.data.errors;
            } else if (error.response.status === 401) {
                // ক্রেডেনশিয়াল ভুল (Wrong Password)
                Swal.fire({
                    icon: 'error',
                    title: 'Login Failed',
                    text: 'Invalid email or password.',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            } else {
                // সার্ভার এরর (500)
                console.error("Login Error:", error);
                Swal.fire({
                    icon: 'error',
                    title: 'System Error',
                    text: error.response.data.message || 'Something went wrong. Please check your connection.',
                });
            }
        } else {
            // নেটওয়ার্ক এরর
            Swal.fire({
                icon: 'error',
                title: 'Connection Error',
                text: 'Could not connect to the server.',
            });
        }
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden transform transition-all hover:scale-[1.01] duration-300 border border-gray-100">

            <div class="bg-indigo-600 p-8 text-center relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-full bg-indigo-700 opacity-20 transform -skew-y-6 origin-top-left"></div>

                <div class="relative z-10">
                    <div class="mx-auto h-16 w-16 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center mb-4 shadow-lg ring-2 ring-white/50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-white tracking-tight">Admin Portal</h2>
                    <p class="text-indigo-100 mt-2 text-sm font-medium">Secure login to your dashboard</p>
                </div>
            </div>

            <div class="p-8">
                <form @submit.prevent="login" class="space-y-6">

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>
                            <input
                                v-model="form.email"
                                type="email"
                                class="pl-10 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition sm:text-sm py-3"
                                :class="{'border-red-500 focus:border-red-500 focus:ring-red-200': errors.email}"
                                placeholder="admin@example.com"
                                required
                            >
                        </div>
                        <p v-if="errors.email" class="text-red-500 text-xs mt-1 font-medium flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            {{ errors.email[0] }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input
                                v-model="form.password"
                                type="password"
                                class="pl-10 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition sm:text-sm py-3"
                                :class="{'border-red-500 focus:border-red-500 focus:ring-red-200': errors.password}"
                                placeholder="••••••••"
                                required
                            >
                        </div>
                        <p v-if="errors.password" class="text-red-500 text-xs mt-1 font-medium flex items-center">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            {{ errors.password[0] }}
                        </p>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input
                                v-model="form.remember"
                                id="remember-me"
                                type="checkbox"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded cursor-pointer"
                            >
                            <label for="remember-me" class="ml-2 block text-sm text-gray-700 cursor-pointer select-none">
                                Remember me
                            </label>
                        </div>

                        <div class="text-sm">
                            <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500 transition">
                                Forgot password?
                            </a>
                        </div>
                    </div>

                    <div>
                        <button
                            type="submit"
                            :disabled="loading"
                            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-bold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200 shadow-md shadow-indigo-200 disabled:opacity-70 disabled:cursor-not-allowed overflow-hidden"
                        >
                            <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-indigo-700">
                                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>

                            <span class="flex items-center" :class="{'opacity-0': loading}">
                                <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                    <svg class="h-5 w-5 text-indigo-400 group-hover:text-indigo-300 transition" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                Sign in
                            </span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-gray-50 px-8 py-4 border-t border-gray-100 text-center">
                <p class="text-xs text-gray-500">
                    &copy; {{ new Date().getFullYear() }} All rights reserved.
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* অতিরিক্ত স্টাইল প্রয়োজন নেই, টেইলউইন্ড ব্যবহার করা হয়েছে */
</style>
