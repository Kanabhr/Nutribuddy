/* ==========================================================================
   Product Detail Page: Gallery, Variants, Cart, Reviews
   ========================================================================== */
(() => {
    function readPdpConfig() {
        if (window.NB_PDP_CONFIG) {
            return window.NB_PDP_CONFIG;
        }

        const configEl = document.getElementById('nbPdpConfig');
        if (!configEl) {
            return {};
        }

        try {
            return JSON.parse(configEl.textContent || '{}');
        } catch (error) {
            console.warn('Unable to parse PDP config.', error);
            return {};
        }
    }

    const config = readPdpConfig();
    if (!config.enabled) return;

    let selectedVariantId = config.selectedVariantId || '';
    const pdpVariants = Array.isArray(config.variants) ? config.variants : [];
    const pdpSelectedAttributes = config.selectedAttributes || {};
    const pdpIsLoggedIn = Boolean(config.isLoggedIn);
    const pdpFallbackCartMeta = config.fallbackCartMeta || {};
    const checkoutUrl = config.checkoutUrl || '/checkout';

    function changePdpImage(el, src) {
        const mainImage = document.getElementById('mainPdpImage');
        if (!mainImage || !src) return;

        mainImage.src = src;
        document.dispatchEvent(new CustomEvent('nb:pdp-image-changed', { detail: { src } }));
        document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
        if (el) {
            el.classList.add('active');
            el.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest',
                inline: 'center'
            });
        }
    }

    function initPdpImageZoom() {
        const gallery = document.querySelector('.pdp-gallery');
        const source = document.querySelector('.pdp-zoom-source');
        const mainImage = document.getElementById('mainPdpImage');
        const lens = source?.querySelector('.pdp-zoom-lens');
        const result = gallery?.querySelector('.pdp-zoom-result');

        if (!gallery || !source || !mainImage || !lens || !result) return;

        const zoom = Number(source.dataset.zoom || 2.25);
        let activePointerId = null;
        let lastPoint = null;

        function clamp(value, min, max) {
            return Math.min(Math.max(value, min), max);
        }

        function syncBackground() {
            const src = mainImage.currentSrc || mainImage.src || '';
            result.style.backgroundImage = src ? `url("${src}")` : '';
        }

        function hideZoom() {
            gallery.classList.remove('is-zooming');
            source.classList.remove('is-zooming');
            activePointerId = null;
            lastPoint = null;
        }

        function updateZoom(clientX, clientY) {
            const imageRect = mainImage.getBoundingClientRect();
            const sourceRect = source.getBoundingClientRect();

            if (!imageRect.width || !imageRect.height) {
                hideZoom();
                return;
            }

            syncBackground();
            gallery.classList.add('is-zooming');
            source.classList.add('is-zooming');

            const rawLensSize = lens.offsetWidth || 118;
            const lensSize = Math.max(48, Math.min(rawLensSize, imageRect.width, imageRect.height));
            const relativeX = clamp(clientX - imageRect.left, 0, imageRect.width);
            const relativeY = clamp(clientY - imageRect.top, 0, imageRect.height);
            const lensX = clamp(relativeX - lensSize / 2, 0, imageRect.width - lensSize);
            const lensY = clamp(relativeY - lensSize / 2, 0, imageRect.height - lensSize);
            const sourceOffsetX = imageRect.left - sourceRect.left;
            const sourceOffsetY = imageRect.top - sourceRect.top;

            lens.style.width = `${lensSize}px`;
            lens.style.height = `${lensSize}px`;
            lens.style.left = `${sourceOffsetX + lensX}px`;
            lens.style.top = `${sourceOffsetY + lensY}px`;

            const resultRect = result.getBoundingClientRect();
            const resultWidth = resultRect.width || 320;
            const resultHeight = resultRect.height || resultWidth;
            const backgroundWidth = imageRect.width * zoom;
            const backgroundHeight = imageRect.height * zoom;
            const focusX = lensX + lensSize / 2;
            const focusY = lensY + lensSize / 2;
            const maxOffsetX = Math.max(0, backgroundWidth - resultWidth);
            const maxOffsetY = Math.max(0, backgroundHeight - resultHeight);
            const offsetX = clamp(focusX * zoom - resultWidth / 2, 0, maxOffsetX);
            const offsetY = clamp(focusY * zoom - resultHeight / 2, 0, maxOffsetY);

            result.style.backgroundSize = `${backgroundWidth}px ${backgroundHeight}px`;
            result.style.backgroundPosition = `-${offsetX}px -${offsetY}px`;
            lastPoint = { clientX, clientY };
        }

        source.addEventListener('pointerenter', event => {
            if (event.pointerType === 'touch') return;
            updateZoom(event.clientX, event.clientY);
        });

        source.addEventListener('pointermove', event => {
            if (event.pointerType === 'touch' && activePointerId !== event.pointerId) return;
            if (event.pointerType === 'touch') event.preventDefault();
            updateZoom(event.clientX, event.clientY);
        });

        source.addEventListener('pointerleave', event => {
            if (event.pointerType !== 'touch') hideZoom();
        });

        source.addEventListener('pointerdown', event => {
            if (event.pointerType !== 'touch' && event.pointerType !== 'pen') return;
            activePointerId = event.pointerId;
            source.setPointerCapture?.(event.pointerId);
            event.preventDefault();
            updateZoom(event.clientX, event.clientY);
        });

        source.addEventListener('pointerup', event => {
            if (activePointerId === event.pointerId) hideZoom();
        });

        source.addEventListener('pointercancel', event => {
            if (activePointerId === event.pointerId) hideZoom();
        });

        mainImage.addEventListener('load', () => {
            syncBackground();
            if (lastPoint) updateZoom(lastPoint.clientX, lastPoint.clientY);
        });

        document.addEventListener('nb:pdp-image-changed', () => {
            syncBackground();
            hideZoom();
        });

        window.addEventListener('resize', () => {
            if (lastPoint) updateZoom(lastPoint.clientX, lastPoint.clientY);
        });

        syncBackground();
    }

    function initPdpGallerySwipe() {
        const galleryTarget = document.querySelector('.pdp-gallery .main-img-wrap .p-image');
        const mainImage = document.getElementById('mainPdpImage');
        const thumbs = Array.from(document.querySelectorAll('.pdp-gallery .thumb'));
        if (!galleryTarget || !mainImage || thumbs.length < 2) return;

        let startX = 0;
        let startY = 0;
        let isDragging = false;
        let hasSwiped = false;
        let dragMode = '';

        mainImage.draggable = false;
        mainImage.addEventListener('dragstart', event => event.preventDefault());

        function activeThumbIndex() {
            const activeIndex = thumbs.findIndex(thumb => thumb.classList.contains('active'));
            if (activeIndex >= 0) return activeIndex;

            const currentSrc = mainImage.getAttribute('src') || '';
            const matchedIndex = thumbs.findIndex(thumb => thumb.querySelector('img')?.getAttribute('src') === currentSrc);
            return matchedIndex >= 0 ? matchedIndex : 0;
        }

        function moveGallery(direction) {
            const current = activeThumbIndex();
            const next = (current + direction + thumbs.length) % thumbs.length;
            const nextThumb = thumbs[next];
            const nextSrc = nextThumb.querySelector('img')?.getAttribute('src');
            changePdpImage(nextThumb, nextSrc);
        }

        function onStart(event, mode = '') {
            const point = event.touches?.[0] || event;
            startX = point.clientX;
            startY = point.clientY;
            isDragging = true;
            hasSwiped = false;
            dragMode = mode;
            galleryTarget.classList.add('is-swiping');
        }

        function onMove(event) {
            if (!isDragging || hasSwiped) return;

            const point = event.touches?.[0] || event;
            const deltaX = point.clientX - startX;
            const deltaY = point.clientY - startY;

            if (Math.abs(deltaX) < 54 || Math.abs(deltaX) < Math.abs(deltaY) * 1.25) return;

            if (dragMode === 'mouse') event.preventDefault();
            hasSwiped = true;
            moveGallery(deltaX < 0 ? 1 : -1);
        }

        function onEnd() {
            isDragging = false;
            hasSwiped = false;
            dragMode = '';
            galleryTarget.classList.remove('is-swiping');
        }

        galleryTarget.addEventListener('touchstart', onStart, { passive: true });
        galleryTarget.addEventListener('touchmove', onMove, { passive: true });
        galleryTarget.addEventListener('touchend', onEnd);
        galleryTarget.addEventListener('touchcancel', onEnd);

        galleryTarget.addEventListener('mousedown', event => {
            if (event.button !== 0) return;
            event.preventDefault();
            onStart(event, 'mouse');
        });
        window.addEventListener('mousemove', event => {
            if (dragMode !== 'mouse') return;
            onMove(event);
        });
        window.addEventListener('mouseup', () => {
            if (dragMode === 'mouse') onEnd();
        });
    }

    function formatMoney(value) {
        return '\u20B9' + Number(value || 0).toLocaleString('en-IN', {
            maximumFractionDigits: 0
        });
    }

    function pdpVariantLabel(variant) {
        const attributes = variant?.attributes || {};
        const parts = Array.isArray(attributes) ?
            attributes.filter(Boolean).map(value => String(value)) :
            Object.entries(attributes)
                .filter(([, value]) => value !== null && value !== undefined && String(value).trim() !== '')
                .map(([name, value]) => `${name}: ${value}`);

        return parts.join(' / ') || variant?.name || '';
    }

    function getAttrValue(obj, key) {
        if (!obj || typeof obj !== 'object') return undefined;
        if (obj[key] !== undefined) return obj[key];
        const lowerKey = String(key).toLowerCase().trim();
        for (const k of Object.keys(obj)) {
            if (k.toLowerCase().trim() === lowerKey) return obj[k];
        }
        return undefined;
    }

    function sameAttributes(variant, selected) {
        const vAttrs = variant?.attributes || {};
        const vKeys = Object.keys(vAttrs);
        if (!vKeys.length) return false;
        return vKeys.every(key => {
            const selVal = getAttrValue(selected, key);
            if (selVal === undefined) return false;
            return String(vAttrs[key] ?? '').toLowerCase().trim() === String(selVal).toLowerCase().trim();
        });
    }

    function findMatchingVariant() {
        return pdpVariants.find(variant => sameAttributes(variant, pdpSelectedAttributes)) || null;
    }

    function syncPdpOptionButtons() {
        document.querySelectorAll('.pdp-option-btn').forEach(item => {
            const attr = item.dataset.attribute;
            const val = item.dataset.value;
            const selVal = getAttrValue(pdpSelectedAttributes, attr);
            const isActive = selVal !== undefined && String(selVal).toLowerCase().trim() === String(val).toLowerCase().trim();
            item.classList.toggle('active', isActive);
        });
    }

    function updateVariantAvailability() {
        document.querySelectorAll('.pdp-option-btn').forEach(button => {
            const attribute = button.dataset.attribute;
            const value = button.dataset.value;

            const possible = pdpVariants.some(variant => {
                const vAttrs = variant.attributes || {};
                const vVal = getAttrValue(vAttrs, attribute);
                if (vVal === undefined) return false;

                return Object.keys(vAttrs).every(key => {
                    if (key.toLowerCase().trim() === String(attribute).toLowerCase().trim()) {
                        return String(vAttrs[key] ?? '').toLowerCase().trim() === String(value).toLowerCase().trim();
                    }
                    const selVal = getAttrValue(pdpSelectedAttributes, key);
                    if (selVal === undefined) {
                        return true;
                    }
                    return String(vAttrs[key] ?? '').toLowerCase().trim() === String(selVal).toLowerCase().trim();
                });
            });
            button.disabled = !possible;
        });
    }

    function applyVariantToPage(variant) {
        const addBtn = document.getElementById('pdpAddToCartBtn');
        const buyBtn = document.getElementById('pdpBuyNowBtn');
        const stockEl = document.getElementById('pdpVariantStock');
        const selectedEl = document.getElementById('pdpVariantSelected');
        const skuEl = document.getElementById('pdpVariantSku');
        const priceNow = document.getElementById('pdpPriceNow');
        const priceOld = document.getElementById('pdpPriceOld');
        const priceSave = document.getElementById('pdpPriceSave');
        const discountBadge = document.getElementById('pdpDiscountBadge');
        const cashback = document.getElementById('pdpCashback');

        if (!variant) {
            selectedVariantId = '';
            if (!pdpVariants.length) {
                if (stockEl) {
                    stockEl.textContent = 'Available';
                    stockEl.classList.remove('out');
                }
                if (addBtn) addBtn.disabled = false;
                if (buyBtn) buyBtn.disabled = false;
                return;
            }
            if (stockEl) {
                stockEl.textContent = 'Select available options';
                stockEl.classList.add('out');
            }
            if (addBtn) addBtn.disabled = true;
            if (buyBtn) buyBtn.disabled = true;
            return;
        }

        selectedVariantId = variant.id;
        if (priceNow) priceNow.textContent = formatMoney(variant.price);
        if (priceOld) {
            priceOld.textContent = variant.compare_price > variant.price ? formatMoney(variant.compare_price) : '';
            priceOld.classList.toggle('d-none', !(variant.compare_price > variant.price));
        }
        if (priceSave) {
            priceSave.textContent = variant.compare_price > variant.price ?
                `Save ${formatMoney(variant.save_amount)} (${variant.discount_percent}% Off)` :
                '';
            priceSave.classList.toggle('d-none', !(variant.compare_price > variant.price));
        }
        if (discountBadge) {
            discountBadge.textContent = variant.discount_percent > 0 ? `${variant.discount_percent}% OFF` : '';
            discountBadge.classList.toggle('d-none', !(variant.discount_percent > 0));
        }
        if (cashback) cashback.textContent = `Get ${variant.coins} NB Coins on this purchase!`;
        if (skuEl) skuEl.textContent = `SKU: ${variant.sku}`;
        if (selectedEl) selectedEl.textContent = pdpVariantLabel(variant);
        if (stockEl) {
            stockEl.classList.toggle('out', !variant.available);
            if (variant.available) {
                stockEl.textContent = 'Available';

                // Update Quantity Input Max
                const qtyInput = document.getElementById('pdpQtyVal');
                if (qtyInput) {
                    const maxStock = variant.track_stock ? variant.stock_qty : 99;
                    qtyInput.max = maxStock;
                    if (parseInt(qtyInput.value) > maxStock) {
                        qtyInput.value = maxStock;
                    }
                }
            } else {
                stockEl.textContent = 'Out of stock';
            }
        }
        if (addBtn) addBtn.disabled = !variant.available;
        if (buyBtn) buyBtn.disabled = !variant.available;
    }

    function addPdpGuestCartFallback(productId, quantity = 1, variantId = null) {
        const key = 'nb_pending_cart';
        const itemKey = String(Number(productId || 0));
        let items = [];

        try {
            const parsed = JSON.parse(localStorage.getItem(key) || '[]');
            items = Array.isArray(parsed) ? parsed : [];
        } catch (error) {
            items = [];
        }

        const activeVariant = pdpVariants.find(variant => String(variant.id) === String(variantId));
        const meta = {
            ...pdpFallbackCartMeta,
            variant_name: pdpVariantLabel(activeVariant),
            unit_price: Number(activeVariant?.price || pdpFallbackCartMeta.unit_price || 0),
        };
        const variantKey = String(Number(variantId || 0));
        const found = items.find(item =>
            String(Number(item.product_id || 0)) === itemKey &&
            String(Number(item.product_variant_id || 0)) === variantKey
        );

        if (found) {
            found.quantity = Number(found.quantity || 0) + Number(quantity || 1);
            found.product_variant_id = variantId ? Number(variantId) : null;
            found.product_name = meta.product_name;
            found.variant_name = meta.variant_name;
            found.image = meta.image;
            found.unit_price = meta.unit_price;
            found.product_url = meta.product_url;
        } else {
            items.push({
                product_id: Number(productId),
                product_variant_id: variantId ? Number(variantId) : null,
                quantity: Number(quantity || 1),
                ...meta,
            });
        }

        localStorage.setItem(key, JSON.stringify(items));

        const count = items.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
        const cartCount = document.getElementById('cartCount');
        if (cartCount) cartCount.textContent = String(count);
        return true;
    }

    function selectPdpOption(button) {
        const attribute = button.dataset.attribute;
        const value = button.dataset.value;
        pdpSelectedAttributes[attribute] = value;

        const candidateVariants = pdpVariants.filter(variant => {
            const vVal = getAttrValue(variant.attributes, attribute);
            return vVal !== undefined && String(vVal).toLowerCase().trim() === String(value).toLowerCase().trim();
        });

        let bestVariant = null;
        if (candidateVariants.length > 0) {
            let maxMatches = -1;
            candidateVariants.forEach(variant => {
                let matches = 0;
                Object.keys(variant.attributes || {}).forEach(k => {
                    const selVal = getAttrValue(pdpSelectedAttributes, k);
                    if (selVal !== undefined && String(variant.attributes[k]).toLowerCase().trim() === String(selVal).toLowerCase().trim()) {
                        matches++;
                    }
                });
                if (matches > maxMatches) {
                    maxMatches = matches;
                    bestVariant = variant;
                }
            });
        }

        if (bestVariant) {
            Object.keys(pdpSelectedAttributes).forEach(key => delete pdpSelectedAttributes[key]);
            Object.assign(pdpSelectedAttributes, bestVariant.attributes);
        }

        syncPdpOptionButtons();
        updateVariantAvailability();
        applyVariantToPage(bestVariant || candidateVariants[0] || null);
    }

    async function handleAddToCart(productId, btn) {
        const variantId = selectedVariantId || null;
        if (pdpVariants.length && !variantId) {
            if (typeof nbToast === 'function') nbToast('Please choose a product option first.', 'error');
            return;
        }

        const qtyInput = document.getElementById('pdpQtyVal');
        const qty = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;

        if (typeof window.addToCart === 'function') {
            if (btn) btn.disabled = true;
            const added = await window.addToCart(productId, qty, variantId, btn);
            if (btn) btn.disabled = false;
            if (added && typeof nbToast === 'function') {
                nbToast('Product added to cart.', 'success');
            } else if (!added && !pdpIsLoggedIn && addPdpGuestCartFallback(productId, qty, variantId)) {
                if (typeof nbToast === 'function') nbToast('Product added to cart.', 'success');
            }
        } else {
            console.warn('Global addToCart not found, using fallback');
            if (!pdpIsLoggedIn && addPdpGuestCartFallback(productId, qty, variantId)) {
                if (typeof nbToast === 'function') nbToast('Product added to cart.', 'success');
            } else if (typeof nbToast === 'function') {
                nbToast('Cart is still loading. Please try again.', 'warning');
            }
        }
    }

    async function handleBuyNow(productId, btn) {
        const variantId = selectedVariantId || null;
        if (pdpVariants.length && !variantId) {
            if (typeof nbToast === 'function') nbToast('Please choose a product option first.', 'error');
            return;
        }

        const qtyInput = document.getElementById('pdpQtyVal');
        const qty = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;

        if (typeof window.addToCart === 'function') {
            if (btn) btn.disabled = true;
            const added = await window.addToCart(productId, qty, variantId, btn);
            if (btn) btn.disabled = false;
            if (added) {
                window.location.href = checkoutUrl;
            } else if (!pdpIsLoggedIn && addPdpGuestCartFallback(productId, qty, variantId)) {
                window.location.href = checkoutUrl;
            }
        } else {
            if (!pdpIsLoggedIn && addPdpGuestCartFallback(productId, qty, variantId)) {
                window.location.href = checkoutUrl;
            } else if (typeof nbToast === 'function') {
                nbToast('Cart is still loading. Please try again.', 'warning');
            }
        }
    }

    window.changePdpImage = changePdpImage;
    window.handleAddToCart = handleAddToCart;
    window.handleBuyNow = handleBuyNow;

    // Star Rating Interaction
    document.querySelectorAll('.star-opt').forEach(star => {
        star.addEventListener('click', function () {
            const val = this.getAttribute('data-val');
            document.getElementById('ratingValue').value = val;

            // Color stars
            document.querySelectorAll('.star-opt').forEach(s => {
                if (s.getAttribute('data-val') <= val) {
                    s.style.color = '#FFD700'; // Gold
                } else {
                    s.style.color = '#ddd';
                }
            });
        });

        star.addEventListener('mouseover', function () {
            const val = this.getAttribute('data-val');
            document.querySelectorAll('.star-opt').forEach(s => {
                if (s.getAttribute('data-val') <= val) {
                    s.style.color = '#FFD700';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });

        star.addEventListener('mouseout', function () {
            const val = document.getElementById('ratingValue').value;
            document.querySelectorAll('.star-opt').forEach(s => {
                if (s.getAttribute('data-val') <= val) {
                    s.style.color = '#FFD700';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });
    });

    // Default set 5 stars
    window.addEventListener('DOMContentLoaded', () => {
        const addToCartBtn = document.getElementById('pdpAddToCartBtn');
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', function(e) {
                e.preventDefault();
                handleAddToCart(config.productId, this);
            });
        }
        
        const buyNowBtn = document.getElementById('pdpBuyNowBtn');
        if (buyNowBtn) {
            buyNowBtn.addEventListener('click', function(e) {
                e.preventDefault();
                handleBuyNow(config.productId, this);
            });
        }

        document.querySelectorAll('.pdp-option-btn').forEach(button => {
            button.addEventListener('click', () => selectPdpOption(button));
        });
        const initialVariant = findMatchingVariant() || pdpVariants[0] || null;
        if (initialVariant && initialVariant.attributes) {
            Object.assign(pdpSelectedAttributes, initialVariant.attributes);
        }
        syncPdpOptionButtons();
        updateVariantAvailability();
        if (pdpVariants.length) {
            applyVariantToPage(initialVariant);
        } else {
            applyVariantToPage(null);
        }
        initPdpGallerySwipe();
        initPdpImageZoom();

        const defaultVal = 5;
        document.querySelectorAll('.star-opt').forEach(s => {
            if (s.getAttribute('data-val') <= defaultVal) {
                s.style.color = '#FFD700';
            }
        });

        // PDP Quantity Logic
        const qtyVal = document.getElementById('pdpQtyVal');
        const qtyPlus = document.getElementById('pdpQtyPlus');
        const qtyMinus = document.getElementById('pdpQtyMinus');

        if (qtyVal && qtyPlus && qtyMinus) {
            qtyPlus.addEventListener('click', () => {
                const max = parseInt(qtyVal.max) || 99;
                const current = parseInt(qtyVal.value) || 1;
                if (current < max) {
                    qtyVal.value = current + 1;
                } else if (typeof nbToast === 'function') {
                    nbToast(`Only ${max} units available in stock.`, 'warning');
                }
            });

            qtyMinus.addEventListener('click', () => {
                const current = parseInt(qtyVal.value) || 1;
                if (current > 1) {
                    qtyVal.value = current - 1;
                }
            });
        }

        const featureSlider = document.getElementById('flavorRow');
        const sliderShell = featureSlider?.closest('.feature-slider-shell');
        if (featureSlider && sliderShell) {
            const scrollByCard = direction => {
                const firstCard = featureSlider.querySelector('.flavor-opt');
                const distance = firstCard ? firstCard.getBoundingClientRect().width + 10 : 142;
                featureSlider.scrollBy({
                    left: direction * distance,
                    behavior: 'smooth',
                });
            };

            sliderShell.querySelector('.feature-slider-prev')?.addEventListener('click', () => scrollByCard(-
                1));
            sliderShell.querySelector('.feature-slider-next')?.addEventListener('click', () => scrollByCard(1));
        }
    });
})();

// --- Return Modal Logic ---
window.initNbReturnModal = function (config) {
    const overlay = document.getElementById("nbRetModalOverlay");
    const openBtn = document.getElementById("nbRetModalOpenBtn");
    const closeBtn = document.getElementById("nbRetModalCloseBtn");
    const form = document.getElementById("returnRequestForm");
    const orderSelect = document.getElementById("returnOrderSelect");

    if (!overlay || !openBtn || !closeBtn || !form || !orderSelect) return;

    let deliveredReturnOrders = [];

    function openModal() {
        overlay.style.display = "flex";
        setTimeout(() => overlay.classList.add("show"), 10);
    }

    function closeModal() {
        overlay.classList.remove("show");
        setTimeout(() => overlay.style.display = "none", 300);
    }

    openBtn.addEventListener("click", openModal);
    closeBtn.addEventListener("click", closeModal);
    overlay.addEventListener("click", (e) => {
        if (e.target === overlay) closeModal();
    });

    function renderReturns(returns) {
        const container = document.getElementById("myReturnsContainer");
        if (!container) return;
        if (!returns.length) {
            container.innerHTML = "<p>No return requests found.</p>";
            return;
        }

        container.innerHTML = returns.map(function (item) {
            const orderNumber = item.order ? item.order.order_number : "-";
            const qty = (item.items || []).reduce(function (sum, line) { return sum + Number(line.quantity || 0); }, 0);
            return `<div style="padding:10px 0;border-bottom:1px solid var(--line,#eee);">
            <p><strong>${item.return_number}</strong> - ${String(item.status || "").toUpperCase()}</p>
            <p style="font-size:.82rem;color:var(--mu,#666)">Order: ${orderNumber}${qty ? ` - Qty: ${qty}` : ""}</p>
            </div>`;
        }).join("");
    }

    function renderDeliveredOrders(orders) {
        const delivered = orders.filter(function (order) {
            return order.status === "delivered" && (order.items || []).some(function (item) {
                return Number(item.returnable_quantity || 0) > 0;
            });
        });
        deliveredReturnOrders = delivered;
        orderSelect.innerHTML = "<option value=\"\">Select Delivered Order</option>";
        delivered.forEach(function (order) {
            const option = document.createElement("option");
            option.value = order.id;
            option.textContent = `${order.order_number} - \u20B9${Number(order.grand_total || 0).toFixed(2)}`;
            orderSelect.appendChild(option);
        });
        renderReturnItemsForOrder("");
    }

    function renderReturnItemsForOrder(orderId) {
        const wrap = document.getElementById("returnItemsContainer");
        if (!wrap) return;

        const order = deliveredReturnOrders.find(function (item) {
            return String(item.id) === String(orderId);
        });

        if (!order) {
            wrap.innerHTML = "";
            return;
        }

        const items = (order.items || []).filter(function (item) {
            return Number(item.returnable_quantity || 0) > 0;
        });

        if (!items.length) {
            wrap.innerHTML = "<p style=\"font-size:.85rem;color:var(--mu,#666);\">No returnable quantity left for this order.</p>";
            return;
        }

        wrap.innerHTML = `
            <div style="font-size:.86rem;font-weight:900;color:var(--dk);margin-bottom:8px;">Select quantity to return</div>
            ${items.map(function (item) {
            const maxQty = Number(item.returnable_quantity || 0);
            const returnedQty = Number(item.returned_quantity || 0);
            return `<div class="nb-ret-item-row">
                <div class="nb-ret-item-details">
                <div class="nb-ret-item-title">${item.product_name || "Product"}</div>
                <div class="nb-ret-item-meta">Purchased: ${item.quantity || 0}${returnedQty ? ` - Already requested: ${returnedQty}` : ""}</div>
                </div>
                <input type="number" class="nb-ret-qty-input" data-order-item-id="${item.id}" min="0" max="${maxQty}" value="0" aria-label="Return quantity for ${item.product_name || "product"}">
            </div>`;
        }).join("")}
        `;
    }

    async function loadReturnData() {
        const [ordersResponse, returnsResponse] = await Promise.all([
            fetch(config.ordersUrl, { headers: { "Accept": "application/json" } }),
            fetch(config.returnsUrl, { headers: { "Accept": "application/json" } })
        ]);

        if (ordersResponse.ok) {
            const ordersPayload = await ordersResponse.json();
            renderDeliveredOrders(ordersPayload.data || []);
        }

        if (returnsResponse.ok) {
            const returnsPayload = await returnsResponse.json();
            renderReturns(returnsPayload.data || []);
        }
    }

    async function submitReturnRequest(event) {
        event.preventDefault();
        const orderId = orderSelect.value;
        const reason = document.getElementById("returnReasonSelect").value;
        const comments = document.getElementById("returnCommentsInput").value.trim();
        const attachments = document.getElementById("returnAttachmentsInput").files;
        const message = document.getElementById("returnFormMessage");
        const selectedItems = Array.from(document.querySelectorAll(".nb-ret-qty-input"))
            .map(function (input) {
                return {
                    order_item_id: input.getAttribute("data-order-item-id"),
                    quantity: Number(input.value || 0),
                    max: Number(input.max || 0)
                };
            })
            .filter(function (item) {
                return item.order_item_id && item.quantity > 0;
            });

        if (!orderId || !reason) {
            message.textContent = "Please select an order and a return reason.";
            return;
        }
        if (!selectedItems.length) {
            message.textContent = "Please enter return quantity for at least one product.";
            return;
        }
        const invalidQty = selectedItems.find(function (item) {
            return item.quantity > item.max;
        });
        if (invalidQty) {
            message.textContent = "Return quantity cannot be greater than purchased quantity left.";
            return;
        }

        const formData = new FormData();
        formData.append("reason", reason);
        if (comments) formData.append("comments", comments);
        selectedItems.forEach(function (item, index) {
            formData.append(`items[${index}][order_item_id]`, item.order_item_id);
            formData.append(`items[${index}][quantity]`, item.quantity);
        });

        for (let i = 0; i < attachments.length; i++) {
            formData.append("attachments[]", attachments[i]);
        }

        message.textContent = "Submitting...";

        const response = await fetch(config.createReturnUrlTemplate.replace("__ORDER_ID__", orderId), {
            method: "POST",
            headers: {
                "Accept": "application/json",
                "X-CSRF-TOKEN": config.csrfToken
            },
            body: formData
        });

        if (!response.ok) {
            const payload = await response.json().catch(function () { return {}; });
            message.textContent = payload.message || "Unable to submit return request.";
            return;
        }

        message.textContent = "Return request submitted successfully.";
        setTimeout(() => {
            closeModal();
            message.textContent = "";
            document.getElementById("returnReasonSelect").value = "";
            document.getElementById("returnCommentsInput").value = "";
            document.getElementById("returnAttachmentsInput").value = "";
            orderSelect.value = "";
            renderReturnItemsForOrder("");
        }, 1500);

        await loadReturnData();
    }

    form.addEventListener("submit", submitReturnRequest);
    orderSelect.addEventListener("change", function () {
        renderReturnItemsForOrder(this.value);
    });

    loadReturnData();
};


window.addEventListener("DOMContentLoaded", () => {
    // --- Order Details Return Modal ---
    const orderRetOverlay = document.getElementById("orderDetailReturnModalOverlay");
    const orderRetForm = document.getElementById("orderDetailReturnForm");
    const orderRetErrorEl = document.getElementById("returnQuantityError");

    // We bind open/close globally if there are triggers.
    window.openOrderDetailReturnModal = function () {
        if (orderRetOverlay) {
            orderRetOverlay.classList.remove("d-none");
            orderRetOverlay.style.display = "flex";
            setTimeout(() => orderRetOverlay.classList.add("show"), 10);
        }
    };

    window.closeOrderDetailReturnModal = function () {
        if (orderRetOverlay) {
            orderRetOverlay.classList.remove("show");
            setTimeout(() => {
                orderRetOverlay.style.display = "none";
                orderRetOverlay.classList.add("d-none");
            }, 300);
        }
    };

    if (orderRetOverlay) {
        orderRetOverlay.addEventListener("click", (e) => {
            if (e.target === orderRetOverlay) window.closeOrderDetailReturnModal();
        });
    }

    if (orderRetForm) {
        orderRetForm.addEventListener("submit", function (event) {
            let hasQuantity = false;

            this.querySelectorAll("[data-return-line]").forEach((line) => {
                const hiddenInput = line.querySelector("[data-return-hidden]");
                const qtyInput = line.querySelector("[data-return-qty]");
                const qty = Number(qtyInput.value || 0);
                const max = Number(qtyInput.max || 0);

                qtyInput.value = Math.max(0, Math.min(qty, max));

                if (Number(qtyInput.value) > 0) {
                    hasQuantity = true;
                    hiddenInput.disabled = false;
                    qtyInput.disabled = false;
                } else {
                    hiddenInput.disabled = true;
                    qtyInput.disabled = true;
                }
            });

            if (!hasQuantity) {
                event.preventDefault();
                this.querySelectorAll("[data-return-hidden], [data-return-qty]").forEach((input) => {
                    input.disabled = false;
                });
                if (orderRetErrorEl) {
                    orderRetErrorEl.style.display = "block";
                }
            }
        });
    }
});



/* --- ABOUT US JS --- */
(function () {
    // Scroll Reveal
    const revObs = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('visible');
                revObs.unobserve(e.target);
            }
        });
    }, {
        threshold: 0.1
    });
    document.querySelectorAll('.about-reveal').forEach(r => revObs.observe(r));

    // Counter animation
    const countObs = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (!e.isIntersecting) return;
            const el = e.target;
            const raw = el.textContent;
            const hasK = raw.includes('K');
            const hasStar = raw.includes('★');
            const hasPct = raw.includes('%');
            const num = parseFloat(raw.replace(/[^0-9.]/g, ''));
            let start = 0;
            const dur = 1600,
                steps = 60,
                inc = num / steps;
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
            countObs.unobserve(el);
        });
    }, {
        threshold: 0.5
    });
    document.querySelectorAll('.hstat-num').forEach(el => countObs.observe(el));

    // Accordion toggle function

    function toggleAboutUsAccordion(header) {
        const item = header.closest('.acc-item');
        const isOpen = item.classList.contains('open');
        document.querySelectorAll('.acc-item.open').forEach(el => el.classList.remove('open'));
        if (!isOpen) item.classList.add('open');
    }

    window.toggleAboutUsAccordion = toggleAboutUsAccordion;
})();


/* ==========================================================================
   Cart Page: Items, Quantity Updates, Guest Cart Rendering
   ========================================================================== */
(function () {
    function readCartPageConfig() {
        if (window.cartPageConfig) {
            return window.cartPageConfig;
        }

        const configEl = document.getElementById('nbCartPageConfig');
        if (!configEl) {
            return {};
        }

        try {
            return JSON.parse(configEl.textContent || '{}');
        } catch (error) {
            console.warn('Unable to parse cart page config.', error);
            return {};
        }
    }

    const cartPageConfig = readCartPageConfig();
    if (!cartPageConfig.enabled) return;
    const { cartUrl, deleteTemplate, updateTemplate, csrf } = cartPageConfig;

    /* ── helpers ── */
    function money(v) {
        return `Rs. ${Number(v || 0).toLocaleString('en-IN', { maximumFractionDigits: 0 })}`;
    }

    function clampQty(v, maxStock) {
        let qty = Math.max(1, parseInt(v, 10) || 1);
        if (maxStock != null && Number.isFinite(maxStock) && maxStock > 0) {
            qty = Math.min(qty, maxStock);
        }
        return qty;
    }

    function normalizeImage(src) {
        if (!src) return '/img/product2.png';
        if (src.startsWith('http://') || src.startsWith('https://') || src.startsWith('/')) return src;
        return `/${src.replace(/^\/+/, '')}`;
    }

    function storageImageUrl(path) {
        if (!path) return '';
        const value = String(path);
        if (value.startsWith('http://') || value.startsWith('https://') || value.startsWith('/')) return value;
        return `/storage/${value.replace(/^\/+/, '')}`;
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function cartItemImage(item) {
        return normalizeImage(
            storageImageUrl(item?.product?.card_image_path) ||
            storageImageUrl(item?.product_variant?.image_path) ||
            storageImageUrl(item?.product?.primary_image?.image_path) ||
            storageImageUrl(item?.product?.images?.[0]?.image_path) ||
            '/img/product2.png'
        );
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

        if (attributeParts.length) return attributeParts.join(' / ');

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

    /* ── pending (guest) cart helpers ── */
    function getPendingItems() {
        try {
            const raw = localStorage.getItem('nb_pending_cart');
            const parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? parsed : [];
        } catch (_) { return []; }
    }

    function savePendingItems(items) {
        localStorage.setItem('nb_pending_cart', JSON.stringify(items || []));
    }

    function pendingKey(productId, variantId = null) {
        return `${Number(productId || 0)}::${Number(variantId || 0)}`;
    }

    function removePendingItem(productId, variantId = null) {
        const key = pendingKey(productId, variantId);
        savePendingItems(getPendingItems().filter(it => pendingKey(it.product_id, it.product_variant_id) !== key));
    }

    function updatePendingQty(productId, variantId = null, qty = 1) {
        const key = pendingKey(productId, variantId);
        savePendingItems(getPendingItems().map(it =>
            pendingKey(it.product_id, it.product_variant_id) === key
                ? { ...it, quantity: clampQty(qty) }
                : it
        ));
    }

    /* ── summary ── */
    function setCartSummary(count, total) {
        document.getElementById('cartPageCount').textContent = count;
        document.getElementById('cartPageSubtotal').textContent = money(total);

        const checkoutBtn = document.getElementById('cartCheckoutBtn');
        if (checkoutBtn) {
            const hasItems = Number(count || 0) > 0;
            checkoutBtn.style.opacity = hasItems ? '' : '0.55';
            checkoutBtn.style.pointerEvents = hasItems ? '' : 'none';
            checkoutBtn.setAttribute('aria-disabled', hasItems ? 'false' : 'true');
        }
    }

    /* ── build a cart row DOM element ── */
    function createCartRow(image, name, price, qty, variantLabel = '', maxStock) {
        const effectiveMax = (maxStock != null && Number.isFinite(maxStock) && maxStock > 0) ? maxStock : 999;
        const row = document.createElement('div');
        row.className = 'cart-page-item';
        row.innerHTML = `
                    <div class="cart-page-item-image">
                        <img src="${escapeHtml(image)}" alt="${escapeHtml(name)}" loading="lazy">
                    </div>
                    <div class="cart-page-item-content">
                        <h5>${escapeHtml(name)}</h5>
                        ${variantLabel ? `<div class="cart-page-variant">${escapeHtml(variantLabel)}</div>` : ''}
                        <p class="cart-page-item-price">${money(price)}</p>
                        <div class="cart-page-qty-row">
                            <button type="button" class="cart-page-qty-btn" data-qty-delta="-1" aria-label="Decrease quantity">-</button>
                            <input type="number" min="1" max="${effectiveMax}" class="cart-page-qty-val" value="${clampQty(qty, effectiveMax)}" aria-label="Quantity">
                            <button type="button" class="cart-page-qty-btn" data-qty-delta="1" aria-label="Increase quantity">+</button>
                        </div>
                    </div>
                    <button type="button" class="cart-page-remove-btn" aria-label="Remove item">&times;</button>
                `;
        return row;
    }

    /* ── bind +/- controls on a row ── */
    function bindQtyControls(row, initialQty, onCommit, maxStock) {
        const effectiveMax = (maxStock != null && Number.isFinite(maxStock) && maxStock > 0) ? maxStock : null;
        const qtyRow = row.querySelector('.cart-page-qty-row');
        const input = qtyRow.querySelector('.cart-page-qty-val');
        let currentQty = clampQty(initialQty, effectiveMax);
        let pendingQty = currentQty;
        let saveTimer = null;

        function setSaving(isSaving) {
            qtyRow.classList.toggle('is-updating', isSaving);
            qtyRow.querySelectorAll('.cart-page-qty-btn, .cart-page-qty-val').forEach(el => el.disabled = isSaving);
        }

        async function submit(nextVal, options = {}) {
            const next = clampQty(nextVal, effectiveMax);
            pendingQty = next;
            input.value = next;

            if (saveTimer) {
                clearTimeout(saveTimer);
                saveTimer = null;
            }

            if (next === currentQty) return;

            if (!options.immediate) {
                saveTimer = setTimeout(() => submit(pendingQty, { immediate: true }), 550);
                return;
            }

            setSaving(true);

            try {
                await onCommit(next);
                currentQty = next;
                input.value = next;
            } catch (err) {
                input.value = currentQty;
                pendingQty = currentQty;
                alert(err.message || 'Unable to update quantity.');
            } finally {
                setSaving(false);
            }
        }

        qtyRow.querySelectorAll('.cart-page-qty-btn').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                submit(clampQty(input.value, effectiveMax) + Number(btn.dataset.qtyDelta || 0));
            });
        });

        input.addEventListener('change', () => submit(input.value));
        input.addEventListener('blur', () => submit(input.value, { immediate: true }));
        input.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); submit(input.value, { immediate: true }); } });
    }

    /* ── render guest/pending cart ── */
    function renderPendingCart() {
        const items = getPendingItems();
        const subtotal = items.reduce((s, it) => s + Number(it.unit_price || 0) * Number(it.quantity || 0), 0);
        const totalQty = items.reduce((s, it) => s + Number(it.quantity || 0), 0);

        setCartSummary(totalQty, subtotal);

        const list = document.getElementById('cartPageItems');
        const empty = document.getElementById('cartPageEmpty');
        list.innerHTML = '';

        if (!items.length) { empty.style.display = 'block'; return; }
        empty.style.display = 'none';

        items.forEach(it => {
            const qty = Number(it.quantity || 1);
            const variantLabel = cartVariantLabel(it);
            const row = createCartRow(
                it.image || '/img/product2.png',
                it.product_name || 'Product',
                it.unit_price,
                qty,
                variantLabel
            );

            const maxStock = it.max_stock !== undefined ? it.max_stock : 999;
            bindQtyControls(row, qty, async value => {
                updatePendingQty(it.product_id, it.product_variant_id, value);
                renderPendingCart();
            }, maxStock);

            row.querySelector('.cart-page-remove-btn').addEventListener('click', () => {
                removePendingItem(it.product_id, it.product_variant_id);
                renderPendingCart();
            });

            list.appendChild(row);
        });
    }

    /* ── API calls ── */
    async function apiUpdateQty(itemId, qty) {
        const res = await fetch(updateTemplate.replace('__ITEM__', itemId), {
            method: 'PATCH',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {})
            },
            body: JSON.stringify({ quantity: clampQty(qty) })
        });
        if (!res.ok) {
            const p = await res.json().catch(() => ({}));
            throw new Error(p.message || 'Unable to update quantity.');
        }
        return res.json().catch(() => ({}));
    }

    async function apiRemoveItem(itemId) {
        const res = await fetch(deleteTemplate.replace('__ITEM__', itemId), {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {})
            }
        });
        if (!res.ok) {
            const p = await res.json().catch(() => ({}));
            throw new Error(p.message || 'Unable to remove item.');
        }
        return res.json().catch(() => ({}));
    }

    /* ── render authenticated cart ── */
    async function loadCart() {
        const res = await fetch(cartUrl, { headers: { 'Accept': 'application/json' } });
        const isGuest = res.status === 401 || res.status === 419 || (res.redirected && /\/login(?:[/?#]|$)/i.test(res.url));

        if (isGuest) { renderPendingCart(); return; }
        if (!res.ok) return;

        const payload = await res.json().catch(() => ({}));
        const items = payload.cart?.items || [];
        const total = payload.pricing?.display_subtotal || 0;
        const totalQty = items.reduce((s, it) => s + Number(it.quantity || 0), 0);

        setCartSummary(totalQty, total);

        const list = document.getElementById('cartPageItems');
        const empty = document.getElementById('cartPageEmpty');
        list.innerHTML = '';

        if (!items.length) { empty.style.display = 'block'; return; }
        empty.style.display = 'none';

        items.forEach(it => {
            const qty = Number(it.quantity || 1);
            const price = it.product_variant ? it.product_variant.display_price : it.product?.display_price;
            const image = cartItemImage(it);
            const variantLabel = cartVariantLabel(it);
            const itemMaxStock = it.available_stock != null ? Number(it.available_stock) : null;

            const row = createCartRow(image, it.product?.name || 'Product', price, qty, variantLabel, itemMaxStock);

            bindQtyControls(row, qty, async value => {
                row.classList.add('is-updating');
                try {
                    await apiUpdateQty(it.id, value);
                    await loadCart();
                } finally {
                    row.classList.remove('is-updating');
                }
            }, itemMaxStock);

            row.querySelector('.cart-page-remove-btn').addEventListener('click', async () => {
                row.classList.add('is-updating');
                try {
                    await apiRemoveItem(it.id);
                    await loadCart();
                } catch (err) {
                    alert(err.message || 'Unable to remove item.');
                    row.classList.remove('is-updating');
                }
            });

            list.appendChild(row);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const notice = sessionStorage.getItem('nb_cart_notice');
        if (notice) {
            sessionStorage.removeItem('nb_cart_notice');
            if (typeof nbToast === 'function') nbToast(notice, 'warning');
        }
        loadCart();
    });
})();

/* --- CHECKOUT PAGE JS --- */
(function () {
    const config = window.NB_CHECKOUT_CONFIG || {};
    if (!config.enabled) return;

    /* ══ STEP NAVIGATION ══ */
    function goToPayment() {
        const addrCard = document.getElementById('addressCard');
        const payCard = document.getElementById('paymentCard');
        const addrBadge = document.getElementById('addrBadge');
        const step2 = document.getElementById('step-addr');
        const step3 = document.getElementById('step-pay');

        addrCard.classList.remove('active-card');
        addrBadge.textContent = '✓';
        addrBadge.classList.add('done-badge');
        step2.classList.remove('active');
        step2.classList.add('done');
        step2.querySelector('.ts-num').textContent = '✓';

        payCard.style.opacity = '1';
        payCard.style.pointerEvents = 'all';
        payCard.classList.add('active-card');
        step3.classList.add('active');

        document.getElementById('progressFill').style.width = '100%';
        document.getElementById('placeOrderWrap').style.display = 'block';

        payCard.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }

    function editSection(sec) {
        if (sec === 'login') alert('Redirecting to login page…');
    }

    /* ══ ADDRESS ══ */
    const checkoutCitiesUrlTemplate = config.citiesUrlTemplate || '';
    const checkoutStateOptions = Array.isArray(config.states) ? config.states : [];
    const checkoutCitiesCache = new Map();
    let checkoutCityOptions = [];
    let checkoutCitiesRequestId = 0;

    function normalizeLookup(value = '') {
        return String(value || '').trim().toLowerCase();
    }

    function closeCheckoutDropdown(id) {
        const menu = document.getElementById(id);
        if (menu) menu.hidden = true;
    }

    function renderCheckoutDropdown(menuId, options = [], onSelect) {
        const menu = document.getElementById(menuId);
        if (!menu) return;

        menu.innerHTML = '';

        if (!options.length) {
            const empty = document.createElement('div');
            empty.className = 'checkout-combobox-empty';
            empty.textContent = 'No matches found';
            menu.appendChild(empty);
            menu.hidden = false;
            return;
        }

        options.forEach((option, index) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `checkout-combobox-option${index === 0 ? ' active' : ''}`;
            btn.textContent = option.name || option;
            btn.addEventListener('mousedown', event => {
                event.preventDefault();
                onSelect(option);
            });
            menu.appendChild(btn);
        });

        menu.hidden = false;
    }

    function matchingStates(query = '') {
        const target = normalizeLookup(query);
        const states = checkoutStateOptions || [];

        if (!target) return states;

        return states.filter(state => normalizeLookup(state.name).includes(target));
    }

    function matchingCities(query = '') {
        const target = normalizeLookup(query);
        if (!target) return checkoutCityOptions;

        return checkoutCityOptions.filter(city => normalizeLookup(city).includes(target));
    }

    async function selectStateOption(state) {
        const stateEl = document.getElementById('stateField');
        const stateCodeEl = document.getElementById('stateCodeField');
        const cityEl = document.getElementById('cityField');

        if (stateEl) stateEl.value = state.name || '';
        if (stateCodeEl) stateCodeEl.value = state.code || '';
        closeCheckoutDropdown('stateDropdown');
        await loadCitiesForState(state.code || '');

        if (cityEl && !cityEl.disabled) {
            cityEl.focus();
            renderCheckoutDropdown('cityDropdown', matchingCities(cityEl.value), selectCityOption);
        }
    }

    function selectCityOption(city) {
        const cityEl = document.getElementById('cityField');

        if (cityEl) cityEl.value = city || '';
        closeCheckoutDropdown('cityDropdown');
    }

    function getNewAddressPayload() {
        const firstName = document.getElementById('firstName')?.value?.trim() || '';
        const lastName = document.getElementById('lastName')?.value?.trim() || '';
        const phoneRaw = document.getElementById('addressPhone')?.value?.trim() || '';
        const line1 = document.getElementById('addressLine1')?.value?.trim() || '';
        const line2 = document.getElementById('addressLine2')?.value?.trim() || '';
        const postalCode = document.getElementById('newPincode')?.value?.trim() || '';
        const city = document.getElementById('cityField')?.value?.trim() || '';
        const state = document.getElementById('stateField')?.value?.trim() || '';
        const activeTypeBtn = document.querySelector('.addr-type-btn.active');
        const label = activeTypeBtn ? activeTypeBtn.getAttribute('data-type') : 'Home';
        const phone = phoneRaw.replace(/\D/g, '').slice(-10);

        return {
            label: label || 'Home',
            full_name: `${firstName} ${lastName}`.trim(),
            phone,
            email: config.email || '',
            address_line_1: line1,
            address_line_2: line2,
            city,
            state,
            postal_code: postalCode,
            country: 'India'
        };
    }

    /* ══ SAVED ADDRESS HELPERS ══ */
    function switchAddrTab(tab) {
        const savedPanel = document.getElementById('savedAddrPanel');
        const newPanel = document.getElementById('newAddrPanel');
        const tabSaved = document.getElementById('tabSaved');
        const tabNew = document.getElementById('tabNew');

        if (tab === 'saved') {
            if (savedPanel) savedPanel.style.display = 'block';
            if (newPanel) newPanel.style.display = 'none';
            tabSaved?.classList.add('active');
            tabNew?.classList.remove('active');
            // Restore selected address id from the currently highlighted card
            const sel = document.querySelector('.addr-item.selected');
            if (sel) window.__selectedAddressId = sel.dataset.addressId || '';
        } else {
            if (savedPanel) savedPanel.style.display = 'none';
            if (newPanel) newPanel.style.display = 'block';
            tabSaved?.classList.remove('active');
            tabNew?.classList.add('active');
            // Clear saved selection so new form is used
            window.__selectedAddressId = '';
        }
    }

    function selectSavedAddress(el, addressId) {
        document.querySelectorAll('#savedAddressList .addr-item')
            .forEach(a => a.classList.remove('selected'));
        el.classList.add('selected');
        window.__selectedAddressId = String(addressId);
    }

    async function deleteAddress(e, addressId, btn) {
        e.stopPropagation();
        nbConfirm('This address will be permanently removed.', async () => {

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetchWithCsrfRetry('/user/addresses/' + addressId, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            if (!res.ok) {
                const errPayload = await res.json().catch(() => ({}));
                nbToast(errPayload.message || 'Could not delete address. Please try again.', 'error');
                return;
            }

            const item = btn.closest('.addr-item');
            const wasSelected = item.classList.contains('selected');
            item.remove();

            const remaining = document.querySelectorAll('#savedAddressList .addr-item');

            if (wasSelected && remaining.length) {
                remaining[0].classList.add('selected');
                window.__selectedAddressId = remaining[0].dataset.addressId || '';
            }

            // Update count badge
            const countEl = document.getElementById('savedAddrCount');
            if (countEl) {
                const n = remaining.length;
                countEl.textContent = `${n} saved ${n === 1 ? 'address' : 'addresses'}`;
            }

            // No saved addresses left — hide tabs and show new form
            if (!remaining.length) {
                const tabsEl = document.querySelector('.addr-tabs');
                if (tabsEl) tabsEl.style.display = 'none';
                const savedP = document.getElementById('savedAddrPanel');
                if (savedP) savedP.style.display = 'none';
                const newPanel = document.getElementById('newAddrPanel');
                if (newPanel) newPanel.style.display = 'block';
                window.__selectedAddressId = '';
                if (countEl) countEl.textContent = '0 saved addresses';
            }

            nbToast('Address deleted successfully.', 'success');
        }, { title: 'Delete Address?', okText: 'Yes, Delete' });
    }

    function appendAddressToSavedList(address) {
        let listEl = document.getElementById('savedAddressList');
        const tabsEl = document.querySelector('.addr-tabs');
        const savedPanel = document.getElementById('savedAddrPanel');
        const newPanel = document.getElementById('newAddrPanel');

        // If no saved list exists yet, create the tabs and panel structure
        if (!listEl) {
            const cardBody = document.querySelector('#addressCard .card-body');
            if (!cardBody) return;

            // Create tabs
            let tabs = tabsEl;
            if (!tabs) {
                tabs = document.createElement('div');
                tabs.className = 'addr-tabs';
                tabs.innerHTML = `
                <button class="addr-tab active" id="tabSaved" onclick="switchAddrTab('saved')">📍 Saved Addresses</button>
                <button class="addr-tab" id="tabNew" onclick="switchAddrTab('new')">➕ Add New</button>
            `;
                cardBody.insertBefore(tabs, cardBody.firstChild);
            }

            // Create saved panel
            let sp = savedPanel;
            if (!sp) {
                sp = document.createElement('div');
                sp.id = 'savedAddrPanel';
                sp.innerHTML = `
                <div class="saved-addresses" id="savedAddressList"></div>
                <button class="add-addr-btn" onclick="switchAddrTab('new')" style="margin-top:8px">
                    <span>➕</span> Add a New Address
                </button>
            `;
                tabs.insertAdjacentElement('afterend', sp);
            }

            listEl = document.getElementById('savedAddressList');
        } else if (tabsEl) {
            tabsEl.style.display = 'flex';
        }

        // Deselect all existing
        listEl.querySelectorAll('.addr-item').forEach(a => a.classList.remove('selected'));

        // Build new card
        const card = document.createElement('div');
        card.className = 'addr-item selected';
        card.dataset.addressId = address.id;
        card.setAttribute('onclick', `selectSavedAddress(this, '${address.id}')`);
        card.innerHTML = `
        <div class="addr-radio"></div>
        <div class="addr-info" style="flex:1">
            <div class="addr-name">
                ${address.full_name || ''}
                <span class="addr-type-tag">${address.label || 'Home'}</span>
            </div>
            <div class="addr-line">
                ${address.address_line_1 || ''}${address.address_line_2 ? ', ' + address.address_line_2 : ''}${address.landmark ? ', Near ' + address.landmark : ''}
            </div>
            <div class="addr-line">
                ${address.city || ''}, ${address.state || ''} — ${address.postal_code || ''}
            </div>
            <div class="addr-phone">📱 ${address.phone || ''}</div>
        </div>
        <button class="addr-del-btn" title="Delete" onclick="deleteAddress(event, ${address.id}, this)">🗑</button>
    `;
        listEl.prepend(card);

        // Update count
        const countEl = document.getElementById('savedAddrCount');
        if (countEl) {
            const n = listEl.querySelectorAll('.addr-item').length;
            countEl.textContent = `${n} saved ${n === 1 ? 'address' : 'addresses'}`;
        }

        // Switch to saved tab
        window.__selectedAddressId = String(address.id);
        switchAddrTab('saved');
    }

    function clearNewAddressForm() {
        ['firstName', 'lastName', 'addressPhone', 'addressLine1', 'addressLine2', 'newPincode'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        const stateEl = document.getElementById('stateField');
        if (stateEl) stateEl.selectedIndex = 0;
        if (stateEl) stateEl.value = '';
        const stateCodeEl = document.getElementById('stateCodeField');
        if (stateCodeEl) stateCodeEl.value = '';
        resetCityDropdown();
        const activeType = document.querySelector('.addr-type-btn.active');
        if (activeType) activeType.classList.remove('active');
        const homeBtn = document.querySelector('.addr-type-btn[data-type="Home"]');
        if (homeBtn) homeBtn.classList.add('active');
    }

    async function saveAndGoToPayment() {
        // If user selected a saved address, go straight to payment
        if (window.__selectedAddressId) {
            goToPayment();
            return;
        }

        // Otherwise validate + save the new address form
        const payload = getNewAddressPayload();
        if (!hasRequiredNewAddressFields(payload)) {
            nbToast('Please fill all required address fields.', 'warning');
            return;
        }
        if (!findStateOption(payload.state)) {
            nbToast('Please select a valid state from the list.', 'warning');
            return;
        }
        if (!cityExistsInOptions(payload.city)) {
            nbToast('Please select a valid city from the list.', 'warning');
            return;
        }

        if (!isLoggedIn) {
            // Guest — keep in sessionStorage
            sessionStorage.setItem('nb_pending_address', JSON.stringify(payload));
            goToPayment();
            return;
        }

        const btn = document.getElementById('addressContinueBtn');
        if (btn) { btn.disabled = true; btn.textContent = 'Saving address…'; }

        const address = await persistNewAddress(payload);

        if (btn) { btn.disabled = false; btn.textContent = 'Continue to Payment'; }

        if (!address?.id) {
            return;
        }

        // Add the new address to the saved list dynamically
        appendAddressToSavedList(address);
        clearNewAddressForm();

        window.__selectedAddressId = String(address.id);
        goToPayment();
    }

    function hasRequiredNewAddressFields(payload = {}) {
        return !!(payload.full_name && payload.phone && payload.address_line_1 && payload.postal_code && payload.city &&
            payload.state);
    }

    async function persistNewAddress(payload, options = {}) {
        const res = await fetchWithCsrfRetry(api.addressesUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                ...(api.csrf ? {
                    'X-CSRF-TOKEN': api.csrf
                } : {})
            },
            body: JSON.stringify(payload)
        });

        const responsePayload = await res.json().catch(() => ({}));
        if (!res.ok) {
            if (!options.silent) {
                nbToast(responsePayload.message || 'Unable to save address.', 'error');
            }
            return null;
        }

        return responsePayload.data || null;
    }



    async function ensureCheckoutAddressReady() {
        if (window.__selectedAddressId) {
            return true;
        }

        const pendingAddress = getNewAddressPayload();
        if (hasRequiredNewAddressFields(pendingAddress)) {
            const createdAddress = await persistNewAddress(pendingAddress, {
                silent: true
            });
            if (createdAddress?.id) {
                window.__selectedAddressId = String(createdAddress.id);
                return true;
            }
        }

        await loadAddresses();
        return !!window.__selectedAddressId;
    }

    function toggleAddrType(el) {
        document.querySelectorAll('.addr-type-btn').forEach(b => b.classList.remove('active'));
        el.classList.add('active');
    }

    function resetCityDropdown(message = 'Select State First') {
        const cityEl = document.getElementById('cityField');
        if (!cityEl) return;

        checkoutCityOptions = [];
        cityEl.value = '';
        cityEl.placeholder = message;
        cityEl.disabled = true;
        closeCheckoutDropdown('cityDropdown');
    }

    function setCityDropdownOptions(cities = [], selectedCity = '') {
        const cityEl = document.getElementById('cityField');
        if (!cityEl) return;

        checkoutCityOptions = cities;
        cityEl.value = selectedCity || '';
        cityEl.placeholder = cities.length ? 'Type or select city' : 'No cities found';
        cityEl.disabled = cities.length === 0;
        closeCheckoutDropdown('cityDropdown');
    }

    function findStateOption(value = '') {
        const target = normalizeLookup(value);
        if (!target) return null;

        return (checkoutStateOptions || []).find(state => {
            return normalizeLookup(state.name) === target ||
                normalizeLookup(state.code) === target;
        }) || null;
    }

    function cityExistsInOptions(city = '') {
        const target = normalizeLookup(city);
        if (!target) return false;

        return checkoutCityOptions.some(option => normalizeLookup(option) === target);
    }

    async function loadCitiesForState(stateCode, selectedCity = '') {
        const spinner = document.getElementById('citySpinner');
        const requestId = ++checkoutCitiesRequestId;

        if (!stateCode) {
            resetCityDropdown();
            return;
        }

        if (checkoutCitiesCache.has(stateCode)) {
            setCityDropdownOptions(checkoutCitiesCache.get(stateCode), selectedCity);
            return;
        }

        resetCityDropdown('Loading cities...');
        spinner?.classList.add('show');

        try {
            const res = await fetch(checkoutCitiesUrlTemplate.replace('__STATE__', encodeURIComponent(stateCode)), {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const payload = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error('Unable to load cities');

            const cities = Array.isArray(payload.cities) ? payload.cities : [];
            checkoutCitiesCache.set(stateCode, cities);

            if (requestId === checkoutCitiesRequestId) {
                setCityDropdownOptions(cities, selectedCity);
            }
        } catch (error) {
            if (requestId === checkoutCitiesRequestId) {
                resetCityDropdown('Unable to load cities');
            }
        } finally {
            if (requestId === checkoutCitiesRequestId) {
                spinner?.classList.remove('show');
            }
        }
    }

    function hydrateLocationFields(stateValue = '', cityValue = '') {
        const stateOption = findStateOption(stateValue);
        const stateEl = document.getElementById('stateField');
        const stateCodeEl = document.getElementById('stateCodeField');

        if (stateEl && stateOption) {
            stateEl.value = stateOption.name || '';
            if (stateCodeEl) stateCodeEl.value = stateOption.code || '';
            loadCitiesForState(stateOption.code || '', cityValue || '');
        } else {
            if (stateCodeEl) stateCodeEl.value = '';
            resetCityDropdown();
        }
    }

    function handleStateTyping() {
        const stateEl = document.getElementById('stateField');
        const stateCodeEl = document.getElementById('stateCodeField');
        const option = findStateOption(stateEl?.value || '');

        renderCheckoutDropdown('stateDropdown', matchingStates(stateEl?.value || ''), selectStateOption);

        if (!option) {
            if (stateCodeEl) stateCodeEl.value = '';
            resetCityDropdown('Type and select a valid state');
            return;
        }

        const code = option.code || '';
        if (stateCodeEl?.value === code) return;

        if (stateCodeEl) stateCodeEl.value = code;
        loadCitiesForState(code);
    }

    async function autoFillCity() {
        const pinInput = document.getElementById('newPincode');
        if (!pinInput) return;

        const pin = pinInput.value.trim();
        if (!/^\d{6}$/.test(pin)) return;

        try {
            const res = await fetch(`https://api.postalpincode.in/pincode/${encodeURIComponent(pin)}`);
            if (!res.ok) return;

            const data = await res.json();
            const offices = data?.[0]?.Status === 'Success' ? data[0].PostOffice : null;
            if (!Array.isArray(offices) || !offices.length) return;

            const po = offices[0];
            const matchedState = findStateOption(po.State || '');
            if (!matchedState) return;

            await selectStateOption(matchedState); // loads the city list

            // Only fill a city that exists in our list, or city validation will fail later
            const candidates = [po.District, po.Block, po.Name].filter(Boolean);
            const match = checkoutCityOptions.find(c =>
                candidates.some(n => normalizeLookup(n) === normalizeLookup(c))
            );

            const cityEl = document.getElementById('cityField');
            if (match && cityEl) {
                cityEl.value = match;
                closeCheckoutDropdown('cityDropdown');
            }
        } catch (_) {
            // ignore postal lookup failures
        }
    }

    /* ══ PAYMENT ══ */
    const api = {
        ...(config.api || {}),
        csrf: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || config.api?.csrf || ''
    };

    // Pre-select first saved address if available
    window.__selectedAddressId = String(config.selectedAddressId || '');
    window.__checkoutToken = '';
    window.__couponCode = '';
    let isLoggedIn = Boolean(config.isLoggedIn);
    let isApplyingCoupon = false;
    let isVerifyingOtp = false;
    let isPlacingOrder = false;
    const pendingCartKey = 'nb_pending_cart';
    const cartPageUrl = config.cartPageUrl || '/cart';
    let currentTotal = 0;

    if (config.isGuest) {
        try {
            const raw = localStorage.getItem(pendingCartKey);
            const items = raw ? JSON.parse(raw) : [];
            const count = Array.isArray(items)
                ? items.reduce((sum, item) => sum + Number(item && item.quantity || 0), 0)
                : 0;

            if (count < 1) {
                sessionStorage.setItem('nb_cart_notice', 'Please add at least one item to your cart before checkout.');
                window.location.replace(cartPageUrl);
            }
        } catch (error) {
            sessionStorage.setItem('nb_cart_notice', 'Please add at least one item to your cart before checkout.');
            window.location.replace(cartPageUrl);
        }
    }

    function markLoginStepDone() {
        const addressStep = document.getElementById('step-addr');
        if (addressStep && !addressStep.classList.contains('done')) {
            addressStep.classList.add('active');
        }
        const progress = document.getElementById('progressFill');
        if (progress && progress.style.width === '') {
            progress.style.width = '50%';
        }
    }

    function redirectEmptyCheckout() {
        sessionStorage.setItem('nb_cart_notice', 'Please add at least one item to your cart before checkout.');
        window.location.replace(cartPageUrl);
    }

    function updateCsrfToken(token) {
        if (!token) {
            return;
        }

        api.csrf = token;
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) {
            csrfMeta.setAttribute('content', token);
        }
    }

    async function refreshCsrfToken() {
        const res = await fetch(api.csrfTokenUrl, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        });
        const payload = await res.json().catch(() => ({}));
        updateCsrfToken(payload.csrf_token || '');
        return api.csrf;
    }

    async function fetchWithCsrfRetry(url, options = {}) {
        const buildOptions = () => ({
            ...options,
            credentials: 'same-origin',
            headers: {
                ...(options.headers || {}),
                ...(api.csrf ? { 'X-CSRF-TOKEN': api.csrf } : {})
            }
        });

        let response = await fetch(url, buildOptions());
        if (response.status === 419) {
            await refreshCsrfToken();
            response = await fetch(url, buildOptions());
        }

        return response;
    }

    function getPendingCartItems() {
        try {
            const raw = localStorage.getItem(pendingCartKey);
            const parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? parsed : [];
        } catch (_) {
            return [];
        }
    }

    function savePendingCartItems(items) {
        localStorage.setItem(pendingCartKey, JSON.stringify(items || []));
    }

    function normalizeCheckoutQuantity(quantity, maxStock) {
        let qty = Math.max(1, Number(quantity || 1));
        if (maxStock != null && Number.isFinite(maxStock) && maxStock > 0) {
            qty = Math.min(qty, maxStock);
        }
        return qty;
    }

    function checkoutStorageImageUrl(path) {
        if (!path) return '';
        const value = String(path);
        if (value.startsWith('http://') || value.startsWith('https://') || value.startsWith('/')) {
            return value;
        }
        return `/storage/${value.replace(/^\/+/, '')}`;
    }

    function checkoutCartItemImage(item) {
        return checkoutStorageImageUrl(item?.product?.card_image_path) ||
            checkoutStorageImageUrl(item?.product_variant?.image_path) ||
            checkoutStorageImageUrl(item?.product?.primary_image?.image_path) ||
            checkoutStorageImageUrl(item?.product?.images?.[0]?.image_path) ||
            item?.fallback_image ||
            '/img/product2.png';
    }

    function escapeCheckoutText(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function checkoutVariantLabel(item) {
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

    function updatePendingCartItemQuantity(productId, productVariantId = null, quantity = 1) {
        const targetKey = `${Number(productId || 0)}::${Number(productVariantId || 0)}`;
        const nextItems = getPendingCartItems().map(it => {
            const itemKey = `${Number(it.product_id || 0)}::${Number(it.product_variant_id || 0)}`;
            if (itemKey !== targetKey) return it;

            return {
                ...it,
                quantity: normalizeCheckoutQuantity(quantity)
            };
        });

        savePendingCartItems(nextItems);
    }

    function createCheckoutQtyControls(quantity, maxStock) {
        const effectiveMax = (maxStock != null && Number.isFinite(maxStock) && maxStock > 0) ? maxStock : 999;
        const qty = normalizeCheckoutQuantity(quantity, effectiveMax);
        return `
                        <button type="button" class="qty-btn" data-qty-delta="-1" aria-label="Decrease quantity">−</button>
                        <input type="number" min="1" max="${effectiveMax}" class="qty-val" value="${qty}" aria-label="Quantity">
                        <button type="button" class="qty-btn" data-qty-delta="1" aria-label="Increase quantity">+</button>
                    `;
    }

    function bindCheckoutQtyControls(row, quantity, onChange, maxStock) {
        const effectiveMax = (maxStock != null && Number.isFinite(maxStock) && maxStock > 0) ? maxStock : null;
        const qtyRow = row.querySelector('.ci-qty-row');
        if (!qtyRow) return;
        const input = qtyRow.querySelector('.qty-val');
        let currentQty = normalizeCheckoutQuantity(quantity, effectiveMax);
        let pendingQty = currentQty;
        let saveTimer = null;

        function setSaving(isSaving) {
            qtyRow.classList.toggle('is-updating', isSaving);
            qtyRow.querySelectorAll('.qty-btn, .qty-val').forEach(control => {
                control.disabled = isSaving;
            });
        }

        async function submitQuantity(nextQty, options = {}) {
            const normalizedQty = normalizeCheckoutQuantity(nextQty, effectiveMax);
            pendingQty = normalizedQty;
            if (input) input.value = String(normalizedQty);

            if (saveTimer) {
                clearTimeout(saveTimer);
                saveTimer = null;
            }

            if (normalizedQty === currentQty) {
                return;
            }

            if (!options.immediate) {
                saveTimer = setTimeout(() => submitQuantity(pendingQty, { immediate: true }), 550);
                return;
            }

            setSaving(true);

            try {
                await onChange(normalizedQty);
                currentQty = normalizedQty;
            } catch (error) {
                pendingQty = currentQty;
                if (input) input.value = String(currentQty);
                nbToast(error.message || 'Unable to update cart quantity.', 'error');
            } finally {
                setSaving(false);
            }
        }

        qtyRow.querySelectorAll('.qty-btn').forEach(btn => {
            btn.addEventListener('click', event => {
                event.preventDefault();
                const delta = Number(btn.dataset.qtyDelta || 0);
                const baseQty = input ? normalizeCheckoutQuantity(input.value, effectiveMax) :
                    normalizeCheckoutQuantity(quantity, effectiveMax);
                submitQuantity(baseQty + delta);
            });
        });

        if (input) {
            input.addEventListener('change', () => {
                submitQuantity(input.value);
            });
            input.addEventListener('blur', () => {
                input.value = String(normalizeCheckoutQuantity(input.value, effectiveMax));
                submitQuantity(input.value, { immediate: true });
            });
            input.addEventListener('keydown', event => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    submitQuantity(input.value, { immediate: true });
                }
            });
        }
    }

    async function updateServerCartQuantity(itemId, quantity) {
        const res = await fetch(api.cartUpdateTemplate.replace('__ITEM__', itemId), {
            method: 'PATCH',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                ...(api.csrf ? {
                    'X-CSRF-TOKEN': api.csrf
                } : {})
            },
            body: JSON.stringify({
                quantity: normalizeCheckoutQuantity(quantity)
            })
        });

        if (!res.ok) {
            const errorPayload = await res.json().catch(() => ({}));
            throw new Error(errorPayload.message || 'Unable to update cart quantity.');
        }

        return res.json().catch(() => ({}));
    }

    async function syncPendingCartToServer() {
        const items = getPendingCartItems();
        if (!items.length) return;
        for (const item of items) {
            await fetch(api.cartUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    ...(api.csrf ? {
                        'X-CSRF-TOKEN': api.csrf
                    } : {})
                },
                body: JSON.stringify({
                    product_id: item.product_id,
                    product_variant_id: item.product_variant_id,
                    quantity: item.quantity || 1
                })
            }).catch(() => null);
        }
        localStorage.removeItem(pendingCartKey);
    }

    // Coins are always auto-applied at max redeemable amount — no toggle/slider needed
    function getCoinsToRedeem() {
        // Returns the stored max redeemable coins from the last server response
        return Number(window.__maxRedeemableCoins || 0);
    }

    function updatePriceUI(pricing, itemsCount) {
        const mrp = Number(pricing.display_subtotal !== undefined ? pricing.display_subtotal : (pricing.subtotal || 0));
        const totalDiscount = Number(pricing.display_discount_total !== undefined ? pricing.display_discount_total : (pricing.discount_total || 0));

        const couponDiscount = Number(pricing.display_coupon_discount !== undefined ? pricing.display_coupon_discount : (pricing.discount_total || 0));
        const coinDiscount = Number(pricing.display_coin_discount !== undefined ? pricing.display_coin_discount : (pricing.coin_discount || 0));

        // Generic discount is any savings NOT coming from coupon or coins (e.g. built-in item discounts)
        const genericDiscount = Math.max(0, totalDiscount - couponDiscount - coinDiscount);

        const shipping = Number(pricing.shipping_total || 0);
        const gst = Number(pricing.display_tax_total !== undefined ? pricing.display_tax_total : (pricing.tax_total || 0));
        const total = Number(pricing.grand_total || 0);

        const mrpLabel = document.getElementById('pbMrpLabel');
        const mrpValue = document.getElementById('pbMrpValue');
        const productDiscount = document.getElementById('pbProductDiscount');
        const delivery = document.getElementById('pbDelivery');
        const gstEl = document.getElementById('pbGst');
        const totalEl = document.getElementById('totalDisplay');
        const savingsEl = document.getElementById('savingsAmt');
        const loyaltyEl = document.getElementById('loyaltyPoints');

        if (mrpLabel) mrpLabel.textContent = `Price (${itemsCount} items)`;
        if (mrpValue) mrpValue.textContent = `₹${mrp.toLocaleString('en-IN', { maximumFractionDigits: 0 })}`;
        if (productDiscount) {
            const pdRow = productDiscount.closest('.pb-row');
            if (pdRow) pdRow.style.display = genericDiscount > 0 ? 'flex' : 'none';
            productDiscount.textContent = `− ₹${genericDiscount.toLocaleString('en-IN', { maximumFractionDigits: 0 })}`;
        }
        if (delivery) delivery.textContent = shipping > 0 ?
            `₹${shipping.toLocaleString('en-IN', { maximumFractionDigits: 0 })}` : 'FREE 🎉';

        // Coupon Discount Display
        const cRow2 = document.getElementById('couponRow2');
        if (cRow2) {
            cRow2.style.display = couponDiscount > 0 ? 'flex' : 'none';
            document.getElementById('couponDiscount').textContent = `− ₹${couponDiscount.toLocaleString('en-IN')}`;
        }

        // Coin Discount Display
        const coinRow = document.getElementById('coinDiscountRow');
        if (coinRow) {
            coinRow.style.display = coinDiscount > 0 ? 'flex' : 'none';
            document.getElementById('coinDiscountVal').textContent = `− ₹${coinDiscount.toLocaleString('en-IN')}`;
        }

        // Update the auto-apply coin info card
        if (pricing.max_redeemable_coins !== undefined) {
            window.__maxRedeemableCoins = Number(pricing.max_redeemable_coins || 0);
        }
        const coinsLabel = document.getElementById('coinsToRedeemValue');
        const coinDiscountText = document.getElementById('coinDiscountText');
        const redeemedCoins = Number(pricing.coins_redeemed || 0);
        const coinDiscountAmt = Number(pricing.display_coin_discount ?? pricing.coin_discount ?? 0);
        if (coinsLabel) {
            coinsLabel.textContent = redeemedCoins > 0
                ? `${redeemedCoins} Coins applied`
                : (window.__maxRedeemableCoins > 0 ? `${window.__maxRedeemableCoins} Coins` : 'No coins to apply');
        }
        if (coinDiscountText) {
            coinDiscountText.textContent = coinDiscountAmt > 0 ? `−₹${coinDiscountAmt.toLocaleString('en-IN')}` : '−₹0';
        }

        if (gstEl) {
            const gstRow = gstEl.closest('.pb-row');
            if (gstRow) {
                if (gst > 0) {
                    gstRow.style.display = 'flex';
                    gstEl.textContent = `+ ₹${gst.toLocaleString('en-IN', { maximumFractionDigits: 0 })}`;
                } else {
                    gstRow.style.display = 'none';
                }
            }
        }
        if (totalEl) totalEl.textContent = `₹${total.toLocaleString('en-IN', { maximumFractionDigits: 0 })}`;
        if (savingsEl) savingsEl.textContent = `₹${totalDiscount.toLocaleString('en-IN', { maximumFractionDigits: 0 })}`;

        // Earned Coins Display
        if (loyaltyEl) {
            const earned = pricing.total_coins_earned || Math.round(total / 20);
            loyaltyEl.textContent = `${earned} NutriBuddy Coins`;
        }
    }

    async function renderPendingCheckoutCart() {
        const pending = getPendingCartItems();
        const totalQuantity = pending.reduce((sum, it) => sum + Number(it.quantity || 0), 0);
        if (totalQuantity < 1) {
            redirectEmptyCheckout();
            return;
        }

        let lineItemsToRender = [];

        try {
            const res = await fetch('/guest/checkout/summary', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    ...(api.csrf ? { 'X-CSRF-TOKEN': api.csrf } : {})
                },
                body: JSON.stringify({ items: pending })
            });

            if (res.ok) {
                const payload = await res.json();
                const pricing = payload.pricing || {};
                currentTotal = Number(pricing.grand_total || 0);
                updatePriceUI(pricing, totalQuantity);

                // Use server-calculated line items
                lineItemsToRender = pricing.line_items || [];

                const totalWithCod = currentTotal;
                const payBtn = document.getElementById('paymentPlaceBtn');
                if (payBtn) payBtn.innerHTML = `Pay Securely — ₹${totalWithCod.toLocaleString('en-IN')}`;
                const pob = document.querySelector('.place-order-btn');
                if (pob) pob.innerHTML = `Pay Securely — ₹${totalWithCod.toLocaleString('en-IN')}`;
                syncPlaceBtn();
            } else {
                throw new Error('Fallback to local calculation');
            }
        } catch (e) {
            const subtotal = pending.reduce((sum, it) => sum + (Number(it.unit_price || 0) * Number(it.quantity || 0)), 0);
            const localGst = subtotal * 0.18;
            const localGrandTotal = subtotal + localGst;
            currentTotal = localGrandTotal;

            updatePriceUI({
                display_subtotal: subtotal,
                discount_total: 0,
                shipping_total: 0,
                display_tax_total: localGst,
                grand_total: localGrandTotal
            }, totalQuantity);

            // Fallback: use pending items and map to standard structure
            lineItemsToRender = pending.map(it => ({
                cart_item: {
                    product: { name: it.product_name },
                    product_variant: { name: it.variant_name },
                    fallback_image: it.image || '/img/product2.png',
                    id: null,
                    product_id: it.product_id,
                    product_variant_id: it.product_variant_id
                },
                quantity: Number(it.quantity || 0),
                display_line_total: Number(it.unit_price || 0) * Number(it.quantity || 0)
            }));
        }

        const itemsCount = document.querySelector('.item-count');
        if (itemsCount) itemsCount.textContent = `${totalQuantity} Items`;

        const cartWrap = document.getElementById('checkoutCartItems');
        if (!cartWrap) return;

        cartWrap.innerHTML = '';
        if (!lineItemsToRender.length) {
            cartWrap.innerHTML = `<div style="padding:10px;color:var(--text-light)">Your cart is empty.</div>`;
            return;
        }

        lineItemsToRender.forEach(li => {
            const it = li.cart_item;
            const name = it.product?.name || 'Product';
            const qty = li.quantity || 1;
            const linePrice = li.display_line_total || 0;
            const variantLabel = checkoutVariantLabel(it);

            const img = checkoutCartItemImage(it);

            const row = document.createElement('div');
            row.className = 'ci';
            row.innerHTML = `
                          <div class="ci-img"><img src="${img}" alt=""></div>
                          <div class="ci-info">
                            <div class="ci-name">${escapeCheckoutText(name)}</div>
                            ${variantLabel ? `<div class="ci-variant">${escapeCheckoutText(variantLabel)}</div>` : ''}
                            <div class="ci-qty-row">
                              ${createCheckoutQtyControls(qty)}
                            </div>
                          </div>
                          <div>
                          <div class="ci-price">₹${Math.round(linePrice).toLocaleString('en-IN')}</div>
                          </div>
                        `;
            cartWrap.appendChild(row);
            bindCheckoutQtyControls(row, qty, async nextQty => {
                updatePendingCartItemQuantity(it.product_id, it.product_variant_id, nextQty);
                renderPendingCheckoutCart();
            });
        });
    }

    function selectPayMethod(el, type) {
        document.querySelectorAll('.pay-method').forEach(m => m.classList.remove('selected'));
        el.classList.add('selected');

        const total = currentTotal;
        const formattedTotal = `₹${total.toLocaleString('en-IN')}`;
        const btnText = type === 'cod' ? `Confirm COD Order — ${formattedTotal}` : `Pay Securely — ${formattedTotal}`;

        const payBtn = document.getElementById('paymentPlaceBtn');
        if (payBtn) payBtn.innerHTML = btnText;

        const placeBtn = document.querySelector('.place-order-btn');
        if (placeBtn) placeBtn.innerHTML = btnText;
    }

    function syncPlaceBtn() {
        const sel = document.querySelector('.pay-method.selected');
        if (sel) {
            selectPayMethod(sel, sel.getAttribute('data-method') || 'cashfree');
        } else {
            const formattedTotal = `₹${currentTotal.toLocaleString('en-IN')}`;
            const payBtn = document.getElementById('paymentPlaceBtn');
            if (payBtn) payBtn.innerHTML = `Pay Securely — ${formattedTotal}`;
            const pob = document.querySelector('.place-order-btn');
            if (pob) pob.innerHTML = `Pay Securely — ${formattedTotal}`;
        }
    }



    async function ensureCheckoutToken(couponCode = null, options = {}) {
        const normalizedCode = typeof couponCode === 'string' ? couponCode.trim().toUpperCase() : '';
        const forceRefresh = !!options.forceRefresh;

        if (!forceRefresh && window.__checkoutToken) {
            return {
                success: true,
                token: window.__checkoutToken,
                payload: null
            };
        }

        const res = await fetchWithCsrfRetry(api.checkoutSummaryUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                ...(api.csrf ? {
                    'X-CSRF-TOKEN': api.csrf
                } : {})
            },
            body: JSON.stringify({
                coupon_code: normalizedCode || null,
                // Always send the full balance; server caps it to the allowed % automatically
                coins_to_redeem: window.NB_CHECKOUT_CONFIG?.userCoinsBalance || 0
            })
        });

        const wasRedirectedToLogin = res.redirected && /\/login(?:[/?#]|$)/i.test(res.url || '');
        if (res.status === 401 || wasRedirectedToLogin) {
            return { success: false, requiresLogin: true, message: 'Login at checkout to continue.' };
        }
        if (res.status === 419) {
            // 419 = CSRF mismatch, NOT an auth failure.
            // If user is already logged in, don't open the OTP modal — ask them to refresh.
            if (isLoggedIn) {
                return { success: false, requiresLogin: false, message: 'Your session has expired. Please refresh the page and try again.' };
            }
            return { success: false, requiresLogin: true, message: 'Login at checkout to continue.' };
        }

        const payload = await res.json().catch(() => ({}));
        if (!res.ok || !payload.checkout_token) {
            return {
                success: false,
                payload,
                message: payload.message || 'Unable to prepare checkout. Please try again.'
            };
        }

        window.__checkoutToken = payload.checkout_token;

        return {
            success: true,
            token: window.__checkoutToken,
            payload
        };
    }

    /* ══ REFRESH SUMMARY ══ */
    async function refreshCheckoutSummary() {
        const couponInput = document.getElementById('couponInput');
        const code = couponInput ? couponInput.value.trim().toUpperCase() : (window.__couponCode || '');
        const msg = document.getElementById('couponMsg');

        if (msg) {
            msg.classList.add('show');
            msg.style.color = 'var(--text-light)';
            msg.textContent = 'Updating totals...';
        }

        const tokenState = await ensureCheckoutToken(code, {
            forceRefresh: true
        });

        if (tokenState.requiresLogin) {
            if (msg) {
                msg.style.color = 'var(--or)';
                msg.textContent = 'Login at checkout to apply discounts.';
            }
            openOtpModal();
            return;
        }

        if (!tokenState.success) {
            if (msg) {
                msg.style.color = 'var(--or)';
                msg.textContent = tokenState.message || 'Error updating totals.';
            }
            return;
        }

        const payload = tokenState.payload || {};
        const pricing = payload.pricing || {};
        window.__couponCode = code;
        window.__checkoutToken = payload.checkout_token;

        // Update UI
        const totalQuantity = (payload.cart?.items || []).reduce((sum, it) => sum + Number(it.quantity || 0), 0);
        updatePriceUI(pricing, totalQuantity);

        if (msg) {
            if (code && pricing.display_coupon_discount > 0) {
                msg.style.color = '#00a870';
                msg.textContent = `✅ Coupon "${code}" applied!`;
                document.getElementById('couponRow')?.classList.add('applied');
            } else if (code) {
                msg.style.color = 'var(--or)';
                msg.textContent = 'Coupon code not applicable.';
            } else {
                msg.textContent = '';
                msg.classList.remove('show');
            }
        }

        currentTotal = Number(pricing.grand_total || 0);
        const totalWithCod = currentTotal;
        const payBtn = document.getElementById('paymentPlaceBtn');
        if (payBtn) payBtn.innerHTML = `Pay Securely — ₹${totalWithCod.toLocaleString('en-IN')}`;
        const pob = document.querySelector('.place-order-btn');
        if (pob) pob.innerHTML = `Pay Securely — ₹${totalWithCod.toLocaleString('en-IN')}`;
        syncPlaceBtn();
    }

    /* ══ COUPON ══ */
    async function applyCoupon() {
        await refreshCheckoutSummary();
    }



    document.getElementById('couponInput').addEventListener('keydown', e => {
        if (e.key === 'Enter') applyCoupon();
    });

    /* ════════════════════════════════════════
       COD OTP MODAL & VERIFICATION LOGIC
    ════════════════════════════════════════ */
    let codResendInterval = null;

    function closeCodOtpModal() {
        const overlay = document.getElementById('codOtpOverlay');
        if (overlay) overlay.style.display = 'none';
        if (codResendInterval) {
            clearInterval(codResendInterval);
            codResendInterval = null;
        }
        const btn = document.querySelector('.place-order-btn');
        if (btn && btn.dataset.originalHtml) {
            btn.disabled = false;
            btn.innerHTML = btn.dataset.originalHtml;
        }
    }

    function startCodResendTimer(seconds = 30) {
        if (codResendInterval) clearInterval(codResendInterval);
        const timerWrap = document.getElementById('codOtpResendTimer');
        const countEl = document.getElementById('codOtpCountdown');
        const resendBtn = document.getElementById('codOtpResendBtn');

        if (timerWrap) timerWrap.style.display = 'inline';
        if (resendBtn) resendBtn.style.display = 'none';
        if (countEl) countEl.textContent = seconds;

        codResendInterval = setInterval(() => {
            seconds--;
            if (countEl) countEl.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(codResendInterval);
                codResendInterval = null;
                if (timerWrap) timerWrap.style.display = 'none';
                if (resendBtn) resendBtn.style.display = 'inline';
            }
        }, 1000);
    }

    async function sendCodOtpRequest(addressId) {
        const errEl = document.getElementById('codOtpError');
        if (errEl) { errEl.style.display = 'none'; errEl.textContent = ''; }

        try {
            const url = api.sendCodOtpUrl || '/user/checkout/cod/send-otp';
            const res = await fetchWithCsrfRetry(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    ...(api.csrf ? { 'X-CSRF-TOKEN': api.csrf } : {})
                },
                body: JSON.stringify({ address_id: Number(addressId) })
            });

            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                const msg = data.message || (data.errors ? Object.values(data.errors).flat()[0] : 'Unable to send verification code.');
                if (errEl) { errEl.textContent = msg; errEl.style.display = 'block'; }
                if (typeof nbToast === 'function') nbToast(msg, 'error');
                return false;
            }

            if (data.phone_masked) {
                const phoneEl = document.getElementById('codOtpMaskedPhone');
                if (phoneEl) phoneEl.textContent = data.phone_masked;
            }

            startCodResendTimer(data.resend_after || 30);
            if (typeof nbToast === 'function') nbToast(data.message || 'Verification code sent!', 'success');
            return true;
        } catch (e) {
            const msg = 'Network error while sending OTP. Please try again.';
            if (errEl) { errEl.textContent = msg; errEl.style.display = 'block'; }
            return false;
        }
    }

    async function resendCodOtp() {
        const addressId = window.__selectedAddressId;
        const resendBtn = document.getElementById('codOtpResendBtn');
        if (resendBtn) { resendBtn.disabled = true; resendBtn.textContent = 'Sending...'; }
        await sendCodOtpRequest(addressId);
        if (resendBtn) { resendBtn.disabled = false; resendBtn.textContent = 'Resend Code'; }
    }

    async function submitCodOtp() {
        const input = document.getElementById('codOtpInput');
        const errEl = document.getElementById('codOtpError');
        const submitBtn = document.getElementById('codOtpSubmitBtn');
        const otpVal = input ? input.value.trim() : '';

        if (!otpVal || otpVal.length < 6) {
            if (errEl) {
                errEl.textContent = 'Please enter the complete 6-digit verification code.';
                errEl.style.display = 'block';
            }
            if (input) input.focus();
            return;
        }

        if (errEl) { errEl.style.display = 'none'; errEl.textContent = ''; }
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Verifying...';
        }

        await placeOrder({ codOtp: otpVal });

        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Verify & Place Order';
        }
    }

    /* ════════════════════════════════════════
       LOGIN & ORDER PLACEMENT LOGIC
    ════════════════════════════════════════ */
    function openOtpModal() {
        if (isLoggedIn) {
            placeOrder();
            return;
        }

        openLoginModal(async (data) => {
            isLoggedIn = true;
            markLoginStepDone();
            updateCsrfToken(data.csrf_token || '');
            await syncPendingCartToServer();

            const hasAddressReady = await ensureCheckoutAddressReady();
            const couponInput = document.getElementById('couponInput');
            if (couponInput && couponInput.value.trim()) {
                window.__checkoutToken = '';
                await applyCoupon();
            } else {
                await loadCartSummary();
            }

            if (!hasAddressReady) {
                nbToast('Login successful! Please add or select a delivery address to place your order.', 'warning', 'Address Required');
                return;
            }

            await placeOrder();
        }, { googleRedirect: window.location.href });
    }
    async function placeOrder(options = {}) {
        if (!isLoggedIn) {
            openOtpModal();
            return;
        }
        const addressId = window.__selectedAddressId;
        if (!addressId) {
            nbToast('Please select a delivery address to continue.', 'warning', 'Address Required');
            return;
        }

        if (!window.__checkoutToken) {
            const tokenState = await ensureCheckoutToken(window.__couponCode || null, {
                forceRefresh: true
            });
            if (tokenState.requiresLogin) {
                openOtpModal();
                return;
            }
            if (!tokenState.success) {
                alert(tokenState.message || 'Unable to prepare checkout. Please try again.');
                return;
            }
        }

        const btn = document.querySelector('.place-order-btn');
        if (btn) {
            if (btn.disabled && !options?.codOtp) return;
            if (!btn.dataset.originalHtml) btn.dataset.originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '⏳ Processing...';
        }

        const resetBtn = () => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = btn.dataset.originalHtml || 'Pay Securely';
            }
        };

        const paymentMethod = document.querySelector('.pay-method.selected')?.getAttribute('data-method') || 'cashfree';
        const isCod = paymentMethod === 'cod';
        const codOtpEnabled = window.NB_CHECKOUT_CONFIG?.codOtpEnabled !== false;

        // COD OTP verification gate: if COD is selected and OTP verification is enabled, request OTP first
        if (isCod && codOtpEnabled && !options?.codOtp) {
            resetBtn();
            const overlay = document.getElementById('codOtpOverlay');
            if (overlay) {
                overlay.style.display = 'flex';
                const input = document.getElementById('codOtpInput');
                if (input) {
                    input.value = '';
                    setTimeout(() => input.focus(), 120);
                }
            }
            const sent = await sendCodOtpRequest(addressId);
            if (!sent) {
                closeCodOtpModal();
            }
            return;
        }

        if (paymentMethod === 'razorpay' && typeof Razorpay !== 'function') {
            if (typeof nbToast === 'function') {
                nbToast('Razorpay checkout is loading. Please wait a moment or refresh the page.', 'warning');
            }
            resetBtn();
            return;
        }

        if (paymentMethod === 'cashfree' && typeof Cashfree !== 'function') {
            if (typeof nbToast === 'function') {
                nbToast('Cashfree checkout is loading. Please wait a moment or refresh the page.', 'warning');
            }
            resetBtn();
            return;
        }

        const requestBody = {
            address_id: Number(addressId),
            coupon_code: window.__couponCode || null,
            // Always send full balance; server caps to allowed % automatically
            coins_to_redeem: window.NB_CHECKOUT_CONFIG?.userCoinsBalance || 0,
            payment_method: paymentMethod,
            checkout_token: window.__checkoutToken || ''
        };

        if (options?.codOtp) {
            requestBody.cod_otp = options.codOtp;
        }

        const res = await fetchWithCsrfRetry(api.placeOrderUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                ...(api.csrf ? {
                    'X-CSRF-TOKEN': api.csrf
                } : {})
            },
            body: JSON.stringify(requestBody)
        });

        if (res.status === 401 || res.status === 419) {
            resetBtn();
            openOtpModal();
            return;
        }

        const payload = await res.json().catch(() => ({}));
        if (!res.ok) {
            const errorMsg = payload.message || (payload.errors ? Object.values(payload.errors).flat()[0] : 'Unable to place order.');
            const codErrEl = document.getElementById('codOtpError');

            // If server rejected because COD OTP is required, ensure the modal is displayed and OTP is sent
            const isCodOtpError = isCod && (payload.errors?.cod_otp || errorMsg.toLowerCase().includes('verification code'));
            if (isCodOtpError && !options?.codOtp) {
                resetBtn();
                const overlay = document.getElementById('codOtpOverlay');
                if (overlay) {
                    overlay.style.display = 'flex';
                    const input = document.getElementById('codOtpInput');
                    if (input) {
                        input.value = '';
                        setTimeout(() => input.focus(), 120);
                    }
                }
                await sendCodOtpRequest(addressId);
                return;
            }

            if (options?.codOtp && codErrEl) {
                codErrEl.textContent = errorMsg;
                codErrEl.style.display = 'block';
                const input = document.getElementById('codOtpInput');
                if (input) {
                    input.select();
                    input.focus();
                }
            } else {
                if (typeof nbToast === 'function') nbToast(errorMsg, 'error');
            }
            resetBtn();
            return;
        }

        // On successful placement, close any open COD OTP modal
        closeCodOtpModal();

        updateCsrfToken(payload.csrf_token || '');

        const orderNumber = payload.order?.order_number || '';
        const orderId = payload.order?.id || '';
        const paymentSessionId = payload.payment?.payment_session_id || '';

        if (payload.payment?.provider === 'razorpay') {
            const razorpayKeyId = payload.payment.razorpay_key_id;
            const razorpayOrderId = payload.payment.razorpay_order_id;

            if (typeof Razorpay !== 'function') {
                if (typeof nbToast === 'function') {
                    nbToast('Razorpay checkout could not load. Please refresh and try again.', 'error');
                }
                resetBtn();
                return;
            }

            const btn = document.querySelector('.place-order-btn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = 'Opening Razorpay...';
            }

            const options = {
                key: razorpayKeyId,
                amount: Math.round(Number(payload.order.grand_total) * 100),
                currency: 'INR',
                name: 'NutriBuddy',
                description: `Order #${orderNumber}`,
                order_id: razorpayOrderId,
                handler: async function (response) {
                    if (btn) btn.innerHTML = 'Verifying...';
                    try {
                        const verifyRes = await fetch('/payment/razorpay/verify', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                ...(api.csrf ? { 'X-CSRF-TOKEN': api.csrf } : {})
                            },
                            body: JSON.stringify({
                                order_id: orderId,
                                razorpay_order_id: response.razorpay_order_id,
                                razorpay_payment_id: response.razorpay_payment_id,
                                razorpay_signature: response.razorpay_signature
                            })
                        });

                        const verifyPayload = await verifyRes.json().catch(() => ({}));
                        if (verifyRes.ok && verifyPayload.success) {
                            window.location.href = `/user/orders/${orderId}/detail`;
                        } else {
                            if (typeof nbToast === 'function') {
                                nbToast(verifyPayload.message || 'Payment verification failed.', 'error');
                            }
                            if (btn) {
                                btn.disabled = false;
                                btn.innerHTML = 'Pay Securely';
                            }
                        }
                    } catch (e) {
                        if (typeof nbToast === 'function') nbToast('Payment verification check failed.', 'error');
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = 'Pay Securely';
                        }
                    }
                },
                modal: {
                    ondismiss: function () {
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = 'Pay Securely';
                        }
                    }
                },
                prefill: {
                    name: (document.getElementById('firstName')?.value || '') + ' ' + (document.getElementById('lastName')?.value || ''),
                    contact: document.getElementById('addressPhone')?.value || ''
                },
                theme: {
                    color: '#063c36'
                }
            };

            const rzp = new Razorpay(options);
            rzp.open();
            return;
        }

        if (paymentSessionId) {
            if (typeof Cashfree !== 'function') {
                if (typeof nbToast === 'function') {
                    nbToast('Cashfree checkout could not load. Please refresh and try again.', 'error');
                }
                resetBtn();
                return;
            }

            const btn = document.querySelector('.place-order-btn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = 'Opening Cashfree...';
            }

            const cashfree = Cashfree({
                mode: payload.payment?.mode || config.cashfreeMode || 'sandbox'
            });

            cashfree.checkout({
                paymentSessionId,
                redirectTarget: '_self'
            });
            return;
        }

        const numEl = document.getElementById('successOrderNumber');
        if (numEl && orderNumber) numEl.textContent = `Order ID: ${orderNumber}`;

        // Wire the "View Order Details" link
        const detailBtn = document.getElementById('orderDetailBtn');
        if (detailBtn && orderId) detailBtn.href = `/user/orders/${orderId}/detail`;

        // Start countdown then redirect
        let countdown = 5;
        const countEl = document.getElementById('redirectCountdown');
        const countTimer = setInterval(() => {
            countdown--;
            if (countEl) countEl.textContent = countdown;
            if (countdown <= 0) {
                clearInterval(countTimer);
                if (orderId) window.location.href = `/user/orders/${orderId}/detail`;
            }
        }, 1000);

        document.getElementById('progressFill').style.width = '100%';
        setTimeout(() => document.getElementById('successOverlay').classList.add('show'), 400);
    }

    async function loadAddresses() {
        const res = await fetchWithCsrfRetry(api.addressesUrl, {
            headers: {
                'Accept': 'application/json'
            }
        });
        const isGuest = res.status === 401 || res.status === 419 || (res.redirected && /\/login(?:[/?#]|$)/i.test(res.url));
        if (isGuest) {
            const panel = document.getElementById('savedAddrPanel');
            if (panel) panel.innerHTML =
                `<div style="padding:14px;color:var(--text-light)">Login at checkout to load saved addresses.</div>`;
            window.__selectedAddressId = '';
            return;
        }
        if (!res.ok) return;

        const payload = await res.json().catch(() => ({}));
        const addresses = payload.data || [];

        if (!addresses.length) {
            window.__selectedAddressId = '';
            return;
        }

        // Pre-fill the form with the most recent address
        const a = addresses[0];
        const names = a.full_name.split(' ');
        if (document.getElementById('firstName')) document.getElementById('firstName').value = names[0] || '';
        if (document.getElementById('lastName')) document.getElementById('lastName').value = names.slice(1).join(' ') || '';
        if (document.getElementById('addressPhone')) document.getElementById('addressPhone').value = a.phone || '';
        if (document.getElementById('addressLine1')) document.getElementById('addressLine1').value = a.address_line_1 || '';
        if (document.getElementById('addressLine2')) document.getElementById('addressLine2').value = a.address_line_2 || '';
        if (document.getElementById('newPincode')) document.getElementById('newPincode').value = a.postal_code || '';
        hydrateLocationFields(a.state || '', a.city || '');

        if (a.label) {
            const btn = document.querySelector(`.addr-type-btn[data-type="${a.label}"]`);
            if (btn) toggleAddrType(btn);
        }

        window.__selectedAddressId = String(a.id);
    }

    async function loadCartSummary() {
        const res = await fetch(api.cartUrl, {
            headers: {
                'Accept': 'application/json'
            }
        });
        const isGuest = res.status === 401 || res.status === 419 || (res.redirected && /\/login(?:[/?#]|$)/i.test(res.url));
        if (isGuest) {
            renderPendingCheckoutCart();
            return;
        }
        if (!res.ok) return;

        const payload = await res.json().catch(() => ({}));
        const items = payload.cart?.items || [];
        const pricing = payload.pricing || {};
        const totalQuantity = items.reduce((sum, it) => sum + Number(it.quantity || 0), 0);
        if (totalQuantity < 1) {
            redirectEmptyCheckout();
            return;
        }

        currentTotal = Number(pricing.grand_total || 0);
        updatePriceUI(pricing, totalQuantity);

        const itemsCount = document.querySelector('.item-count');
        if (itemsCount) itemsCount.textContent = `${totalQuantity} Items`;

        const cartWrap = document.getElementById('checkoutCartItems');
        if (cartWrap) {
            cartWrap.innerHTML = '';
            const lineItems = pricing.line_items || [];
            if (!lineItems.length) {
                cartWrap.innerHTML = `<div style="padding:10px;color:var(--text-light)">Your cart is empty.</div>`;
            } else {
                lineItems.forEach(li => {
                    const it = li.cart_item;
                    const name = it.product?.name || 'Product';
                    const qty = li.quantity || 1;
                    const linePrice = li.display_line_total || 0;
                    const variantLabel = checkoutVariantLabel(it);
                    const img = checkoutCartItemImage(it);

                    const row = document.createElement('div');
                    row.className = 'ci';
                    row.innerHTML = `
                                  <div class="ci-img"><img src="${img}" alt=""></div>
                                  <div class="ci-info">
                                    <div class="ci-name">${escapeCheckoutText(name)}</div>
                                    ${variantLabel ? `<div class="ci-variant">${escapeCheckoutText(variantLabel)}</div>` : ''}
                                    <div class="ci-qty-row">
                                      ${createCheckoutQtyControls(qty)}
                                    </div>
                                  </div>
                                  <div>
                                    <div class="ci-price">₹${linePrice.toLocaleString('en-IN', { maximumFractionDigits: 2 })}</div>
                                  </div>
                                `;
                    cartWrap.appendChild(row);
                    bindCheckoutQtyControls(row, qty, async nextQty => {
                        await updateServerCartQuantity(it.id, nextQty);
                        await loadCartSummary();
                        await refreshCheckoutSummary();
                    });
                });
            }
        }

        // generate a token for idempotency
        window.__checkoutToken = window.__checkoutToken || '';
        if (!window.__checkoutToken) {
            const tokenState = await ensureCheckoutToken(window.__couponCode || null);
            if (tokenState.requiresLogin) {
                return;
            }
        }

        // Cashfree: update payment button with current total
        const totalWithCod = currentTotal;
        const payBtn = document.getElementById('paymentPlaceBtn');
        if (payBtn) payBtn.innerHTML = `Pay Securely — ₹${totalWithCod.toLocaleString('en-IN')}`;
        const pob = document.querySelector('.place-order-btn');
        if (pob) pob.innerHTML = `Pay Securely — ₹${totalWithCod.toLocaleString('en-IN')}`;
        // update payment button with current total matching selected method
        syncPlaceBtn();
    }


    Object.assign(window, {
        goToPayment,
        editSection,
        switchAddrTab,
        selectSavedAddress,
        deleteAddress,
        saveAndGoToPayment,
        toggleAddrType,
        autoFillCity,
        selectPayMethod,
        applyCoupon,
        openOtpModal,
        placeOrder,
        closeCodOtpModal,
        resendCodOtp,
        submitCodOtp
    });
    document.addEventListener('DOMContentLoaded', function () {
        const codOtpInputEl = document.getElementById('codOtpInput');
        if (codOtpInputEl) {
            codOtpInputEl.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    submitCodOtp();
                }
            });
        }
        const stateEl = document.getElementById('stateField');
        const cityEl = document.getElementById('cityField');
        if (stateEl) {
            stateEl.addEventListener('input', handleStateTyping);
            stateEl.addEventListener('change', handleStateTyping);
            stateEl.addEventListener('focus', function () {
                renderCheckoutDropdown('stateDropdown', matchingStates(this.value), selectStateOption);
            });

            if (stateEl.value) {
                hydrateLocationFields(stateEl.value, cityEl?.dataset.oldCity || '');
            }
        }
        if (cityEl) {
            cityEl.addEventListener('input', function () {
                renderCheckoutDropdown('cityDropdown', matchingCities(this.value), selectCityOption);
            });
            cityEl.addEventListener('focus', function () {
                if (!this.disabled) {
                    renderCheckoutDropdown('cityDropdown', matchingCities(this.value), selectCityOption);
                }
            });
        }
        document.addEventListener('mousedown', function (event) {
            if (!event.target.closest('.checkout-combobox')) {
                closeCheckoutDropdown('stateDropdown');
                closeCheckoutDropdown('cityDropdown');
            }
        });

        // Only call loadAddresses() if no saved addresses were rendered server-side
        const hasSavedCards = document.querySelectorAll('#savedAddressList .addr-item').length > 0;
        if (!hasSavedCards) {
            loadAddresses();
        }
        loadCartSummary();

        // Restore pending address if user was logged in midway
        const pending = sessionStorage.getItem('nb_pending_address');
        if (pending) {
            try {
                const a = JSON.parse(pending);
                const names = (a.full_name || '').split(' ');
                if (document.getElementById('firstName')) document.getElementById('firstName').value = names[0] || '';
                if (document.getElementById('lastName')) document.getElementById('lastName').value = names.slice(1).join(' ') || '';
                if (document.getElementById('addressPhone')) document.getElementById('addressPhone').value = a.phone || '';
                if (document.getElementById('addressLine1')) document.getElementById('addressLine1').value = a.address_line_1 || '';
                if (document.getElementById('addressLine2')) document.getElementById('addressLine2').value = a.address_line_2 || '';
                if (document.getElementById('newPincode')) document.getElementById('newPincode').value = a.postal_code || '';
                hydrateLocationFields(a.state || '', a.city || '');
                if (a.label) {
                    const btn = document.querySelector(`.addr-type-btn[data-type="${a.label}"]`);
                    if (btn) toggleAddrType(btn);
                }
                sessionStorage.removeItem('nb_pending_address');
            } catch (e) { }
        }

        const initialPayMethod = document.querySelector('.pay-method.selected') || document.querySelector('.pay-method');
        if (initialPayMethod) {
            selectPayMethod(initialPayMethod, initialPayMethod.getAttribute('data-method'));
        }

        // Refresh checkout summary to get final pricing including auto-applied coins
        refreshCheckoutSummary();
    });

    document.getElementById('successOverlay').addEventListener('click', function (e) {
        if (e.target === this) this.classList.remove('show');
    });
})();

