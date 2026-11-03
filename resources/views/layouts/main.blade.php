<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'NutriBuddy')</title>
    @stack('meta')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Fredoka+One&family=Nunito:wght@400;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet"
        href="{{ asset('assets/css/frontendstyle.css') }}?v={{ filemtime(public_path('assets/css/frontendstyle.css')) }}">
    <link rel="stylesheet"
        href="{{ asset('assets/css/frontendresponsive.css') }}?v={{ filemtime(public_path('assets/css/frontendresponsive.css')) }}">
    @stack('styles')
</head>

<body>
    <!-- ══ GLOBAL TOAST SYSTEM ══ -->
    <div id="nb-toast-wrap"
        style="position:fixed;top:22px;right:22px;z-index:99999;display:flex;flex-direction:column;gap:12px;pointer-events:none;">
    </div>
    <style>
        .nb-toast {
            --toast-accent: var(--pk);
            --toast-soft: var(--pkl);
            pointer-events: all;
            display: flex;
            align-items: flex-start;
            gap: 13px;
            background: linear-gradient(135deg, rgba(255, 255, 255, .98), rgba(255, 248, 252, .96));
            border: 1px solid rgba(255, 77, 143, .14);
            border-radius: 18px;
            padding: 15px 16px 15px 15px;
            min-width: 300px;
            max-width: 390px;
            box-shadow: 0 18px 44px rgba(30, 24, 64, .14);
            font-family: 'Nunito', sans-serif;
            animation: nbSlideIn .35s cubic-bezier(.34, 1.56, .64, 1) forwards;
            transition: opacity .3s, transform .3s;
            position: relative;
            overflow: hidden
        }

        .nb-toast::before {
            content: '';
            position: absolute;
            inset: 0 auto 0 0;
            width: 5px;
            background: linear-gradient(180deg, var(--toast-accent), var(--ye))
        }

        .nb-toast::after {
            content: '';
            position: absolute;
            right: -34px;
            top: -38px;
            width: 96px;
            height: 96px;
            border-radius: 50%;
            background: var(--toast-soft);
            opacity: .55;
            pointer-events: none
        }

        .nb-toast.hiding {
            opacity: 0;
            transform: translateX(22px) scale(.98)
        }

        .nb-toast-icon {
            position: relative;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 12px;
            background: var(--toast-soft);
            color: var(--toast-accent);
            font-size: 1rem;
            font-weight: 900;
            flex-shrink: 0
        }

        .nb-toast-body {
            position: relative;
            z-index: 1;
            flex: 1;
            padding-top: 1px
        }

        .nb-toast-title {
            font-weight: 900;
            font-size: .9rem;
            color: var(--dk);
            margin-bottom: 3px;
            letter-spacing: 0
        }

        .nb-toast-msg {
            font-size: .82rem;
            color: #625b76;
            line-height: 1.45;
            font-weight: 700
        }

        .nb-toast-close {
            position: relative;
            z-index: 1;
            background: rgba(30, 24, 64, .05);
            border: none;
            border-radius: 999px;
            cursor: pointer;
            font-size: .9rem;
            color: #8b829b;
            padding: 0;
            line-height: 1;
            flex-shrink: 0;
            width: 25px;
            height: 25px;
            font-weight: 900
        }

        .nb-toast-close:hover {
            background: var(--toast-soft);
            color: var(--toast-accent)
        }

        .nb-toast.success {
            --toast-accent: var(--mn);
            --toast-soft: var(--mnl);
            border-color: rgba(0, 214, 143, .2)
        }

        .nb-toast.error {
            --toast-accent: #f04438;
            --toast-soft: #fff0ee;
            border-color: rgba(240, 68, 56, .18)
        }

        .nb-toast.warning {
            --toast-accent: var(--or);
            --toast-soft: var(--orl);
            border-color: rgba(255, 107, 53, .2)
        }

        .nb-toast.info {
            --toast-accent: var(--pu);
            --toast-soft: var(--pul);
            border-color: rgba(124, 58, 237, .18)
        }

        @media(max-width:560px) {
            #nb-toast-wrap {
                left: 14px !important;
                right: 14px !important;
                top: 14px !important
            }

            .nb-toast {
                min-width: 0;
                max-width: none;
                width: 100%
            }
        }

        @keyframes nbSlideIn {
            from {
                opacity: 0;
                transform: translateX(60px)
            }

            to {
                opacity: 1;
                transform: translateX(0)
            }
        }

        /* Confirm Dialog */
        .nb-confirm-overlay {
            position: fixed;
            inset: 0;
            z-index: 99998;
            background: rgba(13, 0, 32, .55);
            backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            justify-content: center;
            animation: nbFadeIn .2s ease
        }

        .nb-confirm-box {
            background: #fff;
            border-radius: 20px;
            padding: 28px 28px 22px;
            max-width: 360px;
            width: 90%;
            box-shadow: 0 24px 60px rgba(0, 0, 0, .22);
            border: 2px solid #f0e8ff;
            animation: nbPopUp .3s cubic-bezier(.34, 1.56, .64, 1)
        }

        .nb-confirm-icon {
            font-size: 2rem;
            margin-bottom: 10px
        }

        .nb-confirm-title {
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            font-size: 1.05rem;
            color: #1a0040;
            margin-bottom: 6px
        }

        .nb-confirm-msg {
            font-size: .84rem;
            color: #666;
            margin-bottom: 20px;
            line-height: 1.5
        }

        .nb-confirm-btns {
            display: flex;
            gap: 10px;
            justify-content: flex-end
        }

        .nb-confirm-cancel {
            border: 2px solid #e0e0e0;
            background: #fff;
            border-radius: 10px;
            padding: 9px 18px;
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: .85rem;
            cursor: pointer;
            color: #666;
            transition: all .2s
        }

        .nb-confirm-cancel:hover {
            border-color: #ccc;
            background: #f5f5f5
        }

        .nb-confirm-ok {
            border: none;
            background: linear-gradient(135deg, #ff4d4d, #c0392b);
            border-radius: 10px;
            padding: 9px 18px;
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            font-size: .85rem;
            cursor: pointer;
            color: #fff;
            transition: all .2s
        }

        .nb-confirm-ok:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 77, 77, .4)
        }

        @keyframes nbFadeIn {
            from {
                opacity: 0
            }

            to {
                opacity: 1
            }
        }

        @keyframes nbPopUp {
            from {
                opacity: 0;
                transform: scale(.8)
            }

            to {
                opacity: 1;
                transform: scale(1)
            }
        }
    </style>
    <script>
        function nbToast(msg, type = 'success', title = '') {
            const icons = {
                success: '✓',
                error: '!',
                warning: '!',
                info: 'i'
            };
            const titles = {
                success: 'Success',
                error: 'Error',
                warning: 'Warning',
                info: 'Info'
            };
            const wrap = document.getElementById('nb-toast-wrap');
            const t = document.createElement('div');
            t.className = `nb-toast ${type}`;
            t.innerHTML =
                `<div class="nb-toast-icon">${icons[type]||'i'}</div><div class="nb-toast-body"><div class="nb-toast-title">${title||titles[type]||'Notice'}</div><div class="nb-toast-msg">${msg}</div></div><button class="nb-toast-close" onclick="this.closest('.nb-toast').remove()">x</button>`;
            wrap.appendChild(t);
            setTimeout(() => {
                t.classList.add('hiding');
                setTimeout(() => t.remove(), 350);
            }, 3500);
        }
        @if (session('success'))
            nbToast(@json(session('success')), 'success');
        @endif
        @if (session('error'))
            nbToast(@json(session('error')), 'error');
        @endif
        @if (session('warning'))
            nbToast(@json(session('warning')), 'warning');
        @endif
        @if (session('info'))
            nbToast(@json(session('info')), 'info');
        @endif
        function nbConfirm(msg, onOk, opts = {}) {
            const overlay = document.createElement('div');
            overlay.className = 'nb-confirm-overlay';
            overlay.innerHTML =
                `<div class="nb-confirm-box"><div class="nb-confirm-icon">${opts.icon||'🗑️'}</div><div class="nb-confirm-title">${opts.title||'Are you sure?'}</div><div class="nb-confirm-msg">${msg}</div><div class="nb-confirm-btns"><button class="nb-confirm-cancel">Cancel</button><button class="nb-confirm-ok">${opts.okText||'Yes, Delete'}</button></div></div>`;
            document.body.appendChild(overlay);
            overlay.querySelector('.nb-confirm-cancel').onclick = () => overlay.remove();
            overlay.querySelector('.nb-confirm-ok').onclick = () => {
                overlay.remove();
                onOk();
            };
            overlay.addEventListener('click', e => {
                if (e.target === overlay) overlay.remove();
            });
        }
    </script>
    <div id="cur"></div>
    <div id="cur-ring"></div>

    @include('partials.header')

    <main>
        @yield('content')
    </main>

    <div class="nb-variant-modal" id="nbVariantModal" aria-hidden="true">
        <div class="nb-variant-modal__backdrop" data-nb-variant-close></div>
        <div class="nb-variant-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="nbVariantModalTitle">
            <button type="button" class="nb-variant-modal__close" data-nb-variant-close aria-label="Close">&times;</button>
            <div class="nb-variant-modal__grid">
                <div class="nb-variant-modal__options">
                    <span class="nb-variant-modal__eyebrow">Choose Option</span>
                    <h3 id="nbVariantModalTitle">Select your pack</h3>
                    <div class="nb-variant-modal__groups" id="nbVariantModalGroups"></div>
                    <div class="nb-variant-modal__stock" id="nbVariantModalStock">Available</div>
                </div>
                <div class="nb-variant-modal__summary">
                    <div class="nb-variant-modal__image">
                        <img src="{{ asset('img/product2.png') }}" alt="" id="nbVariantModalImage">
                    </div>
                    <div class="nb-variant-modal__name" id="nbVariantModalName">Product</div>
                    <div class="nb-variant-modal__selected" id="nbVariantModalSelected">Product option</div>
                    <div class="nb-variant-modal__price" id="nbVariantModalPrice">Rs. 0</div>
                    <button type="button" class="nb-variant-modal__add" id="nbVariantModalAdd">Add to Cart</button>
                </div>
            </div>
        </div>
    </div>

    @include('partials.footer')

    @guest
        @include('components.login-modal')
    @endguest

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js" defer></script>
    <script src="{{ asset('assets/js/frontendscript.js') }}?v={{ filemtime(public_path('assets/js/frontendscript.js')) }}"
        defer></script>
    <script src="{{ asset('assets/js/pages/allfile.js') }}?v={{ filemtime(public_path('assets/js/pages/allfile.js')) }}"
        defer></script>
    @stack('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('login')) {
                if (typeof openLoginModal === 'function') {
                    openLoginModal();
                }
            }
        });
    </script>
</body>

</html>
