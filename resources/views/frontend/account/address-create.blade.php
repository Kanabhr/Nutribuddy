@extends('layouts.user-panel')
@section('title', 'Add New Address — NutriBuddy Kids')
@section('panel-page-class', 'panel-personal-info')

@section('panel-content')
    <div class="inner-topbar">
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <line x1="3" y1="6" x2="21" y2="6" />
                <line x1="3" y1="12" x2="21" y2="12" />
                <line x1="3" y1="18" x2="21" y2="18" />
            </svg>
        </button>
        <span class="it-title">Add New Address 📍</span>
        <div style="width:36px"></div>
    </div>

    <div class="main">
        <div class="page">
            <!-- PAGE HEADER -->
            <div class="page-header fade-in d1">
                <div class="page-header-left">
                    <h1>Add New Address 📍</h1>
                    <p>Where should we deliver your wellness?</p>
                </div>
                <a href="{{ route('personal-info') }}" class="nb-back-link">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="19" y1="12" x2="5" y2="12" />
                        <polyline points="12 19 5 12 12 5" />
                    </svg>
                    Back to Profile
                </a>
            </div>

            <div class="nb-form-container fade-in d2">
                <div style="max-width: 700px;">
                    <div style="margin-bottom:32px;">
                        <label class="nb-form-label">Select Address Type</label>
                        <div style="display:flex;gap:12px;">
                            <button class="nb-type-pill active" data-type="Home" onclick="selectType(this)">🏠 Home</button>
                            <button class="nb-type-pill" data-type="Work" onclick="selectType(this)">💼 Work</button>
                            <button class="nb-type-pill" data-type="Other" onclick="selectType(this)">📍 Other</button>
                        </div>
                    </div>

                    <div class="nb-form-grid" style="gap:32px;">
                        <div class="nb-form-group">
                            <label class="nb-form-label">Full Name *</label>
                            <input type="text" class="nb-form-input" id="addrFullName" placeholder="e.g. Priya Sharma">
                        </div>
                        <div class="nb-form-group">
                            <label class="nb-form-label">Mobile Number *</label>
                            <input type="tel" class="nb-form-input" id="addrPhone" placeholder="+91 XXXXX XXXXX">
                        </div>
                        <div class="nb-form-group nb-full">
                            <label class="nb-form-label">Flat / House / Apartment / Area *</label>
                            <input type="text" class="nb-form-input" id="addrLine1"
                                placeholder="e.g. 42, Sunshine Residency, HSR Layout">
                        </div>
                        <div class="nb-form-group nb-full">
                            <label class="nb-form-label">Landmark (Optional)</label>
                            <input type="text" class="nb-form-input" id="addrLandmark"
                                placeholder="Near park, opposite school…">
                        </div>
                        <div class="nb-form-group">
                            <label class="nb-form-label">Pincode *</label>
                            <input type="text" class="nb-form-input" id="addrPincode" maxlength="6"
                                placeholder="6-digit pincode">
                        </div>
                        <div class="nb-form-group">
                            <label class="nb-form-label">City *</label>
                            <input type="text" class="nb-form-input" id="addrCity" placeholder="City">
                        </div>
                        <div class="nb-form-group nb-full">
                            <label class="nb-form-label">State *</label>
                            <select class="nb-form-input" id="addrState">
                                <option value="">Select State</option>
                                <option>Andhra Pradesh</option>
                                <option>Assam</option>
                                <option>Bihar</option>
                                <option>Delhi</option>
                                <option>Goa</option>
                                <option>Gujarat</option>
                                <option>Haryana</option>
                                <option>Himachal Pradesh</option>
                                <option>Jharkhand</option>
                                <option>Karnataka</option>
                                <option>Kerala</option>
                                <option>Madhya Pradesh</option>
                                <option>Maharashtra</option>
                                <option>Odisha</option>
                                <option>Punjab</option>
                                <option>Rajasthan</option>
                                <option>Tamil Nadu</option>
                                <option>Telangana</option>
                                <option>Uttar Pradesh</option>
                                <option>Uttarakhand</option>
                                <option>West Bengal</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top:40px;display:flex;gap:15px;max-width: 450px;">
                        <a href="{{ route('personal-info') }}" class="nb-btn-cancel">Cancel</a>
                        <button class="nb-btn-save" id="addrSaveBtn" onclick="saveNewAddress()">💾 Save Address</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const _addrStoreUrl = '{{ route("user.addresses.store") }}';
            const _csrf = '{{ csrf_token() }}';
            let _selectedType = 'Home';

            function selectType(btn) {
                document.querySelectorAll('.nb-type-pill').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                _selectedType = btn.dataset.type;
            }

            async function saveNewAddress() {
                const btn = document.getElementById('addrSaveBtn');
                const fields = {
                    full_name: document.getElementById('addrFullName').value.trim(),
                    phone: document.getElementById('addrPhone').value.trim(),
                    address_line_1: document.getElementById('addrLine1').value.trim(),
                    landmark: document.getElementById('addrLandmark').value.trim(),
                    postal_code: document.getElementById('addrPincode').value.trim(),
                    city: document.getElementById('addrCity').value.trim(),
                    state: document.getElementById('addrState').value
                };

                if (!fields.full_name || !fields.phone || !fields.address_line_1 || !fields.postal_code || !fields.city || !fields.state) {
                    nbToast('Please fill all required fields.', 'warning');
                    return;
                }

                btn.disabled = true;
                btn.textContent = 'Saving…';

                try {
                    const res = await fetch(_addrStoreUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _csrf, 'Accept': 'application/json' },
                        body: JSON.stringify({ label: _selectedType, ...fields })
                    });
                    const json = await res.json();
                    if (!res.ok) { throw new Error(Object.values(json.errors || {}).flat().join(' ') || json.message || 'Error saving.'); }

                    nbToast('Address saved successfully!', 'success');
                    setTimeout(() => window.location.href = '{{ route("personal-info") }}', 800);
                } catch (err) {
                    nbToast(err.message || 'Could not save address.', 'error');
                    btn.disabled = false;
                    btn.textContent = '💾 Save Address';
                }
            }
        </script>
    @endpush
@endsection
