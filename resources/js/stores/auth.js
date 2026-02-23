import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user')) || null,
        token: localStorage.getItem('token') || null,
    }),
    getters: {
        isAuthenticated: (state) => !!state.token,
    },
    actions: {
        setToken(token) {
            this.token = token;
            localStorage.setItem('token', token);
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        },
        setUser(user) {
            this.user = user;
            localStorage.setItem('user', JSON.stringify(user));
        },
        logout() {
            this.user = null;
            this.token = null;
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            delete axios.defaults.headers.common['Authorization'];
        }
    }
});
