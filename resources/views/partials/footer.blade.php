@php
    $sideSectionLogo = \App\Models\Setting::get('side_section_logo');
    $sideSectionLogoExists =
        $sideSectionLogo && \Illuminate\Support\Facades\Storage::disk('public')->exists($sideSectionLogo);
    $sideSectionLogoUrl = $sideSectionLogoExists
        ? asset('storage/' . $sideSectionLogo) .
        '?v=' .
        \Illuminate\Support\Facades\Storage::disk('public')->lastModified($sideSectionLogo)
        : asset('img/logo.png');
    $footerProducts = collect();
    if (\Illuminate\Support\Facades\Schema::hasTable('products')) {
        $footerProducts = \App\Models\Product::where('is_active', true)
            ->latest()
            ->limit(3)
            ->get(['name', 'slug']);
    }
@endphp

<footer class="kiddex-footer">
    <div class="footer-anim">
        <div class="fa-dot"
            style="width:300px;height:300px;background:var(--pk);top:-80px;left:-80px;--dur:8s;--del:0s">
        </div>
        <div class="fa-dot"
            style="width:200px;height:200px;background:var(--pu);bottom:-50px;right:10%;--dur:6s;--del:2s"></div>
        <div class="fa-dot" style="width:150px;height:150px;background:var(--ye);top:40%;left:40%;--dur:10s;--del:1s">
        </div>
    </div>

    <div class="footer-widget-area">
        <div class="fw-brand">
            <a href="{{ route('home') }}" class="footer-logo-text">
                <img src="{{ $sideSectionLogoUrl }}" alt="NutriBuddy"
                    onerror="this.style.display='none';this.nextElementSibling.style.display='inline'">
                <span
                    style="display:none;font-family:'Fredoka One',cursive;font-size:1.6rem;color:var(--pk)">NutriBuddy</span>
            </a>
            <ul class="footer-contact-list">
                <li>
                    <span class="fci"><img src="{{ asset('img/location.png') }}" alt=""
                            onerror="this.outerHTML='📍'"></span>
                    Office No. - 313, Pehel Lakeview, Beside Tragad Underpass, Near Vaishno Devi Circle, Ahmedabad,
                    Gujarat - 382501
                </li>
                <li>
                    <span class="fci"><img src="{{ asset('img/phone.png') }}" alt=""
                            onerror="this.outerHTML='📞'"></span>
                    <a
                        href="tel:{{ preg_replace('/[^0-9+]/', '', \App\Models\Setting::get('side_section_contact_number') ?: '18001234567') }}">{{ \App\Models\Setting::get('side_section_contact_number') ?: '1800-123-4567' }}</a>
                </li>
                <li>
                    <span class="fci"><img src="{{ asset('img/email.png') }}" alt=""
                            onerror="this.outerHTML='✉️'"></span>
                    <a href="mailto:{{ \App\Models\Setting::get('side_section_email') ?: 'hello@nutribuddy.in' }}">{{
                        \App\Models\Setting::get('side_section_email') ?: 'hello@nutribuddy.in' }}</a>
                </li>
            </ul>
            <div class="footer-socials">
                @php
                    $socialLinks = json_decode(\App\Models\Setting::get('side_section_social_links', '[]'), true);
                @endphp
                @if (!empty($socialLinks))
                    @foreach ($socialLinks as $link)
                        @php
                            $platform = strtolower($link['platform'] ?? '');
                            $socialIcon = $platform . '.png';
                        @endphp
                        <a href="{{ $link['url'] }}" title="{{ ucfirst($link['platform']) }}" target="_blank">
                            <img src="{{ asset('img/' . $socialIcon) }}" alt="{{ ucfirst($link['platform']) }}"
                                onerror="this.outerHTML='<span style=\'font-size:1.5rem; color: #fff;\'>🔗</span>'">
                        </a>
                    @endforeach
                @else
                    <a href="#" title="Instagram"><img src="{{ asset('img/instagram.png') }}" alt="Instagram"
                            onerror="this.outerHTML='<span style=\'font-size:1.5rem\'>📷</span>'"></a>
                    <a href="#" title="Facebook"><img src="{{ asset('img/facebook.png') }}" alt="Facebook"
                            onerror="this.outerHTML='<span style=\'font-size:1.5rem\'>📘</span>'"></a>
                    <a href="#" title="WhatsApp"><img src="{{ asset('img/whatsapp.png') }}" alt="WhatsApp"
                            onerror="this.outerHTML='<span style=\'font-size:1.5rem\'>💬</span>'"></a>
                    <a href="#" title="Twitter"><img src="{{ asset('img/twitter.png') }}" alt="Twitter"
                            onerror="this.outerHTML='<span style=\'font-size:1.5rem\'>🐦</span>'"></a>
                    <a href="#" title="LinkedIn"><img src="{{ asset('img/linkedin.png') }}" alt="LinkedIn"
                            onerror="this.outerHTML='<span style=\'font-size:1.5rem\'>in</span>'"></a>
                @endif
            </div>
        </div>

        <div class="fw-links">
            <h4>Products</h4>
            <ul>
                @forelse($footerProducts as $footerProduct)
                    <li>
                        <a href="{{ route('product.show', $footerProduct->slug) }}">
                            {{ $footerProduct->name }}
                        </a>
                    </li>
                @empty
                    <li><a href="{{ route('product') }}">Our Products</a></li>
                @endforelse
                <li><a href="{{ route('product') }}">Shop All Products</a></li>
            </ul>
        </div>

        <div class="fw-links">
            <h4>Company</h4>
            <ul>
                <li><a href="{{ route('about') }}">About Us</a></li>
                <li><a href="{{ route('ingredients') }}">Our Ingredients</a></li>
                <li><a href="{{ route('blog') }}">Blog & Tips</a></li>
                <li><a href="{{ route('contact') }}">Contact Us</a></li>
            </ul>
        </div>

        <div class="fw-links">
            <h4>Support</h4>
            <ul>
                <li><a href="https://nutribuddy.shiprocket.co/tracking">Track My Order</a></li>
                <li><a href="{{ route('return-policy') }}">Returns & Refunds</a></li>
                <li><a href="{{ route('privacy') }}">Privacy Policy</a></li>
                <li><a href="{{ route('cookies') }}">Cookie Policy</a></li>
                <li><a href="{{ route('terms') }}">Terms of Service</a></li>
            </ul>
        </div>

        <div class="fw-subscribe">
            <h4>Stay Updated</h4>
            <p>Join 25,000+ parents getting Ayurvedic parenting tips, exclusive offers &amp; early access every week.
            </p>
            <form class="subscribe-wrap newsletterSubscribeForm" id="footerNewsletterForm"
                action="{{ route('newsletter.subscribe') }}" method="POST">
                @csrf
                <input type="hidden" name="source" value="footer">
                <input type="email" name="email" maxlength="50" placeholder="Enter your email" class="subs-input"
                    required>
                <button class="subs-btn" type="submit">Subscribe</button>
            </form>
            <div class="newsletterSubscribeMessage" id="footerNewsletterMessage"
                style="display:none;margin-top:8px;font-size:.8rem;font-weight:800;"></div>
        </div>
    </div>

    <div class="footer-payment-row">
        <p style="color:#888;font-size:.78rem;font-family:'Nunito',sans-serif;font-weight:700">Secure Payments</p>
        <div class="footer-payment-cards">
            <div class="payment-card"><img src="{{ asset('img/visa.webp') }}" alt="Visa"
                    onerror="this.outerHTML='<span>VISA</span>'"></div>
            <div class="payment-card"><img src="{{ asset('img/upi.png') }}" alt="UPI"
                    onerror="this.outerHTML='<span>UPI</span>'"></div>
            <div class="payment-card"><img src="{{ asset('img/phonepe.webp') }}" alt="PhonePe"
                    onerror="this.outerHTML='<span>PhonePe</span>'"></div>
            <div class="payment-card"><img src="{{ asset('img/Paytm-logo.webp') }}" alt="Paytm"
                    onerror="this.outerHTML='<span>Paytm</span>'"></div>
        </div>
    </div>

    <div class="footer-bottom-bar">
        <div class="copyright">
            © 2025 <a href="{{ route('home') }}">NutriBuddy Kids</a>. All rights reserved.
            Created by <a href="https://crescitasoftware.com/" target="_blank" rel="noopener noreferrer">Crescita
                Software</a>.
        </div>
        <ul class="foot-links">
            <li><a href="{{ route('privacy') }}">Privacy Policy</a></li>
            <li><a href="{{ route('terms') }}">Terms of Service</a></li>
            <li><a href="{{ route('cookies') }}">Cookie Policy</a></li>
        </ul>
    </div>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.newsletterSubscribeForm').forEach(form => {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const message = form.parentElement?.querySelector('.newsletterSubscribeMessage') || form.querySelector('.newsletterSubscribeMessage');
                const button = form.querySelector('button[type="submit"]');
                const originalText = button ? button.textContent : '';
                const formData = new FormData(form);

                if (!message) return;

                if (button) {
                    button.disabled = true;
                    button.textContent = 'Saving...';
                }

                message.style.display = 'block';
                message.style.color = '#64748b';
                message.textContent = 'Subscribing...';

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': form.querySelector('input[name="_token"]')?.value || ''
                        },
                        body: formData
                    });

                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        const errors = payload.errors || {};
                        const firstError = Object.values(errors).flat()[0];
                        throw new Error(firstError || payload.message || 'Please enter a valid email address.');
                    }

                    form.reset();
                    message.style.color = '#059669';
                    message.textContent = payload.message || 'Thanks for subscribing.';
                } catch (error) {
                    message.style.color = '#dc2626';
                    message.textContent = error.message || 'Unable to subscribe right now.';
                } finally {
                    if (button) {
                        button.disabled = false;
                        button.textContent = originalText;
                    }
                }
            });
        });
    });
</script>