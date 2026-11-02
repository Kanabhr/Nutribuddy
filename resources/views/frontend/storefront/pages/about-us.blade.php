@extends('layouts.main')
@section('title', 'About Us — NutriBuddy Kids')



@section('content')
    <!-- ------------------------------------------
                                                                                                                               HERO
                                                                                                                          ------------------------------------------ -->
    <section class="ns-story-hero">
        <div class="ns-wrap">
            <section class="ns-section">

                <div class="ns-gummy ns-gummy--bear">
                    <img src="{{ asset('img/yammi.png') }}" alt="Gummy bear">
                </div>

                <div class="ns-gummy ns-gummy--berry for-left">
                    <img src="{{ asset('img/yammi.png') }}" alt="Gummy berry">
                </div>

                <span class="ns-star-deco">&#10022;</span>

                <div class="ns-grid">

                    <div class="ns-left">
                        <span class="ns-deco-heart-top">
                            <img src="{{ asset('img/heart.png') }}" alt="">
                        </span>

                        <span class="ns-tag">OUR STORY</span>

                        <h1 class="ns-headline">
                            Every Great Idea<br>
                            Starts With<br>
                            A <span class="ns-headline-accent">Real Moment</span>
                        </h1>

                        <div class="ns-divider"></div>

                        <p class="ns-body">
                            Nutri Buddy began not in a lab, but in a moment of worry. A nephew too active to sit still, yet
                            too weak to stay healthy. Medicines piled up, syrups expired, and nothing truly worked. We
                            realised millions of Indian parents live this same worry daily.
                            Kids missing real nutrition in a world of junk food and screens. So we decided to build the
                            nutrition we wished our child had, and every child deserves - Nutri Buddy.
                        </p>

                        <div class="ns-mascot-wrap">
                            <img class="ns-mascot-img" src="{{ asset('img/NW.png') }}" alt="NutriBuddy mascot">
                        </div>
                    </div>

                    <div class="ns-right">
                        <span class="ns-deco ns-deco--hrt1">&hearts;</span>
                        <span class="ns-deco ns-deco--star1">&#10022;</span>
                        <span class="ns-deco ns-deco--star2">&#10022;</span>
                        <span class="ns-deco ns-deco--plus1">&#10022;</span>
                        <span class="ns-deco ns-deco--leaf">&#10022;</span>

                        <div class="ns-polar ns-polar--worry">
                            <div class="ns-tape"></div>
                            <img src="{{ asset('img/Worried-child.jpeg') }}" alt="Worried child" />
                            <div class="ns-polar-caption">The worry we felt</div>
                        </div>

                        <div class="ns-polar ns-polar--happy">
                            <div class="ns-tape"></div>
                            <img src="{{ asset('img/aboutb1.jpeg') }}" alt="Happy mother and child" />
                            <div class="ns-polar-caption">The reason we started</div>
                        </div>

                        <svg class="ns-arrow-svg" width="72" height="58" viewBox="0 0 72 58" fill="none">
                            <path d="M6 6 C18 6 24 36 54 48" stroke="#cc1177" stroke-width="2.2" stroke-dasharray="5.5 4.5"
                                stroke-linecap="round" fill="none" />
                            <path d="M47 54 L57 48 L51 39" stroke="#cc1177" stroke-width="2.2" stroke-linecap="round"
                                stroke-linejoin="round" fill="none" />
                        </svg>

                        <div class="ns-promise">
                            Our promise is simple <br>
                            Real nutrition,<br>
                            made for kids.<br>
                            With love, always.
                        </div>
                    </div>
                </div>

                <svg class="ns-wave" viewBox="0 0 1440 110" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M0,65 C180,15 360,105 540,65 C720,25 900,105 1100,58 C1260,26 1380,78 1440,65 L1440,110 L0,110 Z"
                        fill="rgba(255,255,255,.5)" />
                    <path
                        d="M0,82 C200,42 400,115 650,78 C850,50 1050,110 1250,75 C1340,59 1400,92 1440,84 L1440,110 L0,110 Z"
                        fill="rgba(255,255,255,.85)" />
                </svg>
            </section>

            <div class="ns-stats-outer">
                <div class="ns-stats">

                    <div class="ns-stat">
                        <div class="ns-stat-icon ns-stat-icon--pink">
                            <svg width="26" height="22" viewBox="0 0 26 22" fill="none">
                                <circle cx="8" cy="8" r="4.5" fill="#e91e8c" opacity=".7" />
                                <circle cx="18" cy="8" r="4.5" fill="#e91e8c" opacity=".7" />
                                <ellipse cx="13" cy="17" rx="9" ry="5" fill="#e91e8c" />
                                <ellipse cx="4" cy="17" rx="4" ry="4.5" fill="#e91e8c" opacity=".6" />
                                <ellipse cx="22" cy="17" rx="4" ry="4.5" fill="#e91e8c" opacity=".6" />
                            </svg>
                        </div>
                        <div>
                            <div class="ns-stat-val">100%</div>
                            <div class="ns-stat-lbl">Made In India</div>
                        </div>
                    </div>

                    <div class="ns-stat">
                        <div class="ns-stat-icon ns-stat-icon--purple">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path
                                    d="M17 8C8 10 5.9 16.17 3.82 19.93 2.19 18.6 2 17.5 2 17.5S2.6 22 9 22c6.64 0 9-4.5 9-4.5S17.8 22 22 22C22 22 20.26 11.29 17 8z"
                                    fill="#9c8abf" />
                            </svg>
                        </div>
                        <div>
                            <div class="ns-stat-val">95% </div>
                            <div class="ns-stat-lbl"> KIDS - ASK FOR MORE</div>
                        </div>
                    </div>

                    <div class="ns-stat">
                        <div class="ns-stat-icon ns-stat-icon--green">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path
                                    d="M17 8C8 10 5.9 16.17 3.82 19.93 2.19 18.6 2 17.5 2 17.5S2.6 22 9 22c6.64 0 9-4.5 9-4.5S17.8 22 22 22C22 22 20.26 11.29 17 8z"
                                    fill="#4caf50" />
                            </svg>
                        </div>
                        <div>
                            <div class="ns-stat-val">0</div>
                            <div class="ns-stat-lbl">Preservatives</div>
                        </div>
                    </div>

                    <div class="ns-stat">
                        <div class="ns-stat-icon ns-stat-icon--gold">
                            <svg width="26" height="26" viewBox="0 0 26 26" fill="none">
                                <path
                                    d="M13 2 L15.5 9.5 H23.5 L17.2 14.2 L19.7 21.7 L13 17 L6.3 21.7 L8.8 14.2 L2.5 9.5 H10.5 Z"
                                    fill="#f5a623" />
                            </svg>
                        </div>
                        <div>
                            <div class="ns-stat-val">15+</div>
                            <div class="ns-stat-lbl"> AYURVEDIC HERBS</div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </section>



    <section class="nbs-section">

        <!-- Background decorations -->
        <div class="nbs-bg-shapes">
            <div class="nbs-blob-tl"></div>
            <div class="nbs-blob-br"></div>
            <!-- Leaves -->
            <img src="img/btn-leafimg.png" alt="">

        </div>

        <div class="nbs-container">

            <!-- OUR STORY tag -->
            <div class="nbs-our-story-tag">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5AB25A" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2C7 2 4 8 4 13c0 3.5 2 6.5 5 8" />
                    <path d="M12 2c5 0 8 6 8 11 0 3.5-2 6.5-5 8" />
                    <line x1="12" y1="21" x2="12" y2="11" />
                </svg>
                Our Story
            </div>

            <!-- --------------- MAIN GRID --------------- -->
            <div class="nbs-main-grid">

                <!-- ---------- LEFT: IMAGES ---------- -->
                <div class="nbs-img-col">

                    <!-- Arrow 1 SVG (top to middle) -->
                    <svg class="nbs-arrow-svg nbs-arrow-1" viewBox="0 0 100 120" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path d="M 85 10 C -10 10, -10 110, 85 110" stroke="#874BDE" stroke-width="2" stroke-dasharray="6 6"
                            stroke-linecap="round" />
                        <path d="M 70 100 L 85 110 L 70 120" stroke="#874BDE" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>

                    <!-- Arrow 2 SVG (middle to bottom) -->
                    <svg class="nbs-arrow-svg nbs-arrow-2" viewBox="0 0 100 120" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path d="M 85 10 C -10 10, -10 110, 85 110" stroke="#874BDE" stroke-width="2" stroke-dasharray="6 6"
                            stroke-linecap="round" />
                        <path d="M 70 100 L 85 110 L 70 120" stroke="#874BDE" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>

                    <!-- Card 1 – sick child -->
                    <div class="nbs-photo-card nbs-card-1">
                        <div class="nbs-tape"></div>
                        <div class="nbs-photo-fill"><img src="{{ asset('img/fever2.png') }}" alt=""></div>
                    </div>

                    <!-- Card 2 – doctor visit -->
                    <div class="nbs-photo-card nbs-card-2">
                        <div class="nbs-tape nbs-tape-ye"></div>
                        <div class="nbs-photo-fill"><img src="{{ asset('img/fever1.png') }}" alt=""></div>
                    </div>

                    <!-- Card 3 – happy running child -->
                    <div class="nbs-photo-card nbs-card-3">
                        <div class="nbs-tape nbs-tape-pu"></div>
                        <div class="nbs-photo-fill"><img src="{{ asset('img/fever3.png') }}" alt=""></div>
                    </div>

                </div><!-- /nbs-img-col -->

                <!-- ---------- RIGHT: CONTENT ---------- -->
                <div class="nbs-content-col">

                    <!-- Badge -->
                    <div class="nbs-badge">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2C7 5 4 10 6 16c2 5 8 7 12 3 3-4 2-10-2-14C14 4 13 3 12 2z" />
                            <line x1="12" y1="22" x2="12" y2="14" />
                        </svg>
                        The Beginning
                    </div>

                    <!-- Heading -->
                    <h2 class="nbs-heading">
                        It Started With<br />
                        <span class="nbs-hd-pink">One Little Boy</span>
                    </h2>

                    <!-- Yellow line -->
                    <div class="nbs-yellow-acc"></div>

                    <!-- STEPS -->
                    <div class="nbs-steps">

                        <!-- 1 -->
                        <div class="nbs-step">
                            <div class="nbs-step-left">
                                <div class="nbs-step-icon nbs-ic-pk">
                                    <img src="{{ asset('img/about-boy.png') }}" alt="">
                                </div>
                            </div>
                            <p class="nbs-step-body">
                                At just 2 years old, he was <span class="nbs-hl-pk">constantly falling sick.</span><br />
                                Cold, cough, weakness, it became a cycle.
                            </p>
                        </div>

                        <!-- 2 -->
                        <div class="nbs-step">
                            <div class="nbs-step-left">
                                <div class="nbs-step-icon nbs-ic-ye">
                                    <img src="{{ asset('img/first-addcart.png') }}" alt="">
                                </div>
                            </div>
                            <p class="nbs-step-body">
                                Doctor visits became routine.
                                Medicines gave <br /> relief, but <span class="nbs-hl-pk">never a real solution.</span>
                            </p>
                        </div>

                        <!-- 3 -->
                        <div class="nbs-step">
                            <div class="nbs-step-left">
                                <div class="nbs-step-icon nbs-ic-pu">
                                    <img src="{{ asset('img/herbs.png') }}" alt="">
                                </div>
                            </div>
                            <p class="nbs-step-body">
                                One day, someone suggested something simple,<br />
                                <span class="nbs-hl-pu">honey</span> and a few <span class="nbs-hl-pu">Ayurvedic
                                    herbs.</span>
                                His cough<br /> improved. But something still felt missing.
                            </p>
                        </div>

                        <!-- 4 -->
                        <div class="nbs-step">
                            <div class="nbs-step-left">
                                <div class="nbs-step-icon nbs-ic-mn">
                                    <img src="{{ asset('img/leave-one.png') }}" alt="">
                                </div>
                            </div>
                            <p class="nbs-step-body">
                                That's when we realised
                                the real issue wasn't <br />illness, <span class="nbs-hl-mn">it was nutrition.</span>
                            </p>
                        </div>

                        <!-- 5 -->
                        <div class="nbs-step">
                            <div class="nbs-step-left">
                                <div class="nbs-step-icon nbs-ic-pk">
                                    <img src="{{ asset('img/three.png') }}" alt="">
                                </div>
                            </div>
                            <p class="nbs-step-body">
                                And if one child was facing this, thousands were too.<br />
                                That moment became the beginning of <span class="nbs-hl-pk">NutriBuddy.</span>
                            </p>
                        </div>

                    </div><!-- /nbs-steps -->
                </div><!-- /nbs-content-col -->

            </div><!-- /nbs-main-grid -->

            <!-- --------------- STATS BAR --------------- -->
            <div class="nbs-stats-bar">

                <div class="nbs-stat-item">
                    <div class="nbs-stat-ico nbs-si-pk">
                        <img src="{{ asset('img/three.png') }}" alt="">
                    </div>
                    <div class="nbs-stat-text">
                        <span class="nbs-stat-num nbs-sn-pk"> Our Promise</span>
                        <span class="nbs-stat-lbl">Quality Without Compromise</span>
                    </div>
                </div>

                <div class="nbs-stat-item">
                    <div class="nbs-stat-ico nbs-si-pu">
                        <img src="{{ asset('img/labb-about.png') }}" alt="">
                    </div>
                    <div class="nbs-stat-text">
                        <span class="nbs-stat-num nbs-sn-pu">Our Philosophy</span>
                        <span class="nbs-stat-lbl">Nature Meets Nutrition</span>
                    </div>
                </div>

                <div class="nbs-stat-item">
                    <div class="nbs-stat-ico nbs-si-mn">
                        <img src="{{ asset('img/leaves.png') }}" alt="">
                    </div>
                    <div class="nbs-stat-text">
                        <span class="nbs-stat-num nbs-sn-mn"> Our Focus</span>
                        <span class="nbs-stat-lbl">Healthy Kids, Happy Families</span>
                    </div>
                </div>

                <div class="nbs-stat-item">
                    <div class="nbs-stat-ico nbs-si-ye">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="#FFC933" stroke="#d19a00" stroke-width="1.5"
                            stroke-linecap="round" stroke-linejoin="round">
                            <polygon
                                points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                        </svg>
                    </div>
                    <div class="nbs-stat-text">
                        <span class="nbs-stat-num nbs-sn-ye"> Our Mission</span>
                        <span class="nbs-stat-lbl">Making Everyday Nutrition Simple</span>
                    </div>
                </div>

            </div><!-- /nbs-stats-bar -->

            <!-- Gummy bears bottom-left -->
            <!-- <div class="nbs-gummies">
                                                                                                        <span class="nbs-gummy"><img src="img/blue-4.png" alt=""></span>
                                                                                                        <span class="nbs-gummy">🧸</span>
                                                                                                      </div> -->

        </div><!-- /nbs-container -->
    </section>


    <section class="origin-section">
        <div class="origin-grid">

            <!-- LEFT: Dark story card -->
            <div class="origin-visual">
                <div class="story-card-main">
                    <span class="eyebrow">The Beginning</span>
                    <h2>PARENTS, A SICK CHILD AND DOCTORS FRUSTATING ANSWERS.</h2>
                    <p>
                        My nephew fell sick often- city life, poor immunity, changing weather, and unhealthy food habits
                        took a toll. Worried, I started digging deeper and realised it wasn't just him. Parents everywhere
                        were facing the same struggle: weak immunity and poor nutrition.
                    </p>
                    <p>
                        His paediatrician suggested nutrition supplements to fill the gaps. . What I found? Hidden sugars,
                        artificial colours, and zero real nutrition just pretty packaging selling empty promises. If it
                        wasn't good enough for my child, it is’nt good enough for yours either. So we built India’s first
                        kids Ayurvedic and nutraceutical wellnes brand Nutri Buddy.
                    </p>
                    <div class="story-pills">
                        <div class="spill">Real Parent Story</div>
                        <div class="spill">Doctor's Advice</div>
                        <div class="spill">A Better Way</div>
                    </div>
                </div>
                <div class="float-accent fa-1">Pediatrician Approved</div>

            </div>

            <!-- RIGHT: Accordion content -->
            <div class="origin-text">
                <span class="sec-eye">Why We Started</span>
                <h2>The Problem Was <span class="acc">Hiding</span> in Plain Sight</h2>
                <p>Every parent faces everyday challenges that no one talks about enough. Click to see what really troubles
                    us.
                </p>

                <ul class="accordion-list">

                    <li class="acc-item open" data-index="0">
                        <div class="acc-header" onclick="toggleAboutUsAccordion(this)">
                            <div class="acc-icon-wrap" style="background:#fde8f3;"> <img src="{{ asset('img/sec-2.png') }}"
                                    alt=""></div>
                            <span class="acc-title">Picky eating, every day</span>
                            <span class="acc-toggle">
                                <svg viewBox="0 0 14 14">
                                    <line x1="7" y1="2" x2="7" y2="12" />
                                    <line x1="2" y1="7" x2="12" y2="7" />
                                </svg>
                            </span>
                        </div>
                        <div class="acc-body">
                            <div class="acc-body-inner">
                                <ul>
                                    <li>One day they eat well, the next day they refuse everything.</li>
                                    <li>You don't know what's normal anymore.</li>
                                </ul>
                            </div>
                        </div>
                    </li>

                    <li class="acc-item" data-index="1">
                        <div class="acc-header" onclick="toggleAboutUsAccordion(this)">
                            <div class="acc-icon-wrap" style="background:#fff3e0;"><img src="{{ asset('img/sec-3.png') }}"
                                    alt=""></div>
                            <span class="acc-title">Constant worry in the back of your mind</span>
                            <span class="acc-toggle">
                                <svg viewBox="0 0 14 14">
                                    <line x1="7" y1="2" x2="7" y2="12" />
                                    <line x1="2" y1="7" x2="12" y2="7" />
                                </svg>
                            </span>
                        </div>
                        <div class="acc-body">
                            <div class="acc-body-inner">
                                <ul>
                                    <li>Is my child getting enough nutrition each day?</li>
                                    <li>Am I doing enough as a parent?</li>
                                </ul>
                            </div>
                        </div>
                    </li>

                    <li class="acc-item" data-index="2">
                        <div class="acc-header" onclick="toggleAboutUsAccordion(this)">
                            <div class="acc-icon-wrap" style="background:#e8f0ff;"><img src="{{ asset('img/sec-5.png') }}"
                                    alt=""></div>
                            <span class="acc-title">Too much advice, too little clarity</span>
                            <span class="acc-toggle">
                                <svg viewBox="0 0 14 14">
                                    <line x1="7" y1="2" x2="7" y2="12" />
                                    <line x1="2" y1="7" x2="12" y2="7" />
                                </svg>
                            </span>
                        </div>
                        <div class="acc-body">
                            <div class="acc-body-inner">
                                <ul>
                                    <li>Every doctor, blog, and relative says something different.</li>
                                    <li>Trusted, science-backed answers are hard to find.</li>
                                </ul>
                            </div>
                        </div>
                    </li>

                    <li class="acc-item" data-index="3">
                        <div class="acc-header" onclick="toggleAboutUsAccordion(this)">
                            <div class="acc-icon-wrap" style="background:#e8faf2;"><img src="{{ asset('img/sec-4.png') }}"
                                    alt=""></div>
                            <span class="acc-title"> Why Doesn't One Diet Chart Work for Every Child?</span>
                            <span class="acc-toggle">
                                <svg viewBox="0 0 14 14">
                                    <line x1="7" y1="2" x2="7" y2="12" />
                                    <line x1="2" y1="7" x2="12" y2="7" />
                                </svg>
                            </span>
                        </div>
                        <div class="acc-body">
                            <div class="acc-body-inner">
                                <ul>
                                    <li> No two children have the same nutritional needs. Share your child's age, weight,
                                        eating habits, and health concerns,</li>
                                    <li> We'll create a free, doctor-informed diet chart made specifically for them.</li>
                                </ul>
                            </div>
                        </div>
                    </li>

                    <li class="acc-item" data-index="4">
                        <div class="acc-header" onclick="toggleAboutUsAccordion(this)">
                            <div class="acc-icon-wrap" style="background:#fde8f3;"><img src="{{ asset('img/sec-6.png') }}"
                                    alt=""></div>
                            <span class="acc-title"> Still Following a Generic Diet Chart?</span>
                            <span class="acc-toggle">
                                <svg viewBox="0 0 14 14">
                                    <line x1="7" y1="2" x2="7" y2="12" />
                                    <line x1="2" y1="7" x2="12" y2="7" />
                                </svg>
                            </span>
                        </div>
                        <div class="acc-body">
                            <div class="acc-body-inner">
                                <ul>
                                    <li> Generic diet plans can't meet every child's unique needs. </li>
                                    <li>Tell us a little about your child, and we'll build a personalized nutrition plan
                                        based on their age, appetite, lifestyle, and nutritional requirements.</li>
                                </ul>
                            </div>
                        </div>
                    </li>

                    <li class="acc-item" data-index="5">
                        <div class="acc-header" onclick="toggleAboutUsAccordion(this)">
                            <div class="acc-icon-wrap" style="background:#fff3e0;"><img src="{{ asset('img/sec-1.png') }}"
                                    alt=""></div>
                            <span class="acc-title"> Not Sure What's Missing in Your Child's Diet?
                            </span>
                            <span class="acc-toggle">
                                <svg viewBox="0 0 14 14">
                                    <line x1="7" y1="2" x2="7" y2="12" />
                                    <line x1="2" y1="7" x2="12" y2="7" />
                                </svg>
                            </span>
                        </div>
                        <div class="acc-body">
                            <div class="acc-body-inner">
                                <ul>
                                    <li> Every child's body is different—age, activity level, eating habits, and nutrition
                                        gaps all matter.</li>
                                    <li> Answer a few quick questions, and we'll create a personalized diet chart designed
                                        specifically for your child's unique nutritional needs.</li>
                                </ul>
                            </div>
                        </div>
                    </li>

                </ul>
            </div>

        </div>
    </section>


    <!-- gap -->
    <section class="gapfix-section">

        <!-- Header -->
        <div class="gapfix-top">
            <span class="gapfix-eyebrow">The Gap We Found</span>
            <h2 class="gapfix-heading">The gap we found — and <span>fixed.</span> <img class="proper-set"
                    src="{{ asset('img/heartmim.png') }}" alt=""></h2>
            <p class="gapfix-subtext">
                After months of research, lab testing, and consulting paediatricians and Ayurvedic practitioners,
                we identified four critical gaps in children's nutrition products available in India.
            </p>
        </div>

        <!-- Column Labels -->
        <div class="gapfix-col-labels">
            <div class="gapfix-label gapfix-label--problem">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24">
                    <path
                        d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"
                        stroke="#FF4D8F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                The Problem
            </div>
            <div class="gapfix-label gapfix-label--fix">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke="#00D68F" stroke-width="2" />
                    <path d="M8 12l3 3 5-5" stroke="#00D68F" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
                Our Fix
            </div>
        </div>

        <!-- Rows -->
        <div class="gapfix-rows">

            <!-- Row 1 -->
            <div class="gapfix-row">
                <div class="gapfix-card">
                    <div class="gapfix-icon-box gapfix-icon-box--problem"> <img src="{{ asset('img/btn-1.png') }}" alt="">
                    </div>
                    <div class="gapfix-num gapfix-num--problem">01</div>
                    <div class="gapfix-card-body">
                        <span class="gapfix-mobile-badge gapfix-mobile-badge--problem">The Problem</span>
                        <p class="gapfix-card-title">"Herbal Blends"
                        </p>
                        <p class="gapfix-card-desc">But What's Actually Inside?
                            Most kids' supplements hide behind vague terms like "proprietary blend," never revealing exactly
                            what's in the bottle or how much.
                        </p>
                    </div>
                </div>

                <div class="gapfix-arrow">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                        <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>

                <div class="gapfix-card">
                    <div class="gapfix-icon-box gapfix-icon-box--fix"> <img src="{{ asset('img/new-btn-1.png') }}" alt="">
                    </div>
                    <div class="gapfix-num gapfix-num--fix">01</div>
                    <div class="gapfix-card-body">
                        <span class="gapfix-mobile-badge gapfix-mobile-badge--fix">Our Fix</span>
                        <p class="gapfix-card-title">Every Herb Named. Every Gram Measured.</p>

                        <p class="gapfix-card-desc"> From Brahmi and Ashwagandha to 95% Curcuminoid Turmeric we list every
                            single ingredient and its exact quantity, so you always know what your child is really taking.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Row 2 -->
            <div class="gapfix-row">
                <div class="gapfix-card">
                    <div class="gapfix-icon-box gapfix-icon-box--problem"><img src="{{ asset('img/btn-2.png') }}" alt="">
                    </div>
                    <div class="gapfix-num gapfix-num--problem">02</div>
                    <div class="gapfix-card-body">
                        <span class="gapfix-mobile-badge gapfix-mobile-badge--problem">The Problem</span>
                        <p class="gapfix-card-title">Medicine That Tastes Like Medicine
                        </p>
                        <p class="gapfix-card-desc">Bitter Syrups Kids Refuse To Take
                            Herbal tablets and syrups often turn into a daily battle half of it ends up spat out or refused.
                        </p>
                    </div>
                </div>

                <div class="gapfix-arrow">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                        <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>

                <div class="gapfix-card">
                    <div class="gapfix-icon-box gapfix-icon-box--fix"> <img src="{{ asset('img/new-btn-2.png') }}" alt="">
                    </div>
                    <div class="gapfix-num gapfix-num--fix">02</div>
                    <div class="gapfix-card-body">
                        <span class="gapfix-mobile-badge gapfix-mobile-badge--fix">Our Fix</span>
                        <p class="gapfix-card-title">Real Ayurveda, Flavours Kids Love
                        </p>
                        <p class="gapfix-card-desc"> Fizzy ginger-lemon tablets, mango and grape gummies the same powerful
                            herbs, turned into something your child actually looks forward to.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Row 3 -->
            <div class="gapfix-row">
                <div class="gapfix-card">
                    <div class="gapfix-icon-box gapfix-icon-box--problem"><img src="{{ asset('img/btn-3.png') }}" alt="">
                    </div>
                    <div class="gapfix-num gapfix-num--problem">03</div>
                    <div class="gapfix-card-body">
                        <span class="gapfix-mobile-badge gapfix-mobile-badge--problem">The Problem</span>
                        <p class="gapfix-card-title">The Ingredient Maze</p>
                        <p class="gapfix-card-desc">Long lists of artificial colors, preservatives and 'natural identical'
                            flavors
                            hide in fine print.</p>
                    </div>
                </div>

                <div class="gapfix-arrow">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                        <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>

                <div class="gapfix-card">
                    <div class="gapfix-icon-box gapfix-icon-box--fix"><img src="{{ asset('img/new-btn-3.png') }}" alt="">
                    </div>
                    <div class="gapfix-num gapfix-num--fix">03</div>
                    <div class="gapfix-card-body">
                        <span class="gapfix-mobile-badge gapfix-mobile-badge--fix">Our Fix</span>
                        <p class="gapfix-card-title">Clean. Transparent. Honest.</p>
                        <p class="gapfix-card-desc">No artificial colors, preservatives or hidden ingredients. Just clean
                            nutrition
                            you can trust.</p>
                    </div>
                </div>
            </div>

            <!-- Row 4 -->
            <div class="gapfix-row">
                <div class="gapfix-card">
                    <div class="gapfix-icon-box gapfix-icon-box--problem"><img src="{{ asset('img/btn-4.png') }}" alt="">
                    </div>
                    <div class="gapfix-num gapfix-num--problem">04</div>
                    <div class="gapfix-card-body">
                        <span class="gapfix-mobile-badge gapfix-mobile-badge--problem">The Problem</span>
                        <p class="gapfix-card-title">Built For Others, Not For Us</p>
                        <p class="gapfix-card-desc">Global brands are formulated for Western diets — not dal-chawal, not
                            Indian
                            climates, not our gut flora.</p>
                    </div>
                </div>

                <div class="gapfix-arrow">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                        <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>

                <div class="gapfix-card">
                    <div class="gapfix-icon-box gapfix-icon-box--fix"><img src="{{ asset('img/new-btn-4.png') }}" alt="">
                    </div>
                    <div class="gapfix-num gapfix-num--fix">04</div>
                    <div class="gapfix-card-body">
                        <span class="gapfix-mobile-badge gapfix-mobile-badge--fix">Our Fix</span>
                        <p class="gapfix-card-title">Built For Indian Kids</p>
                        <p class="gapfix-card-desc">Thoughtfully formulated for Indian diets, lifestyles and growing
                            bodies. Gentle on
                            the gut. Perfect for our kids.</p>
                    </div>
                </div>
            </div>

        </div><!-- /.gapfix-rows -->

        <!-- Footer Banner -->
        <div class="gapfix-footer-banner">
            <div class="gapfix-footer-icon"> <img src="{{ asset('img/taddy.png') }}" alt=""></div>
            <p class="gapfix-footer-text">
                Because your child deserves more than just nutrition —<br>
                they deserve <em>the right nutrition.</em>
            </p>
            <div>
                <img src="{{ asset('img/sheeld.png') }}" alt="">
            </div>

        </div>

    </section>

    <!-- how we build every product -->
    <section class="nbap">
        <div class="nbap-blob nbap-blob-a"></div>
        <div class="nbap-blob nbap-blob-b"></div>
        <div class="nbap-blob nbap-blob-c"></div>

        <!-- HEADER -->
        <div class="nbap-head">
            <div class="nbap-eye"><b></b> Our Approach <b></b></div>
            <h2>How We Build <em>Every Product</em></h2>
            <p>Every NutriBuddy formula is born from a simple but powerful equation that took us <strong>two years</strong>
                to
                perfect.</p>
        </div>

        <!-- STEPS -->
        <div class="nbap-steps">
            <div class="nbap-row">

                <!-- -- Card 1 -- -->
                <div class="nbap-card" data-s="1">
                    <div class="nbap-bubble">
                        <span class="nbap-num">01</span>
                        <img src="{{ asset('img/blue1.png') }}" alt="Carefully Selected Ingredients"
                            onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'nbap-emoji',textContent:'🌿'}))">
                    </div>
                    <div class="nbap-text-wrap">
                        <h3 class="nbap-title">Carefully Selected<br>Ingredients</h3>
                        <p class="nbap-desc">We handpick the best natural herbs, fruits, vitamins &amp; minerals from
                            trusted sources.
                        </p>
                    </div>
                </div>

                <!-- Arrow 1→2 -->
                <div class="nbap-arr" data-a="1">
                    <svg viewBox="0 0 28 28" fill="none">
                        <circle cx="14" cy="14" r="13" stroke="rgba(255,255,255,0.15)" stroke-width="1.5" />
                        <path d="M10 14h8M15 11l3 3-3 3" stroke="rgba(255,255,255,0.75)" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>

                <!-- -- Card 2 -- -->
                <div class="nbap-card" data-s="2">
                    <div class="nbap-bubble">
                        <span class="nbap-num">02</span>
                        <img src="{{ asset('img/blue2.png') }}" alt="Backed by Science and Experts"
                            onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'nbap-emoji',textContent:'🔬'}))">
                    </div>
                    <div class="nbap-text-wrap">
                        <h3 class="nbap-title">Backed by Science<br>&amp; Experts</h3>
                        <p class="nbap-desc">Our formulas are developed by nutrition experts to support kids' growth,
                            immunity &amp;
                            brain development.</p>
                    </div>
                </div>

                <!-- Arrow 2→3 -->
                <div class="nbap-arr" data-a="2">
                    <svg viewBox="0 0 28 28" fill="none">
                        <circle cx="14" cy="14" r="13" stroke="rgba(255,255,255,0.15)" stroke-width="1.5" />
                        <path d="M10 14h8M15 11l3 3-3 3" stroke="rgba(255,255,255,0.75)" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>

                <!-- -- Card 3 -- -->
                <div class="nbap-card" data-s="3">
                    <div class="nbap-bubble">
                        <span class="nbap-num">03</span>
                        <img src="{{ asset('img/blue-3.png') }}" alt="Clean and Safe Manufacturing"
                            onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'nbap-emoji',textContent:'🏭'}))">
                    </div>
                    <div class="nbap-text-wrap">
                        <h3 class="nbap-title">Clean &amp; Safe<br>Manufacturing</h3>
                        <p class="nbap-desc">Made in certified facilities with strict quality checks. No harmful chemicals.
                            Ever.</p>
                    </div>
                </div>

                <!-- Arrow 3→4 -->
                <div class="nbap-arr" data-a="3">
                    <svg viewBox="0 0 28 28" fill="none">
                        <circle cx="14" cy="14" r="13" stroke="rgba(255,255,255,0.15)" stroke-width="1.5" />
                        <path d="M10 14h8M15 11l3 3-3 3" stroke="rgba(255,255,255,0.75)" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>

                <!-- -- Card 4 -- -->
                <div class="nbap-card" data-s="4">
                    <div class="nbap-bubble">
                        <span class="nbap-num">04</span>
                        <img src="{{ asset('img/blue-4.png') }}" alt="Made Fun and Yummy"
                            onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'nbap-emoji',textContent:'🐻'}))">
                    </div>
                    <div class="nbap-text-wrap">
                        <h3 class="nbap-title">Made Fun<br>&amp; Yummy</h3>
                        <p class="nbap-desc">We turn nutrition into delicious gummies kids love to eat, every single day!
                        </p>
                    </div>
                </div>

                <!-- Arrow 4→5 -->
                <div class="nbap-arr" data-a="4">
                    <svg viewBox="0 0 28 28" fill="none">
                        <circle cx="14" cy="14" r="13" stroke="rgba(255,255,255,0.15)" stroke-width="1.5" />
                        <path d="M10 14h8M15 11l3 3-3 3" stroke="rgba(255,255,255,0.75)" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>

                <!-- -- Card 5 -- -->
                <div class="nbap-card" data-s="5">
                    <div class="nbap-bubble">
                        <span class="nbap-num">05</span>
                        <img src="{{ asset('img/kid.png') }}" alt="Better Nutrition Brighter Future"
                            onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'nbap-emoji',textContent:'💪'}))">
                    </div>
                    <div class="nbap-text-wrap">
                        <h3 class="nbap-title">Better Nutrition,<br>Brighter Future</h3>
                        <p class="nbap-desc">Every NutriBuddy product helps kids grow stronger, smarter &amp; healthier.
                        </p>
                    </div>
                </div>

            </div>
        </div>

        <!-- BADGE BAR -->
        <div class="nbap-badges">
            <div class="nbap-badge">
                <div class="nbap-bico"><img src="{{ asset('img/new-btn-2.png') }}" alt=""></div><span>NO Refined
                    Sugar</span>
            </div>
            <div class="nbap-bsep"></div>
            <div class="nbap-badge">
                <div class="nbap-bico">🧪</div><span>100% Veg.</span>
            </div>
            <div class="nbap-bsep"></div>
            <div class="nbap-badge">
                <div class="nbap-bico">🎲</div><span> FSSAI Certified</span>
            </div>
            <div class="nbap-bsep"></div>
            <div class="nbap-badge">
                <div class="nbap-bico">🛡️</div><span> Safe for kids</span>
            </div>

            <div class="nbap-bsep"></div>
            <div class="nbap-badge nbap-fornon">
                <div class="nbap-bico"><img src="{{ asset('img/new-btn-1.png') }}" alt=""></div><span> No Gelatin — Plant
                    Based</span>
            </div>
        </div>
    </section>


    <section class="aj-trust-section">
        <div class="aj-trust-container">
            <div class="closing-inner about-reveal">

                <h2>
                    TRUSTED & LOVED BY
                    <span class="pop">PARENTS</span>
                </h2>




            </div>
            <!-- TOP PARAGRAPH -->
            <p class="aj-trust-desc">
                We understand that our customers rely on us to provide them with safe, effective, and reliable products.
                That's why we go to great lengths to ensure that every product that bears our name is of the highest
                quality.
                Our team of experts works tirelessly to source the best possible ingredients and to craft formulations that
                are gentle, effective, and backed by science.
            </p>

            <!-- MAIN CONTENT -->
            <div class="aj-trust-flex">

                <!-- LEFT IMAGE GRID -->
                <div class="aj-trust-grid">
                    @for ($image = 1; $image <= 16; $image++)
                        <img src="{{ asset('img/about-trust/last' . $image . '.jpg') }}"
                            alt="NutriBuddy family and product moments" loading="lazy" decoding="async">
                    @endfor
                </div>

                <!-- RIGHT CONTENT -->
                <div class="aj-trust-content">
                    <div class="aj-trust-icon">❤</div>
                    <p class="aj-trust-title">Trusted and Loved By</p>
                    <h2 class="aj-trust-highlight">1Million+</h2>
                    <div class="aj-trust-subtitle">Parents, Kids & Experts</div>
                    <div class="aj-trust-stats">
                        <div class="aj-trust-stat">
                            <strong>500K+</strong>
                            <span>Parents</span>
                        </div>
                        <div class="aj-trust-stat">
                            <strong>4.9★</strong>
                            <span>Rating</span>
                        </div>
                        <div class="aj-trust-stat">
                            <strong>100%</strong>
                            <span>Safe</span>
                        </div>
                    </div>
                </div>

            </div>


        </div>
    </section>

    <div class="newsletter about-reveal">
        <span class="sec-eye">Stay in the Loop</span>
        <h2 class="sec-title">Wellness Tips for Your Little Ones</h2>
        <p class="nl-sub">Join 25,000+ parents getting Ayurvedic parenting tips, exclusive discounts & early product access
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
