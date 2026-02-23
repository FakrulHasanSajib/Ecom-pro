<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useAuthStore } from '../../stores/auth';
import { SfButton, SfIconArrowBack } from '@storefront-ui/vue';

const router = useRouter();
const authStore = useAuthStore();

// Toggle between Login and Register
const isLoginMode = ref(true);
const isSubmitting = ref(false);

// Form Data
const form = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: ''
});

const backendUrl = 'http://127.0.0.1:73';

const showToast = (message, type = 'success') => {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: type,
        title: message,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
    });
};

const submitForm = async () => {
    isSubmitting.value = true;
    try {
        if (isLoginMode.value) {
            // 🔥 Login API Call
            const res = await axios.post(`${backendUrl}/api/login`, {
                email: form.value.email,
                password: form.value.password
            });

            authStore.setToken(res.data.token);
            authStore.setUser(res.data.user);
            showToast('Login Successful! 🎉');

            // 🔥 পার্মানেন্ট রিডাইরেক্ট ফিক্স (শ্যাডো আটকাতে)
            setTimeout(() => {
                window.location.href = '/dashboard';
            }, 1000);

        } else {
            // 🔥 Register API Call
            const res = await axios.post(`${backendUrl}/api/register`, {
                name: form.value.name,
                email: form.value.email,
                password: form.value.password,
                password_confirmation: form.value.password_confirmation
            });

            authStore.setToken(res.data.token);
            authStore.setUser(res.data.user);
            showToast('Account Created Successfully! 🎉');

            // 🔥 পার্মানেন্ট রিডাইরেক্ট ফিক্স (শ্যাডো আটকাতে)
            setTimeout(() => {
                window.location.href = '/dashboard';
            }, 1000);
        }
    } catch (error) {
        console.error("Auth Error:", error);
        const errorMsg = error.response?.data?.message || 'Something went wrong! Check your credentials.';
        showToast(errorMsg, 'error');
    } finally {
        isSubmitting.value = false;
    }
};

const goBack = () => router.push('/');
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans">

        <button @click="goBack" class="absolute top-8 left-8 flex items-center gap-2 text-slate-500 hover:text-indigo-600 font-bold transition-colors">
            <SfIconArrowBack size="sm" /> Back to Home
        </button>

        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center mb-6">
                <div class="flex items-center gap-2">
                    <span class="text-4xl">🛍️</span>
                    <span class="font-black text-3xl text-indigo-700 tracking-tight">E-Shop</span>
                </div>
            </div>
            <h2 class="mt-2 text-center text-3xl font-black text-slate-900 tracking-tight">
                {{ isLoginMode ? 'Welcome back' : 'Create an account' }}
            </h2>
            <p class="mt-2 text-center text-sm text-slate-600">
                {{ isLoginMode ? "Don't have an account?" : 'Already have an account?' }}
                <button @click="isLoginMode = !isLoginMode" class="font-bold text-indigo-600 hover:text-indigo-500 transition-colors focus:outline-none">
                    {{ isLoginMode ? 'Sign up for free' : 'Sign in here' }}
                </button>
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-10 px-4 shadow-2xl shadow-slate-200/50 sm:rounded-[2rem] sm:px-10 border border-slate-100">
                <form class="space-y-6" @submit.prevent="submitForm">

                    <div v-if="!isLoginMode" class="transition-all duration-300">
                        <label class="block text-sm font-bold text-slate-700 mb-2">Full Name</label>
                        <input v-model="form.name" type="text" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3.5 focus:ring-2 focus:ring-indigo-500 outline-none font-medium transition-all" placeholder="John Doe" />
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Email Address</label>
                        <input v-model="form.email" type="email" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3.5 focus:ring-2 focus:ring-indigo-500 outline-none font-medium transition-all" placeholder="you@example.com" />
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Password</label>
                        <input v-model="form.password" type="password" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3.5 focus:ring-2 focus:ring-indigo-500 outline-none font-medium transition-all" placeholder="••••••••" />
                    </div>

                    <div v-if="!isLoginMode" class="transition-all duration-300">
                        <label class="block text-sm font-bold text-slate-700 mb-2">Confirm Password</label>
                        <input v-model="form.password_confirmation" type="password" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3.5 focus:ring-2 focus:ring-indigo-500 outline-none font-medium transition-all" placeholder="••••••••" />
                    </div>

                    <div v-if="isLoginMode" class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember-me" type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-slate-300 rounded cursor-pointer">
                            <label for="remember-me" class="ml-2 block text-sm text-slate-700 cursor-pointer">Remember me</label>
                        </div>
                        <div class="text-sm">
                            <a href="#" class="font-bold text-indigo-600 hover:text-indigo-500">Forgot password?</a>
                        </div>
                    </div>

                    <SfButton type="submit" :disabled="isSubmitting" class="w-full bg-slate-900 hover:bg-indigo-600 text-white rounded-xl py-3.5 font-bold shadow-lg shadow-slate-200 transition-all active:scale-95 flex justify-center items-center gap-2">
                        <span v-if="isSubmitting" class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        {{ isLoginMode ? 'Sign In' : 'Create Account' }}
                    </SfButton>
                </form>

                <div class="mt-8">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-slate-200"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-slate-500 font-medium">Or continue with</span>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <button class="w-full inline-flex justify-center py-2.5 px-4 border border-slate-200 rounded-xl shadow-sm bg-white text-sm font-medium text-slate-500 hover:bg-slate-50 transition-colors">
                            <span class="sr-only">Sign in with Google</span>
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path fill-rule="evenodd" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path fill-rule="evenodd" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path fill-rule="evenodd" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                        </button>
                        <button class="w-full inline-flex justify-center py-2.5 px-4 border border-slate-200 rounded-xl shadow-sm bg-white text-sm font-medium text-slate-500 hover:bg-slate-50 transition-colors">
                            <span class="sr-only">Sign in with Facebook</span>
                            <svg class="w-5 h-5 text-[#1877F2]" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
