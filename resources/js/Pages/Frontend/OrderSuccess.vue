<script setup>
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const orderNumber = route.query.order_number || 'ORD-2025-8841';

const goHome = () => router.push('/');
const trackOrder = () => router.push(`/invoice/${orderNumber}`);
</script>

<template>
    <div class="page-root">

        <!-- Background texture -->
        <div class="bg-texture"></div>

        <!-- Top bar -->
        <header class="top-bar">
            <div class="brand">
                <div class="brand-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <path d="M16 10a4 4 0 01-8 0"/>
                    </svg>
                </div>
                <span class="brand-name">ESHOP</span>
            </div>
        </header>

        <!-- Main content -->
        <main class="main">

            <!-- Success card -->
            <div class="card">

                <!-- Left decorative strip -->
                <div class="card-strip"></div>

                <div class="card-body">

                    <!-- Icon -->
                    <div class="success-icon-wrap">
                        <div class="success-ring ring-1"></div>
                        <div class="success-ring ring-2"></div>
                        <div class="success-icon-circle">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Heading -->
                    <div class="heading-block">
                        <p class="eyebrow">Payment Successful</p>
                        <h1 class="heading">Order Confirmed!</h1>
                        <p class="subtext">
                            Thank you for your purchase. We've received your order and our team is currently processing it for dispatch.
                        </p>
                    </div>

                    <!-- Order number box -->
                    <div class="order-box">
                        <div class="order-box-left">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                            <div>
                                <span class="order-label">Order Reference</span>
                                <span class="order-number">{{ orderNumber }}</span>
                            </div>
                        </div>
                        <div class="order-status">
                            <span class="status-dot"></span>
                            Processing
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="timeline">
                        <div class="timeline-step done">
                            <div class="tl-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <div class="tl-text">
                                <span class="tl-title">Order Placed</span>
                                <span class="tl-sub">Just now</span>
                            </div>
                        </div>
                        <div class="tl-connector done"></div>
                        <div class="timeline-step active">
                            <div class="tl-icon pulse">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </div>
                            <div class="tl-text">
                                <span class="tl-title">Processing</span>
                                <span class="tl-sub">In progress</span>
                            </div>
                        </div>
                        <div class="tl-connector"></div>
                        <div class="timeline-step">
                            <div class="tl-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 3h15v13H1z"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                            </div>
                            <div class="tl-text">
                                <span class="tl-title">Dispatched</span>
                                <span class="tl-sub">Pending</span>
                            </div>
                        </div>
                        <div class="tl-connector"></div>
                        <div class="timeline-step">
                            <div class="tl-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            </div>
                            <div class="tl-text">
                                <span class="tl-title">Delivered</span>
                                <span class="tl-sub">Pending</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="actions">
                        <button @click="trackOrder" class="btn-primary">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            View Order Details
                        </button>
                        <button @click="goHome" class="btn-secondary">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            Continue Shopping
                        </button>
                    </div>

                    <!-- Email note -->
                    <p class="email-note">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        A confirmation email has been sent to your inbox.
                    </p>

                </div>
            </div>

        </main>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500;600;700&display=swap');

.page-root {
    --green: #16a34a;
    --green-light: #dcfce7;
    --green-mid: #4ade80;
    --dark: #111827;
    --text: #1f2937;
    --text-sub: #6b7280;
    --text-muted: #9ca3af;
    --border: #e5e7eb;
    --border-light: #f3f4f6;
    --surface: #ffffff;
    --bg: #f9fafb;
    --accent: #2563eb;

    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
}

/* Subtle background */
.bg-texture {
    position: fixed;
    inset: 0;
    background-image:
        radial-gradient(circle at 20% 20%, rgba(22,163,74,0.06) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(37,99,235,0.05) 0%, transparent 50%);
    pointer-events: none;
}

/* TOP BAR */
.top-bar {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 0 32px;
    height: 60px;
    display: flex;
    align-items: center;
    position: relative;
    z-index: 10;
}
.brand { display: flex; align-items: center; gap: 10px; }
.brand-icon {
    width: 34px; height: 34px;
    background: var(--dark); color: white;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
}
.brand-name {
    font-family: 'Playfair Display', serif;
    font-weight: 700; font-size: 1.2rem;
    letter-spacing: 0.08em; color: var(--dark);
}

/* MAIN */
.main {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 16px;
    position: relative;
    z-index: 1;
}

/* CARD */
.card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 24px;
    overflow: hidden;
    width: 100%;
    max-width: 520px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.07), 0 1px 3px rgba(0,0,0,0.04);
    display: flex;
    position: relative;
    animation: cardIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}
@keyframes cardIn {
    from { opacity: 0; transform: translateY(24px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
.card-strip {
    width: 5px;
    background: linear-gradient(180deg, #16a34a 0%, #4ade80 100%);
    flex-shrink: 0;
}
.card-body {
    flex: 1;
    padding: 40px 36px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

/* SUCCESS ICON */
.success-icon-wrap {
    position: relative;
    width: 88px; height: 88px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 28px;
}
.success-ring {
    position: absolute; border-radius: 50%;
    border: 2px solid var(--green-mid);
    opacity: 0;
    animation: ringPulse 2.4s ease-out infinite;
}
.ring-1 { width: 88px; height: 88px; animation-delay: 0s; }
.ring-2 { width: 88px; height: 88px; animation-delay: 0.6s; }
@keyframes ringPulse {
    0%   { transform: scale(0.8); opacity: 0.6; }
    100% { transform: scale(1.6); opacity: 0; }
}
.success-icon-circle {
    width: 72px; height: 72px;
    background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: white;
    box-shadow: 0 8px 24px rgba(22,163,74,0.35);
    position: relative;
    animation: iconDrop 0.5s 0.15s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}
@keyframes iconDrop {
    from { transform: scale(0.4) rotate(-20deg); opacity: 0; }
    to   { transform: scale(1) rotate(0deg); opacity: 1; }
}

/* HEADING */
.heading-block { margin-bottom: 24px; }
.eyebrow {
    font-size: 0.7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.12em;
    color: var(--green); margin-bottom: 6px;
    display: block;
}
.heading {
    font-family: 'Playfair Display', serif;
    font-size: 2rem; font-weight: 700;
    color: var(--dark); line-height: 1.2;
    margin-bottom: 12px;
}
.subtext {
    font-size: 0.9rem; line-height: 1.65;
    color: var(--text-sub);
    max-width: 360px; margin: 0 auto;
}

/* ORDER BOX */
.order-box {
    width: 100%;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 28px;
}
.order-box-left {
    display: flex; align-items: center; gap: 12px;
    color: var(--text-sub);
}
.order-box-left div { display: flex; flex-direction: column; align-items: flex-start; gap: 2px; }
.order-label { font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); }
.order-number { font-size: 1.05rem; font-weight: 700; color: var(--dark); letter-spacing: 0.04em; }
.order-status {
    display: flex; align-items: center; gap: 6px;
    font-size: 0.75rem; font-weight: 600;
    color: #15803d;
    background: #dcfce7;
    border: 1px solid #bbf7d0;
    padding: 5px 12px;
    border-radius: 20px;
    white-space: nowrap;
}
.status-dot {
    width: 6px; height: 6px;
    background: #16a34a; border-radius: 50%;
    animation: blink 1.4s ease-in-out infinite;
}
@keyframes blink { 0%,100% { opacity: 1; } 50% { opacity: 0.2; } }

/* TIMELINE */
.timeline {
    width: 100%;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    gap: 0;
    margin-bottom: 28px;
    padding: 20px;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 14px;
}
.timeline-step {
    display: flex; flex-direction: column;
    align-items: center; gap: 8px;
    flex: 0 0 auto;
}
.tl-connector {
    flex: 1; height: 2px;
    background: var(--border);
    margin-top: 18px;
    min-width: 20px;
}
.tl-connector.done { background: var(--green); }

.tl-icon {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: var(--border-light);
    border: 2px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    color: var(--text-muted);
    flex-shrink: 0;
}
.timeline-step.done .tl-icon {
    background: var(--green-light);
    border-color: var(--green);
    color: var(--green);
}
.timeline-step.active .tl-icon {
    background: #eff6ff;
    border-color: var(--accent);
    color: var(--accent);
}
.tl-icon.pulse { animation: iconBlink 1.8s ease-in-out infinite; }
@keyframes iconBlink { 0%,100% { box-shadow: 0 0 0 0 rgba(37,99,235,0.3); } 50% { box-shadow: 0 0 0 6px rgba(37,99,235,0); } }

.tl-text { display: flex; flex-direction: column; gap: 2px; }
.tl-title { font-size: 0.72rem; font-weight: 600; color: var(--text); white-space: nowrap; }
.tl-sub { font-size: 0.65rem; color: var(--text-muted); white-space: nowrap; }
.timeline-step.done .tl-title { color: var(--green); }
.timeline-step.active .tl-title { color: var(--accent); }

/* ACTIONS */
.actions { width: 100%; display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; }
.btn-primary {
    width: 100%; padding: 14px;
    background: var(--dark); color: white;
    border: none; border-radius: 12px;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.9375rem; font-weight: 600;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: all 0.2s;
    letter-spacing: 0.01em;
}
.btn-primary:hover {
    background: #16a34a;
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(22,163,74,0.3);
}
.btn-primary:active { transform: translateY(0); }

.btn-secondary {
    width: 100%; padding: 14px;
    background: var(--surface); color: var(--text);
    border: 1.5px solid var(--border); border-radius: 12px;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.9375rem; font-weight: 600;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: all 0.2s;
}
.btn-secondary:hover { border-color: var(--dark); color: var(--dark); background: var(--bg); }

/* EMAIL NOTE */
.email-note {
    display: flex; align-items: center; gap: 6px;
    font-size: 0.78rem; color: var(--text-muted); font-weight: 500;
}

/* RESPONSIVE */
@media (max-width: 480px) {
    .card-body { padding: 28px 20px; }
    .heading { font-size: 1.65rem; }
    .tl-title { font-size: 0.65rem; }
    .tl-sub { display: none; }
    .timeline { padding: 14px 12px; }
}
</style>
