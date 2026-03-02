<template>
    <div class="min-h-screen bg-gray-50 font-sans overflow-x-hidden">
        <!-- Loading Overlay -->
        <div v-if="initialLoading" class="fixed inset-0 bg-gradient-to-br from-indigo-950 to-purple-950 z-[100] flex items-center justify-center">
            <div class="text-center">
                <div class="relative mb-8">
                    <div class="w-32 h-32 border-4 border-indigo-500/30 border-t-indigo-500 rounded-full animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-4xl animate-bounce">🛍️</span>
                    </div>
                </div>
                <h2 class="text-3xl font-black text-white mb-3">{{ getSetting('general', 'site_name', 'E-Shop') }}</h2>
                <p class="text-indigo-300/80">Loading amazing products for you...</p>
            </div>
        </div>

        <!-- Header -->
        <header id="navbar_top" class="bg-white shadow-sm sticky top-0 z-50 w-full">
            <!-- Mobile Header -->
            <div class="lg:hidden">
                <div class="mobile-header sticky">
                    <div class="mobile-logo flex items-center justify-between p-4 border-b">
                        <button @click="toggleMobileMenu" class="menu-bar text-2xl">
                            <i class="bi" :class="isMenuOpen ? 'bi-x-lg' : 'bi-list'"></i>
                        </button>
                        <router-link to="/" class="menu-logo">
                            <img v-if="getSetting('general', 'white_logo')"
                                 :src="getImageUrl(getSetting('general', 'white_logo'))"
                                 class="h-10 w-auto" alt="Logo">
                            <span v-else class="font-bold text-xl">{{ getSetting('general', 'name', 'E-Shop') }}</span>
                        </router-link>
                        <router-link to="/checkout" class="menu-bag relative text-2xl">
                            <i class="bi bi-cart3"></i>
                            <span v-if="cartCount" class="mobilecart-qty absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                                {{ cartCount }}
                            </span>
                        </router-link>
                    </div>

                    <!-- Mobile Search -->
                    <div class="mobile-search p-4 bg-gray-100">
                        <form @submit.prevent class="flex">
                            <input v-model="searchKeyword" type="text" placeholder="Search Product..."
                                   class="msearch_keyword flex-1 px-4 py-2 rounded-l-full border focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <button type="submit" class="bg-indigo-600 text-white px-6 rounded-r-full">
                                <i class="bi bi-search"></i>
                            </button>
                        </form>
                        <div class="search_result mt-2 absolute bg-white shadow-lg rounded-lg w-full max-w-md z-50"></div>
                    </div>
                </div>
            </div>

            <!-- Desktop Header -->
            <div class="hidden lg:block main-header">
                <!-- Top Header -->
                <div class="logo-area">
                    <div class="container mx-auto px-4 py-4">
                        <div class="logo-header flex items-center justify-between">
                            <!-- Logo -->
                            <div class="main-logo">
                                <router-link to="/">
                                    <img v-if="getSetting('general', 'white_logo')"
                                         :src="getImageUrl(getSetting('general', 'white_logo'))"
                                         class="h-16 w-auto" alt="Logo">
                                </router-link>
                            </div>

                            <!-- Search -->
                            <div class="main-search flex-1 max-w-xl mx-10">
                                <form @submit.prevent class="flex relative">
                                    <input v-model="searchKeyword" type="text" placeholder="Search Product..."
                                           class="search_keyword flex-1 px-4 py-3 rounded-l-full border focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <button type="submit" class="bg-indigo-600 text-white px-8 rounded-r-full hover:bg-indigo-700 transition">
                                        <i class="bi bi-search"></i>
                                    </button>
                                    <div class="search_result absolute top-full left-0 right-0 mt-2 bg-white shadow-lg rounded-lg z-50 max-h-96 overflow-y-auto"></div>
                                </form>
                            </div>

                            <!-- Header Actions -->
                            <div class="header-list-items">
                                <ul class="flex items-center gap-6">
                                    <!-- Track Order -->
                                    <li class="track_btn">
                                        <router-link to="/track-order" class="text-gray-700 hover:text-indigo-600 flex items-center gap-2">
                                            <i class="bi bi-truck"></i>
                                            <span>Track Order</span>
                                        </router-link>
                                    </li>

                                    <!-- User Account -->
                                    <li class="for_order">
                                        <template v-if="!authStore.isAuthenticated">
                                            <router-link to="/login" class="text-gray-700 hover:text-indigo-600 flex items-center gap-2">
                                                <i class="bi bi-person-circle"></i>
                                                <span>Login / Sign Up</span>
                                            </router-link>
                                        </template>
                                        <template v-else>
                                            <router-link to="/account" class="text-gray-700 hover:text-indigo-600 flex items-center gap-2">
                                                <i class="bi bi-person-circle"></i>
                                                <span>{{ authStore.user?.name?.substring(0, 14) || 'Account' }}</span>
                                            </router-link>
                                        </template>
                                    </li>

                                    <!-- Cart with Dropdown -->
                                    <li class="cart-dialog relative group" id="cart-qty">
                                        <router-link to="/checkout" class="text-gray-700 hover:text-indigo-600">
                                            <p class="margin-shopping relative">
                                                <i class="bi bi-cart3 text-2xl"></i>
                                                <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                                                    {{ cartCount }}
                                                </span>
                                            </p>
                                        </router-link>

                                        <!-- Cart Summary Dropdown -->
                                        <div class="cshort-summary absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl border hidden group-hover:block z-50">
                                            <ul class="p-4 max-h-96 overflow-y-auto">
                                                <template v-if="cartStore.items.length">
                                                    <li v-for="item in cartStore.items" :key="item.id" class="flex gap-3 py-2 border-b">
                                                        <router-link :to="`/product/${item.id}`">
                                                            <img :src="getImageUrl(item.thumbnail)" class="w-16 h-16 object-cover rounded">
                                                        </router-link>
                                                        <div class="flex-1">
                                                            <router-link :to="`/product/${item.id}`" class="font-medium text-sm hover:text-indigo-600">
                                                                {{ item.name.substring(0, 30) }}
                                                            </router-link>
                                                            <p class="text-sm">Qty: {{ item.quantity }}</p>
                                                            <p class="text-indigo-600 font-bold">৳{{ item.price }}</p>
                                                        </div>
                                                        <button @click.stop="removeFromCart(item.id)" class="text-gray-400 hover:text-red-500">
                                                            <i class="bi bi-x-lg"></i>
                                                        </button>
                                                    </li>
                                                    <li class="mt-3 pt-3 border-t">
                                                        <p class="font-bold text-right">সর্বমোট: ৳{{ cartSubtotal }}</p>
                                                        <router-link to="/checkout" class="go_cart block text-center bg-indigo-600 text-white py-2 rounded-lg mt-3 hover:bg-indigo-700">
                                                            অর্ডার করুন
                                                        </router-link>
                                                    </li>
                                                </template>
                                                <li v-else class="text-center py-4 text-gray-500">
                                                    Your cart is empty
                                                </li>
                                            </ul>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Bar -->
                <nav class="navbar navbar-expand-lg" :style="{ backgroundColor: getSetting('general', 'headercolor', '#4f46e5') }">
                    <div class="container mx-auto px-4">
                        <div class="flex items-center w-full">
                            <!-- Categories Dropdown -->
                            <div class="dropdown relative">
                                <button class="btn dropdown-toggle text-white px-4 py-3 flex items-center gap-2 hover:bg-white/10 rounded-lg"
                                        :style="{ backgroundColor: getSetting('general', 'headercolor', '#4f46e5') }">
                                    <span>Categories</span>
                                    <small class="text-xs opacity-75">(See All)</small>
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                                <ul class="dropdown-menu absolute left-0 mt-1 w-64 bg-white rounded-lg shadow-xl border hidden group-hover:block z-40">
                                    <li v-for="cat in menucategories" :key="cat.id">
                                        <router-link :to="`/category/${cat.slug}`"
                                                     class="block px-4 py-2 hover:bg-gray-100 border-b last:border-0">
                                            {{ cat.name }}
                                        </router-link>
                                    </li>
                                </ul>
                            </div>

                            <!-- Header Menu -->
                            <button class="navbar-toggler lg:hidden text-white ml-4" type="button">
                                <span class="navbar-toggler-icon"></span>
                            </button>
                            <div class="collapse navbar-collapse flex-1" id="mainNav">
                                <ul class="navbar-nav flex items-center gap-1 ml-4">
                                    <li v-for="item in headerMenu" :key="item.label" class="nav-item">
                                        <router-link :to="item.url"
                                                     class="nav-link text-white px-4 py-3 hover:bg-white/10 transition block">
                                            {{ item.label }}
                                        </router-link>
                                    </li>
                                </ul>
                            </div>

                            <!-- Cart Total (Desktop) -->
                            <div class="text-white ml-auto hidden lg:block">
                                <i class="bi bi-cart3 mr-2"></i>
                                ৳{{ cartSubtotal }} <small class="text-xs opacity-75">({{ cartCount }} items)</small>
                            </div>
                        </div>
                    </div>
                </nav>
            </div>
        </header>

        <!-- Mobile Menu -->
        <div v-if="isMenuOpen" class="fixed inset-0 bg-black/50 z-40" @click="toggleMobileMenu"></div>
        <div :class="['mobile-menu fixed top-0 left-0 h-full w-80 bg-white z-50 transform transition-transform duration-300', isMenuOpen ? 'translate-x-0' : '-translate-x-full']">
            <div class="mobile-menu-logo p-4 border-b flex items-center justify-between">
                <div class="logo-image">
                    <img v-if="getSetting('general', 'white_logo')" :src="getImageUrl(getSetting('general', 'white_logo'))" class="h-10">
                </div>
                <button @click="toggleMobileMenu" class="mobile-menu-close text-2xl">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <ul class="first-nav p-4 overflow-y-auto h-full">
                <li v-for="cat in menucategories" :key="cat.id" class="parent-category mb-2">
                    <router-link :to="`/category/${cat.slug}`" class="menu-category-name flex items-center py-2 hover:text-indigo-600" @click="toggleMobileMenu">
                        <img v-if="cat.image" :src="getImageUrl(cat.image)" class="side_cat_img w-6 h-6 mr-2 object-contain" alt="">
                        {{ cat.name }}
                    </router-link>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div id="content" class="w-full overflow-x-hidden">
            <!-- Hero Slider Section -->
            <section class="relative bg-gradient-to-br from-slate-900 via-indigo-900 to-purple-900 text-white overflow-hidden min-h-[500px] w-full">
                <div class="absolute inset-0">
                    <div class="absolute inset-0 opacity-20" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%239C92AC\' fill-opacity=\'0.05\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
                </div>

                <div class="max-w-7xl mx-auto px-4 py-24 md:py-32 relative z-10">
                    <transition name="slide-fade" mode="out-in">
                        <div v-if="heroBanners.length > 0" :key="currentSlide" class="grid lg:grid-cols-2 gap-8 items-center">
                            <div class="text-center lg:text-left">
                                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 backdrop-blur-sm border border-white/20 text-indigo-300 text-sm font-medium mb-8">
                                    <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                                    {{ heroBanners[currentSlide].category_name || 'New Collection' }}
                                </div>

                                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold leading-tight mb-6">
                                    {{ heroBanners[currentSlide].title || 'Discover Amazing Products' }}
                                </h1>

                                <p class="text-lg md:text-xl text-indigo-200/90 max-w-2xl mx-auto lg:mx-0 mb-10">
                                    {{ heroBanners[currentSlide].description || 'Premium curated products. Exceptional quality. Prices you\'ll love.' }}
                                </p>

                                <div class="flex flex-wrap gap-4 justify-center lg:justify-start">
                                    <a :href="heroBanners[currentSlide].link || '/shop'"
                                       class="bg-white text-indigo-950 hover:bg-gray-100 shadow-2xl shadow-indigo-500/30 rounded-full px-8 py-4 text-lg font-semibold transition-all hover:scale-105 group inline-flex items-center">
                                        Shop Now
                                        <span class="ml-2 group-hover:translate-x-1 transition-transform">→</span>
                                    </a>
                                </div>
                            </div>

                            <div class="relative hidden lg:block">
                                <img :src="heroBanners[currentSlide].image_url"
                                     class="rounded-2xl shadow-2xl object-cover w-full h-[400px] transform hover:scale-105 transition-transform duration-1000"
                                     alt="Hero Product">
                            </div>
                        </div>

                        <div v-else class="grid lg:grid-cols-2 gap-8 items-center">
                            <div class="text-center lg:text-left">
                                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold leading-tight mb-6">
                                    Discover
                                    <span class="text-indigo-400">Amazing</span>
                                    <br>Products Here
                                </h1>
                                <p class="text-lg md:text-xl text-indigo-200/90 mb-10">
                                    Shop the best products at the best prices
                                </p>
                                <router-link to="/shop" class="bg-white text-indigo-950 rounded-full px-8 py-4 text-lg font-semibold hover:bg-gray-100 transition-all inline-flex items-center">
                                    Shop Now →
                                </router-link>
                            </div>
                            <div class="relative hidden lg:block">
                                <img src="https://images.unsplash.com/photo-1523275335684-37881b6dc50b?q=80&w=1200&auto=format&fit=crop"
                                     class="rounded-2xl shadow-2xl w-full h-[400px] object-cover" alt="Hero">
                            </div>
                        </div>
                    </transition>
                </div>

                <!-- Slider Dots -->
                <div v-if="heroBanners.length > 1" class="absolute bottom-8 left-1/2 -translate-x-1/2 flex gap-3 z-20">
                    <button v-for="(_, index) in heroBanners" :key="index" @click="currentSlide = index"
                            :class="currentSlide === index ? 'w-10 bg-indigo-500' : 'w-3 bg-white/50 hover:bg-white/80'"
                            class="h-3 rounded-full transition-all duration-500"></button>
                </div>
            </section>

            <!-- Featured Products -->
            <section v-if="featuredProducts.length" class="max-w-7xl mx-auto px-4 py-16">
                <div class="section-title text-center mb-12">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Featured Products</h2>
                    <p class="text-gray-600">Handpicked just for you</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="product in featuredProducts.slice(0, 4)" :key="product.id"
                         class="product-card bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition cursor-pointer"
                         @click="navigateToProduct(product.id)">
                        <div class="relative h-48 bg-gray-100 p-4">
                            <img :src="getImageUrl(product.thumbnail)" class="w-full h-full object-contain" alt="">
                            <span v-if="product.offer_price" class="discount-badge absolute top-2 left-2 bg-red-500 text-white text-xs px-2 py-1 rounded">
                                -{{ Math.round((product.base_price - product.offer_price) / product.base_price * 100) }}%
                            </span>
                        </div>
                        <div class="p-4">
                            <h3 class="product-title font-semibold text-gray-900 mb-2 line-clamp-2">{{ product.name }}</h3>
                            <div class="product-price flex items-center justify-between">
                                <div>
                                    <span class="text-xl font-bold text-indigo-600">৳{{ product.offer_price || product.base_price }}</span>
                                    <span v-if="product.offer_price" class="old-price ml-2 text-sm text-gray-400 line-through">৳{{ product.base_price }}</span>
                                </div>
                                <button @click.stop="handleAddToCart(product)"
                                        class="addcartbutton bg-indigo-600 text-white p-2 rounded-lg hover:bg-indigo-700 transition">
                                    <i class="bi bi-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Categories Section -->
            <section v-if="categories.length" class="bg-gray-100 py-16">
                <div class="max-w-7xl mx-auto px-4">
                    <div class="section-title text-center mb-12">
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Shop by Category</h2>
                        <p class="text-gray-600">Explore our wide range of categories</p>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <div v-for="cat in categories.slice(0, 6)" :key="cat.id"
                             @click="filterByCategory(cat.slug)"
                             class="category-card bg-white rounded-lg p-4 text-center shadow-sm hover:shadow-md transition cursor-pointer">
                            <img v-if="cat.image" :src="getImageUrl(cat.image)" class="h-16 mx-auto mb-3 object-contain" alt="">
                            <div v-else class="h-16 w-16 mx-auto mb-3 bg-gray-200 rounded-full flex items-center justify-center text-2xl">
                                📦
                            </div>
                            <h3 class="font-medium text-gray-900">{{ cat.name }}</h3>
                        </div>
                    </div>
                </div>
            </section>

            <!-- All Products Section -->
            <section id="products-section" class="max-w-7xl mx-auto px-4 py-16">
                <div class="flex flex-col md:flex-row justify-between items-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 md:mb-0">All Products</h2>

                    <div class="flex gap-4">
                        <select v-model="sortOption" @change="fetchData"
                                class="border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">Sort: Featured</option>
                            <option value="price_low">Price: Low to High</option>
                            <option value="price_high">Price: High to Low</option>
                            <option value="newest">Newest First</option>
                        </select>

                        <button v-if="activeFilterCount" @click="resetFilters"
                                class="bg-red-50 text-red-600 px-4 py-2 rounded-lg hover:bg-red-100 transition">
                            Clear ({{ activeFilterCount }})
                        </button>
                    </div>
                </div>

                <!-- Products Grid -->
                <div v-if="loading" class="text-center py-16">
                    <div class="custom-loader inline-block w-12 h-12 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                </div>

                <div v-else-if="products.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="product in products" :key="product.id"
                         class="product-card bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition cursor-pointer"
                         @click="navigateToProduct(product.id)">
                        <div class="relative h-48 bg-gray-100 p-4">
                            <img :src="getImageUrl(product.thumbnail)" class="w-full h-full object-contain" alt="">
                            <span v-if="product.offer_price" class="discount-badge absolute top-2 left-2 bg-red-500 text-white text-xs px-2 py-1 rounded">
                                -{{ Math.round((product.base_price - product.offer_price) / product.base_price * 100) }}%
                            </span>
                            <button @click.stop="toggleWishlist(product.id)"
                                    class="absolute top-2 right-2 w-8 h-8 bg-white rounded-full shadow flex items-center justify-center hover:bg-red-50">
                                <i :class="isInWishlist(product.id) ? 'bi bi-heart-fill text-red-500' : 'bi bi-heart text-gray-400'"></i>
                            </button>
                        </div>
                        <div class="p-4">
                            <p class="text-xs text-indigo-600 font-semibold uppercase mb-1">{{ product.category?.name || 'Product' }}</p>
                            <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">{{ product.name }}</h3>
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-xl font-bold text-indigo-600">৳{{ product.offer_price || product.base_price }}</span>
                                    <span v-if="product.offer_price" class="old-price ml-2 text-sm text-gray-400 line-through">৳{{ product.base_price }}</span>
                                </div>
                                <button @click.stop="handleAddToCart(product)"
                                        class="cart_store bg-indigo-600 text-white p-2 rounded-lg hover:bg-indigo-700 transition">
                                    <i class="bi bi-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-16">
                    <p class="text-gray-500">No products found</p>
                </div>
            </section>
        </div>

        <!-- Footer -->
        <footer class="text-white w-full overflow-x-hidden">
            <!-- Footer Top -->
            <div class="footer-shape-1 py-12" :style="{ backgroundColor: getSetting('general', 'footer_color_1', '#1a1a1a') }">
                <div class="container mx-auto px-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <!-- About -->
                        <div class="footer-about">
                            <router-link to="/" class="inline-block mb-4">
                                <img v-if="getSetting('general', 'white_logo')"
                                     :src="getImageUrl(getSetting('general', 'white_logo'))"
                                     class="h-12 w-auto brightness-0 invert" alt="Logo">
                            </router-link>
                            <p class="text-gray-300">
                                {{ getSetting('general', 'name', 'E-Shop') }} – Where Quality Meets Affordability.
                            </p>
                        </div>

                        <!-- Social Icons -->
                        <div>
                            <div class="flex gap-3 mb-4">
                                <a v-for="icon in socialicons" :key="icon.id" :href="icon.link" target="_blank"
                                   class="w-10 h-10 border border-gray-400 rounded-full flex items-center justify-center hover:bg-white hover:text-gray-900 transition">
                                    <i :class="icon.icon"></i>
                                </a>
                            </div>
                            <p class="text-gray-300">Follow our social media to get regular updates.</p>
                        </div>

                        <!-- App Stores -->
                        <div>
                            <div class="flex gap-2 mb-4">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg"
                                     class="h-10" alt="Google Play">
                                <img src="https://developer.apple.com/assets/elements/badges/download-on-the-app-store.svg"
                                     class="h-10" alt="App Store">
                            </div>
                            <p class="text-gray-300">Keep our apps with you to get the best offers.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Middle -->
            <div class="footer-shape-2 py-12" :style="{ backgroundColor: getSetting('general', 'footer_color_2', '#0e4f35') }">
                <div class="container mx-auto px-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                        <!-- Quick Links -->
                        <div>
                            <h6 class="text-white font-bold mb-4">Quick Links</h6>
                            <ul class="space-y-2">
                                <li v-for="item in headerFooter" :key="item.labels">
                                    <router-link :to="item.urls" class="text-gray-300 hover:text-white transition">
                                        {{ item.labels }}
                                    </router-link>
                                </li>
                            </ul>
                        </div>

                        <!-- Contacts -->
                        <div>
                            <h6 class="text-white font-bold mb-4">Contacts</h6>
                            <p class="text-gray-300 mb-2">Address: {{ contact.address || 'N/A' }}</p>
                            <p class="text-gray-300 mb-2">Phone: {{ contact.phone || 'N/A' }}</p>
                            <p class="text-gray-300">Email: {{ contact.hotmail || 'N/A' }}</p>
                        </div>

                        <!-- My Account -->
                        <div>
                            <h6 class="text-white font-bold mb-4">My Account</h6>
                            <ul class="space-y-2">
                                <li><router-link to="/login" class="text-gray-300 hover:text-white transition">Login</router-link></li>
                                <li><router-link to="/order-history" class="text-gray-300 hover:text-white transition">Order History</router-link></li>
                                <li><router-link to="/wishlist" class="text-gray-300 hover:text-white transition">My Wishlist</router-link></li>
                                <li><router-link to="/track-order" class="text-gray-300 hover:text-white transition">Track Order</router-link></li>
                            </ul>
                        </div>

                        <!-- Seller Zone -->
                        <div>
                            <h6 class="text-white font-bold mb-4">Seller Zone</h6>
                            <ul class="space-y-2">
                                <li><a href="#" class="text-gray-300 hover:text-white transition">Become A Reseller</a></li>
                                <li><a href="#" class="text-gray-300 hover:text-white transition">Become A Wholeseller</a></li>
                                <li><a href="#" class="text-gray-300 hover:text-white transition">Become A Partner</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="footer-shape-3 py-4" :style="{ backgroundColor: getSetting('general', 'footer_color_3', '#082e1f') }">
                <div class="container mx-auto px-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm text-gray-300">
                        <div>Copyright © {{ new Date().getFullYear() }} {{ getSetting('general', 'name', 'E-Shop') }}</div>
                        <div>Powered by: <a :href="getSetting('general', 'footer_link', 'https://eiconbd.com/')" target="_blank" class="text-white hover:underline">{{ getSetting('general', 'footer_text', 'EiconBD') }}</a></div>
                        <div class="flex items-center gap-2">
                            Payment: <img src="https://i.postimg.cc/8kQ0qR0P/bkash-logo.png" class="h-5" alt="Payments">
                        </div>
                        <div class="flex items-center gap-2">
                            Delivery: <img src="https://i.postimg.cc/Z5y4wMqP/Shipping.png" class="h-5" alt="Shipping">
                        </div>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Mobile Bottom Navigation -->
        <div class="footer_nav lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg z-40">
            <ul class="grid grid-cols-4 gap-1 p-2">
                <li>
                    <button @click="toggleMobileMenu" class="flex flex-col items-center text-gray-600 hover:text-indigo-600 w-full">
                        <i class="bi bi-grid text-xl"></i>
                        <span class="text-xs">Category</span>
                    </button>
                </li>
                <li>
                    <a :href="`https://wa.me/${contact.whatsapp || ''}`" target="_blank" class="flex flex-col items-center text-gray-600 hover:text-indigo-600">
                        <i class="bi bi-chat-dots text-xl"></i>
                        <span class="text-xs">Message</span>
                    </a>
                </li>
                <li class="mobile_home">
                    <router-link to="/" class="flex flex-col items-center text-gray-600 hover:text-indigo-600">
                        <i class="bi bi-house-door text-xl"></i>
                        <span class="text-xs">Home</span>
                    </router-link>
                </li>
                <li>
                    <router-link to="/checkout" class="flex flex-col items-center text-gray-600 hover:text-indigo-600 relative">
                        <i class="bi bi-cart3 text-xl"></i>
                        <span v-if="cartCount" class="mobilecart-qty absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                            {{ cartCount }}
                        </span>
                        <span class="text-xs">Cart</span>
                    </router-link>
                </li>
            </ul>
        </div>

        <!-- Back to Top Button -->
        <div class="scrolltop fixed bottom-20 lg:bottom-8 right-4 z-40">
            <button v-if="showBackToTop" @click="scrollToTop"
                    class="scroll w-12 h-12 bg-indigo-600 text-white rounded-full shadow-lg hover:bg-indigo-700 transition flex items-center justify-center">
                <i class="bi bi-arrow-up"></i>
            </button>
        </div>

        <!-- Page Overlay -->
        <div id="page-overlay" v-if="isMenuOpen" class="fixed inset-0 bg-black/50 z-30" @click="toggleMobileMenu"></div>

        <!-- Loading Spinner -->
        <div id="loading" v-if="loading" class="fixed inset-0 bg-white/80 z-50 flex items-center justify-center">
            <div class="custom-loader w-16 h-16 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, computed, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useCartStore } from '../../stores/cart';
import { useAuthStore } from '../../stores/auth';
import Swal from 'sweetalert2';

const router = useRouter();
const cartStore = useCartStore();
const authStore = useAuthStore();

// State
const products = ref([]);
const categories = ref([]);
const featuredProducts = ref([]);
const loading = ref(true);
const initialLoading = ref(true);
const wishlist = ref([]);
const showBackToTop = ref(false);
const isMenuOpen = ref(false);
const searchKeyword = ref('');

// Dynamic Data
const sliders = ref([]);
const currentSlide = ref(0);
const settings = ref({});
const menucategories = ref([]);
const headerMenu = ref([]);
const headerFooter = ref([]);
const socialicons = ref([]);
const contact = ref({});
const pixels = ref([]);
const gtm = ref(null);
const activeTiktokPixel = ref(null);

// Filters
const searchQuery = ref('');
const selectedCategory = ref(null);
const sortOption = ref('');
const minPrice = ref('');
const maxPrice = ref('');

// Backend URL
const backendUrl = 'http://127.0.0.1:73';

// Computed
const activeFilterCount = computed(() => {
    let count = 0;
    if (selectedCategory.value) count++;
    if (sortOption.value) count++;
    if (minPrice.value || maxPrice.value) count++;
    if (searchQuery.value) count++;
    return count;
});

const isInWishlist = (productId) => {
    return wishlist.value.includes(productId);
};

const heroBanners = computed(() => {
    if (!Array.isArray(sliders.value)) return [];
    const mainBanners = sliders.value.filter(s => s.category_name === 'Slider' || s.category_name === 'Home Banner');
    return mainBanners.length > 0 ? mainBanners : sliders.value;
});

const cartCount = computed(() => cartStore.items.length);
const cartSubtotal = computed(() => {
    return cartStore.items.reduce((total, item) => total + (item.price * item.quantity), 0);
});

// Helper Functions
const normalize = (str) => {
    return str ? String(str).replace(/[_-\s]+/g, '').toLowerCase() : '';
};

const getSetting = (group, key, defaultValue = '') => {
    if (!settings.value) return defaultValue;
    const targetGroup = Object.keys(settings.value).find(k => normalize(k) === normalize(group));

    if (targetGroup && Array.isArray(settings.value[targetGroup])) {
        const item = settings.value[targetGroup].find(s =>
            normalize(s.key) === normalize(key) ||
            normalize(s.name) === normalize(key)
        );

        if (item && item.value !== null && item.value !== '') {
            return item.type === 'image' ? item.value_url : item.value;
        }
    }
    return defaultValue;
};

const getImageUrl = (path) => {
    if (!path) return 'https://placehold.co/600x400?text=No+Image';
    return path.startsWith('http') ? path : `${backendUrl}/storage/${path}`;
};

// Remove from cart function
const removeFromCart = (itemId) => {
    cartStore.removeFromCart(itemId);
};

// Fetch all data
const fetchData = async () => {
    try {
        loading.value = true;

        const params = {};
        if (searchQuery.value) params.search = searchQuery.value;
        if (selectedCategory.value) params.category_slug = selectedCategory.value;
        if (sortOption.value) params.sort = sortOption.value;
        if (minPrice.value) params.min_price = minPrice.value;
        if (maxPrice.value) params.max_price = maxPrice.value;

        const [
            prodRes, catRes, featuredRes, sliderRes, settingRes,
            menuCatRes, headerMenuRes, footerMenuRes, socialRes,
            contactRes, pixelRes, gtmRes
        ] = await Promise.all([
            axios.get(`${backendUrl}/api/public/products`, { params }).catch(() => ({ data: { data: [] } })),
            axios.get(`${backendUrl}/api/public/categories`).catch(() => ({ data: [] })),
            axios.get(`${backendUrl}/api/public/products/featured`).catch(() => ({ data: [] })),
            axios.get(`${backendUrl}/api/public/sliders`).catch(() => ({ data: [] })),
            axios.get(`${backendUrl}/api/public/settings`).catch(() => ({ data: { data: {} } })),
            axios.get(`${backendUrl}/api/public/menu-categories`).catch(() => ({ data: [] })),
            axios.get(`${backendUrl}/api/public/header-menu`).catch(() => ({ data: [] })),
            axios.get(`${backendUrl}/api/public/footer-menu`).catch(() => ({ data: [] })),
            axios.get(`${backendUrl}/api/public/social-icons`).catch(() => ({ data: [] })),
            axios.get(`${backendUrl}/api/public/contact`).catch(() => ({ data: {} })),
            axios.get(`${backendUrl}/api/public/pixels`).catch(() => ({ data: [] })),
            axios.get(`${backendUrl}/api/public/gtm`).catch(() => ({ data: null }))
        ]);

        products.value = prodRes.data?.data || [];
        categories.value = catRes.data?.data || catRes.data || [];
        featuredProducts.value = featuredRes.data?.data || featuredRes.data || [];

        const rawSliders = sliderRes.data?.data || sliderRes.data || [];
        sliders.value = Array.isArray(rawSliders) ? rawSliders : [];

        settings.value = settingRes.data?.data || {};
        menucategories.value = menuCatRes.data || [];
        headerMenu.value = headerMenuRes.data || [];
        headerFooter.value = footerMenuRes.data || [];
        socialicons.value = socialRes.data || [];
        contact.value = contactRes.data || {};
        pixels.value = pixelRes.data || [];
        gtm.value = gtmRes.data || null;

        // TikTok Pixel separate kora
        if (pixels.value.length > 0) {
            const tiktok = pixels.value.find(p => p.platform === 'tiktok' && p.status === 1);
            if (tiktok) {
                activeTiktokPixel.value = { pixel_id: tiktok.code };
            }
        }

    } catch (error) {
        console.error("Data fetch error:", error);
    } finally {
        loading.value = false;
        initialLoading.value = false;
    }
};

// Methods
const filterByCategory = (slug) => {
    selectedCategory.value = selectedCategory.value === slug ? null : slug;
    fetchData();
    document.getElementById('products-section')?.scrollIntoView({ behavior: 'smooth' });
};

const resetFilters = () => {
    selectedCategory.value = null;
    searchQuery.value = '';
    sortOption.value = '';
    minPrice.value = '';
    maxPrice.value = '';
    fetchData();
};

const handleAddToCart = async (product) => {
    try {
        await cartStore.addToCart(product);

        // Facebook Pixel AddToCart Event
        if (window.fbq) {
            fbq('track', 'AddToCart', {
                content_ids: [product.id],
                content_type: 'product',
                value: product.offer_price || product.base_price,
                currency: 'BDT'
            });
        }

        // TikTok Pixel AddToCart Event
        if (window.ttq) {
            ttq.track('AddToCart', {
                content_id: product.id,
                content_type: 'product',
                value: product.offer_price || product.base_price,
                currency: 'BDT'
            });
        }

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            background: '#1e1e2f',
            color: '#fff'
        });

        Toast.fire({
            icon: 'success',
            title: `${product.name} added to cart`
        });

    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Failed to add',
            text: 'Please try again',
            background: '#1e1e2f',
            color: '#fff'
        });
    }
};

const toggleWishlist = (productId) => {
    if (wishlist.value.includes(productId)) {
        wishlist.value = wishlist.value.filter(id => id !== productId);
        Swal.fire({
            icon: 'info',
            title: 'Removed from wishlist',
            showConfirmButton: false,
            timer: 1500,
            background: '#1e1e2f',
            color: '#fff'
        });
    } else {
        wishlist.value.push(productId);
        Swal.fire({
            icon: 'success',
            title: 'Added to wishlist',
            showConfirmButton: false,
            timer: 1500,
            background: '#1e1e2f',
            color: '#fff'
        });
    }
};

const navigateToProduct = (id) => {
    router.push(`/product/${id}`);
};

const handleScroll = () => {
    showBackToTop.value = window.scrollY > 500;
};

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const toggleMobileMenu = () => {
    isMenuOpen.value = !isMenuOpen.value;
    if (isMenuOpen.value) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = 'auto';
    }
};

// Auto Slider
let slideInterval;
const startSlider = () => {
    slideInterval = setInterval(() => {
        if (heroBanners.value.length > 1) {
            currentSlide.value = (currentSlide.value + 1) % heroBanners.value.length;
        }
    }, 5000);
};

// Debounced search
let debounceTimer = null;
watch(searchQuery, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchData, 500);
});

watch([minPrice, maxPrice], () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchData, 800);
});

// Lifecycle
onMounted(() => {
    fetchData();
    const savedWishlist = localStorage.getItem('wishlist');
    if (savedWishlist) {
        wishlist.value = JSON.parse(savedWishlist);
    }
    window.addEventListener('scroll', handleScroll);
    startSlider();

    // Track PageView for pixels
    setTimeout(() => {
        if (window.fbq) {
            fbq('track', 'PageView');
        }
        if (window.ttq) {
            ttq.track('Pageview');
        }
    }, 500);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    clearInterval(slideInterval);
    document.body.style.overflow = 'auto';
});

watch(wishlist, (newVal) => {
    localStorage.setItem('wishlist', JSON.stringify(newVal));
}, { deep: true });
</script>

<style scoped>
.slide-fade-enter-active { transition: all 0.6s ease-out; }
.slide-fade-leave-active { transition: all 0.4s cubic-bezier(1, 0.5, 0.8, 1); }
.slide-fade-enter-from { transform: translateX(30px); opacity: 0; }
.slide-fade-leave-to { transform: translateX(-30px); opacity: 0; }

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in-up {
    animation: fadeInUp 0.6s ease-out forwards;
    opacity: 0;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

html {
    scroll-behavior: smooth;
    overflow-x: hidden;
}
body {
    overflow-x: hidden;
    width: 100%;
}

/* Scrollbar Styling */
::-webkit-scrollbar { width: 10px; }
::-webkit-scrollbar-track { background: #f1f1f1; }
::-webkit-scrollbar-thumb { background: linear-gradient(to bottom, #6366f1, #a855f7); border-radius: 5px; }
::-webkit-scrollbar-thumb:hover { background: linear-gradient(to bottom, #4f46e5, #9333ea); }

/* Mobile Menu Styles */
.mobile-menu {
    transition: transform 0.3s ease-in-out;
}
</style>
