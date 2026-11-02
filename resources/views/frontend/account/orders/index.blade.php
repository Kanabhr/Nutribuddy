@extends('layouts.user-panel')
@section('title', 'My Orders — NutriBuddy Kids')
@section('panel-page-class', 'panel-order')
@section('panel-content')
    <div class="inner-topbar">
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <line x1="3" y1="6" x2="21" y2="6" />
                <line x1="3" y1="12" x2="21" y2="12" />
                <line x1="3" y1="18" x2="21" y2="18" />
            </svg>
        </button>
        <span class="it-title">My Orders 📋</span>
        <div style="width:36px"></div>
    </div>
    <!-- Wrapper removed to match ud-layout -->
    <!-- Cancel Confirm Modal -->
    <div class="modal-backdrop" id="cancelModal">
        <div class="modal">
            <h3>Cancel Order? 🤔</h3>
            <p>Are you sure you want to cancel order <strong id="cancelOrderId"></strong>? This action cannot be undone.
            </p>
            <div class="modal-btns">
                <button class="m-btn-cancel" onclick="closeModal()">Keep Order</button>
                <button class="m-btn-confirm" onclick="confirmCancel()">Yes, Cancel</button>
            </div>
        </div>
    </div>
    <!-- ════════════ MAIN ════════════ -->
    <div class="page">
        <!-- SUMMARY STRIP -->
        <!-- WELCOME BANNER -->
      

        <!-- PAGE HEADER -->
        <div class="page-header fade-in d2">
            <div class="page-header-left">
                <h1>My Orders 📋</h1>
                <p>Track and manage all your NutriBuddy purchases</p>
            </div>
            <div class="search-bar">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.5" color="var(--muted)">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
                <input type="text" placeholder="Search by order ID..." id="searchInput" oninput="filterOrders()">
            </div>
            <div class="page-header-right">
                <div class="date-filter">
                    <label>
                        <span>From</span>
                        <input type="date" id="dateFromInput">
                    </label>
                    <label>
                        <span>To</span>
                        <input type="date" id="dateToInput">
                    </label>
                    <button type="button" class="date-filter-btn" onclick="applyDateFilter()">Apply</button>
                    <button type="button" class="date-filter-clear" onclick="clearDateFilter()">Clear</button>
                </div>
                <div class="filter-tabs">
                    <button class="ftab active" id="tab-all" onclick="filterTab(this,'all')">All (0)</button>
                    <button class="ftab" id="tab-pending" onclick="filterTab(this,'pending')">Pending (0)</button>
                    <button class="ftab" id="tab-delivered" onclick="filterTab(this,'delivered')">Delivered (0)</button>
                    <button class="ftab" id="tab-cancelled" onclick="filterTab(this,'cancelled')">Cancelled (0)</button>
                </div>
            </div>
        </div>

      

         <div class="summary-strip fade-in d1">
            <div class="sum-card">
                <div class="sum-icon" style="background:var(--mnl)">📦</div>
                <div>
                    <div class="s-num" id="totalOrdersCount">0</div>
                    <div class="s-lbl">Total Orders</div>
                </div>
            </div>
            <div class="sum-card">
                <div class="sum-icon" style="background:var(--yel)">⏳</div>
                <div>
                    <div class="s-num" id="pendingOrdersCount">0</div>
                    <div class="s-lbl">Pending</div>
                </div>
            </div>
            <div class="sum-card">
                <div class="sum-icon" style="background:var(--mnl)">✅</div>
                <div>
                    <div class="s-num" id="deliveredOrdersCount">0</div>
                    <div class="s-lbl">Delivered</div>
                </div>
            </div>
            <div class="sum-card">
                <div class="sum-icon" style="background:#ffe4e6">❌</div>
                <div>
                    <div class="s-num" id="cancelledOrdersCount">0</div>
                    <div class="s-lbl">Cancelled</div>
                </div>
            </div>
        </div>


        <!-- ORDERS TABLE (desktop) -->
        <div class="orders-card fade-in d3">
            <div style="overflow-x:auto">
                <table class="orders-table" id="ordersTable">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ordersBody"></tbody>
                </table>
            </div>

            <!-- MOBILE CARDS -->
            <div class="mobile-orders" id="mobileOrders"></div>

            <!-- empty state (hidden by default) -->
            <div class="empty-state" id="emptyState" style="display:none">
                <span class="empty-icon">📭</span>
                <h3>No orders found</h3>
                <p>We couldn't find any orders matching your filter.</p>
                <a href="#" class="shop-btn">🛒 Shop Now</a>
            </div>

            <!-- pagination -->
            <div class="pagination">
                <span class="pag-info" id="ordersPaginationInfo">Showing 0 of 0 orders</span>
                <div class="pag-btns">
                    <button class="pag-btn">‹</button>
                    <button class="pag-btn active">1</button>
                    <button class="pag-btn">›</button>
                </div>
            </div>
        </div>

    </div><!-- /page -->
    @push('scripts')
        <script>
            // sidebar
            function toggleSidebar() {
                document.getElementById('sidebar').classList.toggle('open');
                document.getElementById('overlay').classList.toggle('show');
            }

            function closeSidebar() {
                document.getElementById('sidebar').classList.remove('open');
                document.getElementById('overlay').classList.remove('show');
            }

            function setActive(el) {
                document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
                el.classList.add('active');
            }

            const apiConfig = {
                listUrl: @json(route('user.orders.index')),
                invoiceUrlTemplate: @json(route('user.orders.invoice', ['order' => '__ORDER_ID__'])),
                detailUrlTemplate: @json(route('user.orders.show', ['order' => '__ORDER_ID__'])),
                detailPageUrlTemplate: @json(route('user.orders.detail-page', ['order' => '__ORDER_ID__'])),
                invoicePageUrlTemplate: @json(route('user.orders.invoice-page', ['order' => '__ORDER_ID__'])),
                cancelUrlTemplate: @json(route('user.orders.cancel', ['order' => '__ORDER_ID__'])),
                csrfToken: @json(csrf_token())
            };

            let allOrders = [];
            let activeStatusFilter = 'all';

            function statusClass(status) {
                if (status === 'delivered') return 's-delivered';
                if (status === 'cancelled') return 's-cancelled';
                return 's-pending';
            }

            function money(value) {
                return `₹${Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            }

            function dateText(value) {
                const dt = new Date(value);
                return Number.isNaN(dt.getTime()) ? '-' : dt.toLocaleDateString('en-IN');
            }

            function renderActions(order) {
                const viewBtn = `<a href="${apiConfig.detailPageUrlTemplate.replace('__ORDER_ID__', order.id)}" class="act-btn act-view">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>View</a>`;

                const invoiceBtn = `<a href="${apiConfig.invoicePageUrlTemplate.replace('__ORDER_ID__', order.id)}" class="act-btn act-review">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M9 14l2 2 4-4"></path><path d="M21 12c0 1.66-4.03 6-9 6s-9-4.34-9-6 4.03-6 9-6 9 4.34 9 6z"></path>
                    </svg>Invoice</a>`;

                const canCancel = ['pending', 'confirmed', 'processing', 'packed'].includes(order.status);
                const cancelBtn = canCancel ?
                    `<button class="act-btn act-cancel" onclick="openCancelModal('${order.order_number}', ${order.id})">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
                        </svg>Cancel</button>` :
                    '';

                return `${viewBtn}${invoiceBtn}${cancelBtn}`;
            }

            function renderOrders(orders) {
                const body = document.getElementById('ordersBody');
                const mobile = document.getElementById('mobileOrders');
                body.innerHTML = '';
                mobile.innerHTML = '';

                orders.forEach(order => {
                    const row = document.createElement('tr');
                    row.dataset.status = order.status;
                    row.innerHTML = `
                        <td>
                            <span class="order-id">${order.order_number}</span>
                            <span class="order-product">${(order.items_count || 0)} item(s)</span>
                        </td>
                        <td>${dateText(order.placed_at || order.created_at)}</td>
                        <td class="amount-cell"><strong>${money(order.grand_total)}</strong><small>${String(order.payment_method || '').toUpperCase()}</small></td>
                        <td><span class="status-badge ${statusClass(order.status)}">${String(order.status || '').toUpperCase()}</span></td>
                        <td><div class="actions-cell">${renderActions(order)}</div></td>
                    `;
                    body.appendChild(row);

                    const card = document.createElement('div');
                    card.className = 'm-order-card';
                    card.dataset.status = order.status;
                    card.innerHTML = `
                        <div class="m-card-top">
                            <div>
                                <div class="m-id">${order.order_number}</div>
                                <div class="m-date">${(order.items_count || 0)} item(s) · ${dateText(order.placed_at || order.created_at)}</div>
                            </div>
                            <span class="status-badge ${statusClass(order.status)}">${String(order.status || '').toUpperCase()}</span>
                        </div>
                        <div class="m-card-mid">
                            <div class="m-amount">${money(order.grand_total)}</div>
                            <small style="color:var(--muted);font-size:.73rem">${String(order.payment_method || '').toUpperCase()}</small>
                        </div>
                        <div class="m-card-actions">${renderActions(order)}</div>
                    `;
                    mobile.appendChild(card);
                });

                applyFilters();
            }

            function updateStats(orders) {
                const counts = {
                    total: orders.length,
                    pending: orders.filter(o => o.status === 'pending').length,
                    delivered: orders.filter(o => o.status === 'delivered').length,
                    cancelled: orders.filter(o => o.status === 'cancelled').length,
                };

                document.getElementById('totalOrdersCount').textContent = counts.total;
                document.getElementById('pendingOrdersCount').textContent = counts.pending;
                document.getElementById('deliveredOrdersCount').textContent = counts.delivered;
                document.getElementById('cancelledOrdersCount').textContent = counts.cancelled;
                const bannerOrdersCount = document.getElementById('bannerOrdersCount');
                const bannerPendingCount = document.getElementById('bannerPendingCount');
                if (bannerOrdersCount) bannerOrdersCount.textContent = counts.total;
                if (bannerPendingCount) bannerPendingCount.textContent = counts.pending;

                document.getElementById('tab-all').textContent = `All (${counts.total})`;
                document.getElementById('tab-pending').textContent = `Pending (${counts.pending})`;
                document.getElementById('tab-delivered').textContent = `Delivered (${counts.delivered})`;
                document.getElementById('tab-cancelled').textContent = `Cancelled (${counts.cancelled})`;
            }

            async function loadOrders() {
                const dateFrom = document.getElementById('dateFromInput')?.value || '';
                const dateTo = document.getElementById('dateToInput')?.value || '';

                if (dateFrom && dateTo && dateFrom > dateTo) {
                    if (typeof nbToast === 'function') {
                        nbToast('Please choose an end date after the start date.', 'warning');
                    }
                    return;
                }

                const url = new URL(apiConfig.listUrl, window.location.origin);
                if (dateFrom) url.searchParams.set('date_from', dateFrom);
                if (dateTo) url.searchParams.set('date_to', dateTo);

                const response = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (!response.ok) {
                    checkEmpty();
                    return;
                }

                const payload = await response.json();
                allOrders = payload.data || [];
                renderOrders(allOrders);
                updateStats(allOrders);
                checkEmpty();
            }

            // filter tabs
            function filterTab(btn, status) {
                document.querySelectorAll('.ftab').forEach(t => t.classList.remove('active'));
                btn.classList.add('active');
                activeStatusFilter = status;
                applyFilters();
            }

            function applyFilters() {
                const q = document.getElementById('searchInput').value.toLowerCase().trim();
                document.querySelectorAll('#ordersBody tr').forEach(tr => {
                    const id = tr.querySelector('.order-id')?.textContent.toLowerCase() || '';
                    const matchesStatus = activeStatusFilter === 'all' || tr.dataset.status === activeStatusFilter;
                    const matchesSearch = !q || id.includes(q);
                    tr.style.display = matchesStatus && matchesSearch ? '' : 'none';
                });
                document.querySelectorAll('#mobileOrders .m-order-card').forEach(c => {
                    const id = c.querySelector('.m-id')?.textContent.toLowerCase() || '';
                    const matchesStatus = activeStatusFilter === 'all' || c.dataset.status === activeStatusFilter;
                    const matchesSearch = !q || id.includes(q);
                    c.style.display = matchesStatus && matchesSearch ? '' : 'none';
                });
                checkEmpty();
            }

            // search
            function filterOrders() {
                applyFilters();
            }

            async function applyDateFilter() {
                await loadOrders();
            }

            async function clearDateFilter() {
                document.getElementById('dateFromInput').value = '';
                document.getElementById('dateToInput').value = '';
                await loadOrders();
            }

            function checkEmpty() {
                const visibleRows = [...document.querySelectorAll('#ordersBody tr')].filter(tr => tr.style.display !== 'none');
                const visibleCards = [...document.querySelectorAll('#mobileOrders .m-order-card')].filter(c => c.style
                    .display !== 'none');
                const empty = visibleRows.length === 0 && visibleCards.length === 0;
                document.getElementById('emptyState').style.display = empty ? 'block' : 'none';
                document.getElementById('ordersPaginationInfo').textContent =
                    `Showing ${visibleRows.length} of ${allOrders.length} orders`;
            }

            // cancel modal
            let pendingCancel = null;
            let pendingCancelOrderId = null;

            function openCancelModal(orderNumber, orderId) {
                pendingCancel = orderNumber;
                pendingCancelOrderId = orderId;
                document.getElementById('cancelOrderId').textContent = orderNumber;
                document.getElementById('cancelModal').classList.add('show');
            }

            function closeModal() {
                pendingCancel = null;
                pendingCancelOrderId = null;
                document.getElementById('cancelModal').classList.remove('show');
            }

            async function confirmCancel() {
                if (!pendingCancelOrderId) return;
                const url = apiConfig.cancelUrlTemplate.replace('__ORDER_ID__', pendingCancelOrderId);
                const response = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': apiConfig.csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                closeModal();
                if (response.ok) {
                    await loadOrders();
                }
            }
            // close modal on backdrop click
            document.getElementById('cancelModal').addEventListener('click', function(e) {
                if (e.target === this) closeModal()
            });

            document.addEventListener('DOMContentLoaded', function() {
                loadOrders();
            });
        </script>
    @endpush
@endsection
