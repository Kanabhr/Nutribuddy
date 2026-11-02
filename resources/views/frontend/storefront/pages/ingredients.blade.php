@extends('layouts.main')
@section('title', 'Ingredients | NutriBuddy')

@push('styles')
<style>
    /* ponytail: critical inline — prevents FOUC & tab wrapping when frontendstyle.css is stale on live */
    .ing-tabs { display:flex !important; flex-wrap:nowrap !important; overflow-x:auto !important; overflow-y:hidden !important; }
    .ing-tabs::-webkit-scrollbar { display:none; }
    .itab { flex-shrink:0 !important; }
    .ing-panel { display:none !important; }
    .ing-panel.active { display:grid !important; }
</style>
@endpush

@section('content')
<section class="ing-section" id="ingredients" style="padding-top: 70px; padding-bottom: 10px;">
    <div class="stars-bg" id="starsBg"></div>

    <div class="ing-header reveal">
        <span class="sec-eye">Ingredient Transparency</span>
        <h2 class="sec-title">Journey of Every <span class="acc">Ingredient</span></h2>
        <p class="sec-sub" style="color:rgba(255,255,255,.5);margin:0 auto"> From ancient forests to your child's gummy,
            a completely honest story of every ingredient we carefully choose and why.</p>
    </div>


    <div class="ing-tabs-wrapper reveal"
        style="display: flex; align-items: center; justify-content: center; margin: 0 5% 50px; gap: 12px; position: relative; overflow: hidden;">
        <button class="ing-slider-btn"
            onclick="document.querySelector('.ing-tabs').scrollBy({left: -200, behavior: 'smooth'})"
            style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #fff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; z-index: 10; transition: 0.2s;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 18l-6-6 6-6" />
            </svg>
        </button>
        <div class="ing-tabs" style="margin: 0; flex: 1; width: calc(100% - 104px); display: flex; flex-wrap: nowrap; overflow-x: auto; overflow-y: hidden; gap: 12px; scrollbar-width: none; -webkit-overflow-scrolling: touch;">
            <button class="itab active" data-ing="0"> <img src="img/gradient1.webp" alt="" width="16" height="16">
                Ashwagandha</button>
            <button class="itab" data-ing="1"> <img src="img/bb.png" alt="" width="16" height="16">Brahmi</button>
            <button class="itab" data-ing="2"> <img src="img/haldi.webp" alt="" width="16" height="16">
                Turmeric</button>
            <button class="itab" data-ing="3"> <img src="img/Amla.webp" alt="" width="16" height="16"> Amla</button>
            <button class="itab" data-ing="4"> <img src="img/flex.png" alt="" width="16" height="16"> Flaxseed
                Oil</button>
            <button class="itab" data-ing="5"> <img src="img/vitamins.jpg" alt="" width="16" height="16">
                Vitamins</button>
            <button class="itab" data-ing="6"> <img src="img/min.png" alt="" width="16" height="16"> Minerals</button>
            <button class="itab" data-ing="7"> <img src="img/gradient1.webp" alt="" width="16" height="16"> Tulsi
                Extract</button>
            <button class="itab" data-ing="8"> <img src="img/haldi.webp" alt="" width="16" height="16"> Mulethi
                Extract</button>
            <button class="itab" data-ing="9"> <img src="img/sabji.png" alt="" width="16" height="16"> Intakari
                Extract</button>
            <button class="itab" data-ing="10"> <img src="img/bana.webp" alt="" width="16" height="16"> Banaphasa
                Extract</button>
            <button class="itab" data-ing="11"> <img src="img/talis.png" alt="" width="16" height="16">
                Talispatra</button>
            <button class="itab" data-ing="12"> <img src="img/adrak.png" alt="" width="16" height="16"> Ginger
                Extract</button>
            <button class="itab" data-ing="13"> <img src="img/piper.png" alt="" width="16" height="16"> Piper Longum
                Extract</button>
            <button class="itab" data-ing="14"> <img src="img/Selenium.png" alt="" width="16" height="16">
                Selenium</button>
            <button class="itab" data-ing="17"> <img src="img/gajar.png" alt="" width="16" height="16"> Carrot
                Extract</button>
            <button class="itab" data-ing="18"> <img src="img/pome.png" alt="" width="16" height="16"> Pomegranate
                Extract</button>
            <button class="itab" data-ing="19"> <img src="img/beetroot.png" alt="" width="16" height="16"> Beetroot
                Extract</button>
            <button class="itab" data-ing="20"> <img src="img/beer.png" alt="" width="16" height="16"> Raspberry
                Extract</button>
        </div>
        <button class="ing-slider-btn"
            onclick="document.querySelector('.ing-tabs').scrollBy({left: 200, behavior: 'smooth'})"
            style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #fff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; z-index: 10; transition: 0.2s;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 18l6-6-6-6" />
            </svg>
        </button>
    </div>

    <div class="ing-panels" style="position:relative;z-index:5;margin:0 auto;padding:0 5% 60px;">
        <!-- Ashwagandha -->
        <div class="ing-panel active" id="ing-panel-0" style="display:grid;grid-template-columns:1fr 1fr;gap:28px;align-items:center;">
            <div class="for-large-img" style="display:flex;justify-content:center ">
                <div class="ing-planet"
                    style="background:radial-gradient(circle at 35% 35%,#2A4A2A,#0D2A0D);--pglow:rgba(0,214,143,.35)">
                    <img class="image-big" src="img/gradient1.webp" alt="Ashwagandha" width="300" height="300"
                        loading="lazy">
                    <div class="orbit-i" style="--orr:8s">⭐</div>
                    <div class="orbit-i" style="--orr:8s">⭐</div>
                    <div class="orbit-i" style="--orr:13s;font-size:1.1rem">⭐</div>
                    <div class="orbit-i" style="--orr:18s;font-size:.9rem">⭐</div>
                </div>
            </div>
            <div class="ing-text">
                <div class="ing-num">01</div>
                <div class="ing-pill"
                    style="background:rgba(0,214,143,.12);color:var(--mn);border:1px solid rgba(0,214,143,.2)">
                    Ayurvedic
                    Powerhouse</div>
                <h3 class="ing-name">Ashwagandha</h3>
                <p class="ing-sci">Withania somnifera · KSM-66® Premium Grade</p>
                <p class="ing-story">Deep in the Rajasthan desert, the "strength of a horse" has been growing for
                    3,000+
                    years. Ancient Ayurvedic healers called it <em>Balya</em> — giver of strength. Today, it's your
                    child's
                    secret superpower for resilience, calm, and growth.</p>
                <div class="ing-powers">
                    <div class="ptag">Builds Immunity</div>
                    <div class="ptag">Reduces Stress</div>
                    <div class="ptag">Muscle Growth</div>
                    <div class="ptag">Better Sleep</div>
                    <div class="ptag">More Energy</div>
                </div>
            </div>
        </div>

        <!-- Brahmi -->
        <div class="ing-panel" id="ing-panel-1">
            <div class="for-large-img" style="display:flex;justify-content:center">
                <div class="ing-planet"
                    style="background:radial-gradient(circle at 35% 35%,#0A1A3A,#0A0A2A);--pglow:rgba(0,191,255,.35)">
                    <img class="image-big" src="img/bb.png" alt="Brahmi" width="300" height="300" loading="lazy">
                    <div class="orbit-i" style="--orr:6s">⭐</div>
                    <div class="orbit-i" style="--orr:7s">⭐</div>
                    <div class="orbit-i" style="--orr:11s;font-size:1rem">⭐</div>
                </div>
            </div>
            <div class="ing-text">
                <div class="ing-num">02</div>
                <div class="ing-pill"
                    style="background:rgba(0,191,255,.12);color:var(--sk);border:1px solid rgba(0,191,255,.2)">Brain
                    Tonic</div>
                <h3 class="ing-name">Brahmi</h3>
                <p class="ing-sci">Bacopa monnieri, Standardised Bacosides </p>
                <p class="ing-story">Growing along riverbanks across India, Brahmi was the herb ancient scholars used
                    before studying sacred texts. Its active Bacosides literally rebuild neural pathways — making your
                    child's brain sharper, one gummy at a time.
                </p>
                <div class="ing-powers">
                    <div class="ptag"> Laser Focus</div>
                    <div class="ptag">Memory Boost</div>
                    <div class="ptag">Problem Solving</div>
                    <div class="ptag">Calm Alertness</div>
                    <div class="ptag">Better Grades</div>
                </div>
            </div>
        </div>

        <!-- Turmeric -->
        <div class="ing-panel" id="ing-panel-2">
            <div class="for-large-img" style="display:flex;justify-content:center">
                <div class="ing-planet"
                    style="background:radial-gradient(circle at 35% 35%,#3A2A00,#2A1800);--pglow:rgba(255,214,0,.4)">
                    <img class="image-big" src="img/haldi.webp" alt="Turmeric" width="300" height="300" loading="lazy">
                    <div class="orbit-i" style="--orr:7s">⭐</div>
                    <div class="orbit-i" style="--orr:9s">⭐</div>
                    <div class="orbit-i" style="--orr:14s;font-size:.9rem">⭐</div>
                </div>
            </div>
            <div class="ing-text">
                <div class="ing-num">03</div>
                <div class="ing-pill"
                    style="background:rgba(255,214,0,.1);color:var(--ye);border:1px solid rgba(255,214,0,.2)">Golden
                    Healer
                </div>
                <h3 class="ing-name">Turmeric Curcumin</h3>
                <p class="ing-sci">Curcuma longa, 95% Curcuminoids </p>
                <p class="ing-story">India's golden spice - used in every kitchen and every healing ritual for 5,000
                    years. Curcumin's anti-inflammatory magic protects your child's developing cells, soothes tummies,
                    and builds a fortress of immunity around them.We use the most potent 95% Curcuminoid form so your
                    child gets the full benefit of this golden gift from nature. </p>
                <div class="ing-powers">
                    <div class="ptag">Natural Anti-Inflammatory for Kids</div>
                    <div class="ptag">Antioxidant Shield, Gut Health Support</div>
                    <div class="ptag">Joint and Bone development</div>
                    <div class="ptag">Cell Protection for Growing Kids</div>

                </div>
            </div>
        </div>

        <!-- Amla -->
        <div class="ing-panel" id="ing-panel-3">
            <div class="for-large-img" style="display:flex;justify-content:center">
                <div class="ing-planet"
                    style="background:radial-gradient(circle at 35% 35%,#1A3A1A,#0A2A0A);--pglow:rgba(0,214,143,.3)">
                    <img class="image-big" src="img/Amla.webp" alt="Amla" width="300" height="300" loading="lazy">
                    <div class="orbit-i" style="--orr:8.5s">⭐</div>
                    <div class="orbit-i" style="--orr:15s;font-size:.9rem">⭐</div>
                    <div class="orbit-i" style="--orr:18s">⭐</div>
                </div>
            </div>
            <div class="ing-text">
                <div class="ing-num">04</div>
                <div class="ing-pill"
                    style="background:rgba(0,214,143,.12);color:var(--mn);border:1px solid rgba(0,214,143,.2)">
                    Superfruit</div>
                <h3 class="ing-name">Amla</h3>
                <p class="ing-sci">Phyllanthus emblica · Indian Gooseberry</p>
                <p class="ing-story">The holy fruit of Ayurveda — revered as the "mother" of all medicines. One tiny
                    Amla
                    holds 20× the Vitamin C of an orange. Our grandmothers were right all along, and now science has
                    proven it
                    beyond any doubt.</p>
                <div class="ing-powers">
                    <div class="ptag">20× Vitamin C</div>
                    <div class="ptag">Iron Absorption</div>
                    <div class="ptag">Gut Healing</div>
                    <div class="ptag">Skin Health</div>
                    <div class="ptag">Super Immunity</div>
                </div>
            </div>
        </div>

        <!-- algal dha -->
        <div class="ing-panel" id="ing-panel-4">
            <div class="for-large-img" style="display:flex;justify-content:center">
                <div class="ing-planet"
                    style="background:radial-gradient(circle at 35% 35%,#0A1A2A,#051020);--pglow:rgba(0,191,255,.25)">
                    <img class="image-big" src="img/flex.png" alt="Omega-3 DHA" width="300" height="300" loading="lazy">
                    <div class="orbit-i" style="--orr:7.5s">⭐</div>
                    <div class="orbit-i" style="--orr:10s;font-size:.9rem">⭐</div>
                    <div class="orbit-i" style="--orr:18s">⭐</div>
                </div>
            </div>
            <div class="ing-text">
                <div class="ing-num">05</div>
                <div class="ing-pill"
                    style="background:rgba(0,191,255,.1);color:var(--sk);border:1px solid rgba(0,191,255,.2)">
                    PLANT-BASED POWER
                </div>
                <h3 class="ing-name">Flaxseed Oil</h3>
                <p class="ing-sci">Alpha-Linolenic Acid (ALA) · Cold-Pressed, 100% Plant-Sourced</p>
                <p class="ing-story">A natural plant-based source of Omega-3 (ALA) that provides essential nutritional
                    support for healthy brain development, cognitive function, and growing minds.
                </p>
                <div class="ing-powers">
                    <div class="ptag">BENEFIT TAGS- Brain Development</div>
                    <div class="ptag"> Heart Health</div>
                    <div class="ptag">Immunity Boost</div>
                    <div class="ptag">100% Vegetarian</div>

                </div>
            </div>
        </div>

        <!-- vitamins -->
        <div class="ing-panel " id="ing-panel-5">
            <div class="for-large-img" style="display:flex;justify-content:center">
                <div class="ing-planet"
                    style="background:radial-gradient(circle at 35% 35%,#2A4A2A,#0D2A0D);--pglow:rgba(0,214,143,.35)">
                    <img class="image-big" src="img/vitamins.webp" alt="Vitamins" width="300" height="300"
                        loading="lazy">
                    <div class="orbit-i" style="--orr:8s">⭐</div>
                    <div class="orbit-i" style="--orr:8s">⭐</div>
                    <div class="orbit-i" style="--orr:13s;font-size:1.1rem">⭐</div>
                    <div class="orbit-i" style="--orr:18s;font-size:.9rem">⭐</div>
                </div>
            </div>
            <div class="ing-text">
                <div class="ing-num">06</div>
                <div class="ing-pill"
                    style="background:rgba(0,214,143,.12);color:var(--mn);border:1px solid rgba(0,214,143,.2)">
                    COMPLETE NUTRITION</div>
                <h3 class="ing-name">Vitamins</h3>
                <p class="ing-sci">Vitamin A, B-Complex, C, D3 & E — Complete Daily Nutrition</p>
                <p class="ing-story"> Your child's body is growing every single day — and it needs the right vitamins to
                    keep up. No single vitamin does it all — that's why children need a complete, balanced mix. Vitamin
                    D3 builds strong bones, Vitamin C fights off seasonal illness, the B-Complex vitamins power focus
                    and energy, and Vitamin A keeps eyesight sharp — all working together behind the scenes, so your
                    child can run, learn, and play without missing a beat.</p>
                <div class="ing-powers">
                    <div class="ptag"> Immunity Boost</div>
                    <div class="ptag"> Bone Strength</div>
                    <div class="ptag"> Energy & Focus</div>
                    <div class="ptag">Cell Protection</div>

                </div>
            </div>
        </div>


        <!-- Minerals -->
        <div class="ing-panel" id="ing-panel-6">
            <div class="for-large-img" style="display:flex;justify-content:center">
                <div class="ing-planet"
                    style="background:radial-gradient(circle at 35% 35%,#0A1A3A,#0A0A2A);--pglow:rgba(0,191,255,.35)">
                    <img class="image-big" src="img/min.png" alt="Minerals" width="300" height="300" loading="lazy">
                    <div class="orbit-i" style="--orr:6s">⭐</div>
                    <div class="orbit-i" style="--orr:7s">⭐</div>
                    <div class="orbit-i" style="--orr:11s;font-size:1rem">⭐</div>
                </div>
            </div>
            <div class="ing-text">
                <div class="ing-num">07</div>
                <div class="ing-pill"
                    style="background:rgba(0,191,255,.12);color:var(--sk);border:1px solid rgba(0,191,255,.2)">ESSENTIAL
                    MINERALS
                </div>
                <h3 class="ing-name">Minerals</h3>
                <p class="ing-sci">Zinc · Magnesium · Iodine · Selenium — Vital Trace Minerals</p>
                <p class="ing-story"> Growth doesn't happen by chance — it happens through the right minerals, every
                    single day. From Zinc that fuels immunity, to Magnesium that supports calm and restful sleep, to
                    Iodine and Selenium that protect your child's developing body from within, these trace minerals
                    quietly power some of the most important processes in childhood.
                </p>
                <div class="ing-powers">
                    <div class="ptag">Immune Defense</div>
                    <div class="ptag"> Better Sleep</div>
                    <div class="ptag">Thyroid Health Cell Protection</div>
                    <div class="ptag">Stronger Growth
                    </div>

                </div>
            </div>
        </div>\n
        <div class="ing-panel" id="ing-panel-7">
            <div class="for-large-img" style="display:flex;justify-content:center">
                <div class="ing-planet"
                    style="background:radial-gradient(circle at 35% 35%,#2A4A2A,#0D2A0D);--pglow:rgba(0,214,143,.35)">
                    <img class="image-big" src="img/tulsi.png" alt="Tulsi Extract" width="300" height="300"
                        loading="lazy">
                    <div class="orbit-i" style="--orr:7s">⭐</div>
                    <div class="orbit-i" style="--orr:9s">⭐</div>
                    <div class="orbit-i" style="--orr:14s;font-size:.9rem">⭐</div>
                </div>
            </div>
            <div class="ing-text">
                <div class="ing-num">08</div>
                <div class="ing-pill"
                    style="background:rgba(0,214,143,.12);color:var(--mn);border:1px solid rgba(0,214,143,.2)">Sacred
                    Herb
                </div>
                <h3 class="ing-name">Tulsi Extract</h3>
                <p class="ing-sci">Ocimum Sanctum </p>
                <p class="ing-story">Revered in Ayurveda as the "Queen of Herbs," Tulsi is an adaptogen that naturally
                    supports respiratory health, boosts immunity, and helps your child's body respond to stress and
                    seasonal changes.</p>
                <div class="ing-powers">
                    <div class="ptag">Immunity Booster</div>\n <div class="ptag">Respiratory Health</div>\n <div
                        class="ptag">Natural Adaptogen</div>
                </div>
            </div>
        </div>

        <div class="ing-panel" id="ing-panel-8">
            <div class="for-large-img" style="display:flex;justify-content:center">
                <div class="ing-planet"
                    style="background:radial-gradient(circle at 35% 35%,#3A2A00,#2A1800);--pglow:rgba(255,214,0,.4)">
                    <img class="image-big" src="img/gradient1.webp" alt="Mulethi Extract" width="300" height="300"
                        loading="lazy">
                    <div class="orbit-i" style="--orr:7s">⭐</div>
                    <div class="orbit-i" style="--orr:9s">⭐</div>
                    <div class="orbit-i" style="--orr:14s;font-size:.9rem">⭐</div>
                </div>
            </div>
            <div class="ing-text">
                <div class="ing-num">09</div>
                <div class="ing-pill"
                    style="background:rgba(255,214,0,.1);color:var(--ye);border:1px solid rgba(255,214,0,.2)">Throat
                    Soother
                </div>
                <h3 class="ing-name">Mulethi Extract</h3>
                <p class="ing-sci">Glycyrrhiza glabra · Licorice Root </p>
                <p class="ing-story">Mulethi has been used for generations to soothe sore throats, calm digestion, and
                    fight off respiratory infections. Its natural sweetness makes it a favorite, while its powerful
                    antioxidants protect growing bodies.</p>
                <div class="ing-powers">
                    <div class="ptag">Soothes Throat</div>\n <div class="ptag">Digestive Comfort</div>\n <div
                        class="ptag">Antioxidant Support</div>
                </div>
            </div>
        </div>

        <div class="ing-panel" id="ing-panel-9">
            <div class="for-large-img" style="display:flex;justify-content:center">
                <div class="ing-planet"
                    style="background:radial-gradient(circle at 35% 35%,#0A1A3A,#0A0A2A);--pglow:rgba(0,191,255,.35)">
                    <img class="image-big" src="img/sabji.png" alt="Intakari Extract" width="300" height="300"
                        loading="lazy">
                    <div class="orbit-i" style="--orr:7s">⭐</div>
                    <div class="orbit-i" style="--orr:9s">⭐</div>
                    <div class="orbit-i" style="--orr:14s;font-size:.9rem">⭐</div>
                </div>
            </div>
            <div class="ing-text">
                <div class="ing-num">10</div>
                <div class="ing-pill"
                    style="background:rgba(0,191,255,.12);color:var(--sk);border:1px solid rgba(0,191,255,.2)">
                    Respiratory Shield
                </div>
                <h3 class="ing-name">Intakari Extract</h3>
                <p class="ing-sci">Solanum virginianum · Kantakari </p>
                <p class="ing-story">A vital herb in traditional medicine, Kantakari (Intakari) clears the airways and
                    provides robust protection against coughs and colds. It acts as a natural bronchodilator for easy
                    breathing.</p>
                <div class="ing-powers">
                    <div class="ptag">Clears Airways</div>\n <div class="ptag">Fights Cough</div>\n <div class="ptag">
                        Lung Health</div>
                </div>
            </div>
        </div>

        <div class="ing-panel" id="ing-panel-10">
            <div class="for-large-img" style="display:flex;justify-content:center">
                <div class="ing-planet"
                    style="background:radial-gradient(circle at 35% 35%,#2A4A2A,#0D2A0D);--pglow:rgba(0,214,143,.35)">
                    <img class="image-big" src="img/bana.webp" alt="Banaphasa Extract" width="300" height="300"
                        loading="lazy">
                    <div class="orbit-i" style="--orr:7s">⭐</div>
                    <div class="orbit-i" style="--orr:9s">⭐</div>
                    <div class="orbit-i" style="--orr:14s;font-size:.9rem">⭐</div>
                </div>
            </div>
            <div class="ing-text">
                <div class="ing-num">11</div>
                <div class="ing-pill"
                    style="background:rgba(0,214,143,.12);color:var(--mn);border:1px solid rgba(0,214,143,.2)">Gentle
                    Healer
                </div>
                <h3 class="ing-name">Banaphasa Extract</h3>
                <p class="ing-sci">Viola odorata · Sweet Violet </p>
                <p class="ing-story">Banaphasa flowers are famous for their cooling and soothing properties. They help
                    relieve nasal congestion and bring down body heat naturally, making them perfect for children's
                    sensitive systems.</p>
                <div class="ing-powers">
                    <div class="ptag">Relieves Congestion</div>\n <div class="ptag">Cooling Effect</div>\n <div
                        class="ptag">Natural Relief</div>
                </div>
            </div>
        </div>

        <div class="ing-panel" id="ing-panel-11">
            <div class="for-large-img" style="display:flex;justify-content:center">
                <div class="ing-planet"
                    style="background:radial-gradient(circle at 35% 35%,#1A3A1A,#0A2A0A);--pglow:rgba(0,214,143,.3)">
                    <img class="image-big" src="img/talis.png" alt="Talispatra" width="300" height="300" loading="lazy">
                    <div class="orbit-i" style="--orr:7s">⭐</div>
                    <div class="orbit-i" style="--orr:9s">⭐</div>
                    <div class="orbit-i" style="--orr:14s;font-size:.9rem">⭐</div>
                </div>
            </div>
            <div class="ing-text">
                <div class="ing-num">12</div>
                <div class="ing-pill"
                    style="background:rgba(0,214,143,.12);color:var(--mn);border:1px solid rgba(0,214,143,.2)">Forest
                    Protector
                </div>
                <h3 class="ing-name">Talispatra</h3>
                <p class="ing-sci">Taxus wallichiana · Himalayan Yew </p>
                <p class="ing-story">Sourced from the high Himalayas, Talispatra leaves are known to clear mucus, ease
                    breathing, and strengthen the respiratory tract against recurrent infections.</p>
                <div class="ing-powers">
                    <div class="ptag">Clears Mucus</div>\n <div class="ptag">Strengthens Airways</div>\n <div
                        class="ptag">Immune Support</div>
                </div>
            </div>
        </div>

        <div class="ing-panel" id="ing-panel-12">
            <div class="for-large-img" style="display:flex;justify-content:center">
                <div class="ing-planet"
                    style="background:radial-gradient(circle at 35% 35%,#3A2A00,#2A1800);--pglow:rgba(255,214,0,.4)">
                    <img class="image-big" src="img/adrak.png" alt="Ginger Extract" width="300" height="300"
                        loading="lazy">
                    <div class="orbit-i" style="--orr:7s">⭐</div>
                    <div class="orbit-i" style="--orr:9s">⭐</div>
                    <div class="orbit-i" style="--orr:14s;font-size:.9rem">⭐</div>
                </div>
            </div>
            <div class="ing-text">
                <div class="ing-num">13</div>
                <div class="ing-pill"
                    style="background:rgba(255,214,0,.1);color:var(--ye);border:1px solid rgba(255,214,0,.2)">Digestive
                    Fire
                </div>
                <h3 class="ing-name">Ginger Extract</h3>
                <p class="ing-sci">Zingiber officinale </p>
                <p class="ing-story">A universal medicine in Ayurveda, Ginger kindles the digestive fire (Agni). It
                    soothes upset tummies, reduces nausea, and helps the body absorb nutrients from other foods more
                    efficiently.</p>
                <div class="ing-powers">
                    <div class="ptag">Happy Tummy</div>\n <div class="ptag">Reduces Nausea</div>\n <div class="ptag">
                        Boosts Absorption</div>
                </div>
            </div>
        </div>

        <div class="ing-panel" id="ing-panel-13">
            <div class="for-large-img" style="display:flex;justify-content:center">
                <div class="ing-planet"
                    style="background:radial-gradient(circle at 35% 35%,#2A4A2A,#0D2A0D);--pglow:rgba(0,214,143,.35)">
                    <img class="image-big" src="img/piper.png" alt="Piper Longum Extract" width="300" height="300"
                        loading="lazy">
                    <div class="orbit-i" style="--orr:7s">⭐</div>
                    <div class="orbit-i" style="--orr:9s">⭐</div>
                    <div class="orbit-i" style="--orr:14s;font-size:.9rem">⭐</div>
                </div>
            </div>
            <div class="ing-text">
                <div class="ing-num">14</div>
                <div class="ing-pill"
                    style="background:rgba(0,214,143,.12);color:var(--mn);border:1px solid rgba(0,214,143,.2)">
                    Metabolism Booster
                </div>
                <h3 class="ing-name">Piper Longum Extract</h3>
                <p class="ing-sci">Piper longum · Long Pepper </p>
                <p class="ing-story">Known as Pippali, this unique spice not only clears respiratory congestion but acts
                    as a bio-enhancer — meaning it multiplies the effectiveness of every other nutrient your child
                    consumes.</p>
                <div class="ing-powers">
                    <div class="ptag">Bio-Enhancer</div>\n <div class="ptag">Respiratory Support</div>\n <div
                        class="ptag">Better Digestion</div>
                </div>
            </div>
        </div>

        <div class="ing-panel" id="ing-panel-14">
            <div class="for-large-img" style="display:flex;justify-content:center">
                <div class="ing-planet"
                    style="background:radial-gradient(circle at 35% 35%,#0A1A3A,#0A0A2A);--pglow:rgba(0,191,255,.35)">
                    <img class="image-big" src="img/Selenium.png" alt="Selenium" width="300" height="300"
                        loading="lazy">
                    <div class="orbit-i" style="--orr:7s">⭐</div>
                    <div class="orbit-i" style="--orr:9s">⭐</div>
                    <div class="orbit-i" style="--orr:14s;font-size:.9rem">⭐</div>
                </div>
            </div>
            <div class="ing-text">
                <div class="ing-num">15</div>
                <div class="ing-pill"
                    style="background:rgba(0,191,255,.12);color:var(--sk);border:1px solid rgba(0,191,255,.2)">Cellular
                    Guardian
                </div>
                <h3 class="ing-name">Selenium</h3>
                <p class="ing-sci">Essential Trace Mineral </p>
                <p class="ing-story">Selenium works quietly in the background, acting as a powerful antioxidant. It
                    supports thyroid function and protects your child's growing cells from oxidative stress and
                    environmental damage.</p>
                <div class="ing-powers">
                    <div class="ptag">Cell Protection</div>\n <div class="ptag">Thyroid Health</div>\n <div
                        class="ptag">Antioxidant Defense</div>
                </div>
            </div>
        </div>



        <div class="ing-panel" id="ing-panel-17">
            <div class="for-large-img" style="display:flex;justify-content:center">
                <div class="ing-planet"
                    style="background:radial-gradient(circle at 35% 35%,#3A2A00,#2A1800);--pglow:rgba(255,214,0,.4)">
                    <img class="image-big" src="img/gajar.png" alt="Carrot Extract" width="300" height="300"
                        loading="lazy">
                    <div class="orbit-i" style="--orr:7s">⭐</div>
                    <div class="orbit-i" style="--orr:9s">⭐</div>
                    <div class="orbit-i" style="--orr:14s;font-size:.9rem">⭐</div>
                </div>
            </div>
            <div class="ing-text">
                <div class="ing-num">18</div>
                <div class="ing-pill"
                    style="background:rgba(255,214,0,.1);color:var(--ye);border:1px solid rgba(255,214,0,.2)">Vision
                    Support
                </div>
                <h3 class="ing-name">Carrot Extract</h3>
                <p class="ing-sci">Daucus carota </p>
                <p class="ing-story">Packed with Beta-Carotene (Vitamin A), Carrot Extract is nature's way of keeping
                    little eyes sharp and healthy. It also provides essential antioxidants for skin health.</p>
                <div class="ing-powers">
                    <div class="ptag">Sharp Vision</div>\n <div class="ptag">Eye Health</div>\n <div class="ptag">Skin
                        Protection</div>
                </div>
            </div>
        </div>

        <div class="ing-panel" id="ing-panel-18">
            <div class="for-large-img" style="display:flex;justify-content:center">
                <div class="ing-planet"
                    style="background:radial-gradient(circle at 35% 35%,#1A3A1A,#0A2A0A);--pglow:rgba(0,214,143,.3)">
                    <img class="image-big" src="img/pome.png" alt="Pomegranate Extract" width="300" height="300"
                        loading="lazy">
                    <div class="orbit-i" style="--orr:7s">⭐</div>
                    <div class="orbit-i" style="--orr:9s">⭐</div>
                    <div class="orbit-i" style="--orr:14s;font-size:.9rem">⭐</div>
                </div>
            </div>
            <div class="ing-text">
                <div class="ing-num">19</div>
                <div class="ing-pill"
                    style="background:rgba(0,214,143,.12);color:var(--mn);border:1px solid rgba(0,214,143,.2)">Heart &
                    Blood
                </div>
                <h3 class="ing-name">Pomegranate Extract</h3>
                <p class="ing-sci">Punica granatum </p>
                <p class="ing-story">A vibrant super-fruit loaded with Vitamin C and iron-boosting properties.
                    Pomegranate promotes healthy blood flow and provides robust immune defense during active play.</p>
                <div class="ing-powers">
                    <div class="ptag">Healthy Blood</div>\n <div class="ptag">Vibrant Energy</div>\n <div class="ptag">
                        Immune Defense</div>
                </div>
            </div>
        </div>

        <div class="ing-panel" id="ing-panel-19">
            <div class="for-large-img" style="display:flex;justify-content:center">
                <div class="ing-planet"
                    style="background:radial-gradient(circle at 35% 35%,#0A1A3A,#0A0A2A);--pglow:rgba(0,191,255,.35)">
                    <img class="image-big" src="img/beetroot.png" alt="Beetroot Extract" width="300" height="300"
                        loading="lazy">
                    <div class="orbit-i" style="--orr:7s">⭐</div>
                    <div class="orbit-i" style="--orr:9s">⭐</div>
                    <div class="orbit-i" style="--orr:14s;font-size:.9rem">⭐</div>
                </div>
            </div>
            <div class="ing-text">
                <div class="ing-num">20</div>
                <div class="ing-pill"
                    style="background:rgba(0,191,255,.12);color:var(--sk);border:1px solid rgba(0,191,255,.2)">Stamina
                    Builder
                </div>
                <h3 class="ing-name">Beetroot Extract</h3>
                <p class="ing-sci">Beta vulgaris </p>
                <p class="ing-story">Rich in natural nitrates and iron, Beetroot increases oxygen flow in the blood. It
                    gives your child the healthy stamina they need for sports and endless physical activity.</p>
                <div class="ing-powers">
                    <div class="ptag">Builds Stamina</div>\n <div class="ptag">Blood Health</div>\n <div class="ptag">
                        Active Energy</div>
                </div>
            </div>
        </div>

        <div class="ing-panel" id="ing-panel-20">
            <div class="for-large-img" style="display:flex;justify-content:center">
                <div class="ing-planet"
                    style="background:radial-gradient(circle at 35% 35%,#2A4A2A,#0D2A0D);--pglow:rgba(0,214,143,.35)">
                    <img class="image-big" src="img/beer.png" alt="Raspberry Extract" width="300" height="300"
                        loading="lazy">
                    <div class="orbit-i" style="--orr:7s">⭐</div>
                    <div class="orbit-i" style="--orr:9s">⭐</div>
                    <div class="orbit-i" style="--orr:14s;font-size:.9rem">⭐</div>
                </div>
            </div>
            <div class="ing-text">
                <div class="ing-num">21</div>
                <div class="ing-pill"
                    style="background:rgba(0,214,143,.12);color:var(--mn);border:1px solid rgba(0,214,143,.2)">Nutrient
                    Powerhouse
                </div>
                <h3 class="ing-name">Raspberry Extract</h3>
                <p class="ing-sci">Rubus idaeus </p>
                <p class="ing-story">Beyond adding a delicious natural flavor, Raspberries are packed with Vitamin C,
                    fiber, and potent antioxidants that support a healthy gut and bright, glowing skin.</p>
                <div class="ing-powers">
                    <div class="ptag">Antioxidant Rich</div>\n <div class="ptag">Gut Health</div>\n <div class="ptag">
                        Natural Flavor</div>
                </div>
            </div>
        </div>
    </div><!-- /ing-panels -->
</section>