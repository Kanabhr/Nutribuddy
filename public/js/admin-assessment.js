function switchTab(idx) {
      document.querySelectorAll('.aqt-tab').forEach(t => {
        t.classList.remove('active');
        t.setAttribute('aria-selected', 'false');
      });
      document.querySelectorAll('.aqt-panel').forEach(p => p.classList.remove('active'));
      const tab = document.getElementById('tab-btn-' + idx);
      const panel = document.getElementById('tab-panel-' + idx);
      if (tab) { tab.classList.add('active'); tab.setAttribute('aria-selected', 'true'); }
      if (panel) { panel.classList.add('active'); }
      // Sync URL so a page refresh or redirect lands on this tab
      const section = tab ? tab.dataset.section : '';
      if (section) {
        const url = new URL(window.location);
        url.searchParams.set('tab', section);
        history.replaceState(null, '', url.toString());
      }
    }

    function openAddModal() {
      const activeTab = document.querySelector('.aqt-tab.active');
      if (activeTab) {
        const sec = activeTab.dataset.section;
        const inp = document.getElementById('modal-section-input');
        if (inp && !inp.value) inp.value = sec;
      }
      document.getElementById('addModal').classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    // Open modal for a brand-new section: clear section field and focus it
    function openNewSectionModal() {
      const inp = document.getElementById('modal-section-input');
      if (inp) { inp.value = ''; }
      document.getElementById('addModal').classList.add('open');
      document.body.style.overflow = 'hidden';
      if (inp) setTimeout(() => inp.focus(), 100);
    }
    function closeModal() {
      document.getElementById('addModal').classList.remove('open');
      document.body.style.overflow = '';
    }
    function closeAddModal(e) {
      if (e.target === document.getElementById('addModal')) closeModal();
    }

    let optionCount = 2;
    function addOption() {
      const c = document.getElementById('optionsContainer');
      const row = document.createElement('div');
      row.className = 'aqt-opt-row';
      row.innerHTML = `
                <input type="text" name="options[${optionCount}][option_text]" class="aqt-ctrl" placeholder="Option text" required>
                <input type="number" name="options[${optionCount}][score]" class="aqt-score" placeholder="Score" min="0" max="99" required>
                <input type="hidden" name="options[${optionCount}][sort_order]" value="${optionCount}">
                <button type="button" class="aqt-rem-opt" onclick="removeOption(this)">&#x2715;</button>
              `;
      c.appendChild(row);
      optionCount++;
      syncRemoveButtons();
    }
    function removeOption(btn) {
      const rows = document.querySelectorAll('.aqt-opt-row');
      if (rows.length <= 2) return;
      btn.closest('.aqt-opt-row').remove();
      syncRemoveButtons();
    }
    function syncRemoveButtons() {
      const rows = document.querySelectorAll('.aqt-opt-row');
      rows.forEach(r => { r.querySelector('.aqt-rem-opt').disabled = rows.length <= 2; });
    }

    function toggleActive(id, btn) {
      fetch(`/admin/assessment/questions/${id}/toggle`, {
        method: 'PATCH',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'Accept': 'application/json',
        }
      })
        .then(r => r.json())
        .then(d => {
          if (!d.success) return;
          const card = document.getElementById('qcard-admin-' + id);
          if (d.is_active) {
            btn.classList.replace('inactive', 'active');
            btn.textContent = '✓ Active';
            card.classList.remove('aqt-inactive');
          } else {
            btn.classList.replace('active', 'inactive');
            btn.textContent = '✗ Inactive';
            card.classList.add('aqt-inactive');
          }
        });
    }

    function startTabRename(editSpan) {
      const tab = editSpan.closest('.aqt-tab');
      const nameEl = tab.querySelector('.aqt-tab-name');
      const oldName = nameEl.dataset.section;
      const inp = document.createElement('input');
      inp.type = 'text';
      inp.value = oldName;
      inp.className = 'aqt-tab-rename-inp';
      nameEl.replaceWith(inp);
      editSpan.style.display = 'none';
      inp.focus(); inp.select();
      let committed = false;
      const commit = () => {
        if (committed) return; committed = true;
        const newName = inp.value.trim();
        if (!newName || newName === oldName) { cancelTabRename(tab, inp, editSpan, oldName); return; }
        doRename(tab, inp, editSpan, oldName, newName);
      };
      inp.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); commit(); }
        if (e.key === 'Escape') { committed = true; cancelTabRename(tab, inp, editSpan, oldName); }
      });
      inp.addEventListener('blur', commit);
    }

    function cancelTabRename(tab, inp, editSpan, oldName) {
      restoreTabName(tab, inp, editSpan, oldName);
    }

    function doRename(tab, inp, editSpan, oldName, newName) {
      fetch(window.assessmentRenameUrl, {
        method: 'PATCH',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ old_name: oldName, new_name: newName }),
      })
        .then(r => r.json())
        .then(d => {
          if (d.success) {
            restoreTabName(tab, inp, editSpan, newName);
            tab.dataset.section = newName;
            if (tab.classList.contains('active')) {
              const mi = document.getElementById('modal-section-input');
              if (mi) mi.value = newName;
            }
          } else {
            restoreTabName(tab, inp, editSpan, oldName);
          }
        })
        .catch(() => restoreTabName(tab, inp, editSpan, oldName));
    }

    function restoreTabName(tab, inp, editSpan, name) {
      const newEl = document.createElement('span');
      newEl.className = 'aqt-tab-name';
      newEl.dataset.section = name;
      newEl.textContent = name;
      inp.replaceWith(newEl);
      editSpan.style.display = '';
    }

    const flash = document.getElementById('aqt-flash');
    if (flash) setTimeout(() => { flash.style.transition = '.4s'; flash.style.opacity = '0'; setTimeout(() => flash.remove(), 400); }, 4000);