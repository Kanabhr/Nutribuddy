(function ($) {
    'use strict';

    $(function () {
        const tableElement = document.getElementById('foodsTable');
        if (!tableElement || !$.fn.DataTable) return;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const canManage = tableElement.dataset.canManage === 'true';
        const textRenderer = $.fn.dataTable.render.text();
        const safeText = (value) => textRenderer.display(value == null ? '' : String(value));
        const detailGroups = {
            'Food identity': ['id', 'dish_id', 'dish_name', 'local_name', 'food_group', 'variant_type'],
            'Meal and dietary fit': ['slot', 'also_suitable_for', 'diet_type', 'is_veg', 'is_vegan', 'is_jain_ok', 'region', 'best_fit_goal', 'goal_tags'],
            'Allergens and safety': ['contains_dairy', 'contains_egg', 'contains_nuts', 'contains_gluten', 'contains_soy', 'contains_sesame', 'contains_honey', 'age_min', 'age_max'],
            'Serving and preparation': ['serving_g', 'household_measure', 'cook_min', 'method', 'ingredient_list_per_serving', 'taste', 'texture', 'tiffin_safe', 'reheat_ok', 'digestive_load'],
            'Nutrition': ['energy_kcal', 'protein_g', 'fat_g', 'carb_g', 'sfa_g', 'mufa_g', 'pufa_g', 'fibre_total_g', 'fibre_sol_g', 'free_sugar_g', 'calcium_mg', 'iron_mg', 'zinc_mg', 'magnesium_mg', 'phosphorus_mg', 'potassium_mg', 'sodium_mg', 'selenium_ug', 'iodine_ug', 'copper_mg', 'vit_a_rae_ug', 'vit_c_mg', 'vit_d_ug', 'vit_e_mg', 'vit_k_ug', 'b1_mg', 'b2_mg', 'b3_mg', 'b6_mg', 'folate_ug', 'b12_ug', 'choline_mg'],
            'Ranking and metadata': ['cost_band', 'kid_accept_1_5', 'repeat_gap_days', 'activity_fit', 'n_ingredients', 'created_at', 'updated_at']
        };

        const table = $('#foodsTable').DataTable({
            processing: true,
            serverSide: true,
            deferRender: true,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            searchDelay: 350,
            ajax: {
                url: tableElement.dataset.feedUrl,
                data: function (data) {
                    document.querySelectorAll('.food-filter').forEach((filter) => {
                        data[filter.dataset.filter] = filter.value;
                    });
                }
            },
            columns: [
                { data: 'serial_number', orderable: false, searchable: false, render: safeText },
                { data: 'dish_id', name: 'dish_id', render: safeText },
                { data: 'dish_name', name: 'dish_name', render: safeText },
                { data: 'food_group', name: 'food_group', render: safeText },
                { data: 'slot', name: 'slot', render: safeText },
                { data: 'diet_type', name: 'diet_type', render: safeText },
                { data: 'is_veg', name: 'is_veg', render: function (value) {
                    if (value === '<span class="badge bg-success">Yes</span>' || value === '<span class="badge bg-danger">No</span>') return value;
                    const label = value == null || value === '' ? 'Not set' : String(value);
                    const color = label.toLowerCase() === 'yes' || label.toLowerCase() === 'true' ? 'success' : 'secondary';
                    return '<span class="badge bg-' + color + '">' + safeText(label) + '</span>';
                } },
                { data: 'region', name: 'region', render: safeText },
                { data: 'energy_kcal', name: 'energy_kcal', render: safeText },
                { data: 'id', orderable: false, searchable: false, render: function (id) {
                    const safeId = Number(id);
                    if (!Number.isSafeInteger(safeId) || safeId < 1) return '';
                    return '<div class="food-row-actions"><button type="button" class="btn btn-sm btn-outline-primary food-view-btn" data-id="' + safeId + '"><i class="fa fa-eye me-1" aria-hidden="true"></i>View</button>' +
                        (canManage ? '<button type="button" class="btn btn-sm btn-outline-danger food-delete-btn" data-id="' + safeId + '" aria-label="Delete food"><i class="fa fa-trash" aria-hidden="true"></i></button>' : '') + '</div>';
                } }
            ],
            order: [[2, 'asc']],
            language: {
                searchPlaceholder: 'Search dish, group, slot, diet, or region…',
                emptyTable: 'No food records are available.',
                processing: '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading foods…'
            }
        });

        document.querySelectorAll('.food-filter').forEach((filter) => {
            filter.addEventListener('change', () => table.ajax.reload());
        });
        document.getElementById('clearFoodFilters')?.addEventListener('click', () => {
            document.querySelectorAll('.food-filter').forEach((filter) => { filter.value = ''; });
            table.search('').draw();
        });

        function foodValue(value) {
            if (value === null || value === undefined || value === '') return 'Not set';
            if (typeof value === 'object') return JSON.stringify(value);
            return String(value);
        }

        function appendDetail(section, key, value) {
            const item = document.createElement('div');
            item.className = 'food-detail-item';
            const label = document.createElement('dt');
            label.textContent = key.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
            const detail = document.createElement('dd');
            detail.textContent = foodValue(value);
            item.append(label, detail);
            section.append(item);
        }

        function renderFoodDetails(food) {
            const content = document.getElementById('foodDetailsContent');
            const used = new Set();
            content.replaceChildren();

            Object.entries(detailGroups).forEach(([title, fields]) => {
                const present = fields.filter((field) => Object.prototype.hasOwnProperty.call(food, field));
                if (!present.length) return;
                const section = document.createElement('section');
                section.className = 'food-detail-section';
                const heading = document.createElement('h6');
                heading.textContent = title;
                const list = document.createElement('dl');
                list.className = 'food-detail-list';
                present.forEach((field) => { appendDetail(list, field, food[field]); used.add(field); });
                section.append(heading, list);
                content.append(section);
            });

            const remaining = Object.keys(food).filter((field) => !used.has(field));
            if (remaining.length) {
                const section = document.createElement('section');
                section.className = 'food-detail-section';
                const heading = document.createElement('h6');
                heading.textContent = 'Other food data';
                const list = document.createElement('dl');
                list.className = 'food-detail-list';
                remaining.forEach((field) => appendDetail(list, field, food[field]));
                section.append(heading, list);
                content.append(section);
            }
            document.getElementById('foodDetailsTitle').textContent = food.dish_name || food.dish_id || 'Food details';
        }

        $(document).on('click', '.food-view-btn', function () {
            const id = Number(this.dataset.id);
            const content = document.getElementById('foodDetailsContent');
            content.replaceChildren(Object.assign(document.createElement('p'), { textContent: 'Loading food details…', className: 'text-muted mb-0' }));
            bootstrap.Modal.getOrCreateInstance(document.getElementById('foodDetailsModal')).show();
            $.ajax({ url: tableElement.dataset.showUrl + '/' + id, method: 'GET', dataType: 'json' })
                .done((response) => renderFoodDetails(response.data || {}))
                .fail((xhr) => {
                    content.replaceChildren(Object.assign(document.createElement('p'), { textContent: xhr.responseJSON?.message || 'Could not load this food record. Please try again.', className: 'text-danger mb-0' }));
                });
        });

        $(document).on('click', '.food-delete-btn', function () {
            const id = Number(this.dataset.id);
            const button = this;
            if (!window.confirm('Delete this food record? Existing diet charts may reference it.')) return;
            button.disabled = true;
            $.ajax({
                url: tableElement.dataset.deleteUrl + '/' + id,
                method: 'POST',
                data: { _method: 'DELETE', _token: csrfToken },
                dataType: 'json'
            }).done((response) => {
                table.ajax.reload(null, false);
                window.toastr?.success(response.message || 'Food deleted.');
            }).fail((xhr) => {
                button.disabled = false;
                window.toastr?.error(xhr.responseJSON?.message || 'Could not delete this food.');
                if (!window.toastr) window.alert(xhr.responseJSON?.message || 'Could not delete this food.');
            });
        });

        const uploadButton = document.getElementById('bulkUploadSubmit');
        uploadButton?.addEventListener('click', function () {
            const input = document.getElementById('bulkUploadFile');
            const result = document.getElementById('bulkUploadResult');
            const failedWrap = document.getElementById('bulkUploadFailedWrap');
            const failedBody = document.getElementById('bulkUploadFailedBody');
            const file = input.files?.[0];
            if (!file) {
                result.textContent = 'Choose a file before uploading.';
                result.className = 'alert alert-warning mt-3';
                return;
            }
            const payload = new FormData();
            payload.append('file', file);
            payload.append('_token', csrfToken);
            uploadButton.disabled = true;
            uploadButton.textContent = 'Uploading…';
            result.textContent = '';
            result.className = 'mt-3';
            failedWrap.hidden = true;
            failedBody.replaceChildren();

            $.ajax({
                url: document.getElementById('bulkUploadModal').dataset.uploadUrl,
                method: 'POST',
                data: payload,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done((response) => {
                result.textContent = response.message || 'Upload complete.';
                result.className = 'alert alert-' + ((response.failedCount || 0) ? 'warning' : 'success') + ' mt-3';
                table.ajax.reload(null, false);
                input.value = '';
                (response.failedRows || []).forEach((row) => {
                    const tr = document.createElement('tr');
                    ['dish_id', 'dish_name', 'food_group', 'slot', 'reason'].forEach((field) => {
                        const td = document.createElement('td');
                        td.textContent = row[field] || (field === 'reason' ? 'Unknown error' : '—');
                        tr.append(td);
                    });
                    failedBody.append(tr);
                });
                failedWrap.hidden = failedBody.children.length === 0;
                if (!failedBody.children.length) {
                    window.setTimeout(() => bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkUploadModal')).hide(), 1200);
                }
            }).fail((xhr) => {
                const message = xhr.responseJSON?.message || 'Upload failed. Check the file and try again.';
                result.textContent = message;
                result.className = 'alert alert-danger mt-3';
                const errors = xhr.responseJSON?.errors;
                if (errors) result.textContent += ' ' + Object.values(errors).flat().join(' ');
            }).always(() => {
                uploadButton.disabled = false;
                uploadButton.innerHTML = '<i class="fa fa-upload me-1" aria-hidden="true"></i> Upload file';
            });
        });
    });
})(window.jQuery);
