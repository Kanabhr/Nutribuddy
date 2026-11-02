/* ══════════════════════════════════════════════════════════════
   NUTRIBUDDY — MAIN SCRIPT
   Sections:
   01. Custom Cursor (simple)
   02. Nav Scroll Shadow
   03. Hamburger / Mobile Menu
   04. Cart Popup
   05. Hero Slider + Sparks
   06. Stars Background (Ingredients)
   07. Ingredient Tabs
   08. Add to Cart
   09. Scroll Reveal
   10. Counter Animation
   11. GSAP Animations
   12. Video Reels (slider with prev/next buttons)
   13. FAQ Accordion
   14. Certificate Carousel
   15. Diet Chart — State & Logic
══════════════════════════════════════════════════════════════ */

function initNutriBuddy() {

  /* ══════════════════════════════════════════
     01. CUSTOM CURSOR (simple dot, no ring)
  ══════════════════════════════════════════ */
  let cur = document.getElementById('cur');
  let ring = document.getElementById('cur-ring');

  if (!cur) {
    cur = document.createElement('div');
    cur.id = 'cur';
    document.body.appendChild(cur);
  }
  if (!ring) {
    ring = document.createElement('div');
    ring.id = 'cur-ring';
    document.body.appendChild(ring);
  }

  let mouseX = window.innerWidth / 2;
  let mouseY = window.innerHeight / 2;
  let ringX = mouseX;
  let ringY = mouseY;

  const animateRing = () => {
    ringX += (mouseX - ringX) * 0.18;
    ringY += (mouseY - ringY) * 0.18;
    ring.style.left = ringX + 'px';
    ring.style.top = ringY + 'px';
    requestAnimationFrame(animateRing);
  };
  animateRing();

  if (cur) {
    document.addEventListener('mousemove', e => {
      mouseX = e.clientX;
      mouseY = e.clientY;
      cur.style.left = mouseX + 'px';
      cur.style.top = mouseY + 'px';
      cur.style.opacity = '1';
      ring.style.opacity = '1';
    });
    document.addEventListener('mouseleave', () => { cur.style.opacity = '0'; ring.style.opacity = '0'; });
    document.addEventListener('mouseenter', () => { cur.style.opacity = '1'; ring.style.opacity = '1'; });
    document.querySelectorAll('button, a, .pc, .reel, .itab, .age-card, .gender-card, .prob-tag, .dpref, .atag, .imgcar-item, .wrev, .faq-q, .tc, .wc').forEach(el => {
      el.addEventListener('mouseenter', () => document.body.classList.add('cursor-hover'));
      el.addEventListener('mouseleave', () => document.body.classList.remove('cursor-hover'));
    });
    document.body.classList.add('has-custom-cursor');
  }

  /* ══════════════════════════════════════════
     02. NAV SCROLL SHADOW
  ══════════════════════════════════════════ */
  window.addEventListener('scroll', () => {
    const nav = document.getElementById('mainNav');
    if (nav) nav.classList.toggle('scrolled', window.scrollY > 60);
  });

  /* ══════════════════════════════════════════
     03. HAMBURGER / MOBILE MENU
  ══════════════════════════════════════════ */
  const hamburgerBtn = document.getElementById('hamburgerBtn');
  const mobileMenu = document.getElementById('mobileMenu');
  const menuOverlay = document.getElementById('menuOverlay');

  function openMenu() {
    hamburgerBtn.classList.add('open');
    mobileMenu.classList.add('open');
    menuOverlay.classList.add('open');
    hamburgerBtn.setAttribute('aria-expanded', 'true');
    mobileMenu.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }
  function closeMenu() {
    hamburgerBtn.classList.remove('open');
    mobileMenu.classList.remove('open');
    menuOverlay.classList.remove('open');
    hamburgerBtn.setAttribute('aria-expanded', 'false');
    mobileMenu.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  if (hamburgerBtn) {
    hamburgerBtn.addEventListener('click', () =>
      mobileMenu.classList.contains('open') ? closeMenu() : openMenu()
    );
  }
  if (menuOverlay) menuOverlay.addEventListener('click', closeMenu);
  if (mobileMenu) mobileMenu.querySelectorAll('a').forEach(a => a.addEventListener('click', closeMenu));
  window.addEventListener('resize', () => { if (window.innerWidth > 640) closeMenu(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMenu(); });


  /* ══════════════════════════════════════════
     04. CART POPUP
  ══════════════════════════════════════════ */
  const cartPopup = document.getElementById('cart-popup');
  const closeCartBtn = document.getElementById('closeCart');
  const cartIconBtn = document.getElementById('cartIconBtn');
  const cartCountEl = document.getElementById('cartCount');
  const PENDING_CART_KEY = 'nb_pending_cart';

  function getCartCount(items) {
    return (items || []).reduce((sum, it) => sum + Number(it.quantity || 0), 0);
  }

  function getPendingCartItems() {
    try {
      const raw = localStorage.getItem(PENDING_CART_KEY);
      const parsed = raw ? JSON.parse(raw) : [];
      if (!Array.isArray(parsed)) return [];

      const normalized = parsed.reduce((items, item) => {
        const productId = Number(item?.product_id || 0);
        if (!productId) return items;

        const variantId = item?.product_variant_id ? Number(item.product_variant_id) : null;
        const existing = items.find(it => getPendingCartItemKey(it.product_id, it.product_variant_id) === getPendingCartItemKey(productId, variantId));
        const normalizedItem = {
          ...item,
          product_id: productId,
          product_variant_id: variantId,
          quantity: Number(item?.quantity || 1),
          unit_price: normalizePendingUnitPrice(item?.unit_price)
        };

        if (existing) {
          existing.quantity = Number(existing.quantity || 0) + normalizedItem.quantity;
          existing.product_variant_id = existing.product_variant_id || normalizedItem.product_variant_id;
          existing.product_name = existing.product_name || normalizedItem.product_name || 'Product';
          existing.variant_name = existing.variant_name || normalizedItem.variant_name || '';
          existing.image = existing.image || normalizedItem.image || '/img/product2.png';
          existing.unit_price = Number(existing.unit_price || 0) || normalizedItem.unit_price;
          existing.product_url = existing.product_url || normalizedItem.product_url || '/product';
        } else {
          items.push(normalizedItem);
        }

        return items;
      }, []);

      if (JSON.stringify(normalized) !== JSON.stringify(parsed)) {
        savePendingCartItems(normalized);
      }

      return normalized;
    } catch (_) {
      return [];
    }
  }

  function savePendingCartItems(items) {
    localStorage.setItem(PENDING_CART_KEY, JSON.stringify(items || []));
  }

  function getPendingCartItemKey(productId, productVariantId = null) {
    return `${Number(productId || 0)}::${Number(productVariantId || 0)}`;
  }

  function removePendingCartItem(productId, productVariantId = null) {
    const targetKey = getPendingCartItemKey(productId, productVariantId);
    savePendingCartItems(
      getPendingCartItems().filter(it => getPendingCartItemKey(it.product_id, it.product_variant_id) !== targetKey)
    );
  }

  function addPendingCartItem(productId, quantity = 1, productVariantId = null, meta = {}) {
    const current = getPendingCartItems();
    const found = current.find(it => getPendingCartItemKey(it.product_id, it.product_variant_id) === getPendingCartItemKey(productId, productVariantId));
    if (found) {
      found.quantity = Number(found.quantity || 0) + Number(quantity || 1);
      found.product_variant_id = found.product_variant_id || (productVariantId ? Number(productVariantId) : null);
      found.product_name = meta.product_name || found.product_name || 'Product';
      found.variant_name = meta.variant_name || found.variant_name || '';
      found.image = meta.image || found.image || '/img/product2.png';
      found.unit_price = Number(meta.unit_price ?? found.unit_price ?? 0);
      found.product_url = meta.product_url || found.product_url || '/product';
    } else {
      current.push({
        product_id: Number(productId),
        product_variant_id: productVariantId ? Number(productVariantId) : null,
        quantity: Number(quantity || 1),
        product_name: meta.product_name || 'Product',
        variant_name: meta.variant_name || '',
        image: meta.image || '/img/product2.png',
        unit_price: Number(meta.unit_price || 0),
        product_url: meta.product_url || '/product'
      });
    }
    savePendingCartItems(current);
  }

  function getPendingCartCount() {
    return getPendingCartItems().reduce((sum, it) => sum + Number(it.quantity || 0), 0);
  }

  function getPendingCartSubtotal() {
    return getPendingCartItems().reduce((sum, it) => sum + (Number(it.unit_price || 0) * Number(it.quantity || 0)), 0);
  }

  function updatePendingCartItemQuantity(productId, productVariantId = null, quantity = 1) {
    const targetKey = getPendingCartItemKey(productId, productVariantId);
    savePendingCartItems(
      getPendingCartItems().map(it => {
        if (getPendingCartItemKey(it.product_id, it.product_variant_id) !== targetKey) return it;
        return { ...it, quantity: Math.max(1, Number(quantity || 1)) };
      })
    );
  }

  function formatCartMoney(value, maximumFractionDigits = 0) {
    return `Rs. ${Number(value || 0).toLocaleString('en-IN', { maximumFractionDigits })}`;
  }

  function escapeCartText(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    }[char]));
  }

  function cartVariantLabel(item) {
    const variant = item?.product_variant || item?.productVariant || null;
    let attributes = variant?.attributes || {};
    if (typeof attributes === 'string') {
      try {
        attributes = JSON.parse(attributes);
      } catch (_) {
        attributes = attributes.trim() ? { Option: attributes } : {};
      }
    }
    const attributeParts = Array.isArray(attributes)
      ? attributes.filter(Boolean).map(value => String(value))
      : Object.entries(attributes)
        .filter(([, value]) => value !== null && value !== undefined && String(value).trim() !== '')
        .map(([name, value]) => `${name}: ${value}`);

    if (attributeParts.length) {
      return attributeParts.join(' / ');
    }

    const variantName = String(variant?.name || item?.variant_name || '').trim();
    if (variantName) return variantName;

    if (item?.product_variant_id) return `Option #${item.product_variant_id}`;

    return [
      item?.product?.flavor ? `Flavour: ${item.product.flavor}` : '',
      item?.product?.pack_size ? `Pack Size: ${item.product.pack_size}` : '',
      item?.product?.age_group ? `Age Group: ${item.product.age_group}` : '',
      item?.product?.dosage ? `Dosage: ${item.product.dosage}` : ''
    ].filter(Boolean).join(' / ');
  }

  function isAuthRedirectResponse(res) {
    const responseUrl = res?.url || '';
    const contentType = res?.headers?.get('content-type') || '';
    const isJsonResponse = contentType.toLowerCase().includes('application/json');
    const authPagePattern = /\/(?:login|signin)(?:[/?#]|$)/i;
    return res?.status === 401 ||
      res?.status === 403 ||
      res?.status === 419 ||
      (res?.redirected && authPagePattern.test(responseUrl)) ||
      (!isJsonResponse && authPagePattern.test(responseUrl));
  }

  function isJsonResponse(res) {
    return String(res?.headers?.get('content-type') || '').toLowerCase().includes('application/json');
  }

  function extractDisplayedPrice(value) {
    const text = String(value || '');
    const match = text.match(/\d[\d,]*(?:\.\d+)?/);
    if (!match) return 0;
    return Number(match[0].replace(/,/g, '')) || 0;
  }

  function normalizePendingUnitPrice(value) {
    const price = Number(value || 0);
    if (!Number.isFinite(price) || price <= 0) return 0;
    if (price < 100000) return price;

    const digits = String(Math.round(price)).replace(/\D/g, '');
    if (digits.length < 6) return price;

    const recovered = Number(digits.slice(0, Math.ceil(digits.length / 2))) || 0;
    return recovered > 0 ? recovered : price;
  }

  function normalizeCartImage(src) {
    if (!src) return '/img/product2.png';
    if (src.startsWith('http://') || src.startsWith('https://') || src.startsWith('/')) return src;
    return `/${src.replace(/^\/+/, '')}`;
  }

  function storageImageUrl(path) {
    if (!path) return '';
    const value = String(path);
    if (value.startsWith('http://') || value.startsWith('https://') || value.startsWith('/')) {
      return value;
    }
    return `/storage/${value.replace(/^\/+/, '')}`;
  }

  function getServerCartItemImage(item) {
    return normalizeCartImage(
      storageImageUrl(item?.product?.card_image_path) ||
      storageImageUrl(item?.product_variant?.image_path) ||
      storageImageUrl(item?.product?.primary_image?.image_path) ||
      storageImageUrl(item?.product?.images?.[0]?.image_path) ||
      '/img/product2.png'
    );
  }

  function clampQty(v, maxStock) {
    let qty = Math.max(1, parseInt(v, 10) || 1);
    if (maxStock != null && Number.isFinite(maxStock) && maxStock > 0) {
      qty = Math.min(qty, maxStock);
    }
    return qty;
  }

  function createPopupQuantityField(quantity, onCommit, maxStock) {
    const effectiveMax = (maxStock != null && Number.isFinite(maxStock) && maxStock > 0) ? maxStock : 999;
    const qty = clampQty(quantity, effectiveMax);
    const wrap = document.createElement('div');
    wrap.className = 'ci-qty-row';
    wrap.innerHTML = `
    <button type="button" class="qty-btn" data-qty-delta="-1" aria-label="Decrease quantity">&minus;</button>
    <input type="number" min="1" max="${effectiveMax}" class="qty-val" value="${qty}" aria-label="Quantity">
    <button type="button" class="qty-btn" data-qty-delta="1" aria-label="Increase quantity">+</button>
  `;

    const input = wrap.querySelector('.qty-val');
    let currentQty = qty;
    let committedQty = qty;   // tracks last value actually sent to onCommit
    let pendingQty = qty;
    let saveTimer = null;

    function setBusy(isBusy) {
      wrap.classList.toggle('is-updating', isBusy);
      wrap.querySelectorAll('.qty-btn, .qty-val').forEach(el => el.disabled = isBusy);
    }

    async function submit(nextVal, options = {}) {
      const next = clampQty(nextVal, effectiveMax);
      pendingQty = next;

      // Optimistic UI update
      input.value = next;
      currentQty = next;

      if (saveTimer) { clearTimeout(saveTimer); saveTimer = null; }

      // Skip if nothing changed from last committed value
      if (next === committedQty && !options.immediate) return;

      if (!options.immediate) {
        saveTimer = setTimeout(() => submit(pendingQty, { immediate: true }), 550);
        return;
      }

      // Nothing to commit if the value hasn't changed from what was last saved
      if (next === committedQty) return;

      const rollbackQty = committedQty;
      committedQty = next;

      try {
        await onCommit(next);
      } catch (err) {
        // Rollback on failure
        committedQty = rollbackQty;
        currentQty = rollbackQty;
        pendingQty = rollbackQty;
        input.value = rollbackQty;
      }
    }

    wrap.querySelectorAll('.qty-btn').forEach(btn => {
      btn.addEventListener('click', e => {
        e.stopPropagation();
        const delta = Number(btn.dataset.qtyDelta || 0);
        const nextVal = clampQty(input.value, effectiveMax) + delta;
        
        if (nextVal < 1) {
          const removeBtn = wrap.closest('.single-cart-box')?.querySelector('.cart-remove-btn');
          if (removeBtn) removeBtn.click();
          return;
        }
        
        submit(nextVal);
      });
    });

    input.addEventListener('change', () => submit(input.value));
    input.addEventListener('blur', () => submit(input.value, { immediate: true }));
    input.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); submit(input.value, { immediate: true }); } });

    return wrap;
  }

  function resolveCartItemMeta(productId, productVariantId = null, sourceEl = null) {
    const productPageName = document.querySelector('.pdp-name');
    const productPagePrice = document.querySelector('.price-now');
    const productPageImage = document.getElementById('mainPdpImage');
    const selectedVariant = document.querySelector('#pdpVariantSelected, .qty-opt.active .flavor-name, .qty-opt.active');

    if (productPageName && productPagePrice) {
      return {
        product_name: productPageName.textContent.trim(),
        variant_name: productVariantId ? (selectedVariant?.textContent || '').trim() : '',
        image: normalizeCartImage(productPageImage?.getAttribute('src') || ''),
        unit_price: extractDisplayedPrice(productPagePrice.textContent || ''),
        product_url: window.location.pathname
      };
    }

    const variantModal = sourceEl ? sourceEl.closest('.nb-variant-modal') : null;
    if (variantModal) {
      return {
        product_name: variantModal.dataset.productName || 'Product',
        variant_name: productVariantId ? (variantModal.dataset.selectedVariantLabel || '') : '',
        image: normalizeCartImage(variantModal.dataset.productImage || ''),
        unit_price: Number(variantModal.dataset.selectedVariantPrice || 0),
        product_url: variantModal.dataset.productUrl || '/product'
      };
    }

    const card = sourceEl ? sourceEl.closest('.pc') : null;
    const cardPriceNow = card?.querySelector('.pc-price-now');
    if (card) {
      const selectedPill = card.querySelector('.pc-selected-pill');
      const selectedLabel = (selectedPill?.textContent || card.dataset.selectedVariantLabel || '').trim();
      return {
        product_name: card.querySelector('.pc-name')?.textContent?.trim() || 'Product',
        variant_name: productVariantId ? selectedLabel : '',
        image: normalizeCartImage(card.querySelector('.default-img, .hover-img, img')?.getAttribute('src') || ''),
        unit_price: extractDisplayedPrice(cardPriceNow?.textContent || card.querySelector('.pc-price')?.textContent || ''),
        product_url: card.querySelector('.pc-name a, .pc-emoji')?.getAttribute('href') || '/product'
      };
    }

    return {
      product_name: 'Product',
      variant_name: '',
      image: '/img/product2.png',
      unit_price: 0,
      product_url: '/product'
    };
  }

  globalThis.resolveCartItemMeta = resolveCartItemMeta;

  if (cartCountEl && cartCountEl.dataset.guest !== 'false') {
    cartCountEl.textContent = String(getPendingCartCount());
  }

  const CART_COUNT_SYNC_TTL = 10000;
  let cartCountSyncPromise = null;
  let cartCountLastSyncedAt = 0;
  let cartPayloadCache = null;
  let cartPayloadCacheAt = 0;
  let cartFetchPromise = null;

  function invalidateCartPayloadCache() {
    cartPayloadCache = null;
    cartPayloadCacheAt = 0;
  }

  async function fetchCartPayload(options = {}) {
    const force = !!options.force;
    const now = Date.now();

    if (!force && cartPayloadCache && (now - cartPayloadCacheAt) < CART_COUNT_SYNC_TTL) {
      return cartPayloadCache;
    }

    if (cartFetchPromise) return cartFetchPromise;

    cartFetchPromise = (async () => {
      // Early return if we already know they are a guest from the HTML attribute
      // This prevents the browser from logging an unavoidable 401 Unauthorized error
      if (cartCountEl && cartCountEl.dataset.guest === 'true') {
        cartPayloadCache = { guest: true };
        cartPayloadCacheAt = Date.now();
        return cartPayloadCache;
      }

      let res = await fetch('/user/cart?t=' + Date.now(), { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
      if (isAuthRedirectResponse(res)) {
        cartPayloadCache = { guest: true };
        cartPayloadCacheAt = Date.now();
        return cartPayloadCache;
      }
      if (!res.ok || !isJsonResponse(res)) throw new Error('cart');

      if (getPendingCartItems().length) {
        const synced = await syncPendingCartToServer();
        if (synced) {
          res = await fetch('/user/cart?t=' + Date.now(), { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
          if (isAuthRedirectResponse(res)) {
            cartPayloadCache = { guest: true };
            cartPayloadCacheAt = Date.now();
            return cartPayloadCache;
          }
          if (!res.ok || !isJsonResponse(res)) throw new Error('cart');
        }
      }
      cartPayloadCache = await res.json().catch(() => ({}));
      cartPayloadCacheAt = Date.now();
      return cartPayloadCache;
    })();

    try {
      return await cartFetchPromise;
    } finally {
      cartFetchPromise = null;
    }
  }

  async function syncCartCount(options = {}) {
    if (!cartCountEl) return;
    const force = !!options.force;
    const now = Date.now();

    if (!force && cartCountSyncPromise) {
      return cartCountSyncPromise;
    }

    if (!force && cartCountLastSyncedAt && (now - cartCountLastSyncedAt) < CART_COUNT_SYNC_TTL) {
      return;
    }

    cartCountSyncPromise = (async () => {
      try {
        const payload = await fetchCartPayload({ force });
        if (payload?.guest) {
          cartCountEl.textContent = String(getPendingCartCount());
          cartCountLastSyncedAt = Date.now();
          return;
        }

        const items = payload.cart?.items || [];
        cartCountEl.textContent = String(getCartCount(items));
        cartCountLastSyncedAt = Date.now();
      } catch (_) { }
    })();

    try {
      return await cartCountSyncPromise;
    } finally {
      cartCountSyncPromise = null;
    }
  }

  async function syncPendingCartToServer() {
    const pendingItems = getPendingCartItems();
    if (!pendingItems.length) return false;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    for (const item of pendingItems) {
      const res = await fetch('/user/cart', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
        },
        body: JSON.stringify({
          product_id: item.product_id,
          product_variant_id: item.product_variant_id,
          quantity: item.quantity || 1,
        })
      }).catch(() => null);

      if (!res || !res.ok) return false;
    }

    localStorage.removeItem(PENDING_CART_KEY);
    return true;
  }
  window.syncPendingCartToServer = syncPendingCartToServer;

  async function updateServerCartItemQuantity(itemId, quantity) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const res = await fetch(`/user/cart/items/${itemId}`, {
      method: 'PATCH',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {})
      },
      body: JSON.stringify({ quantity: Math.max(1, Number(quantity || 1)) })
    }).catch(() => null);

    const ok = !!(res && res.ok);
    if (ok) invalidateCartPayloadCache();
    return ok;
  }

  function toggleCart() {
    if (!cartPopup) return;
    const willOpen = !cartPopup.classList.contains('open');
    cartPopup.classList.toggle('open');
    if (willOpen) {
      loadCartPopup({ force: true });
    }
  }

  if (cartIconBtn) {
    cartIconBtn.addEventListener('click', e => {
      e.stopPropagation();
      toggleCart();
      if (cartCountEl) {
        cartCountEl.style.animation = 'none';
        void cartCountEl.offsetWidth;
        cartCountEl.style.animation = 'cartPop .3s cubic-bezier(.34,1.56,.64,1)';
      }
    });
  }
  if (closeCartBtn) closeCartBtn.addEventListener('click', () => cartPopup?.classList.remove('open'));

  window.addEventListener('pageshow', () => syncCartCount());
  window.addEventListener('focus', () => syncCartCount());
  document.addEventListener('visibilitychange', () => { if (!document.hidden) syncCartCount(); });

  document.addEventListener('click', e => {
    if (cartPopup && cartPopup.classList.contains('open')) {
      const clickedCartButton = cartIconBtn && (e.target === cartIconBtn || cartIconBtn.contains(e.target));
      if (!cartPopup.contains(e.target) && !clickedCartButton) {
        cartPopup.classList.remove('open');
      }
    }
  });

  async function loadCartPopup(options = {}) {
    if (!cartPopup || !cartPopup.classList.contains('open')) return;
    const itemsWrap = document.getElementById('cartPopupItems');
    const countEl = document.getElementById('cartPopupCount');
    const subtotalEl = document.getElementById('cartPopupSubtotal');
    if (!itemsWrap || !countEl || !subtotalEl) return;

    itemsWrap.innerHTML = '<div style="padding:10px;color:#888;font-size:.9rem;">Loading...</div>';

    try {
      const payload = await fetchCartPayload({ force: !!options.force });

      if (payload?.guest) {
        /* ── Guest / pending cart ── */
        const pendingItems = getPendingCartItems();
        const pendingCount = getPendingCartCount();
        if (cartCountEl) cartCountEl.textContent = String(pendingCount);
        countEl.textContent = String(pendingCount);
        subtotalEl.textContent = formatCartMoney(getPendingCartSubtotal());

        if (!pendingItems.length) {
          itemsWrap.innerHTML = '<div style="padding:10px;color:#888;font-size:.9rem;">Your cart is empty.</div>';
          return;
        }

        itemsWrap.innerHTML = '';
        const guestFrag = document.createDocumentFragment();
        pendingItems.forEach(it => {
          const qty = Number(it.quantity || 1);
          const variantLabel = cartVariantLabel(it);
          const row = document.createElement('div');
          row.className = 'single-cart-box';
          row.innerHTML = `
          <div class="image-box"><img src="${escapeCartText(normalizeCartImage(it.image))}" alt=""></div>
          <div class="cart-popup-content">
            <h5>${escapeCartText(it.product_name || 'Product')}</h5>
            ${variantLabel ? `<div class="cart-popup-variant">${escapeCartText(variantLabel)}</div>` : ''}
            <h4>${formatCartMoney(it.unit_price * qty)}</h4>
          </div>
          <button type="button" class="cart-remove-btn" aria-label="Remove">&times;</button>
        `;
          const maxStock = it.max_stock !== undefined ? it.max_stock : 999;
          const content = row.querySelector('.cart-popup-content');
          content.appendChild(createPopupQuantityField(qty, async value => {
            updatePendingCartItemQuantity(it.product_id, it.product_variant_id, value);
            invalidateCartPayloadCache();
            if (cartCountEl) cartCountEl.textContent = String(getPendingCartCount());
            loadCartPopup({ force: true });
          }, maxStock));
          row.querySelector('.cart-remove-btn').addEventListener('click', e => {
            e.stopPropagation();
            row.remove(); // Optimistic remove
            removePendingCartItem(it.product_id, it.product_variant_id);
            invalidateCartPayloadCache();
            if (cartCountEl) cartCountEl.textContent = String(getPendingCartCount());
            loadCartPopup({ force: true });
          });
          guestFrag.appendChild(row);
        });
        itemsWrap.appendChild(guestFrag);
        return;
      }


      /* ── Authenticated cart ── */
      const items = payload.cart?.items || [];
      const subtotal = payload.pricing?.display_subtotal || 0;
      const count = getCartCount(items);

      if (cartCountEl) cartCountEl.textContent = String(count);
      countEl.textContent = String(count);
      subtotalEl.textContent = formatCartMoney(subtotal);

      if (!items.length) {
        itemsWrap.innerHTML = '<div style="padding:10px;color:#888;font-size:.9rem;">Your cart is empty.</div>';
        return;
      }

      itemsWrap.innerHTML = '';
      const authFrag = document.createDocumentFragment();
      items.forEach(it => {
        const name = it.product?.name || 'Product';
        const qty = Number(it.quantity || 1);
        const price = it.product_variant ? it.product_variant.display_price : it.product?.display_price;
        const img = getServerCartItemImage(it);
        const variantLabel = cartVariantLabel(it);
        const lineItem = payload.pricing?.line_items?.find(l => l.cart_item?.id === it.id);
        const displayPrice = lineItem ? lineItem.display_line_total : (price * qty);

        const row = document.createElement('div');
        row.className = 'single-cart-box';
        row.innerHTML = `
        <div class="image-box"><img src="${escapeCartText(img)}" alt=""></div>
        <div class="cart-popup-content">
          <h5>${escapeCartText(name)}</h5>
          ${variantLabel ? `<div class="cart-popup-variant">${escapeCartText(variantLabel)}</div>` : ''}
          <h4>${formatCartMoney(displayPrice)}</h4>
        </div>
        <button type="button" class="cart-remove-btn" aria-label="Remove">&times;</button>
      `;
        const content = row.querySelector('.cart-popup-content');
        const itemMaxStock = it.available_stock != null ? Number(it.available_stock) : null;
        content.appendChild(createPopupQuantityField(qty, async value => {
          row.classList.add('is-updating');
          try {
            const ok = await updateServerCartItemQuantity(it.id, value);
            if (!ok) throw new Error('Update failed');
            loadCartPopup({ force: true });
          } finally {
            row.classList.remove('is-updating');
          }
        }, itemMaxStock));
        row.querySelector('.cart-remove-btn').addEventListener('click', async e => {
          e.stopPropagation();
          row.remove(); // Optimistic remove
          const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
          await fetch(`/user/cart/items/${it.id}`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}) }
          }).catch(() => null);
          invalidateCartPayloadCache();
          syncCartCount({ force: true });
          loadCartPopup({ force: true });
        });
        authFrag.appendChild(row);
      });
      itemsWrap.appendChild(authFrag);

    } catch (_) {
      itemsWrap.innerHTML = '<div style="padding:10px;color:#888;font-size:.9rem;">Unable to load cart.</div>';
    }
  }

  /* ══════════════════════════════════════════
     PRODUCT GALLERY
  ══════════════════════════════════════════ */
  const thumbs = document.querySelectorAll('.thumb');
  const mainImgWrap = document.querySelector('.main-img-wrap .p-image');

  thumbs.forEach(thumb => {
    thumb.addEventListener('click', () => {
      thumbs.forEach(t => t.classList.remove('active'));
      thumb.classList.add('active');
      const thumbImg = thumb.querySelector('img');
      if (thumbImg && mainImgWrap) {
        const mainImage = mainImgWrap.querySelector('img');
        if (mainImage) mainImage.src = thumbImg.src;
      }
    });
  });


  /* ══════════════════════════════════════════
     05. HERO SLIDER + SPARKS
  ══════════════════════════════════════════ */
  let currentSlide = 0;
  let sliderTimer;
  const slides = document.querySelectorAll('.slide');
  const dots = document.querySelectorAll('.dot');
  const currentSlideLabel = document.getElementById('curSlide');

  function goTo(n) {
    if (!slides.length) return;
    slides[currentSlide].classList.remove('active');
    if (dots[currentSlide]) dots[currentSlide].classList.remove('active');
    currentSlide = (n + slides.length) % slides.length;
    slides[currentSlide].classList.add('active');
    if (dots[currentSlide]) dots[currentSlide].classList.add('active');
    if (currentSlideLabel) currentSlideLabel.textContent = currentSlide + 1;
    spawnSparks();
  }
  function startTimer() {
    clearInterval(sliderTimer);
    sliderTimer = setInterval(() => goTo(currentSlide + 1), 500000);
  }

  const prevBtn = document.getElementById('prevBtn');
  const nextBtn = document.getElementById('nextBtn');
  if (prevBtn) prevBtn.addEventListener('click', () => { goTo(currentSlide - 1); startTimer(); });
  if (nextBtn) nextBtn.addEventListener('click', () => { goTo(currentSlide + 1); startTimer(); });
  dots.forEach(d => d.addEventListener('click', () => { goTo(+d.dataset.dot); startTimer(); }));
  startTimer();

  /* WHY CHOOSE US SLIDER */
  (function () {
    const track = document.getElementById('whySliderTrack');
    const viewport = document.getElementById('whySliderViewport');
    const dotsWrap = document.getElementById('whySliderDots');
    const prev = document.getElementById('whySliderPrev');
    const next = document.getElementById('whySliderNext');
    if (!track || !viewport) return;

    const cards = Array.from(track.querySelectorAll('.wc'));
    const GAP = 22;
    let current = 0;

    function isMobileSlider() {
      return window.matchMedia('(max-width: 767px)').matches;
    }

    function perView() {
      return isMobileSlider() ? 1 : cards.length;
    }

    function maxIndex() {
      return Math.max(0, cards.length - perView());
    }

    function cardWidth() {
      const visible = perView();
      return (viewport.offsetWidth - GAP * (visible - 1)) / visible;
    }

    function buildDots() {
      if (!dotsWrap) return;
      dotsWrap.innerHTML = '';
      if (!isMobileSlider()) return;
      for (let i = 0; i <= maxIndex(); i++) {
        const dot = document.createElement('button');
        dot.type = 'button';
        dot.className = 'why-slider-dot';
        dot.setAttribute('aria-label', 'Show reason ' + (i + 1));
        dot.addEventListener('click', () => goToWhy(i));
        dotsWrap.appendChild(dot);
      }
    }

    function render() {
      if (!isMobileSlider()) {
        current = 0;
        track.style.transform = '';
        cards.forEach(card => {
          card.style.flexBasis = '';
          card.style.maxWidth = '';
        });
        if (prev) prev.disabled = true;
        if (next) next.disabled = true;
        return;
      }

      const width = cardWidth();
      cards.forEach(card => {
        card.style.flexBasis = width + 'px';
        card.style.maxWidth = width + 'px';
      });

      current = Math.max(0, Math.min(current, maxIndex()));
      track.style.transform = 'translateX(-' + current * (width + GAP) + 'px)';

      if (prev) prev.disabled = current === 0;
      if (next) next.disabled = current === maxIndex();
      if (dotsWrap) {
        dotsWrap.querySelectorAll('.why-slider-dot').forEach((dot, index) => {
          dot.classList.toggle('active', index === current);
        });
      }
    }

    function goToWhy(index) {
      current = Math.max(0, Math.min(index, maxIndex()));
      render();
    }

    if (prev) prev.addEventListener('click', () => goToWhy(current - 1));
    if (next) next.addEventListener('click', () => goToWhy(current + 1));

    let touchStartX = 0;
    viewport.addEventListener('touchstart', e => {
      touchStartX = e.touches[0].clientX;
    }, { passive: true });
    viewport.addEventListener('touchend', e => {
      const dx = touchStartX - e.changedTouches[0].clientX;
      if (Math.abs(dx) > 40) goToWhy(dx > 0 ? current + 1 : current - 1);
    });

    let resizeTimer;
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(() => {
        buildDots();
        render();
      }, 120);
    });

    buildDots();
    render();
  })();

  function spawnSparks() {
    const colors = ['#FF4D8F', '#FFD600', '#00D68F'];
    for (let i = 0; i < 12; i++) {
      const sp = document.createElement('div');
      sp.style.cssText = `
      left:${20 + Math.random() * 60}%;
      top:${30 + Math.random() * 40}%;
      background:${colors[i % 3]};
      width:${6 + Math.random() * 8}px;
      height:${6 + Math.random() * 8}px;
      position:fixed; z-index:9999; border-radius:50%;
      pointer-events:none;
      animation:sparkUp ${.5 + Math.random() * .8}s ease forwards;
      animation-delay:${Math.random() * .2}s
    `;
      document.body.appendChild(sp);
      setTimeout(() => sp.remove(), 1200);
    }
  }



  const ks = document.createElement('style');
  ks.textContent = '@keyframes sparkUp{0%{opacity:1;transform:translateY(0) scale(1) rotate(0deg)}100%{opacity:0;transform:translateY(-80px) scale(0) rotate(180deg)}}';
  document.head.appendChild(ks);


  /* ══════════════════════════════════════════
     06. STARS BACKGROUND (Ingredients)
  ══════════════════════════════════════════ */
  const sb = document.getElementById('starsBg');
  if (sb) {
    for (let i = 0; i < 140; i++) {
      const s = document.createElement('div');
      s.className = 'sdot';
      const sz = Math.random() * 2.5 + .8;
      s.style.cssText = `width:${sz}px;height:${sz}px;left:${Math.random() * 100}%;top:${Math.random() * 100}%;--dur:${2 + Math.random() * 4}s;--del:${Math.random() * 5}s`;
      sb.appendChild(s);
    }
  }


  /* ══════════════════════════════════════════
     07. INGREDIENT TABS
  ══════════════════════════════════════════ */
  document.querySelectorAll('.itab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.itab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.ing-panel').forEach(p => p.classList.remove('active'));
      tab.classList.add('active');
      const panel = document.getElementById('ing-panel-' + tab.dataset.ing);
      if (panel) panel.classList.add('active');
    });
  });


  /* ══════════════════════════════════════════
     08. ADD TO CART BUTTONS
  ══════════════════════════════════════════ */
  const variantModal = document.getElementById('nbVariantModal');
  const variantModalGroups = document.getElementById('nbVariantModalGroups');
  const variantModalImage = document.getElementById('nbVariantModalImage');
  const variantModalName = document.getElementById('nbVariantModalName');
  const variantModalSelected = document.getElementById('nbVariantModalSelected');
  const variantModalPrice = document.getElementById('nbVariantModalPrice');
  const variantModalStock = document.getElementById('nbVariantModalStock');
  const variantModalAdd = document.getElementById('nbVariantModalAdd');
  let activeVariantModalCard = null;
  let activeVariantModalTrigger = null;
  let activeVariantModalVariants = [];
  let activeVariantModalAttrs = {};
  let activeVariantModalSelection = null;
  let variantModalAddInFlight = false;

  function parseProductCardVariants(card) {
    if (!card) return [];
    try {
      const parsed = JSON.parse(card.dataset.variants || '[]');
      return Array.isArray(parsed) ? parsed : [];
    } catch (_) {
      return [];
    }
  }

  function formatVariantMoney(value) {
    return 'Rs. ' + Number(value || 0).toLocaleString('en-IN', { maximumFractionDigits: 0 });
  }

  function productCardVariantLabel(variant) {
    const attributes = variant?.attributes || {};
    const parts = Array.isArray(attributes)
      ? attributes.filter(Boolean).map(value => String(value))
      : Object.entries(attributes)
        .filter(([, value]) => value !== null && value !== undefined && String(value).trim() !== '')
        .map(([name, value]) => `${name}: ${value}`);

    return parts.join(' / ') || variant?.name || 'Product option';
  }

  function buildVariantAttributeGroups(variants) {
    return variants.reduce((groups, variant) => {
      Object.entries(variant?.attributes || {}).forEach(([name, value]) => {
        const cleanValue = String(value ?? '').trim();
        if (!name || !cleanValue) return;
        groups[name] ||= [];
        if (!groups[name].includes(cleanValue)) groups[name].push(cleanValue);
      });
      return groups;
    }, {});
  }

  function findCardVariantByAttributes(variants, selectedAttrs) {
    const keys = Object.keys(selectedAttrs || {});
    if (!keys.length) return variants.find(variant => variant.available) || variants[0] || null;
    return variants.find(variant => keys.every(key => String(variant.attributes?.[key] ?? '') === String(selectedAttrs[key] ?? ''))) || null;
  }

  function updateVariantModalAvailability() {
    if (!variantModalGroups) return;
    variantModalGroups.querySelectorAll('.nb-variant-modal__option').forEach(button => {
      const attr = button.dataset.attribute;
      const value = button.dataset.value;
      const trial = { ...activeVariantModalAttrs, [attr]: value };
      const isPossible = activeVariantModalVariants.some(variant => {
        return Object.keys(trial).every(key => String(variant.attributes?.[key] ?? '') === String(trial[key] ?? ''));
      });
      button.disabled = variantModalAddInFlight || !isPossible;
      button.classList.toggle('is-active', String(activeVariantModalAttrs[attr] ?? '') === String(value));
    });
  }

  function applyVariantModalSelection(variant) {
    activeVariantModalSelection = variant;
    const label = productCardVariantLabel(variant);
    const price = Number(variant?.price || 0);
    const comparePrice = Number(variant?.compare_price || 0);
    const isAvailable = !!variant?.available;

    if (variantModalSelected) variantModalSelected.textContent = label;
    if (variantModalPrice) {
      variantModalPrice.innerHTML = comparePrice > price
        ? `${formatVariantMoney(price)} <s>${formatVariantMoney(comparePrice)}</s>`
        : formatVariantMoney(price);
    }
    if (variantModalStock) {
      variantModalStock.textContent = isAvailable ? 'Available' : 'Out of stock';
      variantModalStock.classList.toggle('out', !isAvailable);
    }
    if (variantModalAdd) {
      if (variantModalAddInFlight) {
        variantModalAdd.disabled = true;
        variantModalAdd.textContent = 'Adding...';
      } else {
        variantModalAdd.disabled = !variant || !isAvailable;
        variantModalAdd.textContent = isAvailable ? 'Add to Cart' : 'Out of Stock';
      }
    }
    if (variantModal) {
      variantModal.dataset.selectedVariantId = variant?.id || '';
      variantModal.dataset.selectedVariantLabel = label;
      variantModal.dataset.selectedVariantPrice = String(price || 0);
    }
  }

  function renderVariantModalGroups(groups) {
    if (!variantModalGroups) return;
    variantModalGroups.innerHTML = '';

    if (activeVariantModalVariants.length <= 1) return;

    Object.entries(groups).forEach(([attr, values]) => {
      const block = document.createElement('div');
      block.className = 'nb-variant-modal__group';
      block.innerHTML = `<div class="nb-variant-modal__label">${escapeCartText(attr)}</div><div class="nb-variant-modal__row"></div>`;
      const row = block.querySelector('.nb-variant-modal__row');

      values.forEach(value => {
        const option = document.createElement('button');
        option.type = 'button';
        option.className = 'nb-variant-modal__option';
        option.dataset.attribute = attr;
        option.dataset.value = value;
        option.textContent = value;
        option.addEventListener('click', () => {
          if (variantModalAddInFlight) return;
          activeVariantModalAttrs[attr] = value;
          updateVariantModalAvailability();
          applyVariantModalSelection(findCardVariantByAttributes(activeVariantModalVariants, activeVariantModalAttrs));
        });
        row.appendChild(option);
      });

      variantModalGroups.appendChild(block);
    });
  }

  function openVariantModal(card, triggerBtn) {
    if (!variantModal || !variantModalAdd) return false;
    const variants = parseProductCardVariants(card);
    if (!variants.length) return false;

    activeVariantModalCard = card;
    activeVariantModalTrigger = triggerBtn;
    activeVariantModalVariants = variants;
    variantModalAddInFlight = false;

    const currentVariantId = triggerBtn?.dataset.variantId || card?.dataset.selectedVariantId || '';
    const defaultVariant = variants.find(variant => String(variant.id) === String(currentVariantId)) ||
      variants.find(variant => variant.available) ||
      variants[0];
    activeVariantModalAttrs = { ...(defaultVariant?.attributes || {}) };

    const productName = card.querySelector('.pc-name')?.textContent?.trim() || 'Product';
    const productImage = card.querySelector('.default-img, .hover-img, img')?.getAttribute('src') || '/img/product2.png';
    const productUrl = card.querySelector('.pc-name a, .pc-emoji')?.getAttribute('href') || '/product';
    if (variantModalName) variantModalName.textContent = productName;
    if (variantModalImage) {
      variantModalImage.src = productImage;
      variantModalImage.alt = productName;
    }

    variantModal.dataset.productId = triggerBtn?.getAttribute('data-id') || '';
    variantModal.dataset.productName = productName;
    variantModal.dataset.productImage = productImage;
    variantModal.dataset.productUrl = productUrl;

    renderVariantModalGroups(buildVariantAttributeGroups(variants));
    updateVariantModalAvailability();
    applyVariantModalSelection(defaultVariant);

    variantModal.classList.add('open');
    variantModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    return true;
  }

  function closeVariantModal() {
    if (!variantModal) return;
    variantModalAddInFlight = false;
    variantModal.classList.remove('open');
    variantModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    activeVariantModalCard = null;
    activeVariantModalTrigger = null;
    activeVariantModalVariants = [];
    activeVariantModalAttrs = {};
    activeVariantModalSelection = null;
    if (variantModalAdd) {
      variantModalAdd.disabled = false;
      variantModalAdd.textContent = 'Add to Cart';
    }
  }

  document.querySelectorAll('[data-nb-variant-close]').forEach(closeBtn => {
    closeBtn.addEventListener('click', closeVariantModal);
  });

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && variantModal?.classList.contains('open')) closeVariantModal();
  });

  if (variantModalAdd) {
    variantModalAdd.addEventListener('click', async () => {
      const productId = variantModal?.dataset.productId || activeVariantModalTrigger?.getAttribute('data-id');
      const variant = activeVariantModalSelection;
      if (!productId || !variant?.id || variantModalAdd.disabled || variantModalAddInFlight) return;

      variantModalAddInFlight = true;
      updateVariantModalAvailability();
      applyVariantModalSelection(variant);

      const added = await addToCart(productId, 1, variant.id, variantModalAdd);
      if (!added) {
        variantModalAddInFlight = false;
        updateVariantModalAvailability();
        applyVariantModalSelection(activeVariantModalSelection);
        return;
      }

      if (activeVariantModalCard) {
        activeVariantModalCard.dataset.selectedVariantId = String(variant.id);
        activeVariantModalCard.dataset.selectedVariantLabel = productCardVariantLabel(variant);
      }
      if (activeVariantModalTrigger) activeVariantModalTrigger.dataset.variantId = String(variant.id);

      variantModalAdd.textContent = 'Added';
      setTimeout(closeVariantModal, 450);
    });
  }

  document.querySelectorAll('.btn-add').forEach(btn => {
    btn.addEventListener('click', async () => {
      const productId = btn.getAttribute('data-id');
      if (!productId || btn.disabled) return;

      const card = btn.closest('.product-filter-card, .pc');
      const requiresVariant = card?.classList.contains('has-variants');
      if (requiresVariant && parseProductCardVariants(card).length) {
        openVariantModal(card, btn);
        return;
      }

      // Store the true original label once — prevents "Adding..." snapshot bug
      if (!btn.dataset.origHtml) btn.dataset.origHtml = btn.innerHTML;
      const origHtml = btn.dataset.origHtml;

      btn.disabled = true;
      btn.innerHTML = 'Adding...';

      const variantId = btn.dataset.variantId || card?.dataset.selectedVariantId || null;
      if (requiresVariant && !variantId) {
        if (typeof nbToast === 'function') nbToast('Please choose a product option first.', 'warning');
        btn.innerHTML = origHtml;
        btn.disabled = false;
        return;
      }

      const added = await addToCart(productId, 1, variantId, btn);
      if (!added) {
        btn.innerHTML = origHtml;
        btn.disabled = false;
        return;
      }

      btn.classList.add('added');
      btn.innerHTML = 'Added ✓';
      setTimeout(() => {
        btn.classList.remove('added');
        btn.innerHTML = origHtml;
        btn.disabled = false;
      }, 1200);
    });
  });


  /* ══════════════════════════════════════════
     09. SCROLL REVEAL
  ══════════════════════════════════════════ */
  const revObs = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('visible');
        revObs.unobserve(e.target);
      }
    });
  }, { threshold: .1 });
  document.querySelectorAll('.reveal').forEach(r => revObs.observe(r));


  /* ══════════════════════════════════════════
     10. COUNTER ANIMATION
  ══════════════════════════════════════════ */
  const countObs = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (!e.isIntersecting) return;
      const el = e.target;
      const target = +el.dataset.count;
      const suffix = el.textContent.includes('%') ? '%' : '+';
      let start = 0;
      const dur = 1800, step = dur / 60, inc = target / 60;
      const iv = setInterval(() => {
        start = Math.min(start + inc, target);
        el.textContent = Math.round(start).toLocaleString('en-IN') + (start >= target ? suffix : '');
        if (start >= target) clearInterval(iv);
      }, step);
      countObs.unobserve(el);
    });
  }, { threshold: .5 });
  document.querySelectorAll('[data-count]').forEach(el => countObs.observe(el));


  /* ══════════════════════════════════════════
     11. GSAP ANIMATIONS
  ══════════════════════════════════════════ */
  if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') {
    gsap.registerPlugin(ScrollTrigger);

    if (document.querySelector('.pc')) {
      gsap.utils.toArray('.pc').forEach((c, i) => {
        gsap.from(c, { y: 70, opacity: 0, rotation: -4, duration: .9, delay: i * .15, ease: 'back.out(1.5)', scrollTrigger: { trigger: c, start: 'top 88%' } });
      });
    }
    if (document.querySelector('.wc')) {
      gsap.utils.toArray('.wc').forEach((c, i) => {
        gsap.from(c, { y: 50, opacity: 0, duration: .75, delay: i * .1, ease: 'power3.out', scrollTrigger: { trigger: c, start: 'top 88%' } });
      });
    }
    if (document.querySelector('.tc')) {
      gsap.utils.toArray('.tc').forEach((c, i) => {
        gsap.from(c, { y: 40, opacity: 0, scale: .92, duration: .7, delay: i * .08, ease: 'back.out(1.3)', scrollTrigger: { trigger: c, start: 'top 90%' } });
      });
    }
    if (document.querySelector('.step')) {
      gsap.utils.toArray('.step').forEach((s, i) => {
        gsap.from(s, { x: -40, opacity: 0, duration: .7, delay: i * .18, ease: 'power3.out', scrollTrigger: { trigger: s, start: 'top 88%' } });
      });
    }
    if (document.querySelector('.wrev')) {
      gsap.utils.toArray('.wrev').forEach((r, i) => {
        gsap.from(r, { y: 40, opacity: 0, duration: .65, delay: i * .12, ease: 'power3.out', scrollTrigger: { trigger: r, start: 'top 88%' } });
      });
    }

    if (document.querySelector('.ing-planet')) {
      document.querySelectorAll('.ing-planet').forEach((p, i) => {
        gsap.to(p, {
          boxShadow: `0 0 120px var(--pglow,rgba(0,214,143,.5)),inset 0 0 40px rgba(255,255,255,.06)`,
          duration: 2, repeat: -1, yoyo: true, ease: 'sine.inOut', delay: i * .5
        });
      });
    }

    if (document.querySelector('.sball')) {
      document.querySelectorAll('.sball').forEach(b => {
        b.addEventListener('mouseenter', () => gsap.to(b, { rotation: 20, scale: 1.18, duration: .35, ease: 'back.out(2)' }));
        b.addEventListener('mouseleave', () => gsap.to(b, { rotation: 0, scale: 1, duration: .45, ease: 'elastic.out(1,.5)' }));
      });
    }

    if (document.querySelector('.imgcar-item') && document.getElementById('certificates')) {
      gsap.from('.imgcar-item', {
        y: 50, opacity: 0, scale: .92,
        stagger: .1, duration: .75,
        ease: 'back.out(1.4)',
        scrollTrigger: { trigger: '#certificates', start: 'top 82%' }
      });
    }
  }


  /* ══════════════════════════════════════════
     12. VIDEO REELS — slider with prev/next buttons
  ══════════════════════════════════════════ */
  (function () {
    const track = document.getElementById('reelsRow');
    const viewport = document.getElementById('reelsViewport');
    const btnPrev = document.getElementById('reelPrev');
    const btnNext = document.getElementById('reelNext');
    const reelDots = Array.from(document.querySelectorAll('.reels-dot'));
    if (!track || !viewport) return;

    const cards = Array.from(track.querySelectorAll('.reel'));
    const GAP = 16;
    let current = 0;
    let activeReel = -1;
    let reelTimers = {};

    cards.forEach(card => {
      const video = card.querySelector('video');
      if (!video) return;

      video.muted = true;
      video.playsInline = true;
      video.load();

      video.addEventListener('loadeddata', () => {
        card.classList.add('is-video-ready');
      }, { once: true });
    });

    function perView() {
      const vw = viewport.offsetWidth;
      const cw = cards[0] ? cards[0].offsetWidth : 200;
      return Math.max(1, Math.floor((vw + GAP) / (cw + GAP)));
    }

    function maxIndex() {
      return Math.max(0, cards.length - perView());
    }

    function updateReelDots() {
      const max = maxIndex();
      reelDots.forEach((d, i) => {
        d.style.display = i <= max ? '' : 'none';
        d.classList.toggle('active', i === current);
      });
    }

    function goToReel(idx) {
      current = Math.max(0, Math.min(idx, maxIndex()));
      const cw = cards[0] ? cards[0].offsetWidth : 200;
      const offset = current * (cw + GAP);
      track.style.transform = 'translateX(-' + offset + 'px)';
      updateReelDots();
      if (btnPrev) btnPrev.disabled = current === 0;
      if (btnNext) btnNext.disabled = current >= maxIndex();
    }

    if (btnPrev) btnPrev.addEventListener('click', () => goToReel(current - 1));
    if (btnNext) btnNext.addEventListener('click', () => goToReel(current + 1));
    reelDots.forEach(d => d.addEventListener('click', () => goToReel(+d.dataset.index)));

    function refresh() { goToReel(Math.min(current, maxIndex())); }
    window.addEventListener('load', refresh);
    document.addEventListener('DOMContentLoaded', refresh);
    refresh();

    let touchStartX = 0;
    viewport.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
    viewport.addEventListener('touchend', e => {
      const dx = touchStartX - e.changedTouches[0].clientX;
      if (Math.abs(dx) > 40) goToReel(dx > 0 ? current + 1 : current - 1);
    });

    let resizeTimer;
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(refresh, 120);
    });

    function getReel(idx) {
      return track.querySelector('.reel[data-reel="' + idx + '"]');
    }

    function setReelButton(card, isPlaying) {
      const btn = card ? card.querySelector('.reel-play-btn') : null;
      if (btn) btn.textContent = isPlaying ? 'II' : '▶';
    }

    function playReel(idx, keepUnmuted = false) {
      stopAllReels();
      const card = getReel(idx);
      if (!card) return;

      const video = card.querySelector('video');
      const bar = card.querySelector('.reel-bar');
      const muteBtn = card.querySelector('.reel-mute-btn');

      activeReel = idx;
      if (bar) { bar.style.transition = 'width 8s linear'; bar.style.width = '100%'; }
      setReelButton(card, true);

      if (video) {
        if (!keepUnmuted) {
            video.muted = true;
            if (muteBtn) muteBtn.textContent = '🔇';
        }
        const playPromise = video.play();
        if (playPromise && typeof playPromise.catch === 'function') {
          playPromise.catch(() => stopReel(idx));
        }
      }

      reelTimers[idx] = setTimeout(() => {
        stopReel(idx);
        playReel((idx + 1) % cards.length);
      }, 8000);
    }

    function stopReel(idx) {
      const card = getReel(idx);
      if (!card) return;

      const video = card.querySelector('video');
      const bar = card.querySelector('.reel-bar');

      if (video) video.pause();
      if (bar) { bar.style.transition = 'none'; bar.style.width = '0%'; }
      setReelButton(card, false);
      clearTimeout(reelTimers[idx]);
      if (activeReel === idx) activeReel = -1;
    }

    function stopAllReels() { cards.forEach(r => stopReel(+r.dataset.reel)); }

    cards.forEach(r => {
      r.addEventListener('click', () => {
        const i = +r.dataset.reel;
        const video = r.querySelector('video');
        if (activeReel === i) {
            stopReel(i);
        } else {
            playReel(i, video && !video.muted);
        }
      });
    });

    cards.forEach(r => {
        const muteBtn = r.querySelector('.reel-mute-btn');
        if (muteBtn) {
            muteBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const video = r.querySelector('video');
                if (video) {
                    video.muted = !video.muted;
                    muteBtn.textContent = video.muted ? '🔇' : '🔊';
                }
            });
        }
    });

    const rvObs = new IntersectionObserver(entries => {
      if (entries[0].isIntersecting && activeReel === -1) {
        setTimeout(() => playReel(0), 700);
        rvObs.disconnect();
      }
    }, { threshold: .3 });
    const testiSection = document.querySelector('.testi-section');
    if (testiSection) rvObs.observe(testiSection);

    goToReel(0);
  })();


  /* ══════════════════════════════════════════
     12b. TEXT REVIEWS SLIDER
  ══════════════════════════════════════════ */
  (function () {
    const track = document.getElementById('wreviewsTrack');
    const viewport = document.getElementById('wreviewsViewport');
    const btnPrev = document.getElementById('wrevPrev');
    const btnNext = document.getElementById('wrevNext');
    const dotsContainer = document.getElementById('wreviewsDots');

    if (!track || !viewport) return;

    const cards = Array.from(track.querySelectorAll('.wrev'));
    if (cards.length === 0) return;

    const GAP = 22;
    let current = 0;

    function perView() {
      if (window.innerWidth <= 576) return 1;
      if (window.innerWidth <= 991) return 2;
      return 3;
    }

    function maxIndex() {
      return Math.max(0, cards.length - perView());
    }

    function buildDots() {
      if (!dotsContainer) return;
      dotsContainer.innerHTML = '';
      const max = maxIndex();
      
      if (max <= 0) {
        if (btnPrev) btnPrev.style.display = 'none';
        if (btnNext) btnNext.style.display = 'none';
        return;
      } else {
        if (btnPrev) btnPrev.style.display = '';
        if (btnNext) btnNext.style.display = '';
      }

      const totalDots = max + 1;
      for (let i = 0; i < totalDots; i++) {
        const dot = document.createElement('button');
        dot.className = 'wdot' + (i === current ? ' active' : '');
        dot.dataset.index = i;
        dot.setAttribute('aria-label', 'Go to review page ' + (i + 1));
        dot.addEventListener('click', () => goTo(i));
        dotsContainer.appendChild(dot);
      }
    }

    function updateDots() {
      if (!dotsContainer) return;
      const dots = Array.from(dotsContainer.querySelectorAll('.wdot'));
      dots.forEach((d, i) => {
        d.classList.toggle('active', i === current);
      });
    }

    function goTo(idx) {
      current = Math.max(0, Math.min(idx, maxIndex()));
      const cw = cards[0].offsetWidth;
      const offset = current * (cw + GAP);
      track.style.transform = 'translateX(-' + offset + 'px)';
      updateDots();

      if (btnPrev) btnPrev.disabled = current === 0;
      if (btnNext) btnNext.disabled = current >= maxIndex();
    }

    if (btnPrev) btnPrev.addEventListener('click', () => goTo(current - 1));
    if (btnNext) btnNext.addEventListener('click', () => goTo(current + 1));

    function refresh() {
      buildDots();
      goTo(Math.min(current, maxIndex()));
    }

    window.addEventListener('load', refresh);
    document.addEventListener('DOMContentLoaded', refresh);
    refresh();

    let touchStartX = 0;
    viewport.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
    viewport.addEventListener('touchend', e => {
      const dx = touchStartX - e.changedTouches[0].clientX;
      if (Math.abs(dx) > 40) goTo(dx > 0 ? current + 1 : current - 1);
    });

    let resizeTimer;
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(refresh, 120);
    });
  })();


  /* ══════════════════════════════════════════
     13. FAQ ACCORDION
  ══════════════════════════════════════════ */
  document.querySelectorAll('.faq-q').forEach(btn => {
    btn.addEventListener('click', () => {
      const item = btn.parentElement;
      const wasOpen = item.classList.contains('open');
      document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
      if (!wasOpen) item.classList.add('open');
    });
  });


  /* ══════════════════════════════════════════
     14. CERTIFICATE CAROUSEL
  ══════════════════════════════════════════ */
  (function () {
    const GAP = 20;
    const AUTO = 3500;

    const track = document.getElementById('imgcarTrack');
    const vp = document.getElementById('imgcarViewport');
    const pbar = document.getElementById('imgcarPbar');
    const dotsW = document.getElementById('imgcarDots');
    const btnP = document.getElementById('imgcarPrev');
    const btnN = document.getElementById('imgcarNext');

    if (!track || !vp) return;

    const items = Array.from(track.querySelectorAll('.imgcar-item'));
    let cur = 0, raf, t0;

    function perView() {
      const w = vp.offsetWidth;
      if (w < 420) return 1;
      if (w < 720) return 2;
      return 3;
    }
    function totalPages() { return Math.ceil(items.length / perView()); }

    function buildDots() {
      dotsW.innerHTML = '';
      for (let i = 0; i < totalPages(); i++) {
        const d = document.createElement('button');
        d.className = 'imgcar-dot' + (i === cur ? ' active' : '');
        d.setAttribute('aria-label', 'Slide ' + (i + 1));
        d.addEventListener('click', () => goToCert(i));
        dotsW.appendChild(d);
      }
    }

    function render() {
      const n = perView();
      const vpW = vp.offsetWidth;
      const cardW = (vpW - GAP * (n - 1)) / n;
      items.forEach(item => {
        item.style.flex = '0 0 ' + cardW + 'px';
        item.style.maxWidth = cardW + 'px';
      });
      const offset = -(cur * n) * (cardW + GAP);
      track.style.transform = 'translateX(' + offset + 'px)';
      dotsW.querySelectorAll('.imgcar-dot').forEach((d, i) => d.classList.toggle('active', i === cur));
    }

    function goToCert(idx) {
      cur = ((idx % totalPages()) + totalPages()) % totalPages();
      render();
      restartAuto();
    }

    function tick(ts) {
      if (!t0) t0 = ts;
      const pct = Math.min(((ts - t0) / AUTO) * 100, 100);
      if (pbar) pbar.style.width = pct + '%';
      if (pct >= 100) {
        if (pbar) pbar.style.width = '0%';
        t0 = null;
        goToCert(cur + 1);
        return;
      }
      raf = requestAnimationFrame(tick);
    }
    function restartAuto() {
      cancelAnimationFrame(raf);
      if (pbar) pbar.style.width = '0%';
      t0 = null;
      raf = requestAnimationFrame(tick);
    }
    function stopAuto() {
      cancelAnimationFrame(raf);
      if (pbar) pbar.style.width = '0%';
    }

    if (btnP) btnP.addEventListener('click', () => goToCert(cur - 1));
    if (btnN) btnN.addEventListener('click', () => goToCert(cur + 1));
    vp.addEventListener('mouseenter', stopAuto);
    vp.addEventListener('mouseleave', restartAuto);

    let tx = 0;
    vp.addEventListener('touchstart', e => { tx = e.touches[0].clientX; }, { passive: true });
    vp.addEventListener('touchend', e => {
      const dx = tx - e.changedTouches[0].clientX;
      if (Math.abs(dx) > 40) goToCert(dx > 0 ? cur + 1 : cur - 1);
    });

    let certResizeTimer;
    window.addEventListener('resize', () => {
      clearTimeout(certResizeTimer);
      certResizeTimer = setTimeout(() => {
        if (cur >= totalPages()) cur = totalPages() - 1;
        buildDots();
        render();
      }, 120);
    });

    buildDots();
    render();
    requestAnimationFrame(tick);
  })();


  /* ══════════════════════════════════════════
     15. DIET CHART — STATE & LOGIC
  ══════════════════════════════════════════ */
  let state = { age: null, gender: null, problems: [], pref: null, allergies: [] };

  function selectAge(el) {
    document.querySelectorAll('.age-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    state.age = el.dataset.age;
    document.getElementById('ageError').classList.remove('show');
  }

  function selectGender(el) {
    document.querySelectorAll('.gender-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    state.gender = el.dataset.gender;
    document.getElementById('genderError').classList.remove('show');
  }

  function toggleProb(el) {
    el.classList.toggle('selected');
    const p = el.dataset.prob;
    if (el.classList.contains('selected')) state.problems.push(p);
    else state.problems = state.problems.filter(x => x !== p);
    document.getElementById('probError').classList.remove('show');
  }

  function selectPref(el) {
    document.querySelectorAll('.dpref').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    state.pref = el.dataset.pref;
    document.getElementById('prefError').classList.remove('show');
  }

  function toggleAllergy(el) {
    if (el.dataset.allergy === 'none') {
      document.querySelectorAll('.atag').forEach(a => a.classList.remove('selected'));
      el.classList.add('selected');
      state.allergies = ['none'];
      return;
    }
    document.querySelector('.atag[data-allergy="none"]')?.classList.remove('selected');
    el.classList.toggle('selected');
    const a = el.dataset.allergy;
    if (el.classList.contains('selected')) {
      state.allergies = state.allergies.filter(x => x !== 'none');
      state.allergies.push(a);
    } else {
      state.allergies = state.allergies.filter(x => x !== a);
    }
  }

  function goStep(step) {
    if (step === 2) {
      if (!state.age) { document.getElementById('ageError').classList.add('show'); return; }
      if (!state.gender) { document.getElementById('genderError').classList.add('show'); return; }
    }
    if (step === 3) {
      if (!state.problems.length) { document.getElementById('probError').classList.add('show'); return; }
    }

    document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel' + step).classList.add('active');

    for (let i = 1; i <= 4; i++) {
      const s = document.getElementById('sp' + i);
      if (!s) continue;
      s.classList.remove('active', 'done');
      if (i < step) { s.classList.add('done'); s.querySelector('.sp-ball').textContent = '✓'; }
      else if (i === step) { s.classList.add('active'); s.querySelector('.sp-ball').textContent = i; }
      else { s.querySelector('.sp-ball').textContent = i; }
    }
    for (let i = 1; i <= 3; i++) {
      const l = document.getElementById('line' + i);
      if (l) l.classList.toggle('done', i < step);
    }

    document.getElementById('diet-chart').scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function generateChart() {
    if (!state.pref) { document.getElementById('prefError').classList.add('show'); return; }

    document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('loadingState').classList.add('active');

    for (let i = 1; i <= 3; i++) {
      const s = document.getElementById('sp' + i);
      if (s) { s.classList.remove('active'); s.classList.add('done'); s.querySelector('.sp-ball').textContent = '✓'; }
    }
    const sp4 = document.getElementById('sp4');
    if (sp4) sp4.classList.add('active');
    ['line1', 'line2', 'line3'].forEach(id => {
      const l = document.getElementById(id);
      if (l) l.classList.add('done');
    });

    setTimeout(() => {
      document.getElementById('loadingState').classList.remove('active');
      buildResult();
      document.getElementById('resultState').classList.add('active');
      document.getElementById('diet-chart').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 2600);
  }

  function buildResult() {
    const ageLabel = {
      '2-3': 'Toddler (2–3 yrs)',
      '4-6': 'Pre-Schooler (4–6 yrs)',
      '7-9': 'Primary Schooler (7–9 yrs)',
      '10-12': 'Middle Schooler (10–12 yrs)',
      '13-14': 'Teen (13–14 yrs)'
    };
    const probLabel = {
      immunity: 'Immunity', brain: 'Brain Power', growth: 'Growth',
      sleep: 'Better Sleep', energy: 'Energy', weight: 'Healthy Weight',
      digestion: 'Digestion', bones: 'Strong Bones', mood: 'Mood',
      skin: 'Skin Health', appetite: 'Picky Eating', exam: 'Exam Focus'
    };

    document.getElementById('resultTitle').textContent = `Your Child's 7-Day Nutrition Plan`;
    document.getElementById('resultDesc').textContent =
      `Personalized for a ${ageLabel[state.age]} — focused on ${state.problems.slice(0, 2).map(p => probLabel[p] || p).join(' & ')}.`;

    const tags = [];
    if (state.pref) tags.push({ l: state.pref.charAt(0).toUpperCase() + state.pref.slice(1) });
    if (state.age) tags.push({ l: state.age + ' years' });
    state.problems.slice(0, 3).forEach(p => { if (probLabel[p]) tags.push({ l: probLabel[p] }); });
    document.getElementById('resultTags').innerHTML = tags.map(t => `<div class="rtag">${t.l}</div>`).join('');

    const meals = getMealPlan(state.age, state.pref, state.problems);
    document.getElementById('mealGrid').innerHTML = meals.map(m => `
    <div class="meal-card">
      <div class="meal-time">
        <div class="meal-dot" style="background:${m.color}"></div>
        <div class="meal-label" style="color:${m.color}">${m.time}</div>
      </div>
      <span class="meal-icon">${m.icon}</span>
      <div class="meal-name">${m.name}</div>
      <div class="meal-items">${m.items}</div>
      <div class="meal-tag">${m.benefit}</div>
    </div>
  `).join('');

    const nutrients = getNutrients(state.age);
    document.getElementById('nutrientBars').innerHTML = nutrients.map(n => `
    <div class="n-row">
      <div class="n-label">${n.name}</div>
      <div class="n-track"><div class="n-fill" style="width:${n.pct}%;background:${n.color}"></div></div>
      <div class="n-val">${n.val}</div>
    </div>
  `).join('');

    const rec = getProductRec(state.problems);
    document.getElementById('productRec').innerHTML = `
    <div class="prod-info">
      <div class="prod-tag">Recommended for Your Child</div>
      <div class="prod-name">${rec.name}</div>
      <div class="prod-why">${rec.why}</div>
    </div>
    <button class="prod-btn" onclick="alert('Added to cart! 🛒')">Add to Cart — ${rec.price}</button>
  `;

    const tips = getTips(state.problems);
    document.getElementById('tipsGrid').innerHTML = tips.map(t => `
    <div class="tip-card">
      <div class="tip-icon">${t.icon}</div>
      <div>
        <div class="tip-title">${t.title}</div>
        <div class="tip-text">${t.text}</div>
      </div>
    </div>
  `).join('');
  }

  function getMealPlan(age, pref, problems) {
    const isBrain = problems.includes('brain') || problems.includes('exam');
    const isGrowth = problems.includes('growth') || problems.includes('bones');
    const isSleep = problems.includes('sleep');
    const isVeg = ['vegetarian', 'vegan'].includes(pref);
    const isNonVeg = pref === 'non-veg';
    return [
      {
        time: 'Wake-Up', icon: '🌅', color: '#FF9900',
        name: 'Morning Boost',
        items: 'Warm water with lemon + 1 tsp Amla powder\n+ 5 soaked almonds',
        benefit: 'Immunity + Digestion'
      },
      {
        time: 'Breakfast', icon: '🍳', color: '#FF4D8F',
        name: isBrain ? 'Brain Power Breakfast' : 'Power Breakfast',
        items: isBrain
          ? (isVeg ? 'Moong dal chilla + Walnut chutney + Fresh fruit smoothie' : 'Scrambled eggs + Multigrain toast + Walnut smoothie')
          : (isVeg ? 'Oats upma with veggies + Banana + Milk/Soy milk' : 'Egg paratha + Banana + Milk'),
        benefit: isBrain ? 'Omega-3 + Focus' : 'Iron + Protein'
      },
      {
        time: 'Mid-Morning', icon: '🍎', color: '#00BFFF',
        name: 'Smart Snack',
        items: isVeg
          ? 'Seasonal fruit + Roasted chana or Makhana\n+ Coconut water'
          : 'Seasonal fruit + Boiled egg or Roasted chana\n+ Coconut water',
        benefit: 'Vitamins + Hydration'
      },
      {
        time: 'Lunch', icon: '🍱', color: '#7C3AED',
        name: isGrowth ? 'Growth Power Lunch' : 'Nutrition Lunch',
        items: isVeg
          ? 'Brown rice + Dal + Paneer sabzi + Salad + Curd'
          : 'Brown rice + Dal + Chicken curry or Fish + Salad + Curd',
        benefit: isGrowth ? 'Calcium + Protein' : 'Balanced Macro'
      },
      {
        time: 'Evening', icon: '🥤', color: '#00D68F',
        name: 'After-School Refuel',
        items: isBrain
          ? 'Turmeric milk (Haldi doodh) + Dates + Dark chocolate square'
          : 'Banana smoothie + Roasted seeds\n+ Whole wheat crackers',
        benefit: isBrain ? 'Memory + Calm' : 'Energy Refuel'
      },
      {
        time: 'Dinner', icon: '🌙', color: isSleep ? '#5B21B6' : '#FF6B35',
        name: isSleep ? 'Sleep Calm Dinner' : 'Growth Dinner',
        items: isSleep
          ? 'Light khichdi + Ghee + Steamed veggies\n+ Warm chamomile milk at bedtime'
          : (isNonVeg ? 'Roti + Sabzi + Dal + Grilled chicken or Fish' : 'Roti + Sabzi + Dal + Curd or Paneer'),
        benefit: isSleep ? 'L-Theanine + Rest' : 'Protein + Iron'
      }
    ];
  }

  function getNutrients(age) {
    const base = {
      '2-3': { cal: '1200 kcal', pro: '35g', cal2: '700mg', iron: '7mg', vitC: '40mg', omega: '0.5g' },
      '4-6': { cal: '1400 kcal', pro: '45g', cal2: '1000mg', iron: '10mg', vitC: '45mg', omega: '0.9g' },
      '7-9': { cal: '1700 kcal', pro: '55g', cal2: '1000mg', iron: '10mg', vitC: '45mg', omega: '1.2g' },
      '10-12': { cal: '2000 kcal', pro: '65g', cal2: '1300mg', iron: '12mg', vitC: '50mg', omega: '1.4g' },
      '13-14': { cal: '2200 kcal', pro: '75g', cal2: '1300mg', iron: '15mg', vitC: '65mg', omega: '1.6g' },
    };
    const d = base[age] || base['7-9'];
    return [
      { name: 'Calories', val: d.cal, pct: 72, color: 'linear-gradient(90deg,#FF4D8F,#FFD600)' },
      { name: 'Protein', val: d.pro, pct: 68, color: 'linear-gradient(90deg,#7C3AED,#FF4D8F)' },
      { name: 'Calcium', val: d.cal2, pct: 80, color: 'linear-gradient(90deg,#00BFFF,#00D68F)' },
      { name: 'Iron', val: d.iron, pct: 60, color: 'linear-gradient(90deg,#FF6B35,#FF4D8F)' },
      { name: 'Vitamin C', val: d.vitC, pct: 90, color: 'linear-gradient(90deg,#FFD600,#FF6B35)' },
      { name: 'Omega-3 DHA', val: d.omega, pct: 55, color: 'linear-gradient(90deg,#00BFFF,#7C3AED)' },
    ];
  }

  function getProductRec(problems) {
    if (problems.includes('brain') || problems.includes('exam'))
      return {
        name: 'BrainBoost Chews', price: '₹649',
        why: 'Brahmi + Omega-3 DHA + Shankhpushpi — clinically shown to improve focus, memory, and learning scores in 8 weeks.'
      };
    if (problems.includes('sleep') || problems.includes('mood'))
      return {
        name: 'DreamCalm Drops', price: '₹549',
        why: 'Chamomile + L-Theanine + Jatamansi — helps even the most hyperactive kids sleep deeply and wake up refreshed.'
      };
    return {
      name: 'GrowStrong Gummies', price: '₹599',
      why: 'Ashwagandha + Vitamin D3 + Zinc — builds immunity, supports height & bone growth, and tastes amazing!'
    };
  }

  function getTips(problems) {
    const pool = [
      {
        icon: '💧', title: 'Hydration is Key',
        text: 'Ensure 6–8 glasses of water daily. Add lemon or mint for taste. Coconut water is great for electrolytes.'
      },
      {
        icon: '☀️', title: 'Morning Sunlight',
        text: '15 mins of sunlight before 9am gives natural Vitamin D — crucial for bone growth and mood.'
      },
      {
        icon: '🕐', title: 'Consistent Meal Times',
        text: 'Fixed meal timings regulate hunger hormones and improve digestion and energy levels throughout the day.'
      },
      {
        icon: '🚫', title: 'Avoid Ultra-Processed Foods',
        text: 'Chips, packaged biscuits, and sugary drinks spike blood sugar and displace nutrient-dense foods.'
      },
      {
        icon: '🫙', title: 'Add Ghee Daily',
        text: '1 tsp of pure ghee in dal or roti boosts fat-soluble vitamin absorption and supports brain development.'
      },
      {
        icon: '😴', title: 'Sleep Schedule',
        text: '9–10 hours of sleep is non-negotiable for growth hormone release and memory consolidation.'
      },
    ];
    if (problems.includes('brain'))
      pool.unshift({
        icon: '🍳', title: 'Breakfast Before School',
        text: 'Never skip breakfast! The brain uses 20% of all energy. A good breakfast improves focus by up to 35%.'
      });
    if (problems.includes('immunity'))
      pool.unshift({
        icon: '🥛', title: 'Probiotic Power',
        text: 'Include one portion of curd/yogurt daily. Gut health = immune health. 70% of immunity lives in the gut.'
      });
    return pool.slice(0, 4);
  }

  function restart() {
    state = { age: null, gender: null, problems: [], pref: null, allergies: [] };
    document.querySelectorAll('.age-card,.gender-card,.prob-tag,.dpref,.atag').forEach(el => el.classList.remove('selected'));
    document.getElementById('resultState').classList.remove('active');
    document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel1').classList.add('active');
    for (let i = 1; i <= 4; i++) {
      const s = document.getElementById('sp' + i);
      if (s) { s.classList.remove('active', 'done'); s.querySelector('.sp-ball').textContent = i; }
    }
    document.getElementById('sp1').classList.add('active');
    ['line1', 'line2', 'line3'].forEach(id => {
      const l = document.getElementById(id);
      if (l) l.classList.remove('done');
    });
    document.getElementById('diet-chart').scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function printChart() { window.print(); }

  /* ══════════════════════════════════════════
     PRODUCT PAGE — Variant / Pincode / Cart
  ══════════════════════════════════════════ */
  /* 
    NOTE: selectVariant is defined in product.blade.php with 6 parameters
    for the advanced product page. These are stubs to prevent errors.
  */
  function selectVariant(el, id, price, comparePrice) {
    // Stub: selectVariant is defined in product.blade.php if needed
  }

  function selectFlavor(el, val) {
    // Stub: selectFlavor is defined in product.blade.php if needed
  }
  function selectQtyOpt(el, targetId, val) {
    // Stub: selectQtyOpt is defined inline in pages if needed
  }

  function checkPincode() {
    const v = document.getElementById('pincodeInput').value;
    const res = document.getElementById('pincode-result');
    if (v.length === 6 && /^\d+$/.test(v)) {
      res.style.display = 'block';
      res.textContent = '✅ Delivery by Tomorrow — Free Shipping!';
      res.style.color = 'var(--mn)';
    } else {
      res.style.display = 'block';
      res.textContent = '⚠️ Please enter a valid 6-digit pincode.';
      res.style.color = 'var(--or)';
    }
  }
  const pincodeInput = document.getElementById('pincodeInput');
  if (pincodeInput) pincodeInput.addEventListener('keydown', e => { if (e.key === 'Enter') checkPincode(); });

  async function addToCart(productId, quantity = 1, productVariantId = null, sourceEl = null) {
    let requestSucceeded = false;
    let previousCount = null;
    try {
      const resolveMeta = typeof globalThis.resolveCartItemMeta === 'function'
        ? globalThis.resolveCartItemMeta
        : () => ({ product_name: 'Product', variant_name: '', image: '/img/product2.png', unit_price: 0, product_url: '/product' });

      const itemMeta = resolveMeta(productId, productVariantId, sourceEl);
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

      const isGuest = document.getElementById('cartCount')?.getAttribute('data-guest') === 'true';

      if (isGuest) {
        addPendingCartItem(productId, quantity, productVariantId, itemMeta);
        invalidateCartPayloadCache();
        const pendingCount = getPendingCartCount();
        if (cartCountEl) cartCountEl.textContent = String(pendingCount);
        _flashCartBtn(sourceEl);
        if (cartPopup?.classList.contains('open')) loadCartPopup({ force: true });
        return true;
      }

      if (cartCountEl) {
        previousCount = Number(cartCountEl.textContent) || 0;
        cartCountEl.textContent = String(previousCount + Number(quantity));
      }
      _flashCartBtn(sourceEl);

      const res = await fetch('/user/cart', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
        },
        body: JSON.stringify({ product_id: productId, product_variant_id: productVariantId, quantity })
      });

      const responseIsJson = isJsonResponse(res);
      const isGuestFallback = isAuthRedirectResponse(res);

      if (isGuestFallback) {
        addPendingCartItem(productId, quantity, productVariantId, itemMeta);
        invalidateCartPayloadCache();
        const pendingCount = getPendingCartCount();
        if (cartCountEl) cartCountEl.textContent = String(pendingCount);
        if (cartPopup?.classList.contains('open')) loadCartPopup({ force: true });
        return true;
      }

      if (!res.ok || !responseIsJson) {
        if (cartCountEl && previousCount !== null) cartCountEl.textContent = String(previousCount);
        const payload = await res.json().catch(() => ({}));
        if (typeof nbToast === 'function') nbToast(payload.message || 'Unable to add item to cart.', 'error');
        return false;
      }
      requestSucceeded = true;

      const payload = await res.json().catch(() => ({}));
      cartPayloadCache = payload;
      cartPayloadCacheAt = Date.now();
      const items = payload.cart?.items || [];
      const countNow = Number(payload.cart_count || 0) || items.reduce((sum, it) => sum + Number(it.quantity || 0), 0);
      if (cartCountEl) cartCountEl.textContent = String(countNow);
      if (cartPopup?.classList.contains('open')) loadCartPopup({ force: true });
      return true;

    } catch (_) {
      if (cartCountEl && previousCount !== null) cartCountEl.textContent = String(previousCount);
      if (!requestSucceeded && typeof nbToast === 'function') nbToast('Unable to add item to cart.', 'error');
      return false;
    }
  }

  function _flashCartBtn(sourceEl) {
    const btn = sourceEl || document.querySelector('.btn-cart');
    if (!btn) return;
    // .btn-add buttons are managed by the click listener — skip to avoid conflicting restores
    if (btn.classList.contains('btn-add') || btn.closest('.nb-variant-modal')) return;
    const orig = btn.dataset.origHtml || btn.innerHTML;
    btn.innerHTML = 'Added to Cart! ✓';
    btn.style.background = 'var(--mnl)';
    btn.style.color = 'var(--mn)';
    setTimeout(() => { btn.innerHTML = orig; btn.style.background = ''; btn.style.color = ''; }, 2000);
  }

  function buyNow(productId, quantity = 1, productVariantId = null, sourceEl = null) {
    addToCart(productId, quantity, productVariantId, sourceEl).then(added => {
      if (added) window.location.href = '/checkout';
    });
  }

  /* ══════════════════════════════════════════
     EXPOSE GLOBALS
  ══════════════════════════════════════════ */
  window.selectAge = selectAge;
  window.selectGender = selectGender;
  window.toggleProb = toggleProb;
  window.selectPref = selectPref;
  window.toggleAllergy = toggleAllergy;
  window.goStep = goStep;
  window.generateChart = generateChart;
  window.restart = restart;
  window.printChart = printChart;

  // dc-prefixed aliases used in HTML onclick attributes
  window.dcSelectAge = selectAge;
  window.dcSelectGender = selectGender;
  window.dcToggleProb = toggleProb;
  window.dcSelectPref = selectPref;
  window.dcToggleAllergy = toggleAllergy;
  window.dcGoStep = goStep;
  window.dcGenerateChart = generateChart;
  window.dcRestart = restart;
  window.dcPrintChart = printChart;

  window.selectVariant = selectVariant;
  window.selectFlavor = selectFlavor;
  window.selectQtyOpt = selectQtyOpt;
  window.checkPincode = checkPincode;
  window.addToCart = addToCart;
  window.buyNow = buyNow;
  window.syncCartCount = syncCartCount;   // ✅ exposed so external calls work too

  /* ── Initial cart count sync (inside scope — fixes the ReferenceError) ── */
  syncCartCount();

} // end initNutriBuddy


/* ══════════════════════════════════════════
   BOOT
══════════════════════════════════════════ */
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initNutriBuddy);
} else {
  initNutriBuddy();
}


/* ══════════════════════════════════════════
   PRODUCT PAGE — SCROLL REVEAL (outside)
══════════════════════════════════════════ */
const ro = new IntersectionObserver(entries => {
  entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); ro.unobserve(e.target); } });
}, { threshold: .1 });
document.querySelectorAll('.reveal').forEach(r => ro.observe(r));


/* ══════════════════════════════════════════
   PRODUCT PAGE — INGREDIENT SECTION
══════════════════════════════════════════ */
(function () {
  const fallbackIngredients = [
    {
      id: 'ashwagandha', name: 'Ashwagandha (KSM-66®)', emoji: '', cat: 'ay', catLabel: 'Ayurvedic', latin: 'Withania somnifera',
      desc: 'The king of Ayurvedic adaptogens. KSM-66® is the most clinically studied Ashwagandha extract — proven to reduce cortisol, support growth, enhance immunity, and improve sleep quality in children.',
      benefits: ['Reduces Stress', 'Supports Growth', 'Boosts Immunity', 'Improves Sleep'],
      dosage: '150mg per gummy', badgeColor: '#00D68F', badgeBg: 'rgba(0,214,143,.12)', emojiBg: 'rgba(0,214,143,.1)'
    },
    {
      id: 'brahmi', name: 'Brahmi', emoji: '🧠', cat: 'ay', catLabel: 'Ayurvedic', latin: 'Bacopa monnieri',
      desc: 'Used in Ayurveda for over 3000 years as a cognitive enhancer. Brahmi improves memory formation, attention span, and information processing speed in growing minds.',
      benefits: ['Memory Boost', 'Focus & Attention', 'Cognitive Development', 'Nerve Health'],
      dosage: '100mg per gummy', badgeColor: '#00D68F', badgeBg: 'rgba(0,214,143,.12)', emojiBg: 'rgba(0,214,143,.1)'
    },
    {
      id: 'shatavari', name: 'Shatavari', emoji: '🌱', cat: 'ay', catLabel: 'Ayurvedic', latin: 'Asparagus racemosus',
      desc: 'A powerful rejuvenative herb that supports digestive health, strengthens immunity, and provides natural antioxidant protection for growing bodies.',
      benefits: ['Digestive Health', 'Immunity', 'Antioxidant', 'Rejuvenative'],
      dosage: '75mg per gummy', badgeColor: '#00D68F', badgeBg: 'rgba(0,214,143,.12)', emojiBg: 'rgba(0,214,143,.1)'
    },
    {
      id: 'vitd3', name: 'Vitamin D3', emoji: '☀️', cat: 'vi', catLabel: 'Vitamin', latin: 'Cholecalciferol',
      desc: 'The sunshine vitamin — essential for calcium absorption, bone mineralization, and immune function. Over 70% of Indian children are deficient.',
      benefits: ['Bone Growth', 'Calcium Absorption', 'Immunity', 'Mood Support'],
      dosage: '400 IU per gummy', badgeColor: '#00BFFF', badgeBg: 'rgba(0,191,255,.12)', emojiBg: 'rgba(0,191,255,.1)'
    },
    {
      id: 'vitc', name: 'Vitamin C', emoji: '🍊', cat: 'vi', catLabel: 'Vitamin', latin: 'Ascorbic Acid',
      desc: 'A powerful antioxidant that strengthens the immune system, aids collagen formation for healthy skin and gums, and enhances iron absorption from plant-based foods.',
      benefits: ['Immune Defense', 'Collagen Formation', 'Iron Absorption', 'Antioxidant'],
      dosage: '40mg per gummy', badgeColor: '#00BFFF', badgeBg: 'rgba(0,191,255,.12)', emojiBg: 'rgba(0,191,255,.1)'
    },
    {
      id: 'vitb12', name: 'Vitamin B12', emoji: '💉', cat: 'vi', catLabel: 'Vitamin', latin: 'Methylcobalamin',
      desc: 'Critical for nerve development, red blood cell formation, and energy metabolism. Particularly important for vegetarian children who may not get enough from diet alone.',
      benefits: ['Nerve Health', 'Energy', 'Red Blood Cells', 'Brain Development'],
      dosage: '1.2mcg per gummy', badgeColor: '#00BFFF', badgeBg: 'rgba(0,191,255,.12)', emojiBg: 'rgba(0,191,255,.1)'
    },
    {
      id: 'vita', name: 'Vitamin A', emoji: '👁️', cat: 'vi', catLabel: 'Vitamin', latin: 'Retinol Palmitate',
      desc: 'Essential for vision, skin health, and immune function. Supports the maintenance of healthy mucous membranes — the body\'s first line of defense against infections.',
      benefits: ['Vision', 'Skin Health', 'Immunity', 'Cell Growth'],
      dosage: '300mcg per gummy', badgeColor: '#00BFFF', badgeBg: 'rgba(0,191,255,.12)', emojiBg: 'rgba(0,191,255,.1)'
    },
    {
      id: 'vite', name: 'Vitamin E', emoji: '🛡️', cat: 'vi', catLabel: 'Vitamin', latin: 'D-Alpha Tocopherol',
      desc: 'A natural fat-soluble antioxidant that protects cell membranes from damage, supports immune health, and also serves as our clean natural preservative.',
      benefits: ['Antioxidant', 'Cell Protection', 'Skin Health', 'Immune Support'],
      dosage: '5mg per gummy', badgeColor: '#00BFFF', badgeBg: 'rgba(0,191,255,.12)', emojiBg: 'rgba(0,191,255,.1)'
    },
    {
      id: 'vitb6', name: 'Vitamin B6', emoji: '⚡', cat: 'vi', catLabel: 'Vitamin', latin: 'Pyridoxine HCl',
      desc: 'Crucial for brain development, neurotransmitter synthesis, and energy metabolism. Helps convert food into usable energy for active kids.',
      benefits: ['Brain Development', 'Energy', 'Neurotransmitters', 'Metabolism'],
      dosage: '0.6mg per gummy', badgeColor: '#00BFFF', badgeBg: 'rgba(0,191,255,.12)', emojiBg: 'rgba(0,191,255,.1)'
    },
    {
      id: 'folic', name: 'Folic Acid', emoji: '🧬', cat: 'vi', catLabel: 'Vitamin', latin: 'Pteroylglutamic Acid',
      desc: 'Essential for DNA synthesis, cell division, and rapid growth phases. Particularly important during childhood and adolescence when cells are dividing rapidly.',
      benefits: ['DNA Synthesis', 'Cell Division', 'Growth', 'Blood Health'],
      dosage: '100mcg per gummy', badgeColor: '#00BFFF', badgeBg: 'rgba(0,191,255,.12)', emojiBg: 'rgba(0,191,255,.1)'
    },
    {
      id: 'zinc', name: 'Zinc Bisglycinate', emoji: '⚙️', cat: 'mi', catLabel: 'Mineral', latin: 'Zinc Bisglycinate Chelate',
      desc: 'The most bioavailable form of zinc — gentle on tiny tummies. Essential for immune function, wound healing, taste perception, and over 300 enzymatic reactions in the body.',
      benefits: ['Immunity', 'Growth', 'Wound Healing', 'Taste & Appetite'],
      dosage: '5mg per gummy', badgeColor: '#FFD600', badgeBg: 'rgba(255,214,0,.12)', emojiBg: 'rgba(255,214,0,.1)'
    },
    {
      id: 'calcium', name: 'Calcium', emoji: '🦴', cat: 'mi', catLabel: 'Mineral', latin: 'Calcium Citrate',
      desc: 'The building block of strong bones and teeth. Calcium Citrate is chosen for superior absorption, especially important during the rapid growth years of childhood.',
      benefits: ['Bone Strength', 'Teeth Health', 'Muscle Function', 'Nerve Signaling'],
      dosage: '100mg per gummy', badgeColor: '#FFD600', badgeBg: 'rgba(255,214,0,.12)', emojiBg: 'rgba(255,214,0,.1)'
    },
    {
      id: 'iron', name: 'Iron', emoji: '🩸', cat: 'mi', catLabel: 'Mineral', latin: 'Ferrous Bisglycinate',
      desc: 'A chelated form of iron that is up to 4x more absorbable than standard iron, with zero stomach upset. Crucial for oxygen transport, energy, and cognitive development.',
      benefits: ['Oxygen Transport', 'Energy', 'Cognitive Function', 'Blood Health'],
      dosage: '4mg per gummy', badgeColor: '#FFD600', badgeBg: 'rgba(255,214,0,.12)', emojiBg: 'rgba(255,214,0,.1)'
    },
    {
      id: 'iodine', name: 'Iodine', emoji: '🧪', cat: 'mi', catLabel: 'Mineral', latin: 'Potassium Iodide',
      desc: 'Essential for thyroid hormone production which regulates metabolism, growth, and brain development. Mild deficiency can impair cognitive function in children.',
      benefits: ['Thyroid Health', 'Brain Development', 'Metabolism', 'Growth'],
      dosage: '90mcg per gummy', badgeColor: '#FFD600', badgeBg: 'rgba(255,214,0,.12)', emojiBg: 'rgba(255,214,0,.1)'
    },
    {
      id: 'selenium', name: 'Selenium', emoji: '✨', cat: 'mi', catLabel: 'Mineral', latin: 'Sodium Selenite',
      desc: 'A trace mineral with powerful antioxidant properties. Works synergistically with Vitamin E to protect cells and support immune function.',
      benefits: ['Antioxidant', 'Immune Support', 'Cell Protection', 'Thyroid'],
      dosage: '20mcg per gummy', badgeColor: '#FFD600', badgeBg: 'rgba(255,214,0,.12)', emojiBg: 'rgba(255,214,0,.1)'
    },
    {
      id: 'amla', name: 'Amla Extract', emoji: '🫐', cat: 'ex', catLabel: 'Extract', latin: 'Emblica officinalis',
      desc: 'Indian Gooseberry — one of the richest natural sources of Vitamin C. Provides 20x more Vitamin C than oranges, along with powerful polyphenol antioxidants.',
      benefits: ['Vitamin C Rich', 'Antioxidant', 'Digestion', 'Hair & Skin'],
      dosage: '50mg per gummy', badgeColor: '#FF6B35', badgeBg: 'rgba(255,107,53,.12)', emojiBg: 'rgba(255,107,53,.1)'
    },
    {
      id: 'beetroot', name: 'Beetroot Extract', emoji: '🫡', cat: 'ex', catLabel: 'Extract', latin: 'Beta vulgaris',
      desc: 'Our natural coloring agent. Rich in nitrates, betalains, and folate. Provides the vibrant pink-red color naturally while adding nutritional value — no synthetic dyes needed.',
      benefits: ['Natural Color', 'Nitric Oxide', 'Blood Health', 'Endurance'],
      dosage: '30mg per gummy', badgeColor: '#FF6B35', badgeBg: 'rgba(255,107,53,.12)', emojiBg: 'rgba(255,107,53,.1)'
    },
    {
      id: 'turmeric', name: 'Turmeric Extract', emoji: '🌾', cat: 'ex', catLabel: 'Extract', latin: 'Curcuma longa',
      desc: 'Contains curcumin — a powerful anti-inflammatory compound used in Ayurveda for millennia. Supports immunity, digestion, and provides natural golden coloring.',
      benefits: ['Anti-inflammatory', 'Antioxidant', 'Digestion', 'Natural Color'],
      dosage: '25mg per gummy', badgeColor: '#FF6B35', badgeBg: 'rgba(255,107,53,.12)', emojiBg: 'rgba(255,107,53,.1)'
    },
    {
      id: 'spirulina', name: 'Spirulina Extract', emoji: '🌊', cat: 'ex', catLabel: 'Extract', latin: 'Arthrospira platensis',
      desc: 'A superfood algae used for natural blue-green coloring. One of the most nutrient-dense foods on the planet, packed with protein, vitamins, and minerals.',
      benefits: ['Natural Color', 'Protein Rich', 'Detox', 'Energy'],
      dosage: '15mg per gummy', badgeColor: '#FF6B35', badgeBg: 'rgba(255,107,53,.12)', emojiBg: 'rgba(255,107,53,.1)'
    },
    {
      id: 'fruitconc', name: 'Fruit Concentrates', emoji: '🍓', cat: 'ex', catLabel: 'Extract', latin: 'Mixed Berry, Mango, Orange',
      desc: 'Real fruit concentrates provide our gummies with authentic, delicious flavors — not synthetic chemicals. Each flavor comes from actual fruits, ensuring kids love taking their vitamins.',
      benefits: ['Natural Flavor', 'Phytonutrients', 'No Artificial Flavors', 'Kid-Approved Taste'],
      dosage: 'Natural flavoring', badgeColor: '#FF6B35', badgeBg: 'rgba(255,107,53,.12)', emojiBg: 'rgba(255,107,53,.1)'
    },
    {
      id: 'pectin', name: 'Pectin', emoji: '🍎', cat: 'ba', catLabel: 'Base', latin: 'Plant-derived polysaccharide',
      desc: 'Our plant-based geling agent — derived from citrus fruit peels. Unlike gelatin (made from animal bones/skin), pectin is 100% vegetarian, clean, and suitable for all Indian families.',
      benefits: ['100% Vegetarian', 'Gelatin-Free', 'Clean Label', 'Prebiotic Fiber'],
      dosage: 'Gummy base', badgeColor: 'rgba(255,255,255,.4)', badgeBg: 'rgba(255,255,255,.06)', emojiBg: 'rgba(255,255,255,.05)'
    },
    {
      id: 'stevia', name: 'Stevia + Monk Fruit', emoji: '🍃', cat: 'ba', catLabel: 'Base', latin: 'Stevia rebaudiana + Siraitia grosvenorii',
      desc: 'Our natural zero-calorie sweetener blend. 300x sweeter than sugar but with zero glycemic impact — no sugar crashes, no tooth decay. Kids get the sweetness without any downsides.',
      benefits: ['Zero Sugar', 'Zero Calories', 'No Glycemic Impact', 'No Tooth Decay'],
      dosage: 'Natural sweetener', badgeColor: 'rgba(255,255,255,.4)', badgeBg: 'rgba(255,255,255,.06)', emojiBg: 'rgba(255,255,255,.05)'
    },
    {
      id: 'mct', name: 'MCT Oil', emoji: '🥥', cat: 'ba', catLabel: 'Base', latin: 'Medium-chain triglycerides',
      desc: 'Derived from coconut oil, MCT oil enhances the absorption of fat-soluble vitamins (A, D, E, K) and provides quick, clean energy for growing brains and bodies.',
      benefits: ['Fat-Soluble Vitamin Absorption', 'Brain Energy', 'Clean Carrier', 'Coconut-Derived'],
      dosage: 'Carrier oil', badgeColor: 'rgba(255,255,255,.4)', badgeBg: 'rgba(255,255,255,.06)', emojiBg: 'rgba(255,255,255,.05)'
    },
    {
      id: 'tocopherol', name: 'Natural Tocopherol', emoji: '🛡️', cat: 'ba', catLabel: 'Base', latin: 'd-alpha-Tocopherol',
      desc: 'Our natural preservative — Vitamin E derived from sunflower oil. Keeps every gummy fresh without synthetic preservatives like BHA, BHT, or EDTA.',
      benefits: ['Natural Preservative', 'No Synthetic Chemicals', 'Antioxidant', 'Clean Label'],
      dosage: 'Clean preservative', badgeColor: 'rgba(255,255,255,.4)', badgeBg: 'rgba(255,255,255,.06)', emojiBg: 'rgba(255,255,255,.05)'
    },
    {
      id: 'coconutoil', name: 'Coconut Oil', emoji: '🥥', cat: 'ba', catLabel: 'Base', latin: 'Cocos nucifera',
      desc: 'Used as a natural coating agent to prevent gummies from sticking. Also provides a subtle tropical flavor and additional medium-chain fatty acids for energy.',
      benefits: ['Non-Stick Coating', 'Natural', 'Antimicrobial', 'Energy'],
      dosage: 'Coating agent', badgeColor: 'rgba(255,255,255,.4)', badgeBg: 'rgba(255,255,255,.06)', emojiBg: 'rgba(255,255,255,.05)'
    }
  ];

  function readIngredientPayload() {
    const payloadEl = document.getElementById('nbIngredientsData');
    if (!payloadEl) return null;
    try {
      const parsed = JSON.parse(payloadEl.textContent || '[]');
      return Array.isArray(parsed) ? parsed : null;
    } catch (error) {
      console.warn('Unable to parse ingredient payload.', error);
      return null;
    }
  }

  function normalizeIngredient(item, index) {
    const safeBenefits = Array.isArray(item && item.benefits) ? item.benefits.filter(Boolean) : [];
    return {
      id: item && item.id ? item.id : `ingredient-${index}`,
      name: item && item.name ? item.name : (item && item.shortName ? item.shortName : 'Ingredient'),
      shortName: item && item.shortName ? item.shortName : (item && item.name ? item.name : 'Ingredient'),
      emoji: item && item.emoji ? item.emoji : '',
      cat: item && item.cat ? item.cat : 'ot',
      catLabel: item && item.catLabel ? item.catLabel : 'Other',
      latin: item && item.latin ? item.latin : (item && item.shortName ? item.shortName : ''),
      desc: item && item.desc ? item.desc : '',
      benefits: safeBenefits,
      dosage: item && item.dosage ? item.dosage : 'Details available in ingredient panel',
      badgeColor: item && item.badgeColor ? item.badgeColor : '#7C3AED',
      badgeBg: item && item.badgeBg ? item.badgeBg : 'rgba(124,58,237,.12)',
      emojiBg: item && item.emojiBg ? item.emojiBg : 'rgba(124,58,237,.1)',
      image: item && item.image ? item.image : ''
    };
  }

  const payloadEl = document.getElementById('nbIngredientsData');
  if (!payloadEl) return;

  const liveIngredients = readIngredientPayload();
  const ingredients = (liveIngredients || []).map(normalizeIngredient);

  const listEl = document.getElementById('nbList');
  const detailEl = document.getElementById('nbDetailCards');
  const emptyEl = document.getElementById('nbDetailEmpty');
  const mobCardsEl = document.getElementById('nbMobCards');

  if (!listEl || !detailEl) return;

  let activeId = null;
  let currentFilter = 'all';

  function renderList(filter) {
    currentFilter = filter;
    const filtered = filter === 'all' ? ingredients : ingredients.filter(i => i.cat === filter);
    listEl.innerHTML = filtered.map(i => `
      <div class="nb-ing-row${activeId === i.id ? ' nb-row-active' : ''}" data-id="${i.id}">
        <div class="nb-ing-row-emoji" style="background:${i.emojiBg}; overflow: hidden; display: flex; align-items: center; justify-content: center;">
          <img src="${i.image || 'img/logo.png'}" alt="${i.name}" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <div>
          <div class="nb-ing-row-name">${i.name}</div>
          <div class="nb-ing-row-cat">${i.catLabel}</div>
        </div>
        <div class="nb-ing-row-badge" style="background:${i.badgeBg};color:${i.badgeColor}">${i.catLabel}</div>
      </div>
    `).join('');
    listEl.querySelectorAll('.nb-ing-row').forEach(row => {
      row.addEventListener('click', () => showDetail(row.dataset.id));
    });
  }

  function renderDetailCards() {
    detailEl.innerHTML = ingredients.map(i => `
      <div class="nb-detail-card" data-detail="${i.id}">
        <div class="nb-detail-header" style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
          <div style="width:72px;height:72px;border-radius:12px;overflow:hidden;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.04);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <img src="${i.image || 'img/logo.png'}" alt="${i.name}" style="width:100%;height:100%;object-fit:cover;">
          </div>
          <div style="flex:1;min-width:220px;">
            <div class="nb-detail-name">${i.name}</div>
            <div class="nb-detail-latin">${i.latin || ''}</div>
            <div style="margin-top:8px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
              <div class="nb-detail-cat-badge" style="background:${i.badgeBg};color:${i.badgeColor}">${i.catLabel}</div>
            </div>
          </div>
        </div>
        <div class="nb-detail-desc">${i.desc}</div>
        <div class="nb-detail-benefits">
          <h5>Key Benefits</h5>
          <div class="nb-benefit-tags">
            ${i.benefits.map(b => `<span class="nb-benefit-tag">${b}</span>`).join('')}
          </div>
        </div>
      </div>
    `).join('');
  }

  function showDetail(id) {
    activeId = id;
    listEl.querySelectorAll('.nb-ing-row').forEach(r => r.classList.toggle('nb-row-active', r.dataset.id === id));
    if (emptyEl) emptyEl.classList.add('nb-hidden');
    detailEl.querySelectorAll('.nb-detail-card').forEach(c => c.classList.toggle('nb-card-active', c.dataset.detail === id));
  }

  function renderMobCards(filter) {
    if (!mobCardsEl) return;
    const filtered = filter === 'all' ? ingredients : ingredients.filter(i => i.cat === filter);
    mobCardsEl.innerHTML = filtered.map(i => `
      <div class="nb-mob-card" data-mobid="${i.id}">
        <div class="nb-mob-card-head">
          <div class="nb-mob-card-emoji" style="width:32px;height:32px;border-radius:6px;overflow:hidden;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:${i.emojiBg || 'rgba(255,255,255,0.05)'}">
            <img src="${i.image || 'img/logo.png'}" alt="${i.name}" style="width:100%;height:100%;object-fit:cover;">
          </div>
          <div class="nb-mob-card-name">${i.name}</div>
          <div class="nb-mob-card-arrow">▼</div>
        </div>
        <div class="nb-mob-card-body">
          <div class="nb-mob-card-desc">${i.desc}</div>
          <div class="nb-mob-card-benefits">
            ${i.benefits.map(b => `<span class="nb-mob-benefit">${b}</span>`).join('')}
          </div>
        </div>
      </div>
    `).join('');
    mobCardsEl.querySelectorAll('.nb-mob-card-head').forEach(head => {
      head.addEventListener('click', () => {
        const card = head.closest('.nb-mob-card');
        const wasOpen = card.classList.contains('nb-mob-open');
        mobCardsEl.querySelectorAll('.nb-mob-card').forEach(c => c.classList.remove('nb-mob-open'));
        if (!wasOpen) card.classList.add('nb-mob-open');
      });
    });
  }

  window.nbFilter = function (cat, btn) {
    document.querySelectorAll('.nb-cat-pill').forEach(p => p.classList.remove('nb-active'));
    btn.classList.add('nb-active');
    renderList(cat);
    activeId = null;
    if (emptyEl) emptyEl.classList.remove('nb-hidden');
    detailEl.querySelectorAll('.nb-detail-card').forEach(c => c.classList.remove('nb-card-active'));
  };

  window.nbMobFilter = function (cat, btn) {
    document.querySelectorAll('.nb-mob-tab').forEach(t => t.classList.remove('nb-sel-mob'));
    btn.classList.add('nb-sel-mob');
    renderMobCards(cat);
  };

  renderList('all');
  renderDetailCards();
  renderMobCards('all');
})();


/* ══════════════════════════════════════════
   ABOUT US — SCROLL REVEAL + COUNTERS
══════════════════════════════════════════ */
const nbAboutRevObs = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.classList.add('nb-about-visible');
      nbAboutRevObs.unobserve(e.target);
    }
  });
}, { threshold: 0.1 });
document.querySelectorAll('.nb-about-reveal').forEach(r => nbAboutRevObs.observe(r));

const nbAboutCountObs = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (!e.isIntersecting) return;
    const el = e.target;
    const raw = el.textContent;
    const hasK = raw.includes('K');
    const hasStar = raw.includes('★');
    const hasPct = raw.includes('%');
    const num = parseFloat(raw.replace(/[^0-9.]/g, ''));
    let start = 0;
    const dur = 1600, steps = 60, inc = num / steps;
    const iv = setInterval(() => {
      start = Math.min(start + inc, num);
      let display = Number.isInteger(num) ? Math.round(start) : start.toFixed(1);
      if (hasK) display += 'K+';
      else if (hasStar) display += '★';
      else if (hasPct) display += '%';
      else display += '+';
      el.textContent = display;
      if (start >= num) clearInterval(iv);
    }, dur / steps);
    nbAboutCountObs.unobserve(el);
  });
}, { threshold: 0.5 });
document.querySelectorAll('.nb-about-hstat-num').forEach(el => nbAboutCountObs.observe(el));


/* --- Diet Chart Script --- */
(function () {

                var dcState = {
                    childName: null,
                    age: null,
                    gender: null,
                    problems: [],
                    pref: null,
                    allergies: [],
                    height: null,
                    weight: null,
                    plan: 'pro'
                };

                /* -- Selectors -- */
                window.dcSelectAge = function (el) {
                    document.querySelectorAll('#ageGrid .age-card').forEach(c => c.classList.remove('selected'));
                    el.classList.add('selected');
                    dcState.age = el.dataset.age;
                    document.getElementById('ageError').classList.remove('show');
                };

                window.dcSelectGender = function (el) {
                    document.querySelectorAll('#genderRow .gender-card').forEach(c => c.classList.remove('selected'));
                    el.classList.add('selected');
                    dcState.gender = el.dataset.gender;
                    document.getElementById('genderError').classList.remove('show');
                };

                window.dcToggleProb = function (el) {
                    el.classList.toggle('selected');
                    var p = el.dataset.prob;
                    if (el.classList.contains('selected')) {
                        if (!dcState.problems.includes(p)) dcState.problems.push(p);
                    } else {
                        dcState.problems = dcState.problems.filter(x => x !== p);
                    }
                    document.getElementById('probError').classList.remove('show');
                };

                window.dcSelectPref = function (el) {
                    document.querySelectorAll('#dietPrefRow .dpref').forEach(c => c.classList.remove('selected'));
                    el.classList.add('selected');
                    dcState.pref = el.dataset.pref;
                    document.getElementById('prefError').classList.remove('show');
                };

                window.dcToggleAllergy = function (el) {
                    if (el.dataset.allergy === 'none') {
                        document.querySelectorAll('#allergyRow .atag').forEach(a => a.classList.remove('selected'));
                        el.classList.add('selected');
                        dcState.allergies = ['none'];
                        return;
                    }
                    var noneTag = document.querySelector('#allergyRow .atag[data-allergy="none"]');
                    if (noneTag) noneTag.classList.remove('selected');
                    el.classList.toggle('selected');
                    var a = el.dataset.allergy;
                    if (el.classList.contains('selected')) {
                        dcState.allergies = dcState.allergies.filter(x => x !== 'none');
                        if (!dcState.allergies.includes(a)) dcState.allergies.push(a);
                    } else {
                        dcState.allergies = dcState.allergies.filter(x => x !== a);
                    }
                };

                window.dcSelectPlan = function (p) {
                    dcState.plan = p;
                    document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('active'));
                    document.getElementById('plan' + p.charAt(0).toUpperCase() + p.slice(1)).classList.add('active');
                };

                /* -- Step navigation -- */
                window.dcGoStep = function (step) {
                    if (step === 2) {
                        // Validate name
                        var nameVal = document.getElementById('childNameInput').value.trim();
                        if (!nameVal) {
                            document.getElementById('nameError').classList.add('show');
                            return;
                        }
                        dcState.childName = nameVal;
                        document.getElementById('nameError').classList.remove('show');

                        if (!dcState.age) {
                            document.getElementById('ageError').classList.add('show');
                            return;
                        }
                        if (!dcState.gender) {
                            document.getElementById('genderError').classList.add('show');
                            return;
                        }
                        dcState.height = document.getElementById('heightInput').value || null;
                        dcState.weight = document.getElementById('weightInput').value || null;
                    }
                    if (step === 3) {
                        if (!dcState.problems.length) {
                            document.getElementById('probError').classList.add('show');
                            return;
                        }
                    }
                    dcShowPanel('panel' + step);
                    dcUpdateStepper(step);
                    document.getElementById('diet-chart').scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                };

                function dcShowPanel(id) {
                    document.querySelectorAll('.diet-card .step-panel, .loading-state, .result-state').forEach(p => {
                        p.classList.remove('active');
                        p.style.display = '';
                    });
                    var el = document.getElementById(id);
                    if (el) {
                        el.classList.add('active');
                        el.style.display = 'block';
                    }
                }

                function dcUpdateStepper(step) {
                    for (var i = 1; i <= 4; i++) {
                        var s = document.getElementById('sp' + i);
                        if (!s) continue;
                        s.classList.remove('active', 'done');
                        if (i < step) {
                            s.classList.add('done');
                            s.querySelector('.sp-ball').textContent = 'OK';
                        } else if (i === step) {
                            s.classList.add('active');
                            s.querySelector('.sp-ball').textContent = i;
                        } else {
                            s.querySelector('.sp-ball').textContent = i;
                        }
                    }
                    for (var j = 1; j <= 3; j++) {
                        var l = document.getElementById('line' + j);
                        if (l) l.classList.toggle('done', j < step);
                    }
                }

                /* -- Generate -- */
                window.dcGenerateChart = function () {
                    if (!dcState.pref) {
                        document.getElementById('prefError').classList.add('show');
                        return;
                    }
                    dcShowPanel('loadingState');
                    dcUpdateStepper(4);
                    setTimeout(dcBuildResult, 2400);
                };

                /* -- BMI -- */
                function dcGetBMI() {
                    if (!dcState.height || !dcState.weight) return null;
                    var h = parseFloat(dcState.height) / 100,
                        w = parseFloat(dcState.weight);
                    if (h <= 0 || w <= 0) return null;
                    var bmi = Math.round((w / (h * h)) * 10) / 10;
                    var status, color;
                    if (bmi < 14.5) {
                        status = 'Underweight';
                        color = '#FF6B35';
                    } else if (bmi < 22) {
                        status = 'Healthy Weight OK';
                        color = '#00D68F';
                    } else if (bmi < 25) {
                        status = 'Slightly Overweight';
                        color = '#FFD600';
                    } else {
                        status = 'High BMI  -  Focus on Healthy Weight';
                        color = '#FF4D8F';
                    }
                    return {
                        bmi: bmi,
                        status: status,
                        color: color
                    };
                }

                /* -- Build result -- */
                function dcBuildResult() {
                    var ageMap = {
                        '2-3': 'Toddler (2-3 yrs)',
                        '4-6': 'Pre-Schooler (4-6 yrs)',
                        '7-9': 'Primary Schooler (7-9 yrs)',
                        '10-12': 'Middle Schooler (10-12 yrs)',
                        '13-14': 'Teen (13-14 yrs)'
                    };
                    var pmap = {
                        immunity: 'Immunity',
                        brain: 'Brain Power',
                        growth: 'Growth',
                        sleep: 'Better Sleep',
                        energy: 'Energy',
                        weight: 'Healthy Weight',
                        digestion: 'Digestion',
                        bones: 'Strong Bones',
                        mood: 'Mood & Calm',
                        skin: 'Skin & Hair',
                        appetite: 'Picky Eating',
                        exam: 'Exam Focus'
                    };

                    // Title with child's name
                    var cname = dcState.childName ? dcState.childName + "'s" : "Your Child's";
                    document.getElementById('resultTitle').textContent = cname + ' 7-Day Nutrition Plan';
                    document.getElementById('resultDesc').textContent = 'Personalized for a ' + (ageMap[dcState.age] ||
                        dcState.age) + '  -  focused on ' + dcState.problems.slice(0, 2).map(p => pmap[p] || p).join(
                            ' & ') + '.';

                    // BMI
                    var bmiInfo = dcGetBMI();
                    if (bmiInfo) {
                        document.getElementById('bmiRow').innerHTML = '<div class="bmi-badge" style="background:' + bmiInfo
                            .color + '22;color:' + bmiInfo.color + ';border:1.5px solid ' + bmiInfo.color +
                            '44"><span style="font-size:16px">BMI</span> BMI ' + bmiInfo.bmi + '  -  ' + bmiInfo.status +
                            '</div>';
                    } else {
                        document.getElementById('bmiRow').innerHTML = '';
                    }

                    // Tags
                    var tags = [];
                    if (dcState.childName) tags.push(dcState.childName);
                    if (dcState.pref) tags.push(dcState.pref.charAt(0).toUpperCase() + dcState.pref.slice(1));
                    if (dcState.age) tags.push(dcState.age + ' yrs');
                    if (dcState.height && dcState.weight) tags.push(dcState.height + 'cm | ' + dcState.weight + 'kg');
                    dcState.problems.slice(0, 3).forEach(p => {
                        if (pmap[p]) tags.push(pmap[p]);
                    });
                    document.getElementById('resultTags').innerHTML = tags.map(t => '<div class="rtag">' + t + '</div>')
                        .join('');

                    // Meals
                    var meals = dcGetMeals(dcState.age, dcState.pref, dcState.problems);
                    document.getElementById('mealGrid1').innerHTML = meals[0].map(m => dcMealCard(m)).join('');
                    document.getElementById('mealGrid2').innerHTML = meals[1].map(m => dcMealCard(m)).join('');

                    // Nutrients
                    var nuts = dcGetNutrients(dcState.age);
                    document.getElementById('nutrientBars').innerHTML = nuts.map(n =>
                        '<div class="n-row"><div class="n-label">' + n.name +
                        '</div><div class="n-track"><div class="n-fill" style="width:0%;background:' + n.color +
                        '"></div></div><div class="n-val">' + n.val + '</div></div>'
                    ).join('');
                    setTimeout(() => {
                        nuts.forEach((n, i) => {
                            var fills = document.querySelectorAll('#nutrientBars .n-fill');
                            if (fills[i]) fills[i].style.width = n.pct + '%';
                        });
                    }, 120);

                    // Product rec
                    var rec = dcGetProductRec(dcState.problems);
                    document.getElementById('productRec').innerHTML =
                        '<div class="prod-info"><div class="prod-tag">Recommended for ' + (dcState.childName ||
                            'Your Child') + '</div><div class="prod-name">' + rec.name + '</div><div class="prod-why">' +
                        rec.why + '</div></div>' +
                        '<button class="prod-btn" type="button">Add to Cart  -  ' + rec.price +
                        '</button>';

                    // Tips
                    var tips = dcGetTips(dcState.problems);
                    document.getElementById('tipsGrid').innerHTML = tips.map(t =>
                        '<div class="tip-card"><div class="tip-icon">' + t.icon + '</div><div><div class="tip-title">' +
                        t.title + '</div><div class="tip-text">' + t.text + '</div></div></div>'
                    ).join('');

                    document.getElementById('lockBanner').style.display = 'block';
                    document.getElementById('successBanner').style.display = 'none';

                    dcShowPanel('resultState');
                    document.getElementById('diet-chart').scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }

                function dcMealCard(m) {
                    return '<div class="meal-card"><div class="meal-time"><div class="meal-dot" style="background:' + m
                        .color + '"></div><div class="meal-label" style="color:' + m.color + '">' + m.time +
                        '</div></div><span class="meal-icon">' + m.icon + '</span><div class="meal-name">' + m.name +
                        '</div><div class="meal-items">' + m.items + '</div><div class="meal-tag"> ' + m.benefit +
                        '</div></div>';
                }

                /* -- Meal data -- */
                function dcGetMeals(age, pref, problems) {
                    var isVeg = ['vegetarian', 'vegan'].includes(pref);
                    var isEgg = pref === 'eggetarian';
                    var isBrain = problems.includes('brain') || problems.includes('exam');
                    var isSleep = problems.includes('sleep');
                    var isGrowth = problems.includes('growth') || problems.includes('bones');

                    var day1 = [{
                        time: 'Wake-Up',
                        icon: '',
                        color: '#FF9900',
                        name: 'Morning Boost',
                        items: 'Warm water with lemon + 1 tsp Amla powder + 5 soaked almonds',
                        benefit: 'Immunity + Digestion'
                    },
                    {
                        time: 'Breakfast',
                        icon: '',
                        color: '#FF4D8F',
                        name: isBrain ? 'Brain Power Breakfast' : 'Power Breakfast',
                        items: isBrain ? (isVeg ? 'Moong dal chilla + Walnut chutney + Fresh fruit smoothie' :
                            'Scrambled eggs + Multigrain toast + Walnut smoothie') : (isVeg ?
                                'Oats upma with veggies + Banana + Milk' : (isEgg ? 'Egg paratha + Banana + Milk' :
                                    'Chicken sandwich + Banana + Milk')),
                        benefit: isBrain ? 'Omega-3 + Focus' : 'Iron + Protein'
                    },
                    {
                        time: 'Mid-Morning',
                        icon: '',
                        color: '#00BFFF',
                        name: 'Smart Snack',
                        items: 'Seasonal fruit + Roasted chana or Makhana + Coconut water',
                        benefit: 'Vitamins + Hydration'
                    },
                    {
                        time: 'Lunch',
                        icon: '',
                        color: '#7C3AED',
                        name: isGrowth ? 'Growth Power Lunch' : 'Nutrition Lunch',
                        items: isVeg ? 'Brown rice + Dal + Paneer sabzi + Salad + Curd' : (isEgg ?
                            'Brown rice + Dal + Egg curry + Salad + Curd' :
                            'Brown rice + Dal + Chicken curry + Salad + Curd'),
                        benefit: isGrowth ? 'Calcium + Protein' : 'Balanced Macros'
                    },
                    {
                        time: 'Evening',
                        icon: '',
                        color: '#00D68F',
                        name: 'After-School Refuel',
                        items: isBrain ? 'Turmeric milk + Dates + Dark chocolate square' :
                            'Banana smoothie + Roasted seeds + Whole wheat crackers',
                        benefit: isBrain ? 'Memory + Calm' : 'Energy Refuel'
                    },
                    {
                        time: 'Dinner',
                        icon: '',
                        color: isSleep ? '#5B21B6' : '#FF6B35',
                        name: isSleep ? 'Sleep Calm Dinner' : 'Growth Dinner',
                        items: isSleep ? 'Light khichdi + Ghee + Steamed veggies + Warm chamomile milk' : (isVeg ?
                            'Roti + Sabzi + Dal + Curd' : (isEgg ? 'Roti + Sabzi + Dal + Egg bhurji' :
                                'Roti + Sabzi + Dal + Grilled chicken')),
                        benefit: isSleep ? 'L-Theanine + Rest' : 'Protein + Iron'
                    }
                    ];

                    var day2 = [{
                        time: 'Wake-Up',
                        icon: '',
                        color: '#FF9900',
                        name: 'Morning Ritual',
                        items: 'Soaked methi water + 1 tsp honey + 4 walnuts',
                        benefit: 'Anti-Inflammatory'
                    },
                    {
                        time: 'Breakfast',
                        icon: '',
                        color: '#FF4D8F',
                        name: 'Wholesome Breakfast',
                        items: isVeg ? 'Ragi dosa + Peanut chutney + Seasonal fruit + Buttermilk' : (isEgg ?
                            'Omelette + Multigrain toast + Orange juice' : 'Chicken sandwich + Orange juice'),
                        benefit: 'Calcium + Energy'
                    },
                    {
                        time: 'Mid-Morning',
                        icon: '',
                        color: '#00BFFF',
                        name: 'Power Snack',
                        items: 'Roasted makhana + A2 milk or coconut water + Seasonal fruit',
                        benefit: 'Protein + Hydration'
                    },
                    {
                        time: 'Lunch',
                        icon: '',
                        color: '#7C3AED',
                        name: 'Power Lunch',
                        items: isVeg ? 'Wheat roti + Palak paneer + Rajma + Salad + Curd' : (isEgg ?
                            'Wheat roti + Palak dal + Boiled eggs + Salad' :
                            'Wheat roti + Fish curry + Sabzi + Salad'),
                        benefit: 'Iron + Protein'
                    },
                    {
                        time: 'Evening',
                        icon: '',
                        color: '#00D68F',
                        name: 'Energizing Snack',
                        items: 'Dates + Roasted chana + Coconut water + Seasonal fruit',
                        benefit: 'Electrolytes + Fibre'
                    },
                    {
                        time: 'Dinner',
                        icon: '',
                        color: '#5B21B6',
                        name: 'Light Nourishing Dinner',
                        items: isVeg ? 'Vegetable daliya + Buttermilk + Salad' : (isEgg ?
                            'Multigrain roti + Egg curry + Sabzi' : 'Multigrain roti + Grilled fish + Sabzi'),
                        benefit: 'Probiotics + Recovery'
                    }
                    ];

                    return [day1, day2];
                }

                /* -- Nutrients -- */
                function dcGetNutrients(age) {
                    var base = {
                        '2-3': {
                            cal: '1,200 kcal',
                            pro: '35g',
                            ca: '700mg',
                            fe: '7mg',
                            vc: '40mg',
                            om: '0.5g'
                        },
                        '4-6': {
                            cal: '1,400 kcal',
                            pro: '45g',
                            ca: '1,000mg',
                            fe: '10mg',
                            vc: '45mg',
                            om: '0.9g'
                        },
                        '7-9': {
                            cal: '1,700 kcal',
                            pro: '55g',
                            ca: '1,000mg',
                            fe: '10mg',
                            vc: '45mg',
                            om: '1.2g'
                        },
                        '10-12': {
                            cal: '2,000 kcal',
                            pro: '65g',
                            ca: '1,300mg',
                            fe: '12mg',
                            vc: '50mg',
                            om: '1.4g'
                        },
                        '13-14': {
                            cal: '2,200 kcal',
                            pro: '75g',
                            ca: '1,300mg',
                            fe: '15mg',
                            vc: '65mg',
                            om: '1.6g'
                        }
                    };
                    var d = base[age] || base['7-9'];
                    return [{
                        name: ' Calories',
                        val: d.cal,
                        pct: 72,
                        color: 'linear-gradient(90deg,#FF4D8F,#FFD600)'
                    },
                    {
                        name: ' Protein',
                        val: d.pro,
                        pct: 68,
                        color: 'linear-gradient(90deg,#7C3AED,#FF4D8F)'
                    },
                    {
                        name: ' Calcium',
                        val: d.ca,
                        pct: 80,
                        color: 'linear-gradient(90deg,#00BFFF,#00D68F)'
                    },
                    {
                        name: ' Iron',
                        val: d.fe,
                        pct: 60,
                        color: 'linear-gradient(90deg,#FF6B35,#FF4D8F)'
                    },
                    {
                        name: ' Vitamin C',
                        val: d.vc,
                        pct: 90,
                        color: 'linear-gradient(90deg,#FFD600,#FF6B35)'
                    },
                    {
                        name: ' Omega-3 DHA',
                        val: d.om,
                        pct: 55,
                        color: 'linear-gradient(90deg,#00BFFF,#7C3AED)'
                    }
                    ];
                }

                /* -- Product rec -- */
                function dcGetProductRec(problems) {
                    if (problems.includes('brain') || problems.includes('exam'))
                        return {
                            name: 'BrainBoost Chews',
                            price: '₹649',
                            why: 'Brahmi + Omega-3 DHA + Shankhpushpi  -  clinically shown to improve focus, memory, and learning scores in 8 weeks.'
                        };
                    if (problems.includes('sleep') || problems.includes('mood'))
                        return {
                            name: 'DreamCalm Drops',
                            price: '₹549',
                            why: 'Chamomile + L-Theanine + Jatamansi  -  helps even the most hyperactive kids sleep deeply and wake up refreshed.'
                        };
                    return {
                        name: 'GrowStrong Gummies',
                        price: '₹599',
                        why: 'Ashwagandha + Vitamin D3 + Zinc  -  builds immunity, supports height & bone growth, and tastes amazing!'
                    };
                }

                /* -- Tips -- */
                function dcGetTips(problems) {
                    var pool = [{
                        icon: '',
                        title: 'Hydration is Key',
                        text: 'Ensure 6-8 glasses of water daily. Add lemon or mint for taste. Coconut water is great for electrolytes.'
                    },
                    {
                        icon: 'Sun',
                        title: 'Morning Sunlight',
                        text: '15 mins of sunlight before 9am gives natural Vitamin D  -  crucial for bone growth and mood regulation.'
                    },
                    {
                        icon: '',
                        title: 'Consistent Meal Times',
                        text: 'Fixed meal timings regulate hunger hormones and improve digestion and energy levels throughout the day.'
                    },
                    {
                        icon: '',
                        title: 'Avoid Ultra-Processed Foods',
                        text: 'Chips, packaged biscuits, and sugary drinks spike blood sugar and displace nutrient-dense foods.'
                    },
                    {
                        icon: '',
                        title: 'Add Ghee Daily',
                        text: '1 tsp of pure ghee in dal or roti boosts fat-soluble vitamin absorption and supports brain development.'
                    },
                    {
                        icon: '',
                        title: 'Sleep Schedule',
                        text: '9-10 hours of sleep is non-negotiable for growth hormone release and memory consolidation.'
                    }
                    ];
                    if (problems.includes('brain')) pool.unshift({
                        icon: '',
                        title: 'Breakfast Before School',
                        text: 'Never skip breakfast! The brain uses 20% of all energy. A good breakfast improves focus by up to 35%.'
                    });
                    if (problems.includes('immunity')) pool.unshift({
                        icon: '',
                        title: 'Probiotic Power',
                        text: 'Include one portion of curd/yogurt daily. Gut health = immune health. 70% of immunity lives in the gut.'
                    });
                    return pool.slice(0, 4);
                }

                /* -- Modal -- */
                window.dcOpenModal = function () {
                    var titles = {
                        basic: 'Unlock 7-Day Plan ',
                        pro: 'Start Monthly Subscription '
                    };
                    var subs = {
                        basic: 'One-time payment. Receive the complete 7-day chart as a PDF instantly via email.',
                        pro: 'Unlock new personalized plans every month, progress tracker, and priority support.'
                    };
                    var items = {
                        basic: '7-Day Diet Chart (One-Time)',
                        pro: '7-Day Diet Chart (Monthly)'
                    };
                    var prices = {
                        basic: '₹99',
                        pro: '₹199'
                    };
                    document.getElementById('dcModalTitle').textContent = titles[dcState.plan] || titles.pro;
                    document.getElementById('dcModalSub').textContent = subs[dcState.plan] || subs.pro;
                    document.getElementById('dcOrderItem').textContent = items[dcState.plan] || items.pro;
                    document.getElementById('dcOrderPrice').textContent = prices[dcState.plan] || '₹199';
                    document.getElementById('dcModalOverlay').classList.add('open');
                };

                window.dcCloseModal = function () {
                    document.getElementById('dcModalOverlay').classList.remove('open');
                };

                window.dcCloseModalOutside = function (e) {
                    if (e.target === document.getElementById('dcModalOverlay')) dcCloseModal();
                };

                window.dcProcessPayment = function () {
                    var name = document.getElementById('dcName').value.trim();
                    var email = document.getElementById('dcEmail').value.trim();
                    if (!name || !email) {
                        alert('Please enter your name and email to continue.');
                        return;
                    }
                    dcCloseModal();
                    document.getElementById('lockBanner').style.display = 'none';
                    var sb = document.getElementById('successBanner');
                    sb.style.display = 'block';
                    sb.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                };

                window.dcDownload = function () {
                    var lines = [
                        'NUTRIBUDDY  -  7-DAY PERSONALIZED DIET CHART',
                        '='.repeat(46),
                        'Child: ' + (dcState.childName || 'N/A') + ' | Age: ' + (dcState.age || 'N/A') +
                        ' yrs | Diet: ' + (dcState.pref || 'N/A'),
                        'Goals: ' + dcState.problems.join(', '),
                        'Height: ' + (dcState.height || 'N/A') + ' cm | Weight: ' + (dcState.weight || 'N/A') +
                        ' kg',
                        '',
                        'Day 1 & Day 2 shown on screen.',
                        'Day 3-7: Personalized plan sent to your email.',
                        '',
                        'Thank you for choosing NutriBuddy Kids!',
                        'Generated by NutriBuddy  -  nutribuddy.in'
                    ].join('\n');
                    var blob = new Blob([lines], {
                        type: 'text/plain'
                    });
                    var a = document.createElement('a');
                    a.href = URL.createObjectURL(blob);
                    a.download = 'NutriBuddy_7Day_Diet_Chart.txt';
                    a.click();
                };

                /* -- Restart -- */
                window.dcRestart = function () {
                    dcState = {
                        childName: null,
                        age: null,
                        gender: null,
                        problems: [],
                        pref: null,
                        allergies: [],
                        height: null,
                        weight: null,
                        plan: 'pro'
                    };
                    document.querySelectorAll('.age-card, .gender-card, .prob-tag, .dpref, .atag').forEach(el => el
                        .classList.remove('selected'));
                    document.getElementById('childNameInput').value = '';
                    document.getElementById('heightInput').value = '';
                    document.getElementById('weightInput').value = '';
                    document.getElementById('lockBanner').style.display = 'block';
                    document.getElementById('successBanner').style.display = 'none';
                    dcGoStep(1);
                };

                window.goStep = window.dcGoStep;
                window.generateChart = window.dcGenerateChart;
                window.restart = window.dcRestart;
                window.printChart = window.dcDownload;

            })();