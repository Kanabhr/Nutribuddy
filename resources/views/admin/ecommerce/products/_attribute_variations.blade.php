@php
    $selectedAttributeValues = [];
    $existingVariations = [];

    if (isset($product)) {
        if (!empty($product->variant_types)) {
            $selectedAttributeValues = $product->variant_types;
        }

        foreach ($product->variants as $variant) {
            if ($variant->is_default && empty($variant->attributes)) {
                continue;
            }

            if (empty($product->variant_types)) {
                foreach (($variant->attributes ?? []) as $attributeName => $value) {
                    $selectedAttributeValues[$attributeName][] = $value;
                }
            }

            $existingVariations[] = [
                'id' => $variant->id,
                'attributes' => $variant->attributes ?? [],
                'name' => $variant->name,
                'sku' => $variant->sku,
                'price' => $variant->price,
                'compare_at_price' => $variant->compare_at_price,
                'cost_price' => $variant->cost_price,
                'stock_qty' => $variant->inventory?->stock_qty ?? 0,
                'track_stock' => $variant->inventory?->track_stock ?? true,
                'is_in_stock' => $variant->inventory?->is_in_stock ?? true,
                'is_default' => $variant->is_default,
                'is_active' => $variant->is_active,
            ];
        }
    }

    $attributeDefinitions = $attributes->map(function ($attribute) {
        return [
            'id' => $attribute->id,
            'name' => $attribute->name,
            'slug' => $attribute->slug,
            'values' => $attribute->values ?? [],
        ];
    })->values();
@endphp

<div class="card border-0 radius-12 mb-24 wc-product-data" id="productVariationBuilder">
    <div class="card-header bg-base border-bottom py-16 px-24 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-0">Product Data</h5>
            <small class="text-secondary-light">Variable product attributes and variations</small>
        </div>
        <span class="badge bg-primary-50 text-primary-600">Variable product</span>
        <input type="hidden" name="is_variant_enabled" id="isVariantEnabledInput" value="{{ !empty($existingVariations) ? 1 : 0 }}">
        <input type="hidden" name="variant_types" value="[]">
    </div>
    <div class="card-body p-0">
        @if($attributes->isEmpty())
            <div class="alert alert-warning m-24">
                No product attributes found. Create attributes such as Size or Color before creating variations.
            </div>
        @else
            <div class="wc-data-layout">
                <div class="wc-data-tabs">
                    <button type="button" class="wc-data-tab active" data-wc-tab="attributesPanel">
                        <iconify-icon icon="lucide:list-tree"></iconify-icon>
                        Attributes
                    </button>
                    <button type="button" class="wc-data-tab" data-wc-tab="variationsPanel">
                        <iconify-icon icon="lucide:git-branch"></iconify-icon>
                        Variations
                        <span id="variationCountLabel">0</span>
                    </button>
                </div>

                <div class="wc-data-content">
                    <div class="wc-tab-panel active" id="attributesPanel">
                        <div class="wc-panel-toolbar">
                            <div>
                                <h6 class="mb-1">Attributes</h6>
                                <small class="text-secondary-light">Choose attribute values and mark them for variations.</small>
                            </div>
                            <button type="button" class="btn btn-primary-600 btn-sm" id="goToVariationsBtn">
                                Save Attributes
                            </button>
                        </div>

                        <div class="wc-attribute-list">
                            @foreach($attributes as $attribute)
                                @php
                                    $chosenValues = collect($selectedAttributeValues[$attribute->name] ?? [])->unique()->all();
                                    $isChosen = function($val) use ($chosenValues) {
                                        foreach ($chosenValues as $chosen) {
                                            $c = strtolower(trim((string)$chosen));
                                            $v = strtolower(trim((string)$val));
                                            if ($c === $v) return true;
                                            if (str_contains($c, 'grape') && str_contains($v, 'grape')) return true;
                                            if (str_contains($c, 'banana') && str_contains($v, 'banana')) return true;
                                            if (str_contains($c, 'mango') && str_contains($v, 'mango')) return true;
                                            if (str_contains($c, 'apple') && str_contains($v, 'apple')) return true;
                                        }
                                        return false;
                                    };
                                @endphp
                                <div class="wc-attribute-item attribute-card" data-attribute-name="{{ $attribute->name }}">
                                    <button type="button" class="wc-attribute-heading">
                                        <span>{{ $attribute->name }}</span>
                                        <small>{{ count($attribute->values ?? []) }} values</small>
                                        <iconify-icon icon="lucide:chevron-down"></iconify-icon>
                                    </button>
                                    <div class="wc-attribute-body">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold">Name</label>
                                                <input type="text" class="form-control" value="{{ $attribute->name }}" readonly>
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label fw-bold">Value(s)</label>
                                                <div class="wc-value-box attribute-values">
                                                    @foreach(($attribute->values ?? []) as $value)
                                                        <label class="wc-value-pill">
                                                            <input type="checkbox"
                                                                class="form-check-input variation-value-toggle"
                                                                name="product_attribute_values[{{ $attribute->id }}][]"
                                                                value="{{ $value }}"
                                                                data-attribute-name="{{ $attribute->name }}"
                                                                {{ $isChosen($value) ? 'checked' : '' }}>
                                                            <span>{{ $value }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="col-12 d-flex flex-wrap gap-4">
                                                <input type="hidden" name="product_attributes[]" value="{{ $attribute->id }}" disabled class="attribute-hidden-input">
                                                <label class="form-check d-flex align-items-center gap-2 p-0 mb-0">
                                                    <input type="checkbox"
                                                        class="form-check-input variation-attribute-toggle"
                                                        value="{{ $attribute->id }}"
                                                        data-attribute-name="{{ $attribute->name }}"
                                                        {{ !empty($chosenValues) ? 'checked' : '' }}>
                                                    <span>Used for variations</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="wc-tab-panel" id="variationsPanel">
                        <div class="wc-panel-toolbar">
                            <div>
                                <h6 class="mb-1">Variations</h6>
                                <small class="text-secondary-light">Create all variations from selected attribute values.</small>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <select class="form-select form-select-sm wc-action-select" id="variationActionSelect">
                                    <option value="generate">Create variations from all attributes</option>
                                </select>
                                <button type="button" class="btn btn-primary-600 btn-sm" id="generateVariationsBtn">Go</button>
                            </div>
                        </div>

                        <div class="wc-defaults-row">
                            <span>Default Form Values:</span>
                            <small class="text-secondary-light">Set a default variation inside any variation row.</small>
                        </div>

                        <div class="wc-variation-list" id="variationsTableBody">
                            <div id="emptyVariationsRow" class="wc-empty-variations">
                                No variations yet. Select attributes, then choose "Create variations from all attributes" and click Go.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
    window.productAttributeDefinitions = @json($attributeDefinitions);
    window.existingProductVariations = @json($existingVariations);
</script>
<style>
    .wc-product-data {
        overflow: hidden;
    }

    .wc-data-layout {
        display: grid;
        grid-template-columns: 220px minmax(0, 1fr);
        min-height: 520px;
    }

    .wc-data-tabs {
        background: #f8fafc;
        border-right: 1px solid var(--nb-line);
        padding: 12px;
    }

    .wc-data-tab {
        width: 100%;
        min-height: 46px;
        display: flex;
        align-items: center;
        gap: 10px;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: var(--nb-ink-soft);
        font-weight: 800;
        text-align: left;
        padding: 0 12px;
    }

    .wc-data-tab.active {
        background: #fff;
        color: var(--nb-ink);
        box-shadow: 0 8px 18px rgba(6, 78, 59, 0.08);
    }

    .wc-data-tab span {
        margin-left: auto;
        min-width: 24px;
        height: 24px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(16, 185, 129, 0.12);
        color: var(--nb-pink-dark);
        font-size: 12px;
    }

    .wc-data-content {
        min-width: 0;
        background: rgba(255, 255, 255, 0.62);
    }

    .wc-tab-panel {
        display: none;
    }

    .wc-tab-panel.active {
        display: block;
    }

    .wc-panel-toolbar,
    .wc-defaults-row {
        min-height: 66px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 16px 20px;
        border-bottom: 1px solid var(--nb-line);
    }

    .wc-action-select {
        width: 280px;
    }

    .wc-attribute-list,
    .wc-variation-list {
        padding: 16px 20px 20px;
    }

    .wc-attribute-item,
    .variation-row {
        border: 1px solid var(--nb-line);
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
        margin-bottom: 12px;
    }

    .wc-attribute-heading,
    .wc-variation-heading {
        width: 100%;
        min-height: 52px;
        display: flex;
        align-items: center;
        gap: 12px;
        border: 0;
        background: #fff;
        padding: 0 16px;
    }

    .wc-attribute-heading {
        font-weight: 900;
        text-align: left;
    }

    .wc-attribute-heading small {
        margin-left: auto;
        color: var(--nb-ink-soft);
        font-weight: 700;
    }

    .wc-attribute-body,
    .wc-variation-body {
        display: none;
        padding: 18px;
        border-top: 1px solid var(--nb-line);
        background: #fbfefc;
    }

    .wc-attribute-item.is-open .wc-attribute-body,
    .variation-row.is-open .wc-variation-body {
        display: block;
    }

    .wc-value-box {
        min-height: 46px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 8px;
        border: 1px solid var(--nb-line);
        border-radius: 8px;
        background: #fff;
    }

    .wc-value-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(16, 185, 129, 0.1);
        color: var(--nb-pink-dark);
        font-weight: 800;
        font-size: 12px;
    }

    .wc-variation-title {
        min-width: 0;
        flex: 1;
        display: flex;
        align-items: center;
        gap: 8px;
        border: 0;
        background: transparent;
        color: var(--nb-ink);
        font-weight: 900;
        text-align: left;
    }

    .wc-variation-title span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .wc-variation-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .wc-row-toggle {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--nb-line);
        border-radius: 8px;
        background: #fff;
        color: var(--nb-ink);
    }

    .variation-row.is-open .wc-row-toggle iconify-icon,
    .wc-attribute-item.is-open .wc-attribute-heading iconify-icon {
        transform: rotate(180deg);
    }

    .wc-checkbox-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px 16px;
        padding: 12px;
        border: 1px solid var(--nb-line);
        border-radius: 8px;
        background: #fff;
    }

    .wc-checkbox-grid label {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
        font-weight: 700;
    }

    .wc-empty-variations {
        padding: 34px 18px;
        border: 1px dashed rgba(6, 78, 59, 0.24);
        border-radius: 8px;
        background: #fff;
        color: var(--nb-ink-soft);
        text-align: center;
        font-weight: 700;
    }

    @media (max-width: 991.98px) {
        .wc-data-layout {
            grid-template-columns: 1fr;
        }

        .wc-data-tabs {
            display: flex;
            gap: 8px;
            border-right: 0;
            border-bottom: 1px solid var(--nb-line);
        }

        .wc-data-tab {
            width: auto;
            flex: 1;
        }

        .wc-panel-toolbar,
        .wc-defaults-row {
            align-items: flex-start;
            flex-direction: column;
        }

        .wc-action-select {
            width: 100%;
        }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const body = document.getElementById('variationsTableBody');
        const emptyRow = document.getElementById('emptyVariationsRow');
        const countLabel = document.getElementById('variationCountLabel');
        const generateButton = document.getElementById('generateVariationsBtn');
        const variantEnabledInput = document.getElementById('isVariantEnabledInput');

        if (!body || !generateButton) return;

        let rowIndex = 0;
        const existingByKey = new Map();

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function attributeKey(attributes) {
            return Object.keys(attributes)
                .sort()
                .map(key => `${key}:${attributes[key]}`)
                .join('|');
        }

        function slugPart(value) {
            return String(value ?? '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-|-$/g, '')
                .toUpperCase();
        }

        function syncCount() {
            const rows = body.querySelectorAll('.variation-row').length;
            if (countLabel) countLabel.textContent = rows;
            if (emptyRow) emptyRow.style.display = rows ? 'none' : '';
            if (variantEnabledInput) variantEnabledInput.value = rows ? '1' : '0';
            syncParentStockVisibility(rows);
        }

        function syncParentStockVisibility(rows = null) {
            const parentStockGroup = document.getElementById('parentStockGroup');
            const productStockInput = document.getElementById('productStockInput');
            const variationCount = rows ?? body.querySelectorAll('.variation-row').length;
            if (!parentStockGroup || !productStockInput) return;

            const useVariationStock = variationCount > 0;
            parentStockGroup.classList.toggle('d-none', useVariationStock);
            productStockInput.required = !useVariationStock;
            productStockInput.disabled = useVariationStock;
        }

        function selectedAttributeGroups() {
            const groups = [];
            document.querySelectorAll('.variation-attribute-toggle:checked').forEach(attributeCheckbox => {
                const attributeName = attributeCheckbox.dataset.attributeName;
                const card = attributeCheckbox.closest('.attribute-card');
                const values = Array.from(card.querySelectorAll('.variation-value-toggle:checked'))
                    .map(input => input.value)
                    .filter(Boolean);

                if (values.length) {
                    groups.push({ name: attributeName, values });
                }
            });
            return groups;
        }

        function combinations(groups, index = 0, current = {}) {
            if (index >= groups.length) return [current];
            return groups[index].values.flatMap(value => combinations(groups, index + 1, {
                ...current,
                [groups[index].name]: value,
            }));
        }

        function isVariationAllowed(attributes) {
            const groups = selectedAttributeGroups();
            if (!groups.length) return true;

            for (const key of Object.keys(attributes)) {
                const val = attributes[key];
                const group = groups.find(g => g.name.toLowerCase() === key.toLowerCase());
                if (group) {
                    const matched = group.values.some(v => {
                        const vLower = v.toLowerCase().trim();
                        const valLower = String(val).toLowerCase().trim();
                        if (vLower === valLower) return true;
                        if (vLower.includes('grape') && valLower.includes('grape')) return true;
                        if (vLower.includes('banana') && valLower.includes('banana')) return true;
                        if (vLower.includes('mango') && valLower.includes('mango')) return true;
                        if (vLower.includes('apple') && valLower.includes('apple')) return true;
                        return false;
                    });
                    if (!matched) return false;
                }
            }
            return true;
        }

        function appendVariationRow(variation) {
            const index = rowIndex++;
            const attributes = variation.attributes || {};
            const label = Object.entries(attributes).map(([key, value]) => `${key}: ${value}`).join(' / ');
            const baseSku = document.querySelector('input[name="sku"]')?.value || 'SKU';
            const generatedSku = `${baseSku}-${Object.values(attributes).map(slugPart).filter(Boolean).join('-')}`;
            const sku = variation.sku || generatedSku;
            const price = variation.price ?? document.querySelector('input[name="base_price"]')?.value ?? 0;
            const compareAtPrice = variation.compare_at_price ?? document.querySelector('input[name="compare_at_price"]')?.value ?? '';
            const costPrice = variation.cost_price ?? '';
            const stockQty = variation.stock_qty ?? 0;

            const isAllowed = isVariationAllowed(attributes);
            const isActive = variation.is_active !== false && isAllowed;

            const attributeInputs = Object.entries(attributes).map(([key, value]) => `
                <input type="hidden" name="variations[${index}][attributes][${escapeHtml(key)}]" value="${escapeHtml(value)}">
            `).join('');

            const row = document.createElement('div');
            row.className = 'variation-row';
            row.innerHTML = `
                <div class="wc-variation-heading">
                    <input type="hidden" name="variations[${index}][id]" value="${escapeHtml(variation.id || '')}">
                    <input type="hidden" name="variations[${index}][name]" value="${escapeHtml(label)}">
                    ${attributeInputs}
                    <button type="button" class="wc-variation-title">
                        <iconify-icon icon="lucide:grip-vertical"></iconify-icon>
                        <span>#${variation.id ? escapeHtml(variation.id) : 'new'} ${escapeHtml(label)}</span>
                    </button>
                    <div class="wc-variation-actions">
                        <span class="badge ${isActive ? 'bg-success-100 text-success-600' : 'bg-danger-100 text-danger-600'} status-badge">${isActive ? 'Enabled' : 'Disabled'}</span>
                        <button type="button" class="btn btn-sm btn-outline-danger-600 remove-variation">Remove</button>
                        <button type="button" class="wc-row-toggle" aria-label="Toggle variation">
                            <iconify-icon icon="lucide:chevron-down"></iconify-icon>
                        </button>
                    </div>
                </div>
                <div class="wc-variation-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">SKU</label>
                            <input type="text" name="variations[${index}][sku]" class="form-control" value="${escapeHtml(sku)}" placeholder="Auto generated if empty">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">MRP / Compare (INR)</label>
                            <input type="number" step="0.01" min="0" name="variations[${index}][compare_at_price]" class="form-control" value="${escapeHtml(compareAtPrice)}" placeholder="MRP">
                            <input type="hidden" name="variations[${index}][cost_price]" value="${escapeHtml(costPrice)}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Selling Price (INR)</label>
                            <input type="number" step="0.01" min="0" name="variations[${index}][price]" class="form-control" value="${escapeHtml(price)}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Stock Quantity</label>
                            <input type="hidden" name="variations[${index}][track_stock]" value="0">
                            <input type="hidden" name="variations[${index}][is_in_stock]" value="0">
                            <input type="number" min="0" name="variations[${index}][stock_qty]" class="form-control" value="${escapeHtml(stockQty)}">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Variation Settings</label>
                            <div class="wc-checkbox-grid">
                                <input type="hidden" name="variations[${index}][track_stock]" value="1">
                                <input type="hidden" name="variations[${index}][is_in_stock]" value="1">
                                <input type="hidden" name="variations[${index}][is_active]" value="0">
                                <input type="hidden" class="variation-default-hidden" name="variations[${index}][is_default]" value="${variation.is_default ? 1 : 0}">
                                <label>
                                    <input class="form-check-input variation-default-radio" type="radio" name="default_variation_row" value="${index}" ${variation.is_default ? 'checked' : ''}>
                                    Default variation
                                </label>
                                <label>
                                    <input class="form-check-input variation-active-checkbox" type="checkbox" name="variations[${index}][is_active]" value="1" ${isActive ? 'checked' : ''}>
                                    Enabled
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            body.appendChild(row);
            row.querySelector('.remove-variation').addEventListener('click', function () {
                row.remove();
                syncCount();
            });
            row.querySelector('.variation-default-radio').addEventListener('change', syncDefaultVariation);
            row.querySelector('.wc-row-toggle').addEventListener('click', function () {
                row.classList.toggle('is-open');
            });
            row.querySelector('.wc-variation-title').addEventListener('click', function () {
                row.classList.toggle('is-open');
            });

            const activeCheckbox = row.querySelector('.variation-active-checkbox');
            const statusBadge = row.querySelector('.status-badge');
            if (activeCheckbox && statusBadge) {
                activeCheckbox.addEventListener('change', function () {
                    if (this.checked) {
                        statusBadge.textContent = 'Enabled';
                        statusBadge.className = 'badge bg-success-100 text-success-600 status-badge';
                    } else {
                        statusBadge.textContent = 'Disabled';
                        statusBadge.className = 'badge bg-danger-100 text-danger-600 status-badge';
                    }
                });
            }

            syncCount();
        }

        function syncDefaultVariation() {
            body.querySelectorAll('.variation-default-hidden').forEach(input => input.value = '0');
            const checked = body.querySelector('.variation-default-radio:checked');
            if (checked) {
                const row = checked.closest('.variation-row');
                row.querySelector('.variation-default-hidden').value = '1';
            }
        }

        window.existingProductVariations.forEach(variation => {
            existingByKey.set(attributeKey(variation.attributes || {}), variation);
            appendVariationRow(variation);
        });
        syncDefaultVariation();
        syncCount();

        generateButton.addEventListener('click', function () {
            const groups = selectedAttributeGroups();
            if (!groups.length) {
                alert('Please select at least one attribute value.');
                return;
            }

            const currentKeys = new Set(Array.from(body.querySelectorAll('.variation-row')).map(row => {
                const attributes = {};
                row.querySelectorAll('input[name*="[attributes]"]').forEach(input => {
                    const match = input.name.match(/\[attributes\]\[(.+)\]$/);
                    if (match) attributes[match[1]] = input.value;
                });
                return attributeKey(attributes);
            }));

            combinations(groups).forEach(attributes => {
                const key = attributeKey(attributes);
                if (!currentKeys.has(key)) {
                    appendVariationRow(existingByKey.get(key) || { attributes });
                    currentKeys.add(key);
                }
            });
            syncDefaultVariation();
        });

        document.querySelectorAll('.variation-attribute-toggle').forEach(input => {
            input.addEventListener('change', function () {
                const card = this.closest('.attribute-card');
                const hiddenInput = card.querySelector('.attribute-hidden-input');
                if (hiddenInput) hiddenInput.disabled = !this.checked;
                card.querySelectorAll('.variation-value-toggle').forEach(valueInput => {
                    valueInput.disabled = !this.checked;
                    if (!this.checked) valueInput.checked = false;
                });
            });
            input.dispatchEvent(new Event('change'));
        });

        // Auto-deactivate variations in UI if the attribute option value is unchecked
        document.querySelectorAll('.variation-value-toggle').forEach(valueInput => {
            valueInput.addEventListener('change', function () {
                const attrName = this.dataset.attributeName;
                const attrVal = this.value;
                const isChecked = this.checked;

                if (!isChecked) {
                    body.querySelectorAll('.variation-row').forEach(row => {
                        const attrInput = row.querySelector(`input[name*="[attributes][${attrName}]"]`);
                        if (attrInput && attrInput.value === attrVal) {
                            const activeCheckbox = row.querySelector('.variation-active-checkbox');
                            if (activeCheckbox) {
                                activeCheckbox.checked = false;
                                activeCheckbox.dispatchEvent(new Event('change'));
                            }
                        }
                    });
                }
            });
        });

        document.querySelectorAll('.wc-data-tab').forEach(tab => {
            tab.addEventListener('click', function () {
                document.querySelectorAll('.wc-data-tab').forEach(item => item.classList.remove('active'));
                document.querySelectorAll('.wc-tab-panel').forEach(panel => panel.classList.remove('active'));
                this.classList.add('active');
                document.getElementById(this.dataset.wcTab)?.classList.add('active');
            });
        });

        document.querySelectorAll('.wc-attribute-heading').forEach(button => {
            button.addEventListener('click', function () {
                this.closest('.wc-attribute-item').classList.toggle('is-open');
            });
        });

        document.querySelectorAll('.wc-attribute-item').forEach(item => {
            if (item.querySelector('.variation-attribute-toggle')?.checked) {
                item.classList.add('is-open');
            }
        });

        const basePriceInput = document.querySelector('input[name="base_price"]');
        const comparePriceInput = document.querySelector('input[name="compare_at_price"]');

        if (basePriceInput) {
            basePriceInput.addEventListener('input', function () {
                const newVal = this.value;
                body.querySelectorAll('input[name*="[price]"]').forEach(input => {
                    input.value = newVal;
                });
            });
        }

        if (comparePriceInput) {
            comparePriceInput.addEventListener('input', function () {
                const newVal = this.value;
                body.querySelectorAll('input[name*="[compare_at_price]"]').forEach(input => {
                    input.value = newVal;
                });
            });
        }

        document.getElementById('goToVariationsBtn')?.addEventListener('click', function () {
            document.querySelector('[data-wc-tab="variationsPanel"]')?.click();
        });
    });
</script>
