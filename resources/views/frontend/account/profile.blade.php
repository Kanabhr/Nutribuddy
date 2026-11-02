@extends('layouts.user-panel')
@section('title', 'Personal Info — NutriBuddy Kids')
@section('panel-page-class', 'panel-personal-info')

@section('panel-content')

@php
    $user = auth()->user();
    // Priority: uploaded avatar → google avatar → initials
    $avatar = $user->avatar
        ? asset('storage/'.$user->avatar)
        : ($user->google_avatar ?: null);
    $initial = strtoupper(substr($user->name, 0, 1));
    $isGoogleUser = (bool) $user->google_id;
@endphp

    <div class="inner-topbar">
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <line x1="3" y1="6" x2="21" y2="6" />
                <line x1="3" y1="12" x2="21" y2="12" />
                <line x1="3" y1="18" x2="21" y2="18" />
            </svg>
        </button>
        <span class="it-title">Personal Info 👤</span>
        <div style="width:36px"></div>
    </div>

<!-- EDIT MODAL moved to bottom for z-index fix -->

<!-- ════════ MAIN ════════ -->
<div class="main">
 

  <div class="page">

    <!-- PAGE HEADER -->
    <div class="page-header fade-in d1">
      <div class="page-header-left">
        <h1>Personal Info 👤</h1>
        <p>Manage your profile details and account settings</p>
      </div>
    </div>

    <!-- WELCOME BANNER -->
    <!-- <div class="welcome-banner d1">
      <div class="welcome-text" style="position:relative;z-index:1">
        <h2>Welcome back, <span>{{ explode(' ', $user->name)[0] ?? '' }}!</span> </h2>
        <p>Here's a quick overview of your account and profile status.</p>
      </div>
      <div class="welcome-right">
        <div class="banner-stat">
          <div class="bs-num">{{ $user->orders()->count() }}</div>
          <div class="bs-lbl">Orders</div>
        </div>
        <div class="banner-stat">
          <div class="bs-num">{{ $user->created_at->format('Y') }}</div>
          <div class="bs-lbl">Member since</div>
        </div>
        <div class="banner-emoji"></div>
      </div>
    </div> -->

    <!-- PROFILE HERO -->
    <div class="profile-hero fade-in d2">
      <div class="hero-avatar-wrap">
        <input type="file" id="heroAvatarInput" accept="image/*" style="display: none;">
        <div class="hero-avatar" id="heroAvatarPreview">
            @if($avatar)
                <img src="{{ $avatar }}" alt="Avatar" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
            @else
                {{ $initial }}
            @endif
        </div>
        <div class="hero-avatar-ring"></div>
        <div class="avatar-upload" title="Change photo" onclick="document.getElementById('heroAvatarInput').click()">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
        </div>
        @if($isGoogleUser && !$user->avatar)
        <div title="Profile photo from Google" style="position:absolute;bottom:2px;right:2px;width:22px;height:22px;border-radius:50%;background:#fff;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.15);display:flex;align-items:center;justify-content:center;z-index:2;">
            <svg width="13" height="13" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
        </div>
        @endif
      </div>
      <div class="hero-info">
        <div class="hero-name">
          {{ $user->name }}
          <div class="verified">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
        </div>
        <div class="hero-email">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          {{ $user->email }}
        </div>
        <div class="hero-badges">
          <span class="hero-badge badge-member">⭐ Member since {{ $user->created_at->format('Y') }}</span>
          <span class="hero-badge badge-orders">📦 {{ $user->orders()->count() }} Orders placed</span>
          @if($isGoogleUser)
          <span class="hero-badge" style="background:rgba(66,133,244,.1);color:#4285F4;">
            <svg width="11" height="11" viewBox="0 0 24 24" style="vertical-align:middle;"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
            Signed in with Google
          </span>
          @endif
        </div>
      </div>
      <div class="hero-actions">
        <button class="edit-btn" onclick="openModal()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          Edit Profile
        </button>
      </div>
    </div>

    <!-- INFO GRID -->
    <div class="info-grid">

      <!-- Basic Details -->
      <div class="info-section fade-in d3">
        <div class="section-head">
          <div class="section-title">
            <div class="section-icon" style="background:var(--pkl)">👤</div>
            Basic Details
          </div>
          <button class="s-edit" onclick="openModal()">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit
          </button>
        </div>
        <div class="info-list">
          <div class="info-row">
            <div class="row-icon" style="background:var(--pkl)">🪪</div>
            <div class="row-content">
              <div class="row-label">Full Name</div>
              <div class="row-value">{{ $user->name }}</div>
            </div>
            <span class="row-chip chip-done">✓ Set</span>
          </div>
          <div class="info-row">
            <div class="row-icon" style="background:var(--skl)">📧</div>
            <div class="row-content">
              <div class="row-label">Email Address</div>
              <div class="row-value">{{ $user->email }}</div>
            </div>
            <span class="row-chip chip-done">✓ Verified</span>
          </div>
          <div class="info-row">
            <div class="row-icon" style="background:var(--yel)">📱</div>
            <div class="row-content">
              <div class="row-label">Phone Number</div>
              @if($user->phone)
                <div class="row-value">{{ $user->phone }}</div>
              @else
                <div class="row-value empty">Not provided</div>
              @endif
            </div>
            @if($user->phone)
                <span class="row-chip chip-done">✓ Set</span>
            @else
                <span class="row-chip chip-add" onclick="openModal()">+ Add</span>
            @endif
          </div>
        </div>
      </div>

      <!-- Personal Details -->
      <div class="info-section fade-in d3">
        <div class="section-head">
          <div class="section-title">
            <div class="section-icon" style="background:var(--pul)">🎂</div>
            Personal Details
          </div>
          <button class="s-edit" onclick="openModal()">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit
          </button>
        </div>
        <div class="info-list">
          <div class="info-row">
            <div class="row-icon" style="background:var(--pul)">🎂</div>
            <div class="row-content">
              <div class="row-label">Date of Birth</div>
              @if($user->dob)
                <div class="row-value">{{ \Carbon\Carbon::parse($user->dob)->format('d M, Y') }}</div>
              @else
                <div class="row-value empty">Not provided</div>
              @endif
            </div>
            @if(!$user->dob) <span class="row-chip chip-add" onclick="openModal()">+ Add</span> @else <span class="row-chip chip-done">✓ Set</span> @endif
          </div>
          <div class="info-row">
            <div class="row-icon" style="background:var(--orl)">🧑</div>
            <div class="row-content">
              <div class="row-label">Gender</div>
              @if($user->gender)
                <div class="row-value">{{ $user->gender }}</div>
              @else
                <div class="row-value empty">Not provided</div>
              @endif
            </div>
            @if(!$user->gender) <span class="row-chip chip-add" onclick="openModal()">+ Add</span> @else <span class="row-chip chip-done">✓ Set</span> @endif
          </div>
          <div class="info-row">
            <div class="row-icon" style="background:var(--mnl)">📝</div>
            <div class="row-content">
              <div class="row-label">Bio</div>
              @if($user->bio)
                <div class="row-value">{{ $user->bio }}</div>
              @else
                <div class="row-value empty">Not provided</div>
              @endif
            </div>
            @if(!$user->bio) <span class="row-chip chip-add" onclick="openModal()">+ Add</span> @else <span class="row-chip chip-done">✓ Set</span> @endif
          </div>
        </div>
      </div>

      <!-- Saved Addresses -->
      <div class="info-section full fade-in d4" id="addressSection">
        <div class="section-head">
          <div class="section-title">
            <div class="section-icon" style="background:var(--skl)">📍</div>
            Saved Addresses
          </div>
          <a href="{{ route('user.addresses.create') }}" class="s-edit">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Address
          </a>
        </div>
        <div class="nb-address-grid" id="addressGrid">
          @forelse($savedAddresses as $addr)
          <div class="nb-address-card {{ $addr->is_default_shipping ? 'is-default' : '' }}" id="addr-card-{{ $addr->id }}">
            <div class="nb-addr-header">
              <span class="nb-addr-badge {{ strtolower($addr->label ?? 'home') }}">
                {{ $addr->label === 'Work' ? '🏢' : ($addr->label === 'Other' ? '📍' : '🏠') }} {{ $addr->label ?? 'Home' }}
              </span>
              @if($addr->is_default_shipping)
                <span class="nb-addr-default-tag">DEFAULT</span>
              @endif
            </div>
            <div class="nb-addr-body">
              <h5 class="nb-addr-name">{{ $addr->full_name }}</h5>
              <p class="nb-addr-text">
                {{ $addr->address_line_1 }}{{ $addr->address_line_2 ? ', '.$addr->address_line_2 : '' }}
                @if($addr->landmark)<br><span class="nb-addr-landmark">📍 Near {{ $addr->landmark }}</span>@endif
                <br>{{ $addr->city }}, {{ $addr->state }} - {{ $addr->postal_code }}
              </p>
              <div class="nb-addr-contact">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                <span>{{ $addr->phone }}</span>
              </div>
            </div>
            <div class="nb-addr-footer">
              @if(!$addr->is_default_shipping)
                <button class="nb-addr-btn btn-def" onclick="setDefaultAddress({{ $addr->id }}, this)">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                  Set Default
                </button>
              @endif
              <a href="{{ route('user.addresses.edit', $addr->id) }}" class="nb-addr-btn btn-edit">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit
              </a>
              <button class="nb-addr-btn btn-del" onclick="deleteAddress({{ $addr->id }}, this)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                Delete
              </button>
            </div>
          </div>
          @empty
          <div class="nb-no-addr" id="noAddrMsg">
            <div style="font-size:2.5rem;margin-bottom:8px">📭</div>
            <div style="font-weight:700;color:var(--dk);margin-bottom:4px">No saved addresses yet</div>
            <div style="font-size:.82rem;color:var(--text-light)">You don't have any saved addresses.</div>
          </div>
          @endforelse
          
          <div class="nb-add-card" onclick="window.location.href='{{ route('user.addresses.create') }}'">
            <div class="nb-add-card-inner">
              <div class="nb-add-circle">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              </div>
              <span>Add New Address</span>
              <p>Ship to a different location</p>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /info-grid -->

    <!-- DANGER ZONE -->
    <div class="danger-zone fade-in d5" style="display:none;">
      <div class="danger-head">
        <div class="d-icon">⚠️</div>
        <h3>Danger Zone</h3>
      </div>
      <div class="danger-items">
        <div class="danger-row">
          <div class="danger-row-info">
            <h4>Deactivate Account</h4>
            <p>Temporarily disable your account. You can reactivate anytime.</p>
          </div>
          <button class="danger-btn d-btn-soft">Deactivate</button>
        </div>
        <div class="danger-row">
          <div class="danger-row-info">
            <h4>Delete Account</h4>
            <p>Permanently delete your account and all associated data. This cannot be undone.</p>
          </div>
          <button class="danger-btn d-btn-hard">Delete Account</button>
        </div>
      </div>
    </div>

  </div><!-- /page -->
</div><!-- /main -->

<!-- ── EDIT PROFILE MODAL ── -->
<div class="modal-backdrop" id="editModal" style="display:none;align-items:center;justify-content:center;">
  <div class="modal">
    <div class="modal-header">
      <h3>Edit Profile ✏️</h3>
      <div class="modal-close" onclick="closeModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </div>
    </div>
    <div class="modal-avatar-section">
      <input type="file" id="modalAvatarInput" accept="image/*" style="display: none;">
      <div class="modal-avatar" id="modalAvatarPreview">
        @if($avatar)
            <img src="{{ $avatar }}" alt="Avatar" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
        @else
            {{ $initial }}
        @endif
      </div>
      <button class="modal-avatar-change" onclick="document.getElementById('modalAvatarInput').click()">📷 Change Photo</button>
      @if($isGoogleUser && !$user->avatar)
      <p style="font-size:0.75rem;color:#94a3b8;margin:6px 0 0;text-align:center;">
        <svg width="12" height="12" viewBox="0 0 24 24" style="vertical-align:middle;margin-right:3px;"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
        Using Google profile photo. Upload one to override.
      </p>
      @endif
    </div>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label">First Name</label>
        <input type="text" class="form-input" id="editFirstName" placeholder="First name" value="{{ explode(' ', $user->name)[0] ?? '' }}">
      </div>
      <div class="form-group">
        <label class="form-label">Last Name</label>
        <input type="text" class="form-input" id="editLastName" placeholder="Last name" value="{{ implode(' ', array_slice(explode(' ', $user->name), 1)) }}">
      </div>
      <div class="form-group full">
        <label class="form-label">Email Address</label>
        <input type="email" class="form-input" placeholder="Email" value="{{ $user->email }}" readonly style="opacity:0.7;cursor:not-allowed">
      </div>
      <div class="form-group">
        <label class="form-label">Phone Number</label>
        <input type="tel" class="form-input" id="editPhone" placeholder="+91 00000 00000" value="{{ $user->phone }}">
      </div>
      <div class="form-group">
        <label class="form-label">Date of Birth</label>
        <input type="date" class="form-input" id="editDob" value="{{ $user->dob ? \Carbon\Carbon::parse($user->dob)->format('Y-m-d') : '' }}">
      </div>
      <div class="form-group">
        <label class="form-label">Gender</label>
        <select class="form-input" id="editGender">
          <option value="">Select gender</option>
          <option {{ $user->gender == 'Male' ? 'selected' : '' }}>Male</option>
          <option {{ $user->gender == 'Female' ? 'selected' : '' }}>Female</option>
          <option {{ $user->gender == 'Other' ? 'selected' : '' }}>Other</option>
          <option {{ $user->gender == 'Prefer not to say' ? 'selected' : '' }}>Prefer not to say</option>
        </select>
      </div>
      <div class="form-group full">
        <label class="form-label">Bio / About</label>
        <input type="text" class="form-input" id="editBio" placeholder="Tell us a little about yourself..." value="{{ $user->bio }}">
      </div>
    </div>
    <div class="modal-btns">
      <button class="m-btn-cancel" onclick="closeModal()">Cancel</button>
      <button class="m-btn-save" id="btnSaveProfile" onclick="saveProfile()">💾 Save Changes</button>
    </div>
  </div>
</div>

</div>

@push('styles')
<style>
/* Modern Address UI */
.address-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.address-card {
    background: #fff;
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 20px;
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    transition: all 0.3s ease;
    position: relative;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}

.address-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.08);
    border-color: var(--pk);
}

.address-card.default {
    border: 2px solid var(--pk);
    background: linear-gradient(to bottom right, #fff, var(--pkl));
}

.nb-address-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 32px;
    margin-top: 32px;
}

.nb-address-card {
    background: #fff !important;
    border: 1px solid rgba(0,0,0,0.06) !important;
    border-radius: 28px !important;
    padding: 30px !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 20px !important;
    transition: all 0.3s ease !important;
    position: relative !important;
    box-shadow: 0 4px 20px rgba(0,0,0,0.04) !important;
    overflow: hidden;
}

.nb-address-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.08) !important;
    border-color: var(--pk) !important;
}

.nb-address-card.is-default {
    border: 2px solid var(--pk) !important;
    background: linear-gradient(135deg, #fff, #fff9fb) !important;
}

.nb-addr-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.nb-addr-badge {
    padding: 6px 14px;
    border-radius: 99px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: #f8f9fa;
    color: #666;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.nb-addr-badge.home { background: #eef2ff !important; color: #4338ca !important; }
.nb-addr-badge.work { background: #fdf2f8 !important; color: #be185d !important; }
.nb-addr-badge.other { background: #fffbeb !important; color: #b45309 !important; }

.nb-addr-default-tag {
    background: var(--pk);
    color: #fff;
    font-size: 10px;
    font-weight: 900;
    padding: 4px 10px;
    border-radius: 8px;
}

.nb-addr-name {
    font-size: 19px;
    font-weight: 900;
    margin: 0;
    color: #1a202c;
}

.nb-addr-text {
    font-size: 14px;
    color: #4a5568;
    line-height: 1.7;
    margin: 0;
}

.nb-addr-landmark {
    color: #718096;
    font-size: 13px;
    font-weight: 600;
}

.nb-addr-contact {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 800;
    color: #2d3748;
}

.nb-addr-footer {
    display: flex !important;
    gap: 12px !important;
    margin-top: auto !important;
    padding-top: 22px !important;
    border-top: 1px solid #f1f5f9 !important;
}

.nb-addr-btn {
    flex: 1 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 6px !important;
    padding: 10px 8px !important;
    border-radius: 12px !important;
    font-size: 12px !important;
    font-weight: 800 !important;
    border: none !important;
    cursor: pointer !important;
    transition: all 0.25s !important;
    text-decoration: none !important;
    font-family: 'Nunito', sans-serif !important;
    line-height: 1 !important;
}

.nb-addr-btn.btn-def { background: #f0f9ff !important; color: #0369a1 !important; }
.nb-addr-btn.btn-def:hover { background: #0369a1 !important; color: #fff !important; transform: translateY(-2px); }

.nb-addr-btn.btn-edit { background: #f0fdf4 !important; color: #15803d !important; }
.nb-addr-btn.btn-edit:hover { background: #15803d !important; color: #fff !important; transform: translateY(-2px); }

.nb-addr-btn.btn-del { background: #fff1f2 !important; color: #be123c !important; }
.nb-addr-btn.btn-del:hover { background: #be123c !important; color: #fff !important; transform: translateY(-2px); }

/* Add Address Card */
.nb-add-card {
    border: 2px dashed #cbd5e0 !important;
    border-radius: 24px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer !important;
    min-height: 220px !important;
    transition: all 0.3s !important;
    background: #fafafa !important;
}

.nb-add-card:hover {
    border-color: var(--pk) !important;
    background: #fff9fb !important;
}

.nb-add-card-inner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    text-align: center;
}

.nb-add-circle {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    transition: all 0.3s;
}

.nb-add-card:hover .nb-add-circle {
    background: var(--pk);
    color: #fff;
    transform: rotate(90deg);
}

.nb-add-card-inner span { font-weight: 900; color: #334155; font-size: 16px; }
.nb-add-card-inner p { font-size: 13px; color: #94a3b8; margin: 0; }

.nb-no-addr {
    text-align: center;
    padding: 40px 24px;
    border: 2px dashed #e2e8f0;
    border-radius: 24px;
    color: #64748b;
    grid-column: 1 / -1;
}

#addressModal.show,
#editModal.show {
  display: flex !important;
}
</style>
@endpush

@push('scripts')
<script>
const _addrStoreUrl  = '{{ route("user.addresses.store") }}';
const _addrDeleteBase = '{{ url("/user/addresses") }}';
const _csrf = '{{ csrf_token() }}';

// ── Sidebar helpers ──
function toggleSidebar(){
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('overlay').classList.toggle('show');
}
function closeSidebar(){
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('overlay').classList.remove('show');
}
function setActive(el){
  document.querySelectorAll('.nav-item').forEach(i=>i.classList.remove('active'));
  el.classList.add('active');
}

// ── Profile Edit Modal ──
function openModal(){ 
    try {
        const m = document.getElementById('editModal');
        if (m) {
            m.style.setProperty('display', 'flex', 'important');
            m.classList.add('show');
        } else {
            console.error('editModal element not found in DOM!');
        }
    } catch(e) { console.error('Error opening modal:', e); }
}
function closeModal(){ 
    const m = document.getElementById('editModal');
    if (m) {
        m.style.setProperty('display', 'none', 'important');
        m.classList.remove('show');
    }
}
function saveProfile(){
    const btn = document.getElementById('btnSaveProfile');
    btn.disabled = true;
    btn.textContent = 'Saving...';

    const formData = new FormData();
    formData.append('first_name', document.getElementById('editFirstName').value.trim());
    formData.append('last_name', document.getElementById('editLastName').value.trim());
    formData.append('phone', document.getElementById('editPhone').value.trim());
    formData.append('dob', document.getElementById('editDob').value);
    formData.append('gender', document.getElementById('editGender').value);
    formData.append('bio', document.getElementById('editBio').value.trim());

    const avatarInput = document.getElementById('modalAvatarInput');
    if (avatarInput && avatarInput.files[0]) {
        formData.append('avatar', avatarInput.files[0]);
    } else {
        const heroInput = document.getElementById('heroAvatarInput');
        if (heroInput && heroInput.files[0]) {
            formData.append('avatar', heroInput.files[0]);
        }
    }

    fetch('{{ route("personal-info.update") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': _csrf,
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal();
            nbToast('Profile updated successfully!', 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            nbToast(data.message || 'Error saving profile', 'error');
            btn.disabled = false;
            btn.textContent = '💾 Save Changes';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        nbToast('An unexpected error occurred.', 'error');
        btn.disabled = false;
        btn.textContent = '💾 Save Changes';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editModal');
    if (editModal) {
        editModal.addEventListener('click', function(e){ 
            if(e.target === this) closeModal(); 
        });
    }

    // Avatar preview logic
    function handleAvatarSelect(e, previewId) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(evt) {
                const previewEl = document.getElementById(previewId);
                if (previewEl) {
                    previewEl.innerHTML = `<img src="${evt.target.result}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">`;
                }
            };
            reader.readAsDataURL(file);
        }
    }

    const heroInput = document.getElementById('heroAvatarInput');
    if (heroInput) heroInput.addEventListener('change', (e) => handleAvatarSelect(e, 'heroAvatarPreview'));

    const modalInput = document.getElementById('modalAvatarInput');
    if (modalInput) modalInput.addEventListener('change', (e) => handleAvatarSelect(e, 'modalAvatarPreview'));
});

// Address management is now on dedicated pages.
// Only setDefaultAddress and deleteAddress remain here.

// ── Delete Address ──
function deleteAddress(id, btn){
  nbConfirm('This address will be permanently removed.', async () => {
    btn.disabled = true;
    try {
      const res = await fetch(_addrDeleteBase+'/'+id, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': _csrf, 'Accept': 'application/json' }
      });
      if(!res.ok) throw new Error('Delete failed');
      const card = document.getElementById('addr-card-'+id);
      if(card){
        card.style.transition = 'opacity .3s, transform .3s';
        card.style.opacity = '0'; card.style.transform = 'scale(.95)';
        setTimeout(()=>{ card.remove(); checkEmpty(); }, 300);
      }
      nbToast('Address deleted successfully.', 'success');
    } catch(e){
      nbToast('Could not delete address. Please try again.', 'error');
      btn.disabled = false;
    }
  }, { title: 'Delete Address?', okText: 'Yes, Delete' });
}

// ── Set Default Address ──
async function setDefaultAddress(id, btn){
  btn.disabled = true;
  btn.textContent = 'Updating...';
  try {
    const res = await fetch(_addrDeleteBase+'/'+id+'/default', {
      method: 'PATCH',
      headers: { 'X-CSRF-TOKEN': _csrf, 'Accept': 'application/json' }
    });
    if(!res.ok) throw new Error('Update failed');
    nbToast('Default address updated successfully.', 'success');
    setTimeout(() => window.location.reload(), 800);
  } catch(e){
    nbToast('Could not update default address.', 'error');
    btn.disabled = false;
    btn.textContent = '⭐ Set as Default';
  }
}

function checkEmpty(){
  const grid = document.getElementById('addressGrid');
  if(!grid) return;
  const cards = grid.querySelectorAll('.address-card');
  if(cards.length === 0 && !grid.querySelector('.no-addr-msg')){
    const msg = document.createElement('div');
    msg.id = 'noAddrMsg';
    msg.className = 'no-addr-msg';
    msg.innerHTML = '<div style="font-size:2.5rem;margin-bottom:8px">📭</div><div style="font-weight:700;color:var(--dk);margin-bottom:4px">No saved addresses yet</div><div style="font-size:.82rem;color:var(--text-light)">You don\'t have any saved addresses.</div>';
    grid.insertBefore(msg, grid.querySelector('.nb-add-card'));
  }
}
</script>
@endpush
@endsection