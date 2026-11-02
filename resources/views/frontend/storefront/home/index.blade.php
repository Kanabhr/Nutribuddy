@extends('layouts.main')
@section('title', "NutriBuddy – India's #1 Kids Wellness Gummy")

@section('content')
    <!-- ══════════════════════════════════════════
                                                                                                                                                                       HERO SLIDER
                                                                                                                                                                  ══════════════════════════════════════════ -->
    <!-- ── HERO ── -->
    @include('frontend.storefront.home.sections.hero')


    <!-- ══════════════════════════════════════════
                                                                                                                                                                       TRUST BAR (Scrolling Ticker)
                                                                                                                                                                  ══════════════════════════════════════════ -->
    <!-- ── TRUST BAR ── -->
    <div class="tbar">
        <div class="tscroll">
            <div class="titem"><span class="tic"></span> 100% Natural Ingredients</div>
            <div class="titem"><span class="tic">🏆</span> FSSAI Certified</div>
            <div class="titem"><span class="tic">🚫</span> No Artificial Colors</div>
            <div class="titem"><span class="tic">🔬</span> Third-Party Lab Tested</div>
            <div class="titem"><span class="tic">🌱</span> Vegan Options Available</div>
            <div class="titem"><span class="tic">🇮🇳</span> Proudly Made in India</div>
            <div class="titem"><span class="tic">👨‍⚕️</span> Pediatrician Approved</div>
            <div class="titem"><span class="tic">💊</span> Zero Harmful Additives</div>
            <div class="titem"><span class="tic"></span> 100% Natural Ingredients</div>
            <div class="titem"><span class="tic">🏆</span> FSSAI Certified</div>
            <div class="titem"><span class="tic">🚫</span> No Artificial Colors</div>
            <div class="titem"><span class="tic">🔬</span> Third-Party Lab Tested</div>
            <div class="titem"><span class="tic">🌱</span> Vegan Options Available</div>
            <div class="titem"><span class="tic">🇮🇳</span> Proudly Made in India</div>
            <div class="titem"><span class="tic">👨‍⚕️</span> Pediatrician Approved</div>
            <div class="titem"><span class="tic">💊</span> Zero Harmful Additives</div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
                                                                                                                                                                       TRUST INDICATORS
                                                                                                                                                                  ══════════════════════════════════════════ -->
    <!-- ── TRUST INDICATORS ── -->
    @include('frontend.storefront.home.sections.trust')


    <!-- ── WHY CHOOSE US ── -->
    @include('frontend.storefront.home.sections.why')




    <!-- ══════════════════════════════════════════
                                                                                                                                                                       PRODUCTS
                                                                                                                                                                  ══════════════════════════════════════════ -->
    @include('frontend.storefront.home.sections.products')

    <!-- ══════════════════════════════════════════
                                                                                                                                                                   INGREDIENTS HIGHLIGHT
                                                                                                                                                              ══════════════════════════════════════════ -->
    @include('frontend.storefront.home.sections.ingredients')



    <!-- ══════════════════════════════════════════
                                                                                                                                                                       QUIZ CTA
                                                                                                                                                                  ══════════════════════════════════════════ -->
    <div class="quiz-cta reveal" id="quiz">
        <div>
            <span class="quiz-label"> Personalized Nutrition</span>
            <h2 class="quiz-h">Not Sure Which Gummy<br>Is Right for Your Child?</h2>
            <p class="quiz-p">Take our 2-minute wellness quiz and get a FREE personalized diet chart crafted by certified
                Ayurvedic nutritionists. No signup needed.</p>
        </div>
        <a href="{{ route('user.assessment.index') }}" class="quiz-btn" style="text-decoration: none; display: inline-block; text-align: center;">Start Free Quiz →</a>
    </div>






    <!-- ══════════════════════════════════════════
                                                                                                                                                                     DIET CHART SECTION
                                                                                                                                                                ══════════════════════════════════════════ -->
    @include('frontend.storefront.home.sections.diet-chart')
    <!-- ══════════════════════════════════════════
                                                                                                                                                                     SUBSCRIPTION MODAL
                                                                                                                                                                ══════════════════════════════════════════ -->
    <div class="modal-overlay" id="dcModalOverlay" onclick="dcCloseModalOutside(event)">
        <div class="modal-box">
            <button class="modal-close-btn" onclick="dcCloseModal()">✕</button>

            <h2 class="modal-h" id="dcModalTitle">Unlock Your 7-Day Plan </h2>
            <p class="modal-sub" id="dcModalSub">Enter your details to receive the complete personalized diet chart via
                email.
            </p>

            <div class="mfield">
                <label>Parent's name</label>
                <input type="text" id="dcName" placeholder="e.g. Priya Sharma">
            </div>
            <div class="mfield">
                <label>Email address</label>
                <input type="email" id="dcEmail" placeholder="hello@gmail.com">
            </div>
            <div class="mfield">
                <label>Phone number</label>
                <input type="tel" id="dcPhone" placeholder="+91 98765 43210">
            </div>

            <div class="order-summary-box">
                <div
                    style="font-family:'Nunito',sans-serif;font-weight:800;font-size:.75rem;color:#888;text-transform:uppercase;letter-spacing:1px;margin-bottom:10px">
                    Order Summary</div>
                <div class="os-row">
                    <span class="os-lbl" id="dcOrderItem">7-Day Diet Chart (Monthly)</span>
                    <span class="os-val" id="dcOrderPrice">₹199</span>
                </div>
            </div>

            <button class="btn-generate" style="width:100%;justify-content:center;font-size:.95rem"
                onclick="dcProcessPayment()">
                Pay & Download Chart →
            </button>

            <div class="secure-note">🔒 Secure payment &nbsp;·&nbsp; Instant PDF delivery &nbsp;·&nbsp; Cancel anytime
            </div>
        </div>
    </div>


    <!-- ══════════════════════════════════════════
                                                                                                                                                                       HOW IT WORKS
                                                                                                                                                                  ══════════════════════════════════════════ -->
    <!-- <section class="how-section reveal">
                                                                                                                                                        <div class="how-layout">
                                                                                                                                                        <div class="how-head">
                                                                                                                                                            <span class="sec-eye">Simple Process</span>
                                                                                                                                                            <h2 class="sec-title">How It <span class="acc">Works</span></h2>
                                                                                                                                                            <p>From a quick parent quiz to a plan you can actually follow, NutriBuddy keeps each step simple.</p>
                                                                                                                                                            <div class="how-stats">
                                                                                                                                                                <div><strong>2 min</strong><span>quick quiz</span></div>
                                                                                                                                                                <div><strong>Free</strong><span>diet plan</span></div>
                                                                                                                                                            </div>
                                                                                                                                                        </div>
                                                                                                                                                        <div class="steps">
                                                                                                                                                            <div class="step-new">
                                                                                                                                                                <div class="step-top">
                                                                                                                                                                    <div class="sball s1"><img src="{{ asset('img/quiz.png') }}" alt=""></div>
                                                                                                                                                                    <div class="snum">Step 01</div>
                                                                                                                                                                </div>
                                                                                                                                                                <div class="stitle">Take the Quiz</div>
                                                                                                                                                                <div class="sdesc">5 quick questions about your child's age, health goals, and diet preferences.</div>
                                                                                                                                                            </div>
                                                                                                                                                            <div class="step-new">
                                                                                                                                                                <div class="step-top">
                                                                                                                                                                    <div class="sball s2"><img src="{{ asset('img/plan.png') }}" alt=""></div>
                                                                                                                                                                    <div class="snum">Step 02</div>
                                                                                                                                                                </div>
                                                                                                                                                                <div class="stitle">Get Your Plan</div>
                                                                                                                                                                <div class="sdesc">Personalized supplement plan by Ayurvedic nutritionists — completely free!</div>
                                                                                                                                                            </div>
                                                                                                                                                            <div class="step-new">
                                                                                                                                                                <div class="step-top">
                                                                                                                                                                    <div class="sball s3"><img src="{{ asset('img/order.png') }}" alt=""></div>
                                                                                                                                                                    <div class="snum">Step 03</div>
                                                                                                                                                                </div>
                                                                                                                                                                <div class="stitle">Order & Save</div>
                                                                                                                                                                <div class="sdesc">Subscribe & Save for up to 20% off. Delivered fresh to your doorstep.</div>
                                                                                                                                                            </div>
                                                                                                                                                            <div class="step-new">
                                                                                                                                                                <div class="step-top">
                                                                                                                                                                    <div class="sball s4"><img src="{{ asset('img/rising.png') }}" alt=""></div>
                                                                                                                                                                    <div class="snum">Step 04</div>
                                                                                                                                                                </div>
                                                                                                                                                                <div class="stitle">Track Progress</div>
                                                                                                                                                                <div class="sdesc">Log milestones on your parent dashboard and chat directly with our team.</div>
                                                                                                                                                            </div>
                                                                                                                                                        </div>
                                                                                                                                                        <div class="how-cta-row">
                                                                                                                                                            <a href="#diet-chart" class="hbtn hbtn-main">Start Free Quiz</a>
                                                                                                                                                            <span>No sign-up required to begin</span>
                                                                                                                                                        </div>
                                                                                                                                                        </div>
                                                                                                                                                    </section> -->




    <!-- ══════════════════════════════════════════
                                                                                                                                                                       CERTIFICATIONS CAROUSEL
                                                                                                                                                                  ══════════════════════════════════════════ -->
    <!-- <section class="imgcar-section reveal" id="certificates">
                                                                                                                                                                    <div class="imgcar-header">
                                                                                                                                                                      <span class="sec-eye">Our Certifications</span>
                                                                                                                                                                      <h2 class="sec-title">Certified, Tested &amp; <span class="acc">Trusted</span> 🔬</h2>
                                                                                                                                                                      <p class="sec-sub" style="max-width:500px;margin:0 auto">Every product is backed by rigorous testing and globally
                                                                                                                                                                        recognised certifications.</p>
                                                                                                                                                                    </div>

                                                                                                                                                                    <div class="imgcar-wrapper">
                                                                                                                                                                      <button class="imgcar-arrow imgcar-arrow-prev" id="imgcarPrev" aria-label="Previous">‹</button>
                                                                                                                                                                      <div class="imgcar-viewport" id="imgcarViewport">
                                                                                                                                                                        <div class="imgcar-pbar" id="imgcarPbar"></div>
                                                                                                                                                                        <div class="imgcar-track" id="imgcarTrack">
                                                                                                                                                                          <div class="imgcar-item"><img src="{{ asset('img/cert-fssai.png') }}" alt="FSSAI Certified"
                                                                                                                                                                              onerror="this.src='https://placehold.co/300x180/FFD6E8/C0306F?text=FSSAI'"></div>
                                                                                                                                                                          <div class="imgcar-item"><img src="{{ asset('img/cert-nabl.png') }}" alt="NABL Lab Tested"
                                                                                                                                                                              onerror="this.src='https://placehold.co/300x180/EDE9FE/5B21B6?text=NABL+Lab'"></div>
                                                                                                                                                                          <div class="imgcar-item"><img src="{{ asset('img/cert-nongmo.png') }}" alt="Non-GMO Verified"
                                                                                                                                                                              onerror="this.src='https://placehold.co/300x180/D0FFF2/007755?text=Non-GMO'"></div>
                                                                                                                                                                          <div class="imgcar-item"><img src="{{ asset('img/cert-iap.png') }}" alt="Pediatrician Approved"
                                                                                                                                                                              onerror="this.src='https://placehold.co/300x180/DCFBFF/0077AA?text=Pediatrician'"></div>
                                                                                                                                                                          <div class="imgcar-item"><img src="{{ asset('img/cert-cruelty.png') }}" alt="Cruelty Free"
                                                                                                                                                                              onerror="this.src='https://placehold.co/300x180/FFFBE0/907000?text=Cruelty+Free'"></div>
                                                                                                                                                                          <div class="imgcar-item"><img src="{{ asset('img/cert-iso.png') }}" alt="ISO 22000 GMP"
                                                                                                                                                                              onerror="this.src='https://placehold.co/300x180/FFE8DF/A03010?text=ISO+22000'"></div>
                                                                                                                                                                        </div>
                                                                                                                                                                      </div>
                                                                                                                                                                      <button class="imgcar-arrow imgcar-arrow-next" id="imgcarNext" aria-label="Next">›</button>
                                                                                                                                                                    </div>

                                                                                                                                                                    <div class="imgcar-dots" id="imgcarDots"></div>
                                                                                                                                                                  </section> -->

    <!-- ══════════════════════════════════════════
                                                                                                                                                                       TESTIMONIALS
                                                                                                                                                                  ══════════════════════════════════════════ -->
    @include('partials.parent-reviews', ['product' => null])

    <!-- ══════════════════════════════════════════
                                                                                                                                                                       FAQ
                                                                                                                                                                  ══════════════════════════════════════════ -->
    @include('partials.faq-section')
    <!-- ══════════════════════════════════════════
                                                                                                                                                                       NEWSLETTER
                                                                                                                                                                  ══════════════════════════════════════════ -->
    <div class="newsletter reveal">
        <span class="sec-eye">Stay in the Loop</span>
        <h2 class="sec-title">Wellness Tips for Your Little Ones</h2>
        <p class="nl-sub">Join 25,000+ parents getting Ayurvedic parenting tips, exclusive discounts & early product
            access
            every week.</p>
        <form class="nl-form newsletterSubscribeForm" action="{{ route('newsletter.subscribe') }}" method="POST">
            @csrf
            <input type="hidden" name="source" value="newsletter_block">
            <input class="nl-input" type="email" name="email" maxlength="50" placeholder="Enter your email address"
                required>
            <button class="hbtn hbtn-main fornewres" type="submit"
                style="padding:13px 28px;font-size:.9rem">Subscribe</button>
            <div class="newsletterSubscribeMessage"
                style="display:none;width:100%;margin-top:8px;font-size:.82rem;font-weight:800;text-align:center;"></div>
        </form>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.pc-variant-panel').forEach(panel => {
                const card = panel.closest('.pc');
                const addButton = card?.querySelector('.btn-add');
                const variants = (() => {
                    try {
                        return JSON.parse(card?.dataset.variants || '[]');
                    } catch (_) {
                        return [];
                    }
                })();

                function findSelectedVariant() {
                    const selected = Object.fromEntries(
                        Array.from(panel.querySelectorAll('.pc-option-btn.active[data-attribute]'))
                            .map(item => [item.dataset.attribute, item.dataset.value])
                    );

                    return variants.find(variant => {
                        return Object.entries(selected).every(([name, value]) => {
                            return String(variant.attributes?.[name] ?? '') === String(value ?? '');
                        });
                    }) || null;
                }

                function applySelectedVariant() {
                    const selected = Array.from(panel.querySelectorAll('.pc-option-btn.active[data-attribute]'))
                        .map(item => `${item.dataset.attribute}: ${item.dataset.value}`)
                        .join(' / ');
                    const selectedPill = panel.querySelector('.pc-selected-pill');
                    const stockPill = panel.querySelector('.pc-stock-pill');
                    const variant = findSelectedVariant();

                    if (selectedPill && selected) {
                        selectedPill.textContent = selected;
                        selectedPill.title = selected;
                    }

                    if (card) card.dataset.selectedVariantId = variant?.id || '';
                    if (card) card.dataset.selectedVariantLabel = selected || variant?.name || '';
                    if (addButton) addButton.dataset.variantId = variant?.id || '';

                    if (card && variant) {
                        const priceLabel = card.querySelector('[data-price-label]');
                        const price = Number(variant.price || 0);
                        const comparePrice = Number(variant.compare_price || 0);

                        if (priceLabel) {
                            priceLabel.innerHTML = `₹${price.toLocaleString('en-IN', { maximumFractionDigits: 0 })}`;
                            if (comparePrice > price) {
                                priceLabel.insertAdjacentHTML('beforeend', ` <s>₹${comparePrice.toLocaleString('en-IN', { maximumFractionDigits: 0 })}</s>`);
                            }
                        }
                    }

                    if (stockPill && variant) {
                        stockPill.classList.toggle('out', !variant.available);
                        stockPill.textContent = variant.available
                            ? 'Available'
                            : 'Out of stock';
                    }
                }

                applySelectedVariant();

                panel.querySelectorAll('.pc-option-btn[data-attribute]').forEach(button => {
                    button.addEventListener('click', event => {
                        event.preventDefault();
                        event.stopPropagation();

                        const attribute = button.dataset.attribute;
                        panel.querySelectorAll(`.pc-option-btn[data-attribute="${CSS.escape(attribute)}"]`).forEach(item => {
                            item.classList.toggle('active', item === button);
                        });

                        applySelectedVariant();
                    });
                });
            });
        });
    </script>
@endpush