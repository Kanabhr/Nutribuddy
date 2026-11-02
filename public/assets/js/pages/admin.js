/**
 * ==========================================
 * Admin Panel Custom Scripts
 * ==========================================
 * Contains global initializations and custom JS logic 
 * for various components in the NutriBuddy admin panel.
 */

document.addEventListener('DOMContentLoaded', function() {
    
    /* --- TomSelect Initialization --- */
    const tomSelects = {};

    function initTomSelects() {
        if (typeof TomSelect === 'undefined') return;

        document.querySelectorAll('.select2-user').forEach(el => {
            if (el.tomselect) return;
            
            tomSelects[el.id || el.name] = new TomSelect(el, {
                plugins: ['dropdown_input'],
                placeholder: 'Any User',
                allowEmptyOption: true,
                maxItems: 1,
                hideSelected: false,
            });
        });
    }

    initTomSelects();

    /* --- DataTable Initialization --- */
    if (document.getElementById('dataTable') && typeof DataTable !== 'undefined') {
        new DataTable('#dataTable');
    }

    /* --- Edit Coupon Modal Logic --- */
    const editModal = document.getElementById('editCouponModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            const action = button.getAttribute('data-action');
            const userId = button.getAttribute('data-user_id');
            const code = button.getAttribute('data-code');
            const name = button.getAttribute('data-name');
            const discountType = button.getAttribute('data-discount_type');
            const discountValue = button.getAttribute('data-discount_value');
            const minOrderAmount = button.getAttribute('data-min_order_amount');
            const maxDiscountAmount = button.getAttribute('data-max_discount_amount');
            const usageLimit = button.getAttribute('data-usage_limit');
            const usageLimitPerUser = button.getAttribute('data-usage_limit_per_user');
            const startsAt = button.getAttribute('data-starts_at');
            const endsAt = button.getAttribute('data-ends_at');
            const isActive = button.getAttribute('data-is_active');

            const form = editModal.querySelector('#editCouponForm');
            form.setAttribute('action', action);
            
            // Update Tom Select value
            const selectEl = editModal.querySelector('#edit_coupon_user_id');
            if (selectEl && selectEl.tomselect) {
                selectEl.tomselect.setValue(userId || '');
            }
            
            editModal.querySelector('#edit_coupon_code').value = code || '';
            editModal.querySelector('#edit_coupon_name').value = name || '';
            editModal.querySelector('#edit_coupon_discount_type').value = discountType;
            editModal.querySelector('#edit_coupon_discount_value').value = discountValue;
            editModal.querySelector('#edit_coupon_min_order_amount').value = minOrderAmount || '';
            editModal.querySelector('#edit_coupon_max_discount_amount').value = maxDiscountAmount || '';
            editModal.querySelector('#edit_coupon_usage_limit').value = usageLimit || '';
            editModal.querySelector('#edit_coupon_usage_limit_per_user').value = usageLimitPerUser || '';
            editModal.querySelector('#edit_coupon_starts_at').value = startsAt || '';
            editModal.querySelector('#edit_coupon_ends_at').value = endsAt || '';
            editModal.querySelector('#edit_coupon_is_active').checked = isActive === '1';

            // Trigger change to update icon
            editModal.querySelector('#edit_coupon_discount_type').dispatchEvent(new Event('change'));
        });
    }

    /* --- Handle Icon change for Discount Type --- */
    function updateDiscountIcon(selectElement, iconElement) {
        if (!iconElement) return;
        if (selectElement.value === 'percentage') {
            iconElement.setAttribute('icon', 'lucide:percent');
        } else {
            iconElement.setAttribute('icon', 'lucide:indian-rupee');
        }
    }

    // Bind event for Create Coupon form
    const createTypeSelect = document.querySelector('select[name="discount_type"]');
    if (createTypeSelect) {
        const iconElement = createTypeSelect.closest('.row').querySelector('input[name="discount_value"]').closest('.icon-field').querySelector('iconify-icon');
        createTypeSelect.addEventListener('change', () => updateDiscountIcon(createTypeSelect, iconElement));
    }

    // Bind event for Edit Coupon modal
    const editTypeSelect = document.getElementById('edit_coupon_discount_type');
    if (editTypeSelect) {
        const iconElement = editTypeSelect.closest('.row').querySelector('#edit_coupon_discount_value').closest('.icon-field').querySelector('iconify-icon');
        editTypeSelect.addEventListener('change', () => updateDiscountIcon(editTypeSelect, iconElement));
    }
});
