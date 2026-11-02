@extends('layouts.main')
@section('title', 'Contact Us – NutriBuddy')



@section('content')
    <!-- CONTACT HERO -->
    <section class="contact-hero">
        <div class="contact-blob contact-blob-1"></div>
        <div class="contact-blob contact-blob-2"></div>
        <div class="contact-content-wrapper">
            <div class="page-breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span>/</span>
                <span>Contact Us</span>
            </div>
            <div class="slide-badge badge-pk" style="margin: 0 auto 20px; display: inline-block;">Hello There</div>
            <h1 class="contact-title">Let's Get In <span class="acc" style="color:var(--pk);">Touch</span></h1>
            <p class="contact-subtitle">Have a question about our gummies, need help with your child's personalized plan, or
                just want to say hi? We'd love to hear from you!</p>
        </div>
    </section>
    <!-- CONTACT GRID -->
    <div class="contact-grid">

        <!-- Info Cards -->
        <div class="contact-info-col">
            <div class="contact-info-card contact-card-email">
                <div class="contact-info-icon">💌</div>
                <div class="contact-info-details">
                    <h3>Email Us</h3>
                    <p>We're here to help.</p>
                    <a href="mailto:hello@nutribuddy.in">hello@nutribuddy.in</a>
                </div>
            </div>

            <div class="contact-info-card contact-card-phone">
                <div class="contact-info-icon">📞</div>
                <div class="contact-info-details">
                    <h3>Call Us</h3>
                    <p>Mon - Fri, 9am - 6pm (IST)</p>
                    <a href="tel:18001234567">1800-123-4567</a>
                </div>
            </div>

            <div class="contact-info-card contact-card-location">
                <div class="contact-info-icon">🗺️</div>
                <div class="contact-info-details">
                    <h3>Visit Us</h3>
                    <p>Office No. - 313, Pehel Lakeview, Beside Tragad Underpass, Near Vaishno Devi Circle, Ahmedabad, Gujarat - 382501</p>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="contact-form-col">
            <h3>Send us a Message </h3>
            <p>Fill out the form below and our wellness team will get back to you within 24 hours.</p>

            @if(session('contact_success'))
                <div style="background: #e6f9f0; border: 2px solid #2ecc71; border-radius: 16px; padding: 16px 20px; margin-bottom: 24px; color: #1a7a4a; font-weight: 600;">
                    ✅ {{ session('contact_success') }}
                </div>
            @endif

            @if($errors->any())
                <div style="background: #fff0f3; border: 2px solid var(--pk); border-radius: 16px; padding: 16px 20px; margin-bottom: 24px; color: #c0392b;">
                    <ul style="margin:0; padding-left: 18px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('contact.store') }}" method="POST">
                @csrf

                <div class="contact-form-row">
                    <div class="contact-form-group">
                        <label class="contact-form-label" for="firstName">First Name *</label>
                        <input type="text" id="firstName" name="first_name" class="contact-form-control @error('first_name') is-invalid @enderror" placeholder="e.g. Priya" value="{{ old('first_name') }}" autocomplete="given-name" required aria-invalid="@error('first_name') true @else false @enderror">
                        @error('first_name')<span class="contact-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="contact-form-group">
                        <label class="contact-form-label" for="lastName">Last Name</label>
                        <input type="text" id="lastName" name="last_name" class="contact-form-control @error('last_name') is-invalid @enderror" placeholder="e.g. Sharma" value="{{ old('last_name') }}" autocomplete="family-name" aria-invalid="@error('last_name') true @else false @enderror">
                        @error('last_name')<span class="contact-field-error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="contact-form-row">
                    <div class="contact-form-group">
                        <label class="contact-form-label" for="email">Email Address *</label>
                        <input type="email" id="email" name="email" class="contact-form-control @error('email') is-invalid @enderror" placeholder="your@email.com" value="{{ old('email') }}" autocomplete="email" inputmode="email" required aria-invalid="@error('email') true @else false @enderror">
                        @error('email')<span class="contact-field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="contact-form-group">
                        <label class="contact-form-label" for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="contact-form-control @error('phone') is-invalid @enderror" placeholder="+91 98765 43210" value="{{ old('phone') }}" autocomplete="tel" inputmode="tel" pattern="[0-9+\-\s()]{7,20}" aria-invalid="@error('phone') true @else false @enderror">
                        @error('phone')<span class="contact-field-error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="contact-form-group contact-select-group">
                    <label class="contact-form-label" for="subject">Subject *</label>
                    <select id="subject" name="subject" class="contact-form-control @error('subject') is-invalid @enderror" required aria-invalid="@error('subject') true @else false @enderror">
                        <option value="" disabled {{ old('subject') ? '' : 'selected' }}>Select a topic...</option>
                        <option value="Where is my order?" {{ old('subject') == 'Where is my order?' ? 'selected' : '' }}>Where is my order?</option>
                        <option value="Question about a product" {{ old('subject') == 'Question about a product' ? 'selected' : '' }}>Question about a product</option>
                        <option value="Help with Diet Chart" {{ old('subject') == 'Help with Diet Chart' ? 'selected' : '' }}>Help with Diet Chart</option>
                        <option value="Wholesale / Partnership" {{ old('subject') == 'Wholesale / Partnership' ? 'selected' : '' }}>Wholesale / Partnership</option>
                        <option value="Other" {{ old('subject') == 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('subject')<span class="contact-field-error">{{ $message }}</span>@enderror
                </div>

                <div class="contact-form-group">
                    <label class="contact-form-label" for="message">Your Message *</label>
                    <textarea id="message" name="message" class="contact-form-control @error('message') is-invalid @enderror" placeholder="How can we help you and your little one today?" required aria-invalid="@error('message') true @else false @enderror">{{ old('message') }}</textarea>
                    @error('message')<span class="contact-field-error">{{ $message }}</span>@enderror
                </div>

                <button type="submit" class="contact-btn-submit">Send Message </button>
            </form>
        </div>

    </div> <!-- /contact-grid -->

    <!-- ══════════════════════════════════════════
                 PARENT REVIEWS
            ══════════════════════════════════════════ -->
    @include('partials.parent-reviews')

    <!-- ══════════════════════════════════════════
                 FAQ
            ══════════════════════════════════════════ -->
    @include('partials.faq-section')

@endsection
