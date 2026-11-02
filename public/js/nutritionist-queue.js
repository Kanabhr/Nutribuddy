document.addEventListener('DOMContentLoaded', () => {
  if (!window.jQuery || !jQuery.fn.DataTable) return;

  const tableElement = document.querySelector('#nutritionistQueueTable');
  if (!tableElement) return;

  const escapeHtml = value => {
    const element = document.createElement('span');
    element.textContent = String(value ?? '');
    return element.innerHTML;
  };
  const statusBadge = status => {
    const labels = {
      new: 'New',
      assigned: 'Assigned',
      awaiting_review: 'Awaiting review',
      completed: 'Completed'
    };
    const classes = {
      new: 'bg-primary-focus text-primary-main',
      assigned: 'bg-info-focus text-info-main',
      awaiting_review: 'bg-warning-focus text-warning-main',
      completed: 'bg-success-focus text-success-main'
    };
    const label = labels[status] || String(status || 'Unknown').replaceAll('_', ' ');
    return '<span class="badge ' + (classes[status] || 'bg-secondary-focus text-secondary-main') + '">' + escapeHtml(label) + '</span>';
  };

  const table = jQuery(tableElement).DataTable({
    processing: true,
    serverSide: true,
    pageLength: 10,
    lengthChange: false,
    searchDelay: 350,
    order: [[3, 'desc']],
    ajax: {
      url: tableElement.dataset.source,
      data: request => {
        document.querySelectorAll('[data-queue-filter]').forEach(filter => {
          request[filter.dataset.queueFilter] = filter.value;
        });
      }
    },
    columns: [
      {
        data: 'customer',
        orderable: false,
        render: (value, type, row) => type !== 'display'
          ? value
          : '<strong>' + escapeHtml(value) + '</strong><br><small>' + escapeHtml(row.email) + '</small>'
      },
      { data: 'child', orderable: false, render: (value, type) => type !== 'display' ? value : escapeHtml(value) },
      { data: 'plan', orderable: false, render: (value, type) => type !== 'display' ? value : escapeHtml(value) },
      { data: 'assigned_at', orderable: true, searchable: false, render: (value, type) => type !== 'display' ? value : escapeHtml(value) },
      { data: 'status', orderable: true, render: (value, type) => type !== 'display' ? value : statusBadge(value) },
      {
        data: 'id',
        orderable: false,
        searchable: false,
        render: (id, type) => {
          if (type !== 'display') return id;
          const link = document.createElement('a');
          link.className = 'btn btn-primary btn-sm';
          link.href = tableElement.dataset.reviewBase + '/' + Number(id);
          link.textContent = 'Review chart';
          return link.outerHTML;
        }
      }
    ],
    language: {
      emptyTable: tableElement.dataset.superAdmin === 'true' ? 'No nutrition leads match these filters.' : 'No leads are assigned to you yet.',
      processing: 'Loading nutrition leads…'
    }
  });

  document.querySelectorAll('[data-queue-filter]').forEach(filter => {
    filter.addEventListener('change', () => table.ajax.reload());
  });

  document.querySelector('[data-clear-queue-filters]')?.addEventListener('click', () => {
    document.querySelectorAll('[data-queue-filter]').forEach(filter => { filter.value = ''; });
    table.search('');
    table.ajax.reload();
  });
});
