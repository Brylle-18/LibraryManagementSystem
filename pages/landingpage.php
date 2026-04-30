<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libraread — Library Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,300&display=swap" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --ink:     #0c0c0c;
        --mid:     #555;
        --light:   #999;
        --border:  #e8e5e0;
        --surface: #f9f8f6;
        --white:   #ffffff;
        --accent:  #0c0c0c;
    }

    html { scroll-behavior: smooth; }

    body {
        font-family: 'DM Sans', sans-serif;
        background: var(--white);
        color: var(--ink);
        overflow-x: hidden;
    }

    /* ── Noise overlay ─────────────────────── */
    body::before {
        content: '';
        position: fixed;
        inset: 0;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.025'/%3E%3C/svg%3E");
        pointer-events: none;
        z-index: 0;
    }

    /* ── NAV ───────────────────────────────── */
    nav {
        position: fixed;
        top: 0; left: 0; right: 0;
        z-index: 100;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 60px;
        height: 64px;
        background: rgba(255,255,255,.88);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--border);
    }

    .nav-logo {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
    }
    .nav-logo svg { flex-shrink: 0; }
    .nav-logo-text {
        font-family: 'DM Serif Display', serif;
        font-size: 20px;
        color: var(--ink);
        letter-spacing: -0.3px;
    }

    .nav-links {
        display: flex;
        align-items: center;
        gap: 32px;
    }
    .nav-links a {
        font-size: 14px;
        color: var(--mid);
        text-decoration: none;
        font-weight: 500;
        transition: color .2s;
    }
    .nav-links a:hover { color: var(--ink); }

    .nav-cta {
        display: flex;
        gap: 10px;
    }
    .btn-ghost {
        padding: 8px 18px;
        border: 1.5px solid var(--border);
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        color: var(--ink);
        text-decoration: none;
        background: transparent;
        transition: all .2s;
    }
    .btn-ghost:hover { background: var(--surface); }
    .btn-solid {
        padding: 8px 18px;
        border: 1.5px solid var(--ink);
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        color: #fff;
        text-decoration: none;
        background: var(--ink);
        transition: all .2s;
    }
    .btn-solid:hover { background: #333; border-color: #333; }

    /* ── HERO ──────────────────────────────── */
    .hero {
        position: relative;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 120px 40px 80px;
        text-align: center;
        overflow: hidden;
    }

    /* Large background letter */
    .hero-bg-letter {
        position: absolute;
        font-family: 'DM Serif Display', serif;
        font-size: clamp(280px, 40vw, 540px);
        color: transparent;
        -webkit-text-stroke: 1px rgba(0,0,0,.05);
        user-select: none;
        pointer-events: none;
        letter-spacing: -20px;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        white-space: nowrap;
        z-index: 0;
    }

    .hero-eyebrow {
        position: relative;
        z-index: 1;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        color: var(--light);
        margin-bottom: 28px;
        padding: 6px 14px;
        border: 1px solid var(--border);
        border-radius: 20px;
        background: var(--white);
    }
    .hero-eyebrow-dot {
        width: 6px; height: 6px;
        border-radius: 50%;
        background: var(--ink);
        display: inline-block;
    }

    .hero-title {
        position: relative;
        z-index: 1;
        font-family: 'DM Serif Display', serif;
        font-size: clamp(48px, 8vw, 96px);
        line-height: 1.0;
        letter-spacing: -3px;
        color: var(--ink);
        max-width: 900px;
        margin-bottom: 28px;
    }
    .hero-title em {
        font-style: italic;
        color: var(--mid);
    }

    .hero-subtitle {
        position: relative;
        z-index: 1;
        font-size: clamp(15px, 2vw, 18px);
        color: var(--mid);
        max-width: 520px;
        line-height: 1.7;
        font-weight: 300;
        margin-bottom: 44px;
    }

    .hero-actions {
        position: relative;
        z-index: 1;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        justify-content: center;
    }

    .btn-hero-primary {
        padding: 14px 32px;
        background: var(--ink);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-family: inherit;
        font-size: 15px;
        font-weight: 600;
        text-decoration: none;
        transition: background .2s, transform .1s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-hero-primary:hover { background: #333; }
    .btn-hero-primary:active { transform: scale(.98); }

    .btn-hero-secondary {
        padding: 14px 32px;
        background: transparent;
        color: var(--ink);
        border: 1.5px solid var(--border);
        border-radius: 8px;
        font-family: inherit;
        font-size: 15px;
        font-weight: 500;
        text-decoration: none;
        transition: all .2s;
    }
    .btn-hero-secondary:hover { border-color: var(--ink); background: var(--surface); }

    .hero-scroll {
        position: relative;
        z-index: 1;
        margin-top: 64px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        color: var(--light);
        font-size: 11px;
        letter-spacing: 1px;
        text-transform: uppercase;
        animation: bounce 2s infinite;
    }
    @keyframes bounce {
        0%,100% { transform: translateY(0); }
        50%      { transform: translateY(6px); }
    }

    /* ── STATS BAR ─────────────────────────── */
    .stats-bar {
        background: var(--ink);
        padding: 20px 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0;
    }
    .stat-item {
        flex: 1;
        text-align: center;
        padding: 8px 20px;
        border-right: 1px solid rgba(255,255,255,.1);
        max-width: 220px;
    }
    .stat-item:last-child { border-right: none; }
    .stat-num {
        font-family: 'DM Serif Display', serif;
        font-size: 32px;
        color: #fff;
        display: block;
        line-height: 1;
        margin-bottom: 4px;
    }
    .stat-lbl {
        font-size: 12px;
        color: #777;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }

    /* ── FEATURES ──────────────────────────── */
    .features {
        padding: 100px 60px;
        max-width: 1200px;
        margin: 0 auto;
    }
    .section-label {
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: var(--light);
        margin-bottom: 16px;
    }
    .section-title {
        font-family: 'DM Serif Display', serif;
        font-size: clamp(36px, 5vw, 54px);
        letter-spacing: -1.5px;
        line-height: 1.1;
        color: var(--ink);
        max-width: 600px;
        margin-bottom: 64px;
    }
    .section-title em { font-style: italic; color: var(--mid); }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1px;
        background: var(--border);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
    }
    .feature-card {
        background: var(--white);
        padding: 36px 32px;
        transition: background .2s;
    }
    .feature-card:hover { background: var(--surface); }
    .feature-icon {
        width: 44px; height: 44px;
        background: var(--ink);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
    }
    .feature-icon svg { color: #fff; }
    .feature-title {
        font-family: 'DM Serif Display', serif;
        font-size: 20px;
        color: var(--ink);
        margin-bottom: 10px;
        letter-spacing: -0.3px;
    }
    .feature-desc {
        font-size: 14px;
        color: var(--mid);
        line-height: 1.65;
        font-weight: 300;
    }

    /* ── HOW IT WORKS ──────────────────────── */
    .how-it-works {
        background: var(--surface);
        padding: 100px 60px;
    }
    .how-inner {
        max-width: 1200px;
        margin: 0 auto;
    }
    .steps {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 40px;
        margin-top: 64px;
    }
    .step {
        position: relative;
    }
    .step-num {
        font-family: 'DM Serif Display', serif;
        font-size: 64px;
        color: var(--border);
        line-height: 1;
        margin-bottom: 16px;
        letter-spacing: -2px;
    }
    .step-title {
        font-size: 16px;
        font-weight: 600;
        color: var(--ink);
        margin-bottom: 8px;
    }
    .step-desc {
        font-size: 14px;
        color: var(--mid);
        line-height: 1.6;
        font-weight: 300;
    }

    /* Arrow connector between steps */
    .step:not(:last-child)::after {
        content: '→';
        position: absolute;
        top: 24px;
        right: -24px;
        font-size: 20px;
        color: var(--border);
    }

    /* ── ROLES ─────────────────────────────── */
    .roles {
        padding: 100px 60px;
        max-width: 1200px;
        margin: 0 auto;
    }
    .roles-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
        margin-top: 64px;
    }
    .role-card {
        border: 1.5px solid var(--border);
        border-radius: 12px;
        padding: 36px 28px;
        transition: border-color .2s, transform .2s;
        position: relative;
        overflow: hidden;
    }
    .role-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: var(--ink);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform .3s;
    }
    .role-card:hover::before { transform: scaleX(1); }
    .role-card:hover { border-color: var(--ink); transform: translateY(-2px); }
    .role-badge-lp {
        display: inline-block;
        padding: 4px 12px;
        background: var(--ink);
        color: #fff;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-bottom: 20px;
    }
    .role-badge-lp.lib  { background: #444; }
    .role-badge-lp.stu  { background: #888; }
    .role-title {
        font-family: 'DM Serif Display', serif;
        font-size: 24px;
        color: var(--ink);
        margin-bottom: 12px;
        letter-spacing: -0.3px;
    }
    .role-desc {
        font-size: 14px;
        color: var(--mid);
        line-height: 1.65;
        margin-bottom: 20px;
        font-weight: 300;
    }
    .role-perms {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .role-perms li {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: var(--mid);
    }
    .role-perms li::before {
        content: '';
        display: inline-block;
        width: 5px; height: 5px;
        border-radius: 50%;
        background: var(--light);
        flex-shrink: 0;
    }

    /* ── CTA BAND ──────────────────────────── */
    .cta-band {
        background: var(--ink);
        padding: 80px 60px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .cta-band::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
        background-size: 60px 60px;
    }
    .cta-band-inner { position: relative; z-index: 1; }
    .cta-band h2 {
        font-family: 'DM Serif Display', serif;
        font-size: clamp(36px, 5vw, 56px);
        color: #fff;
        letter-spacing: -1.5px;
        margin-bottom: 16px;
    }
    .cta-band h2 em { font-style: italic; color: #888; }
    .cta-band p {
        font-size: 16px;
        color: #777;
        max-width: 440px;
        margin: 0 auto 36px;
        font-weight: 300;
        line-height: 1.6;
    }
    .cta-band-btns {
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
    }
    .btn-cta-white {
        padding: 13px 28px;
        background: #fff;
        color: var(--ink);
        border: none;
        border-radius: 8px;
        font-family: inherit;
        font-size: 15px;
        font-weight: 600;
        text-decoration: none;
        transition: background .2s;
    }
    .btn-cta-white:hover { background: #eee; }
    .btn-cta-outline {
        padding: 13px 28px;
        background: transparent;
        color: #fff;
        border: 1.5px solid rgba(255,255,255,.25);
        border-radius: 8px;
        font-family: inherit;
        font-size: 15px;
        font-weight: 500;
        text-decoration: none;
        transition: border-color .2s;
    }
    .btn-cta-outline:hover { border-color: rgba(255,255,255,.6); }

    /* ── FOOTER ────────────────────────────── */
    footer {
        background: #080808;
        padding: 40px 60px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        border-top: 1px solid #1a1a1a;
    }
    .footer-logo {
        font-family: 'DM Serif Display', serif;
        font-size: 18px;
        color: #fff;
        letter-spacing: -0.3px;
    }
    .footer-copy {
        font-size: 13px;
        color: #555;
    }
    .footer-links {
        display: flex;
        gap: 20px;
    }
    .footer-links a {
        font-size: 13px;
        color: #555;
        text-decoration: none;
        transition: color .2s;
    }
    .footer-links a:hover { color: #fff; }

    /* ── Page enter animations ─────────────── */
    .fade-up {
        opacity: 0;
        transform: translateY(24px);
        transition: opacity .6s ease, transform .6s ease;
    }
    .fade-up.visible {
        opacity: 1;
        transform: none;
    }

    /* ── Responsive ────────────────────────── */
    @media (max-width: 1024px) {
        nav, .features, .how-it-works, .roles, .cta-band, footer {
            padding-left: 32px;
            padding-right: 32px;
        }
        .features-grid { grid-template-columns: repeat(2, 1fr); }
        .steps          { grid-template-columns: repeat(2, 1fr); }
        .step:nth-child(2)::after { display: none; }
        .roles-grid     { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 640px) {
        nav { padding: 0 20px; }
        .nav-links { display: none; }
        .hero { padding: 100px 20px 60px; }
        .features, .roles { padding: 60px 20px; }
        .how-it-works      { padding: 60px 20px; }
        .cta-band           { padding: 60px 20px; }
        footer              { padding: 32px 20px; flex-direction: column; text-align: center; }
        .features-grid { grid-template-columns: 1fr; }
        .steps          { grid-template-columns: 1fr; }
        .step::after   { display: none; }
        .roles-grid    { grid-template-columns: 1fr; }
        .stats-bar     { flex-wrap: wrap; padding: 20px; gap: 8px; }
        .stat-item     { border-right: none; border-bottom: 1px solid rgba(255,255,255,.1); }
        .stat-item:last-child { border-bottom: none; }
    }
    </style>
</head>
<body>

<!-- ── Navigation ──────────────────────────────────────────── -->
<nav>
    <a href="landingpage.php" class="nav-logo">
        <svg width="28" height="28" viewBox="0 0 36 36" fill="none">
            <rect width="36" height="36" rx="7" fill="#0c0c0c"/>
            <path d="M9 8h12a7 7 0 0 1 0 14H9V8z" fill="white"/>
            <path d="M9 22h13a5 5 0 0 1 0 10H9V22z" fill="#aaa"/>
        </svg>
        <span class="nav-logo-text">Libraread</span>
    </a>
    <div class="nav-links">
        <a href="#features">Features</a>
        <a href="#how-it-works">How it works</a>
        <a href="#roles">Roles</a>
    </div>
    <div class="nav-cta">
        <a href="login.php"    class="btn-ghost">Sign In</a>
        <a href="register.php" class="btn-solid">Get Started</a>
    </div>
</nav>

<!-- ── Hero ────────────────────────────────────────────────── -->
<section class="hero">
    <div class="hero-bg-letter" aria-hidden="true">Lr</div>

    <div class="hero-eyebrow">
        <span class="hero-eyebrow-dot"></span>
        Library Management System
    </div>

    <h1 class="hero-title">
        Your library,<br><em>beautifully</em> organized.
    </h1>

    <p class="hero-subtitle">
        Libraread gives librarians and students a clean, fast way to manage books,
        track borrowed records, and stay on top of overdue items — all in one place.
    </p>

    <div class="hero-actions">
        <a href="register.php" class="btn-hero-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/>
            </svg>
            Create Free Account
        </a>
        <a href="login.php" class="btn-hero-secondary">Sign In</a>
    </div>

    <div class="hero-scroll" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 12 15 18 9"/>
        </svg>
        Scroll
    </div>
</section>

<!-- ── Stats bar ────────────────────────────────────────────── -->
<div class="stats-bar">
    <div class="stat-item">
        <span class="stat-num" id="c-books">0</span>
        <span class="stat-lbl">Books in catalogue</span>
    </div>
    <div class="stat-item">
        <span class="stat-num" id="c-users">0</span>
        <span class="stat-lbl">Registered users</span>
    </div>
    <div class="stat-item">
        <span class="stat-num" id="c-borrows">0</span>
        <span class="stat-lbl">Active borrows</span>
    </div>
    <div class="stat-item">
        <span class="stat-num">3</span>
        <span class="stat-lbl">User roles</span>
    </div>
</div>

<!-- ── Features ─────────────────────────────────────────────── -->
<section class="features" id="features">
    <p class="section-label fade-up">What we offer</p>
    <h2 class="section-title fade-up">Everything a library needs, <em>nothing it doesn't.</em></h2>

    <div class="features-grid">
        <div class="feature-card fade-up">
            <div class="feature-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                </svg>
            </div>
            <h3 class="feature-title">Book Catalogue</h3>
            <p class="feature-desc">Manage your full inventory with titles, authors, categories, ISBNs, and real-time copy tracking.</p>
        </div>
        <div class="feature-card fade-up" style="transition-delay:.08s">
            <div class="feature-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
            </div>
            <h3 class="feature-title">Borrow & Return</h3>
            <p class="feature-desc">Log borrows and returns with automatic due-date calculation and copy count updates.</p>
        </div>
        <div class="feature-card fade-up" style="transition-delay:.16s">
            <div class="feature-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <h3 class="feature-title">Overdue Alerts</h3>
            <p class="feature-desc">Instantly see which borrowers are overdue — sorted by days past due, right on your dashboard.</p>
        </div>
        <div class="feature-card fade-up" style="transition-delay:.24s">
            <div class="feature-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <h3 class="feature-title">User Management</h3>
            <p class="feature-desc">Add, edit, deactivate users across three roles. Full audit trail on every change.</p>
        </div>
        <div class="feature-card fade-up" style="transition-delay:.32s">
            <div class="feature-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
            </div>
            <h3 class="feature-title">Live Analytics</h3>
            <p class="feature-desc">Pie charts, stat cards, and real-time counts give you instant insight into library activity.</p>
        </div>
        <div class="feature-card fade-up" style="transition-delay:.40s">
            <div class="feature-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
            <h3 class="feature-title">Secure by Design</h3>
            <p class="feature-desc">Bcrypt passwords, session regeneration, anti-enumeration, role-based access, and full audit logs.</p>
        </div>
    </div>
</section>

<!-- ── How it works ─────────────────────────────────────────── -->
<section class="how-it-works" id="how-it-works">
    <div class="how-inner">
        <p class="section-label fade-up">Simple workflow</p>
        <h2 class="section-title fade-up">Up and running in <em>four steps.</em></h2>

        <div class="steps">
            <div class="step fade-up">
                <div class="step-num">01</div>
                <h3 class="step-title">Create an account</h3>
                <p class="step-desc">Sign up as a student or have an admin add your librarian account.</p>
            </div>
            <div class="step fade-up" style="transition-delay:.1s">
                <div class="step-num">02</div>
                <h3 class="step-title">Add your books</h3>
                <p class="step-desc">Import your catalogue with titles, authors, and copy counts.</p>
            </div>
            <div class="step fade-up" style="transition-delay:.2s">
                <div class="step-num">03</div>
                <h3 class="step-title">Log borrows</h3>
                <p class="step-desc">Record every borrow with a due date. Copies decrement automatically.</p>
            </div>
            <div class="step fade-up" style="transition-delay:.3s">
                <div class="step-num">04</div>
                <h3 class="step-title">Monitor your dashboard</h3>
                <p class="step-desc">See overdue items, stats, and activity at a glance — every day.</p>
            </div>
        </div>
    </div>
</section>

<!-- ── Roles ─────────────────────────────────────────────────── -->
<section class="roles" id="roles">
    <p class="section-label fade-up">Access control</p>
    <h2 class="section-title fade-up">The right access for <em>every role.</em></h2>

    <div class="roles-grid">
        <div class="role-card fade-up">
            <span class="role-badge-lp">Admin</span>
            <h3 class="role-title">System Administrator</h3>
            <p class="role-desc">Full system control. Manages users, books, roles, and views audit logs.</p>
            <ul class="role-perms">
                <li>Create, edit, deactivate, or delete users</li>
                <li>Assign and change user roles</li>
                <li>Access full audit trail</li>
                <li>All librarian permissions</li>
            </ul>
        </div>
        <div class="role-card fade-up" style="transition-delay:.1s">
            <span class="role-badge-lp lib">Librarian</span>
            <h3 class="role-title">Librarian</h3>
            <p class="role-desc">Day-to-day operations. Manages the catalogue and processes all borrow transactions.</p>
            <ul class="role-perms">
                <li>Add, edit, and archive books</li>
                <li>Log borrows and process returns</li>
                <li>View overdue borrowers</li>
                <li>Search users and catalogue</li>
            </ul>
        </div>
        <div class="role-card fade-up" style="transition-delay:.2s">
            <span class="role-badge-lp stu">Student</span>
            <h3 class="role-title">Student</h3>
            <p class="role-desc">Read-only access to browse the catalogue and view their own borrow history.</p>
            <ul class="role-perms">
                <li>Browse book catalogue</li>
                <li>View personal borrow history</li>
                <li>Check due dates and status</li>
                <li>Update personal profile</li>
            </ul>
        </div>
    </div>
</section>

<!-- ── CTA band ──────────────────────────────────────────────── -->
<section class="cta-band">
    <div class="cta-band-inner">
        <h2>Ready to <em>get organized?</em></h2>
        <p>Join Libraread and bring order to your library in minutes. Free for student projects.</p>
        <div class="cta-band-btns">
            <a href="signup.php" class="btn-cta-white">Create Free Account</a>
            <a href="login.php"  class="btn-cta-outline">Sign In</a>
        </div>
    </div>
</section>

<!-- ── Footer ────────────────────────────────────────────────── -->
<footer>
    <span class="footer-logo">Libraread</span>
    <span class="footer-copy">© <?= date('Y') ?> Libraread — Library Management System</span>
    <div class="footer-links">
        <a href="login.php">Sign In</a>
        <a href="signup.php">Sign Up</a>
        <a href="forgot-password.php">Reset Password</a>
    </div>
</footer>

<script>
// ── Scroll-triggered fade-in ─────────────────────────────────
const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => {
        if (e.isIntersecting) {
            e.target.classList.add('visible');
            observer.unobserve(e.target);
        }
    });
}, { threshold: 0.12 });

document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));

// ── Animated counters (live DB values injected from PHP if desired) ──
function animateCount(id, target, suffix = '') {
    const el = document.getElementById(id);
    if (!el) return;
    const duration = 1400;
    const start    = performance.now();
    function step(now) {
        const progress = Math.min((now - start) / duration, 1);
        const ease     = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.floor(ease * target) + suffix;
        if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

// Trigger counters when stats bar enters viewport
const statsBar = document.querySelector('.stats-bar');
const statsObserver = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting) {
        // These are seeded defaults — swap for PHP-echoed values
        animateCount('c-books',   10);
        animateCount('c-users',    3);
        animateCount('c-borrows',  2);
        statsObserver.disconnect();
    }
}, { threshold: 0.5 });
statsObserver.observe(statsBar);
</script>
</body>
</html>