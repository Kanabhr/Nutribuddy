@extends('layouts.admin')
@php
    $title = 'Edit Question';
    $subTitle = 'Assessment / Questions / Edit';
@endphp

@section('content')
<div>

  <div class="aq-page-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;">
    <div>
      <h1 style="font-family:'Fredoka One',cursive;font-size:1.8rem;color:#1a0040;margin:0 0 4px">Edit Question</h1>
      <p style="color:#888;font-size:.88rem;margin:0">Update this question's title, section, and answer options.</p>
    </div>
    <a href="{{ route('admin.assessment.questions.index') }}" class="aq-btn-outline">← Back</a>
  </div>

  @if($errors->any())
    <div class="admin-alert" style="background:#fef2f2;color:#dc2626;border:1.5px solid #fca5a5;padding:12px 20px;border-radius:12px;margin-bottom:20px">
      <strong>Please fix the errors below:</strong>
      <ul style="margin:6px 0 0 16px">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div style="background:#fff;border-radius:20px;padding:28px;border:1.5px solid #ede8f5;">
    <form action="{{ route('admin.assessment.questions.update', $question) }}" method="POST" id="editForm">
      @csrf @method('PUT')

      <div class="aq-field" style="margin-bottom:16px">
        <label style="display:block;font-weight:700;font-size:.83rem;color:#444;margin-bottom:6px">Question Title <span style="color:#ff4d8f">*</span></label>
        <textarea name="title" rows="2" style="width:100%;border:1.5px solid #e5e7eb;border-radius:10px;padding:9px 13px;font-size:.88rem;outline:none;transition:.2s;font-family:inherit;box-sizing:border-box" required>{{ old('title', $question->title) }}</textarea>
      </div>

      <div class="aq-field" style="margin-bottom:16px">
        <label style="display:block;font-weight:700;font-size:.83rem;color:#444;margin-bottom:6px">Description / Hint (optional)</label>
        <input type="text" name="description"
               style="width:100%;border:1.5px solid #e5e7eb;border-radius:10px;padding:9px 13px;font-size:.88rem;outline:none;box-sizing:border-box"
               value="{{ old('description', $question->description) }}">
      </div>

      <div style="display:flex;gap:12px;margin-bottom:16px">
        <div style="flex:1">
          <label style="display:block;font-weight:700;font-size:.83rem;color:#444;margin-bottom:6px">Section <span style="color:#ff4d8f">*</span></label>
          <select name="section" required
                  style="width:100%;border:1.5px solid #e5e7eb;border-radius:10px;padding:9px 13px;font-size:.88rem;outline:none;box-sizing:border-box">
            @foreach($sections as $sec)
              <option value="{{ $sec }}" {{ old('section', $question->section) == $sec ? 'selected' : '' }}>{{ $sec }}</option>
            @endforeach
          </select>
        </div>
        <div style="width:130px">
          <label style="display:block;font-weight:700;font-size:.83rem;color:#444;margin-bottom:6px">Sort Order</label>
          <input type="number" name="sort_order" min="0"
                 style="width:100%;border:1.5px solid #e5e7eb;border-radius:10px;padding:9px 13px;font-size:.88rem;outline:none;box-sizing:border-box"
                 value="{{ old('sort_order', $question->sort_order) }}">
        </div>
      </div>

      {{-- Options --}}
      <div style="margin-bottom:20px">
        <label style="display:block;font-weight:700;font-size:.83rem;color:#444;margin-bottom:4px">Answer Options <span style="color:#ff4d8f">*</span></label>
        <p style="font-size:.77rem;color:#888;margin:0 0 10px">Score = points awarded for that answer. 0 = poorest, higher = better.</p>

        <div id="editOptionsContainer">
          @foreach($question->options as $i => $option)
            <div class="edit-option-row" style="display:flex;gap:8px;margin-bottom:8px;align-items:center">
              <input type="hidden" name="options[{{ $i }}][id]" value="{{ $option->id }}">
              <input type="hidden" name="options[{{ $i }}][_delete]" value="0" class="delete-flag">
              <input type="text" name="options[{{ $i }}][option_text]"
                     style="flex:1;border:1.5px solid #e5e7eb;border-radius:10px;padding:9px 13px;font-size:.88rem;outline:none;box-sizing:border-box"
                     value="{{ old("options.$i.option_text", $option->option_text) }}" required>
              <input type="number" name="options[{{ $i }}][score]" min="0" max="99"
                     style="width:90px;border:1.5px solid #e5e7eb;border-radius:10px;padding:9px 10px;font-size:.88rem;outline:none;box-sizing:border-box"
                     value="{{ old("options.$i.score", $option->score) }}" required>
              <input type="hidden" name="options[{{ $i }}][sort_order]" value="{{ $i }}">
              <button type="button" onclick="markDelete(this)"
                      style="background:none;border:1.5px solid #fca5a5;color:#dc2626;border-radius:8px;padding:6px 10px;cursor:pointer;font-weight:700">✕</button>
            </div>
          @endforeach
        </div>

        <button type="button" onclick="addEditOption()"
                style="background:none;border:2px dashed #d1d5db;border-radius:10px;padding:8px 16px;cursor:pointer;color:#666;font-size:.83rem;font-weight:600;width:100%;margin-top:4px;transition:.2s">
          + Add Another Option
        </button>
      </div>

      <div style="display:flex;justify-content:flex-end;gap:10px;padding-top:16px;border-top:2px solid #f3f4f6">
        <a href="{{ route('admin.assessment.questions.index') }}"
           style="background:#fff;border:2px solid #ff4d8f;color:#ff4d8f;border-radius:12px;padding:10px 22px;font-weight:700;cursor:pointer;font-size:.9rem;text-decoration:none;display:inline-flex;align-items:center">
          Cancel
        </a>
        <button type="submit"
                style="background:linear-gradient(135deg,#ff4d8f,#7c3aed);color:#fff;border:none;border-radius:12px;padding:10px 22px;font-weight:700;cursor:pointer;font-size:.9rem">
          Update Question
        </button>
      </div>
    </form>
  </div>
</div>

<script>
let editOptCount = {{ $question->options->count() }};

function addEditOption() {
  const container = document.getElementById('editOptionsContainer');
  const row = document.createElement('div');
  row.className = 'edit-option-row';
  row.style.cssText = 'display:flex;gap:8px;margin-bottom:8px;align-items:center';
  row.innerHTML = `
    <input type="hidden" name="options[${editOptCount}][_delete]" value="0" class="delete-flag">
    <input type="text" name="options[${editOptCount}][option_text]"
           style="flex:1;border:1.5px solid #e5e7eb;border-radius:10px;padding:9px 13px;font-size:.88rem;outline:none;box-sizing:border-box"
           placeholder="Option text" required>
    <input type="number" name="options[${editOptCount}][score]" min="0" max="99"
           style="width:90px;border:1.5px solid #e5e7eb;border-radius:10px;padding:9px 10px;font-size:.88rem;outline:none;box-sizing:border-box"
           placeholder="Score" required>
    <input type="hidden" name="options[${editOptCount}][sort_order]" value="${editOptCount}">
    <button type="button" onclick="markDelete(this)"
            style="background:none;border:1.5px solid #fca5a5;color:#dc2626;border-radius:8px;padding:6px 10px;cursor:pointer;font-weight:700">✕</button>
  `;
  container.appendChild(row);
  editOptCount++;
}

function markDelete(btn) {
  const rows = document.querySelectorAll('.edit-option-row:not([style*="display:none"])');
  if (rows.length <= 2) { alert('You must keep at least 2 options.'); return; }
  const row = btn.closest('.edit-option-row');
  const flag = row.querySelector('.delete-flag');
  if (flag) flag.value = '1';
  // If this is a new (no id) option, just remove it.
  const hasId = row.querySelector('input[name*="[id]"]');
  if (!hasId) { row.remove(); return; }
  row.style.opacity = '0.35';
  row.style.pointerEvents = 'none';
  btn.textContent = 'Marked for deletion';
}
</script>
@endsection
