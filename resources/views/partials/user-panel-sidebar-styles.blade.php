.ud-layout,
.ud-main-wrapper {
    display: flex;
    gap: 28px;
    margin-top: 100px;
    min-height: calc(100vh - var(--nav-h));
    position: relative;
}

.ud-sidebar,
    .sidebar.ud-sidebar {
    width: var(--sidebar-w);
    flex-shrink: 0;
    background: var(--wh);
    border-right: 2px solid var(--border);
    display: flex;
    flex-direction: column;
    position: sticky;
    top: var(--nav-h);
    height: calc(100vh - var(--nav-h));
    overflow-y: auto;
    z-index: 100;
    transition: transform .35s cubic-bezier(.34, 1.56, .64, 1);
}

.profile-block {
    padding: 22px 18px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    border-bottom: 2px solid var(--border);
}

.avatar {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--pk), var(--pu));
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Fredoka One', cursive;
    font-size: 2rem;
    color: #fff;
    box-shadow: 0 8px 22px rgba(255, 77, 143, .28);
    position: relative;
}

.online-dot {
    position: absolute;
    bottom: 3px;
    right: 3px;
    width: 13px;
    height: 13px;
    border-radius: 50%;
    background: var(--mn);
    border: 2.5px solid #fff;
}

.profile-name {
    font-family: 'Nunito', sans-serif;
    font-weight: 900;
    font-size: .95rem;
    color: var(--dk);
}

.profile-email {
    font-size: .75rem;
    color: var(--muted);
}

.nav-section {
    padding: 18px 14px 4px;
}

.sidebar-nav-scroll { display: flex !important; flex: 1; flex-direction: column !important; overflow-y: auto; }
.nav-group { box-sizing: border-box; width: 100%; padding-top: 12px; }
.nav-group-toggle {
    width: 100%;
    min-height: 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    box-sizing: border-box;
    margin: 0;
    padding: 0 8px 6px !important;
    border: 0;
    background: transparent;
    color: var(--muted);
    cursor: pointer;
    font: inherit;
    text-align: left;
}
.nav-group-toggle .nav-label { flex: 1; margin: 0 !important; padding: 0 !important; line-height: 1; text-align: left; }
.nav-group-toggle:hover .nav-label { color: var(--dk); }
.nav-group-toggle.is-active .nav-label { color: var(--pk); }
.nav-group-chevron { width: 14px; height: 14px; flex: 0 0 auto; transition: transform .2s ease; }
.nav-group-toggle[aria-expanded="true"] .nav-group-chevron { transform: rotate(180deg); }
.nav-group-toggle:focus-visible { outline: none; box-shadow: inset 0 -2px 0 rgba(255, 77, 143, .45); }
.nav-group-links[hidden] { display: none; }

.nav-label {
    font-family: 'Nunito', sans-serif;
    font-weight: 900;
    font-size: .62rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--muted);
    padding: 0 8px;
    margin-bottom: 6px;
    display: block;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 10px 13px;
    border-radius: 13px;
    font-size: .86rem;
    font-weight: 600;
    color: var(--muted);
    cursor: pointer;
    transition: .2s;
    text-decoration: none;
    margin-bottom: 1px;
    position: relative;
}

.nav-item:hover {
    background: var(--cr);
    color: var(--dk);
}

.nav-item.active {
    background: var(--pkl);
    color: var(--pk);
    font-weight: 800;
}

.nav-item.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 58%;
    background: var(--pk);
    border-radius: 0 4px 4px 0;
}

.nav-item svg {
    width: 17px;
    height: 17px;
    flex-shrink: 0;
}

.nav-item .nbadge {
    margin-left: auto;
    background: var(--pk);
    color: #fff;
    border-radius: 50px;
    padding: 2px 8px;
    font-size: .66rem;
    font-weight: 900;
}

/* ═══════════════════════════════════
           WELCOME BANNER
        ═══════════════════════════════════ */
    .welcome-banner {
        border-radius: 22px;
        background: linear-gradient(130deg, var(--dk2) 0%, #3d0080 60%, #1a004a 100%);
        padding: 30px 34px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 22px;
        position: relative;
        overflow: hidden;
        animation: fadeUp .4s cubic-bezier(.34, 1.1, .64, 1) forwards;
        opacity: 0;
    }

    .welcome-banner::before {
        content: '';
        position: absolute;
        width: 320px;
        height: 320px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 77, 143, .18), transparent 65%);
        right: -60px;
        top: -80px;
        pointer-events: none;
    }

    .welcome-text h2 {
        font-family: 'Fredoka One', cursive;
        font-size: 1.65rem;
        color: #fff;
        margin-bottom: 5px;
    }

    .welcome-text h2 span {
        color: var(--ye);
    }

    .welcome-text p {
        font-size: .85rem;
        color: rgba(255, 255, 255, .55);
        line-height: 1.6;
    }

    .welcome-right {
        display: flex;
        align-items: center;
        gap: 14px;
        position: relative;
        z-index: 1;
    }

    .banner-stat {
        text-align: center;
        background: rgba(255, 255, 255, .08);
        border: 1.5px solid rgba(255, 255, 255, .14);
        border-radius: 16px;
        padding: 13px 18px;
        backdrop-filter: blur(10px);
    }

    .banner-stat .bs-num {
        font-family: 'Fredoka One', cursive;
        font-size: 1.6rem;
        color: #fff;
        line-height: 1;
    }

    .banner-stat .bs-lbl {
        font-size: .68rem;
        color: rgba(255, 255, 255, .5);
        margin-top: 3px;
        font-weight: 600;
    }

    .banner-emoji {
        font-size: 3.6rem;
        animation: bFloat 3s ease-in-out infinite;
    }

    @keyframes bFloat {
        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-10px);
        }
    }

    .sidebar-footer {
        margin-top: auto;
        padding: 14px;
        border-top: 2px solid var(--border);
    }

.logout-btn {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 10px 13px;
    border-radius: 13px;
    font-size: .86rem;
    font-weight: 700;
    color: #ef4444;
    cursor: pointer;
    transition: .2s;
    width: 100%;
    background: none;
    border: none;
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.logout-btn:hover {
    background: #fff0f0;
}

.main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.ud-main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.inner-topbar {
    display: none;
    background: var(--wh);
    border-bottom: 2px solid var(--border);
    height: 52px;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    position: sticky;
    top: var(--nav-h);
    z-index: 90;
}

.inner-topbar .it-title {
    font-family: 'Fredoka One', cursive;
    font-size: 1rem;
    color: var(--dk);
}

.page {
    padding: 24px 28px;
    flex: 1;
}

.page-header {
    margin-bottom: 22px;
}

.page-header-left h1 {
    font-family: 'Fredoka One', cursive;
    font-size: 1.7rem;
    color: var(--dk);
    margin-bottom: 3px;
}

.page-header-left p {
    font-size: .84rem;
    color: var(--muted);
}

@media (max-width: 900px) {
    .ud-layout,
    .ud-main-wrapper {
        flex-direction: column;
        margin-top: var(--nav-h);
    }

    .ud-sidebar {
        position: fixed;
        top: var(--nav-h);
        left: 0;
        width: var(--sidebar-w);
        height: calc(100vh - var(--nav-h));
        transform: translateX(-100%);
    }

    .ud-sidebar.open {
        transform: translateX(0);
    }

    .main {
        margin-right: 0;
    }

    .hamburger {
        display: flex;
    }
}

.hamburger,
.sidebar-toggle {
    display: none;
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    border-radius: 10px;
    color: var(--dk);
    transition: .2s;
}

.hamburger:hover,
.sidebar-toggle:hover {
    background: var(--cr);
}

.overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(13, 0, 32, .45);
    z-index: 95;
    backdrop-filter: blur(4px);
}

.overlay.show {
    display: block;
}
