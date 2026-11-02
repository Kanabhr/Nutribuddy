@extends('layouts.main')
@section('title', 'Privacy Policy — NutriBuddy Kids')

@section('content')
    <section class="product-listing-hero">
        <div class="product-listing-hero-inner">
            <div class="product-listing-breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span>/</span>
                <span>Privacy Policy</span>
            </div>
            <span class="product-listing-hero-badge">Legal · NutriBuddy Kids</span>
            <h1 class="product-listing-hero-title">Privacy Policy</h1>
            <p class="product-listing-hero-sub">Please read this policy carefully to understand how we collect, use, and protect your
                personal information.</p>
        </div>
    </section>
    <section class="privacy-text-wrap">
        <div class="privacy-block">
            <h2>Privacy Policy</h2>
            <p><strong>Last Updated: August 7, 2026</strong></p>
            <p>Welcome to NutriBuddy (&ldquo;NutriBuddy,&rdquo; &ldquo;we,&rdquo; &ldquo;us,&rdquo; or &ldquo;our&rdquo;). This Privacy Policy explains how we collect, use, share, and protect your personal information when you visit or make a purchase from <a href="https://www.nutribuddy.in">www.nutribuddy.in</a> (the &ldquo;Website&rdquo;), including through our mobile-friendly platform.</p>
            <p>By using our Website, creating an account, or placing an order, you agree to the collection and use of your information as described in this Privacy Policy. If you do not agree with our practices, please do not use our Website.</p>
            <p>NutriBuddy products are formulated for children, but our Website itself is intended to be used by parents, guardians, and adult customers on behalf of their children. We do not knowingly collect personal information directly from children through the Website.</p>

            <hr>

            <h3>1. Information We Collect</h3>
            <p>We collect information in the following ways:</p>
            
            <h4>a) Information You Provide to Us</h4>
            <ul>
                <li>Name, email address, phone number, and delivery address</li>
                <li>Billing information (processed securely through our third-party payment gateway; we do not store your full card/UPI details)</li>
                <li>Account login credentials, if you create an account</li>
                <li>Information about your child (such as age group) that you voluntarily provide to help us recommend the right product or generate a personalized diet/nutrition suggestion</li>
                <li>Order history, wishlist items, and product reviews or feedback you submit</li>
                <li>Any information you share with us via email, WhatsApp, phone calls, or customer support chats</li>
            </ul>

            <h4>b) Information Collected Automatically</h4>
            <ul>
                <li>IP address, browser type, device type, and operating system</li>
                <li>Pages visited, time spent on the Website, and referring website</li>
                <li>Location data (approximate, based on IP address) for shipping and currency purposes</li>
                <li>Cookies and similar tracking technologies (see our separate <a href="{{ route('cookies') }}">Cookie Policy</a> for details)</li>
            </ul>

            <h4>c) Information from Third Parties</h4>
            <ul>
                <li>Payment confirmation details from payment gateways (e.g., Razorpay, PayU, or similar)</li>
                <li>Delivery status updates from our logistics/courier partners</li>
                <li>Information from social media platforms if you interact with us through Instagram, Facebook, or other channels</li>
            </ul>

            <hr>

            <h3>2. How We Use Your Information</h3>
            <p>We use the information we collect to:</p>
            <ul>
                <li>Process, confirm, and deliver your orders</li>
                <li>Communicate with you about your orders, account, or customer support queries</li>
                <li>Send order updates, shipping notifications, and transactional messages via SMS, WhatsApp, or email</li>
                <li>Send promotional offers, product updates, and newsletters (only where you have opted in, and you can unsubscribe anytime)</li>
                <li>Improve our Website, products, and customer experience</li>
                <li>Personalize product recommendations, including diet charts or nutrition suggestions based on details you provide</li>
                <li>Detect and prevent fraud, unauthorized transactions, and misuse of the Website</li>
                <li>Comply with applicable legal and regulatory requirements (including FSSAI, tax, and consumer protection laws)</li>
            </ul>

            <hr>

            <h3>3. How We Share Your Information</h3>
            <p>We do not sell your personal information. We may share your information with:</p>
            <ul>
                <li>Payment Gateways &ndash; to securely process your payments</li>
                <li>Logistics &amp; Courier Partners &ndash; to deliver your orders to the correct address</li>
                <li>Manufacturing &amp; Fulfilment Partners (e.g., Calibro Nutrasciences) &ndash; solely for order fulfilment purposes</li>
                <li>IT &amp; Technology Service Providers &ndash; who help us host, maintain, and secure the Website</li>
                <li>Marketing &amp; Analytics Tools (e.g., Google Analytics, Meta/Facebook Pixel) &ndash; to understand Website usage and improve our marketing</li>
                <li>Legal &amp; Regulatory Authorities &ndash; where required by law, court order, or to protect our legal rights</li>
            </ul>
            <p>All third parties we work with are required to handle your information responsibly and only for the purposes we specify.</p>

            <hr>

            <h3>4. Cookies and Tracking Technologies</h3>
            <p>Our Website uses cookies and similar technologies to enhance your browsing experience, remember your preferences, and analyze Website traffic. For full details on the types of cookies we use and how to manage them, please refer to our <a href="{{ route('cookies') }}">Cookie Policy</a>.</p>

            <hr>

            <h3>5. Data Security</h3>
            <p>We implement reasonable technical and organizational security measures &mdash; including encrypted payment processing and restricted access to personal data &mdash; to protect your information from unauthorized access, alteration, disclosure, or destruction. However, no method of transmission over the internet is 100% secure, and we cannot guarantee absolute security.</p>

            <hr>

            <h3>6. Data Retention</h3>
            <p>We retain your personal information for as long as necessary to fulfil the purposes outlined in this Privacy Policy, including to comply with legal, accounting, or reporting obligations (such as tax and FSSAI record-keeping requirements). Once no longer required, your information is securely deleted or anonymized.</p>

            <hr>

            <h3>7. Your Rights</h3>
            <p>Depending on applicable law, you have the right to:</p>
            <ul>
                <li>Access the personal information we hold about you</li>
                <li>Request correction of inaccurate or incomplete information</li>
                <li>Request deletion of your personal information, subject to legal retention requirements</li>
                <li>Withdraw consent for marketing communications at any time</li>
                <li>Raise a grievance regarding how your data is handled</li>
            </ul>
            <p>To exercise any of these rights, please contact us at <a href="mailto:hello@nutribuddy.in">hello@nutribuddy.in</a>.</p>

            <hr>

            <h3>8. Children&rsquo;s Privacy</h3>
            <p>NutriBuddy products are designed for children, but our Website and online transactions are meant to be used by adults (parents/guardians) only. We do not knowingly collect personal information directly from children under 18 through the Website. If we become aware that a child has provided us with personal information without parental consent, we will take steps to delete such information.</p>

            <hr>

            <h3>9. Third-Party Links</h3>
            <p>Our Website may contain links to third-party websites (such as social media platforms). We are not responsible for the privacy practices or content of these external sites. We encourage you to review their privacy policies separately.</p>

            <hr>

            <h3>10. Changes to This Privacy Policy</h3>
            <p>We may update this Privacy Policy from time to time to reflect changes in our practices or legal requirements. The updated version will be posted on this page with a revised &ldquo;Last Updated&rdquo; date. We encourage you to review this page periodically.</p>

            <hr>

            <h3>11. Grievance Officer</h3>
            <p>In accordance with the Information Technology Act, 2000, and rules made thereunder, and the Digital Personal Data Protection Act, 2023, the details of the Grievance Officer are provided below:</p>
            <ul>
                <li><strong>Name:</strong> [Insert Grievance Officer Name]</li>
                <li><strong>Designation:</strong> [Insert Designation]</li>
                <li><strong>Email:</strong> <a href="mailto:hello@nutribuddy.in">hello@nutribuddy.in</a></li>
                <li><strong>Phone:</strong> <a href="tel:+919213507981">+91 9213507981</a></li>
                <li><strong>Address:</strong> Trivana Lifescience LLP, D/503, Orchid Blue, Shantipura, Ahmedabad, Gujarat &ndash; 380058</li>
            </ul>

            <hr>

            <h3>12. Contact Us</h3>
            <p>If you have any questions or concerns about this Privacy Policy, please reach out to us:</p>
            <ul>
                <li><strong>Email:</strong> <a href="mailto:hello@nutribuddy.in">hello@nutribuddy.in</a></li>
                <li><strong>Phone:</strong> <a href="tel:+919213507981">+91 9213507981</a></li>
                <li><strong>Website:</strong> <a href="https://www.nutribuddy.in" target="_blank">www.nutribuddy.in</a></li>
            </ul>
        </div>
    </section>
@endsection
