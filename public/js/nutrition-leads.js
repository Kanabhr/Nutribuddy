document.addEventListener('DOMContentLoaded', () => {
  if (!window.jQuery || !jQuery.fn.DataTable) return;

  const tableElement = document.querySelector('#nutritionLeadsTable');
  const table = jQuery(tableElement);
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const optionsTemplate = document.querySelector('[data-assignee-options]');
  const successMessage = document.querySelector('[data-lead-message]');
  const errorMessage = document.querySelector('[data-lead-error]');
  const selectionCount = document.querySelector('[data-selection-count]');

  const escapeText = value => {
    const element = document.createElement('span');
    element.textContent = String(value ?? '');
    return element.innerHTML;
  };
  const showMessage = (message, isError = false) => {
    successMessage.classList.toggle('d-none', isError || !message);
    errorMessage.classList.toggle('d-none', !isError || !message);
    (isError ? errorMessage : successMessage).textContent = message || '';
  };
  const statusBadge = status => {
    const label = String(status || 'new').replaceAll('_', ' ');
    const classes = {
      new: 'bg-primary-focus text-primary-main',
      assigned: 'bg-info-focus text-info-main',
      awaiting_review: 'bg-warning-focus text-warning-main',
      completed: 'bg-success-focus text-success-main'
    };
    return '<span data-lead-status class="badge ' + (classes[status] || 'bg-secondary-focus text-secondary-main') + '">' + escapeText(label.replace(/\b\w/g, character => character.toUpperCase())) + '</span>';
  };
  const setSelectionLabel = () => {
    const selected = tableElement.querySelectorAll('[data-lead-checkbox]:checked').length;
    selectionCount.textContent = selected ? `${selected} selected on this page` : 'Select rows on this page';
  };
  const selectedRows = () => Array.from(tableElement.querySelectorAll('tbody tr'))
    .filter(row => row.querySelector('[data-lead-checkbox]:checked'));
  const updateLeadRow = (row, result) => {
    const select = row.querySelector('[data-assign-url]');
    if (select) {
      select.value = String(result.assigned_to || '');
      select.dataset.previousValue = select.value;
    }
    const assigneeCell = row.querySelector('[data-assignee-label]');
    if (assigneeCell) assigneeCell.textContent = result.assignee || 'Unassigned';
    const badge = row.querySelector('[data-lead-status]');
    if (badge) badge.outerHTML = statusBadge(result.status || 'assigned');
    const checkbox = row.querySelector('[data-lead-checkbox]');
    if (checkbox) checkbox.checked = false;
  };
  const requestJson = async (url, method, body) => {
    const response = await fetch(url, {
      method,
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken,
        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
      },
      body: body.toString()
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(payload.message || 'The assignment could not be saved. Please try again.');
    return payload;
  };

  const dataTable = table.DataTable({
    processing: true,
    serverSide: true,
    pageLength: 10,
    lengthChange: false,
    searchDelay: 350,
    order: [[3, 'desc']],
    ajax: {
      url: table.data('source'),
      data: request => {
        document.querySelectorAll('[data-lead-filter]').forEach(filter => {
          request[filter.dataset.leadFilter] = filter.value;
        });
      }
    },
    columns: [
      {
        data: 'id', orderable: false, searchable: false,
        render: (id, type, row) => '<input class="form-check-input border-secondary" type="checkbox" value="' + Number(id) + '" data-lead-checkbox' + (row.status === 'completed' ? ' disabled aria-label="Completed leads cannot be reassigned"' : ' aria-label="Select lead"') + '>'
      },
      {
        data: 'customer',
        render: (value, type, row) => type !== 'display' ? value : '<strong>' + escapeText(value) + '</strong><br><small>' + escapeText(row.email) + '</small>'
      },
      {
        data: 'child',
        render: (value, type, row) => type !== 'display' ? value : escapeText(value) + '<br><small>' + escapeText(row.plan) + '</small>'
      },
      {
        data: 'amount_paid',
        render: (value, type, row) => type !== 'display' ? value : '₹' + Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '<br><small>' + escapeText(row.purchased_at) + '</small>'
      },
      { data: 'status', render: (value, type) => type !== 'display' ? value : statusBadge(value) },
      { data: 'assignee', render: (value, type) => type !== 'display' ? value : '<span data-assignee-label>' + escapeText(value) + '</span>' },
      {
        data: 'assigned_to', orderable: false, searchable: false,
        render: (value, type, row) => {
          if (type !== 'display') return value;
          const select = document.createElement('select');
          select.className = 'form-select form-select-sm';
          select.dataset.assignUrl = window.location.pathname + '/' + Number(row.id) + '/assign';
          select.dataset.previousValue = value ? String(value) : '';
          select.setAttribute('aria-label', 'Assign ' + String(row.customer || 'lead'));
          select.disabled = row.status === 'completed';
          Array.from(optionsTemplate.options).forEach(option => select.add(option.cloneNode(true)));
          select.value = value ? String(value) : '';
          return select.outerHTML;
        }
      }
    ],
    drawCallback: () => {
      const selectAll = document.querySelector('[data-select-all]');
      if (selectAll) selectAll.checked = false;
      setSelectionLabel();
    },
    language: { emptyTable: 'No purchased diet leads match these filters.', processing: 'Loading nutrition leads…' }
  });

  document.querySelectorAll('[data-lead-filter]').forEach(filter => {
    filter.addEventListener('change', () => dataTable.ajax.reload());
  });
  document.querySelector('[data-clear-lead-filters]')?.addEventListener('click', () => {
    document.querySelectorAll('[data-lead-filter]').forEach(filter => { filter.value = ''; });
    dataTable.search('');
    dataTable.ajax.reload();
  });
  document.querySelector('[data-select-all]')?.addEventListener('change', event => {
    tableElement.querySelectorAll('tbody [data-lead-checkbox]:not(:disabled)').forEach(box => { box.checked = event.target.checked; });
    setSelectionLabel();
  });
  tableElement.addEventListener('change', async event => {
    const select = event.target.closest('[data-assign-url]');
    if (!select) {
      if (event.target.matches('[data-lead-checkbox]')) setSelectionLabel();
      return;
    }
    if (!select.value) return;

    const previousValue = select.dataset.previousValue || '';
    select.disabled = true;
    showMessage('Saving lead assignment…');
    try {
      const result = await requestJson(select.dataset.assignUrl, 'PATCH', new URLSearchParams({ assigned_to: select.value }));
      updateLeadRow(select.closest('tr'), result);
      showMessage(result.message || 'Lead assigned successfully.');
    } catch (error) {
      select.value = previousValue;
      showMessage(error.message, true);
    } finally {
      select.disabled = false;
    }
  });

  document.querySelector('[data-bulk-leads]')?.addEventListener('submit', async event => {
    event.preventDefault();
    const form = event.currentTarget;
    const assignedTo = form.elements.assigned_to.value;
    const rows = selectedRows();
    if (!assignedTo || rows.length === 0) {
      showMessage('Choose a nutritionist and select at least one lead on this page.', true);
      return;
    }

    const body = new URLSearchParams({ assigned_to: assignedTo });
    rows.forEach(row => body.append('lead_ids[]', row.querySelector('[data-lead-checkbox]').value));
    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;
    showMessage('Assigning selected leads…');
    try {
      const result = await requestJson(form.dataset.bulkUrl, 'PATCH', body);
      form.elements.assigned_to.value = '';
      document.querySelector('[data-select-all]').checked = false;
      dataTable.ajax.reload(null, false);
      showMessage(result.message || 'Selected leads assigned successfully.');
    } catch (error) {
      showMessage(error.message, true);
    } finally {
      button.disabled = false;
    }
  });
});
