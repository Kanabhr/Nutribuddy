@extends('layouts.user-panel')
@section('title', 'Create Child Profile')

@push('styles')
<style>
:root{
  --pk:#FF4D8F;--pkl:#FFD6E8;--pkd:#C0306F;--pu:#7C3AED;--pul:#EDE9FE;
  --g:#00000;--gdk:#00000;--glt:#ffd0ed;--gbg:#F2FFF9;--ghov:#00BE80;
  --or:#FF6B35;--orl:#FFE8DF;--rd:#E53935;--rdl:#FDE8E8;--ye:#FFD600;--yel:#FFFBE0;
  --dk:#0D0020;--bd:#24163f;--mu:#6B6B80;--br:#EAEAEA;
  --bg:#F7F7F9;--wh:#FFFFFF;
  --fh:'Nunito',sans-serif;--fb:'DM Sans',sans-serif;
  --r:12px;--rl:18px;--rp:999px;
  --sh:0 2px 12px rgba(13,0,32,.06);--shh:0 10px 28px rgba(255,77,143,.16);
  --max:1080px;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html{scroll-behavior:smooth;}
body{font-family:var(--fb);background:var(--bg);color:var(--bd);min-height:100vh;font-size:15px;line-height:1.6;}
button{cursor:pointer;font-family:var(--fb);}
input,select{font-family:var(--fb);}

/* ONBOARDING */
.ob-shell{max-width:680px;margin:0 auto;padding:32px 20px 80px;}
@keyframes ou{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:translateY(0);}}
.os{display:none;}
.os.on{display:block;animation:ou .38s ease both;}

/* Step bar */
.osb{display:flex;align-items:center;gap:10px;margin-bottom:28px;}
.ob-badge{font-family:var(--fh);font-size:.62rem;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:var(--pkd);background:var(--pkl);border:1.5px solid var(--pk);border-radius:var(--rp);padding:5px 13px;white-space:nowrap;flex-shrink:0;}
.ob-lines{display:flex;gap:5px;flex:1;}
.ol{flex:1;height:3px;border-radius:99px;background:var(--br);transition:background .4s;}
.ol.done{background:var(--pkd);}.ol.active{background:var(--pk);}
.btn-back{font-family:var(--fh);font-size:.82rem;font-weight:800;color:var(--pk);background:var(--wh);border:2px solid var(--pkl);border-radius:var(--rp);padding:9px 15px;display:flex;align-items:center;gap:4px;flex-shrink:0;transition:all .18s;}
.btn-back:hover{border-color:var(--pk);background:#fff5f9;}

.oh1{font-family:var(--fh);font-size:clamp(1.45rem,4vw,1.85rem);font-weight:800;letter-spacing:-.035em;color:var(--dk);margin-bottom:6px;}
.osub{font-size:.86rem;color:var(--mu);margin-bottom:24px;}

/* Field */
.fl-label{font-family:var(--fh);font-size:.62rem;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:var(--mu);margin-bottom:6px;display:block;}
.nb-i,.nb-s{width:100%;background:var(--wh);border:1.5px solid var(--br);border-radius:var(--r);padding:12px 14px;font-family:var(--fb);font-size:.9rem;font-weight:500;color:var(--dk);outline:none;transition:all .18s;appearance:none;-webkit-appearance:none;}
.nb-i:focus,.nb-s:focus{border-color:var(--pk);box-shadow:0 0 0 3px rgba(255,77,143,.12);}
.nb-i.err,.nb-s.err{border-color:var(--rd)!important;box-shadow:0 0 0 3px rgba(229,57,53,.09)!important;}
.sw{position:relative;}
.sw::after{content:'▾';position:absolute;right:13px;top:50%;transform:translateY(-50%);font-size:.8rem;color:var(--mu);pointer-events:none;}
.fr{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;}
.fc2{display:flex;flex-direction:column;}
.ff{margin-bottom:14px;}
.emsg{font-size:.76rem;color:var(--rd);font-weight:500;margin-top:4px;display:none;align-items:center;gap:4px;}
.emsg.on{display:flex;}
.sec-err{display:none;background:var(--rdl);border:1.5px solid var(--rd);border-radius:var(--r);padding:12px 16px;margin-bottom:16px;font-size:.84rem;color:var(--rd);font-weight:600;align-items:center;gap:8px;}
.sec-err.on{display:flex;}

/* Gender */
.gr-row{display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap;}
.gbtn{display:flex;flex-direction:column;align-items:center;gap:6px;background:var(--wh);border:2px solid var(--br);border-radius:14px;padding:14px 20px;min-width:80px;transition:all .18s;}
.gbtn:hover{border-color:var(--pk);}
.gbtn.sel{border-color:var(--pk);background:#fff5f9;}
.gbtn span:first-child{font-size:1.8rem;}
.gl{font-family:var(--fh);font-size:.74rem;font-weight:600;color:var(--dk);}

/* Phase */
.ppr{display:flex;gap:7px;flex-wrap:wrap;margin-bottom:12px;}
.pp{font-family:var(--fh);font-size:.72rem;font-weight:600;background:var(--wh);border:1.5px solid var(--br);border-radius:var(--rp);padding:7px 14px;transition:all .18s;white-space:nowrap;}
.pp span{display:block;font-size:.6rem;font-weight:400;color:var(--mu);margin-top:1px;}
.pp.sel{background:var(--pk);color:#fff;border-color:var(--pk);}
.pp.sel span{color:rgba(255,255,255,.65);}
.pnote{background:#fff5f9;border-left:3px solid var(--pk);border-radius:10px;padding:12px 16px;font-size:.82rem;color:var(--pkd);line-height:1.55;margin-bottom:20px;}

/* Concern cards */
.ccl{display:flex;flex-direction:column;gap:9px;margin-bottom:22px;}
.cc{display:flex;align-items:center;gap:13px;background:var(--wh);border:1.5px solid var(--br);border-radius:var(--r);padding:14px 18px;transition:all .18s;}
.cc:hover{border-color:var(--pk);box-shadow:0 6px 16px rgba(255,77,143,.12);}
.cc.sel{background:#fff5f9;border-color:var(--pk);}
.ci{font-size:1.45rem;width:34px;text-align:center;flex-shrink:0;}
.cn{font-family:var(--fh);font-size:.88rem;font-weight:700;color:var(--dk);margin-bottom:2px;}
.cde{font-size:.77rem;color:var(--mu);}

/* Diet / picky / life */
.dg{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:18px;}
.dc,.pc,.lc{display:flex;align-items:center;gap:10px;background:var(--wh);border:1.5px solid var(--br);border-radius:var(--r);padding:13px 16px;transition:all .18s;}
.dc:hover,.pc:hover,.lc:hover{border-color:var(--pk);}
.dc.sel,.pc.sel,.lc.sel{background:#fff5f9;border-color:var(--pk);}
.dc span:first-child,.pc span:first-child,.lc span:first-child{font-size:1.25rem;flex-shrink:0;}
.dn2,.pn{font-family:var(--fh);font-size:.85rem;font-weight:600;color:var(--dk);}

/* Goals */
.gcl{display:flex;flex-direction:column;gap:9px;margin-bottom:20px;}
.gc{display:flex;align-items:center;gap:13px;background:var(--wh);border:1.5px solid var(--br);border-radius:var(--r);padding:14px 18px;transition:all .18s;}
.gc:hover{border-color:var(--pk);}
.gc.sel{background:#fff5f9;border-color:var(--pk);}
.gci{font-size:1.3rem;width:32px;text-align:center;flex-shrink:0;}
.gcn{font-family:var(--fh);font-size:.88rem;font-weight:700;color:var(--dk);margin-bottom:2px;}
.gcd{font-size:.76rem;color:var(--mu);}

/* Continue btn */
.step-actions{display:flex;flex-direction:column;gap:10px;margin-top:14px;}
.btn-co{width:100%;font-family:var(--fh);font-size:.92rem;font-weight:900;color:#fff;background:linear-gradient(135deg,var(--pk),var(--pkd));border:none;border-radius:var(--r);padding:15px 24px;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 8px 18px rgba(255,77,143,.22);transition:all .18s;margin-top:6px;}
.btn-co:hover{transform:translateY(-2px);box-shadow:0 12px 24px rgba(255,77,143,.26);}

/* Toast */
.toast{position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(70px);background:var(--gdk);color:#fff;font-family:var(--fh);font-size:.84rem;font-weight:600;border-radius:var(--rp);padding:12px 26px;box-shadow:0 6px 24px rgba(27,67,50,.35);opacity:0;transition:all .34s cubic-bezier(.4,0,.2,1);pointer-events:none;z-index:999;white-space:nowrap;}
.toast.on{opacity:1;transform:translateX(-50%) translateY(0);}

@media(max-width:680px){
  .ob-shell{padding:20px 14px 60px;}
  .fr{grid-template-columns:1fr;}
}
</style>
@endpush

@section('panel-content')

<div id="vEdit">
<main class="ob-shell">

  <!-- STEP 1 -->
  <div class="os on" id="s1">
    <div class="osb">
      <span class="ob-badge">Step 1 of 5</span>
      <div class="ob-lines"><div class="ol active"></div><div class="ol"></div><div class="ol"></div><div class="ol"></div><div class="ol"></div></div>
    </div>
    <h1 class="oh1">Tell us about your child</h1>
    <p class="osub">We'll personalise everything to their exact profile</p>
    <div class="sec-err" id="e1">⚠️ Please fill in all required fields before continuing.</div>

    <div class="gr-row">
      <button class="gbtn sel" data-g="boy" onclick="selG(this)"><span>👦</span><span class="gl">Boy</span></button>
      <button class="gbtn" data-g="girl" onclick="selG(this)"><span>👧</span><span class="gl">Girl</span></button>
      <button class="gbtn" data-g="other" onclick="selG(this)"><span>🧒</span><span class="gl">Other</span></button>
    </div>

    <div class="ff">
      <label class="fl-label">Child's Name <span class="req" style="color:var(--rd);">*</span></label>
      <input class="nb-i" id="oName" type="text" placeholder="e.g. Arjun" oninput="clrE(this,'eName')"/>
      <div class="emsg" id="eName">⚠ Name is required.</div>
    </div>

    <div class="fr">
      <div class="fc2">
        <label class="fl-label">Age <span class="req" style="color:var(--rd);">*</span></label>
        <div class="sw"><select class="nb-s" id="oAge" onchange="autoPhase();clrE(this,'eAge')">
          <option value="">Select age</option>
          <option value="4">4 years</option><option value="5">5 years</option>
          <option value="6">6 years</option><option value="7">7 years</option>
          <option value="8">8 years</option><option value="9">9 years</option>
          <option value="10">10 years</option><option value="11">11 years</option>
          <option value="12">12 years</option><option value="13">13 years</option>
          <option value="14">14 years</option><option value="15">15 years</option>
          <option value="16">16 years</option><option value="17">17 years</option>
        </select></div>
        <div class="emsg" id="eAge">⚠ Please select an age.</div>
      </div>
      <div class="fc2">
        <label class="fl-label">Birth Month</label>
        <div class="sw"><select class="nb-s" id="oMonth">
          <option>January</option><option>February</option><option>March</option>
          <option>April</option><option>May</option><option>June</option>
          <option>July</option><option>August</option><option>September</option>
          <option>October</option><option>November</option><option>December</option>
        </select></div>
      </div>
    </div>

    <div class="fr">
      <div class="fc2">
        <label class="fl-label">Height (cm) <span class="req" style="color:var(--rd);">*</span></label>
        <input class="nb-i" id="oHeight" type="number" placeholder="106" min="50" max="220" oninput="clrE(this,'eHeight')"/>
        <div class="emsg" id="eHeight">⚠ Enter valid height (50–220 cm).</div>
      </div>
      <div class="fc2">
        <label class="fl-label">Weight (kg) <span class="req" style="color:var(--rd);">*</span></label>
        <input class="nb-i" id="oWeight" type="number" placeholder="18" min="5" max="150" oninput="clrE(this,'eWeight')"/>
        <div class="emsg" id="eWeight">⚠ Enter valid weight (5–150 kg).</div>
      </div>
    </div>

    <div class="fr">
      <div class="fc2">
        <label class="fl-label">City</label>
        <input class="nb-i" id="oCity" type="text" placeholder="Ahmedabad"/>
      </div>
      <div class="fc2">
        <label class="fl-label">Region <span class="req" style="color:var(--rd);">*</span></label>
        <div class="sw"><select class="nb-s" id="oRegion" onchange="clrE(this,'eRegion')">
          <option value="">Select region</option>
          <option>Gujarat / West India</option>
          <option>Maharashtra / West India</option>
          <option>Punjab / North India</option>
          <option>Tamil Nadu / South India</option>
          <option>Karnataka / South India</option>
          <option>West Bengal / East India</option>
          <option>Rajasthan / North India</option>
          <option>Uttar Pradesh / North India</option>
          <option>Madhya Pradesh / Central India</option>
        </select></div>
        <div class="emsg" id="eRegion">⚠ Please select a region.</div>
      </div>
    </div>

    <div class="ppr">
      <button class="pp sel" data-p="1" onclick="selP(this)">Phase 1<span>4–6 yrs</span></button>
      <button class="pp" data-p="2" onclick="selP(this)">Phase 2<span>7–9 yrs</span></button>
      <button class="pp" data-p="3" onclick="selP(this)">Phase 3<span>10–12 yrs</span></button>
      <button class="pp" data-p="4" onclick="selP(this)">Phase 4<span>13–15 yrs</span></button>
      <button class="pp" data-p="5" onclick="selP(this)">Phase 5<span>16–17 yrs</span></button>
    </div>
    <div class="pnote" id="pNote"><strong>Phase 1 — Early childhood (4–6 yrs)</strong><br>Calcium bone foundation · Iron for brain · Probiotics for gut immunity · Soft textures, mild flavours</div>

    <div class="step-actions">
      <button class="btn-co" onclick="goTo(2)">Continue <span class="arr">→</span></button>
    </div>
  </div>

  <!-- STEP 2 -->
  <div class="os" id="s2">
    <div class="osb">
      <button class="btn-back" onclick="goTo(1)">← Back</button>
      <span class="ob-badge">Step 2 of 5</span>
      <div class="ob-lines" style="flex:1"><div class="ol done"></div><div class="ol active"></div><div class="ol"></div><div class="ol"></div><div class="ol"></div></div>
    </div>
    <h1 class="oh1">What worries you most?</h1>
    <p class="osub">Select all that apply</p>
    <div class="sec-err" id="e2">⚠️ Please select at least one concern to continue.</div>
    <div class="ccl">
      <div class="cc" onclick="togCC(this)"><span class="ci">🤧</span><div><div class="cn">Gets sick too often</div><div class="cde">Frequent colds, cough, fever — low immunity</div></div></div>
      <div class="cc" onclick="togCC(this)"><span class="ci">🧠</span><div><div class="cn">Focus &amp; memory issues</div><div class="cde">Difficulty concentrating, poor academics</div></div></div>
      <div class="cc" onclick="togCC(this)"><span class="ci">📏</span><div><div class="cn">Height / weight concerns</div><div class="cde">Not growing as expected for age</div></div></div>
      <div class="cc" onclick="togCC(this)"><span class="ci">🫁</span><div><div class="cn">Digestive issues</div><div class="cde">Constipation, bloating, irregular stomach</div></div></div>
      <div class="cc" onclick="togCC(this)"><span class="ci">⚡</span><div><div class="cn">Low energy / always tired</div><div class="cde">Fatigue, lacks enthusiasm</div></div></div>
      <div class="cc" onclick="togCC(this)"><span class="ci">✨</span><div><div class="cn">Skin &amp; hair issues</div><div class="cde">Dry skin, rashes, eczema</div></div></div>
      <div class="cc" onclick="togCC(this)"><span class="ci">😴</span><div><div class="cn">Sleep problems</div><div class="cde">Trouble falling or staying asleep</div></div></div>
    </div>
    <div class="step-actions">
      <button class="btn-co" onclick="goTo(3)">Continue <span class="arr">→</span></button>
    </div>
  </div>

  <!-- STEP 3 -->
  <div class="os" id="s3">
    <div class="osb">
      <button class="btn-back" onclick="goTo(2)">← Back</button>
      <span class="ob-badge">Step 3 of 5</span>
      <div class="ob-lines" style="flex:1"><div class="ol done"></div><div class="ol done"></div><div class="ol active"></div><div class="ol"></div><div class="ol"></div></div>
    </div>
    <h1 class="oh1">What does <span id="nd3">your child</span> eat?</h1>
    <div class="sec-err" id="e3">⚠️ Please select a diet type and picky level.</div>
    <span style="font-family:var(--fh);font-size:.62rem;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:var(--mu);margin-bottom:8px;display:block;">Diet Type <span class="req" style="color:var(--rd);">*</span></span>
    <div class="dg">
      <div class="dc" onclick="selO(this,'.dc','sel')"><span>🌱</span><span class="dn2">Pure vegetarian</span></div>
      <div class="dc" onclick="selO(this,'.dc','sel')"><span>🥚</span><span class="dn2">Veg + eggs</span></div>
      <div class="dc" onclick="selO(this,'.dc','sel')"><span>🍗</span><span class="dn2">Non-vegetarian</span></div>
      <div class="dc" onclick="selO(this,'.dc','sel')"><span>🙏</span><span class="dn2">Jain diet</span></div>
    </div>
    <span style="font-family:var(--fh);font-size:.62rem;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:var(--mu);margin-bottom:8px;display:block;">Picky Level <span class="req" style="color:var(--rd);">*</span></span>
    <div class="pl">
      <div class="pc" onclick="selO(this,'.pc','sel')"><span>😋</span><span class="pn">Eats most things</span></div>
      <div class="pc" onclick="selO(this,'.pc','sel')"><span>🤔</span><span class="pn">Slightly picky</span></div>
      <div class="pc" onclick="selO(this,'.pc','sel')"><span>😤</span><span class="pn">Very picky eater</span></div>
    </div>
    <span style="font-family:var(--fh);font-size:.62rem;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:var(--mu);margin-bottom:8px;display:block;">Allergies</span>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px;">
      <button class="ptl sel" style="font-family:var(--fh);font-size:.72rem;font-weight:600;background:var(--wh);border:1.5px solid var(--br);border-radius:var(--rp);padding:7px 16px;transition:all .18s;" onclick="togP(this)">None</button>
      <button class="ptl" style="font-family:var(--fh);font-size:.72rem;font-weight:600;background:var(--wh);border:1.5px solid var(--br);border-radius:var(--rp);padding:7px 16px;transition:all .18s;" onclick="togP(this)">Dairy</button>
      <button class="ptl" style="font-family:var(--fh);font-size:.72rem;font-weight:600;background:var(--wh);border:1.5px solid var(--br);border-radius:var(--rp);padding:7px 16px;transition:all .18s;" onclick="togP(this)">Gluten</button>
      <button class="ptl" style="font-family:var(--fh);font-size:.72rem;font-weight:600;background:var(--wh);border:1.5px solid var(--br);border-radius:var(--rp);padding:7px 16px;transition:all .18s;" onclick="togP(this)">Nuts</button>
      <button class="ptl" style="font-family:var(--fh);font-size:.72rem;font-weight:600;background:var(--wh);border:1.5px solid var(--br);border-radius:var(--rp);padding:7px 16px;transition:all .18s;" onclick="togP(this)">Soy</button>
      <button class="ptl" style="font-family:var(--fh);font-size:.72rem;font-weight:600;background:var(--wh);border:1.5px solid var(--br);border-radius:var(--rp);padding:7px 16px;transition:all .18s;" onclick="togP(this)">Eggs</button>
    </div>
    <div class="step-actions">
      <button class="btn-co" onclick="goTo(4)">Continue <span class="arr">→</span></button>
    </div>
  </div>

  <!-- STEP 4 -->
  <div class="os" id="s4">
    <div class="osb">
      <button class="btn-back" onclick="goTo(3)">← Back</button>
      <span class="ob-badge">Step 4 of 5</span>
      <div class="ob-lines" style="flex:1"><div class="ol done"></div><div class="ol done"></div><div class="ol done"></div><div class="ol active"></div><div class="ol"></div></div>
    </div>
    <h1 class="oh1"><span id="nd4">Your child</span>'s Daily Lifestyle</h1>
    <div class="sec-err" id="e4">⚠️ Please select an activity level.</div>
    <span style="font-family:var(--fh);font-size:.62rem;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:var(--mu);margin-bottom:8px;display:block;">Activity Level <span class="req" style="color:var(--rd);">*</span></span>
    <div class="ll" style="display:flex;flex-direction:column;gap:8px;margin-bottom:18px;">
      <div class="lc" onclick="selO(this,'.lc','sel')"><span>🏃</span><div><div class="lcn" style="font-family:var(--fh);font-size:.86rem;font-weight:700;color:var(--dk);margin-bottom:2px;">Very active</div><div class="lcd" style="font-size:.75rem;color:var(--mu);">Outdoor sports, runs around a lot</div></div></div>
      <div class="lc" onclick="selO(this,'.lc','sel')"><span>🚶</span><div><div class="lcn" style="font-family:var(--fh);font-size:.86rem;font-weight:700;color:var(--dk);margin-bottom:2px;">Moderately active</div><div class="lcd" style="font-size:.75rem;color:var(--mu);">Some play, PE at school</div></div></div>
      <div class="lc" onclick="selO(this,'.lc','sel')"><span>📱</span><div><div class="lcn" style="font-family:var(--fh);font-size:.86rem;font-weight:700;color:var(--dk);margin-bottom:2px;">Mostly sedentary</div><div class="lcd" style="font-size:.75rem;color:var(--mu);">Screen time, indoor activities</div></div></div>
    </div>
    <div class="step-actions">
      <button class="btn-co" onclick="goTo(5)" style="margin-top:16px">Continue <span class="arr">→</span></button>
    </div>
  </div>

  <!-- STEP 5 -->
  <div class="os" id="s5">
    <div class="osb">
      <button class="btn-back" onclick="goTo(4)">← Back</button>
      <span class="ob-badge">Step 5 of 5</span>
      <div class="ob-lines" style="flex:1"><div class="ol done"></div><div class="ol done"></div><div class="ol done"></div><div class="ol done"></div><div class="ol active"></div></div>
    </div>
    <h1 class="oh1">3 months from now…</h1>
    <p class="osub">What does success look like for <span id="nd5">your child</span>?</p>
    <div class="sec-err" id="e5">⚠️ Please select at least one goal.</div>
    <div class="gcl">
      <div class="gc" onclick="togG(this)"><span class="gci">🛡️</span><div><div class="gcn">Falls sick less often</div><div class="gcd">Build strong lasting immunity</div></div></div>
      <div class="gc" onclick="togG(this)"><span class="gci">🧠</span><div><div class="gcn">Better focus and school performance</div><div class="gcd">Sharper memory, calm sustained energy</div></div></div>
      <div class="gc" onclick="togG(this)"><span class="gci">📏</span><div><div class="gcn">Healthy weight gain and growth</div><div class="gcd">Reach target height, stronger bones</div></div></div>
      <div class="gc" onclick="togG(this)"><span class="gci">⚡</span><div><div class="gcn">More energy and stamina</div><div class="gcd">Active, enthusiastic, less tired</div></div></div>
      <div class="gc" onclick="togG(this)"><span class="gci">🌿</span><div><div class="gcn">Balanced overall nutrition</div><div class="gcd">Fill all gaps, build lifelong food foundation</div></div></div>
    </div>
    <div class="step-actions">
      <button class="btn-co" onclick="goTo(6)">Generate <span id="nd5c">Plan</span> <span class="arr">→</span></button>
    </div>
  </div>

</main>
</div>

<div class="toast" id="toast"></div>

@push('scripts')
<script>
const pDesc={'1':'<strong>Phase 1 — Early childhood (4–6 yrs)</strong><br>Calcium bone foundation · Iron for brain · Probiotics for gut immunity · Soft textures, mild flavours','2':'<strong>Phase 2 — Middle childhood (7–9 yrs)</strong><br>Omega-3 for brain growth · Zinc for immunity · Complex carbs for energy · Variety building','3':'<strong>Phase 3 — Late childhood (10–12 yrs)</strong><br>Iron for girls · Protein for growth spurts · Calcium peak window · Sports nutrition','4':'<strong>Phase 4 — Early teen (13–15 yrs)</strong><br>Hormonal support · B-vitamins for energy · Protein for muscle · Iron replacement','5':'<strong>Phase 5 — Late teen (16–17 yrs)</strong><br>Bone density final window · Stress & focus support · Heart health foundations'};

function selG(b){document.querySelectorAll('.gbtn').forEach(x=>x.classList.remove('sel'));b.classList.add('sel');}
function selP(b){document.querySelectorAll('.pp').forEach(x=>x.classList.remove('sel'));b.classList.add('sel');document.getElementById('pNote').innerHTML=pDesc[b.dataset.p]||'';}
function autoPhase(){const a=parseInt(document.getElementById('oAge').value);const p=a<=6?'1':a<=9?'2':a<=12?'3':a<=15?'4':'5';const pill=document.querySelector(`.pp[data-p="${p}"]`);if(pill)selP(pill);}
function selO(el,sel,cls){document.querySelectorAll(sel).forEach(c=>c.classList.remove(cls));el.classList.add(cls);}
function togCC(el){el.classList.toggle('sel');}
function togG(el){el.classList.toggle('sel');}
function togP(el){el.classList.toggle('sel');}
function markE(iId,eId){document.getElementById(iId).classList.add('err');document.getElementById(eId).classList.add('on');}
function clrE(el,eId){if(el.classList)el.classList.remove('err');const e=document.getElementById(eId);if(e)e.classList.remove('on');}

const vmap={1:v1,2:v2,3:v3,4:v4,5:v5};
function v1(){
  let ok=true;
  const n=document.getElementById('oName').value.trim();
  if(!n){markE('oName','eName');ok=false;}else clrE(document.getElementById('oName'),'eName');
  const a=document.getElementById('oAge').value;
  if(!a){markE('oAge','eAge');ok=false;}else clrE(document.getElementById('oAge'),'eAge');
  const h=parseFloat(document.getElementById('oHeight').value);
  if(!h||h<50||h>220){markE('oHeight','eHeight');ok=false;}else clrE(document.getElementById('oHeight'),'eHeight');
  const w=parseFloat(document.getElementById('oWeight').value);
  if(!w||w<5||w>150){markE('oWeight','eWeight');ok=false;}else clrE(document.getElementById('oWeight'),'eWeight');
  const r=document.getElementById('oRegion').value;
  if(!r){markE('oRegion','eRegion');ok=false;}else clrE(document.getElementById('oRegion'),'eRegion');
  document.getElementById('e1').classList.toggle('on',!ok);
  if(!ok)document.getElementById('e1').scrollIntoView({behavior:'smooth',block:'nearest'});
  return ok;
}
function v2(){const ok=!!document.querySelector('#s2 .cc.sel');document.getElementById('e2').classList.toggle('on',!ok);return ok;}
function v3(){const ok=!!document.querySelector('.dc.sel')&&!!document.querySelector('.pc.sel');document.getElementById('e3').classList.toggle('on',!ok);return ok;}
function v4(){const ok=!!document.querySelector('.lc.sel');document.getElementById('e4').classList.toggle('on',!ok);return ok;}
function v5(){const ok=!!document.querySelector('#s5 .gc.sel');document.getElementById('e5').classList.toggle('on',!ok);return ok;}

function goTo(n){
  const cur=parseInt(document.querySelector('.os.on')?.id.slice(1));
  if(n>cur&&vmap[cur]&&!vmap[cur]())return;
  const name=(document.getElementById('oName').value.trim())||'your child';
  ['nd3','nd4','nd5','nd5c'].forEach(id=>{const el=document.getElementById(id);if(el)el.textContent=name;});
  document.querySelectorAll('.os').forEach(s=>s.classList.remove('on'));
  document.getElementById('s'+n).classList.add('on');
  window.scrollTo({top:0,behavior:'smooth'});
}

function saveReturn(){
  const name=(document.getElementById('oName').value.trim())||'your child';
  const age=document.getElementById('oAge').value||'5';
  const h=document.getElementById('oHeight').value||'106';
  const w=document.getElementById('oWeight').value||'18';
  const region=document.getElementById('oRegion').value||'West India (Gujarat)';
  const g=document.querySelector('.gbtn.sel')?.dataset.g||'boy';
  const diet=document.querySelector('.dc.sel .dn2')?.textContent||'Pure vegetarian';
  const picky=document.querySelector('.pc.sel .pn')?.textContent||'Slightly picky';
  const act=document.querySelector('.lc.sel .lcn')?.textContent||'Moderately active';
  const alg=[...document.querySelectorAll('.pt .ptl.sel')].map(p=>p.textContent);
  const allergies=alg.length?alg.join(', '):'None';

  const btn = document.querySelector('.btn-co.or') || document.querySelector('.btn-co');
  btn.innerHTML = '⏳ Generating Diet Plan (may take 20s)...';
  btn.disabled = true;

  fetch("{{ route('child-profile.store') }}", {
      method: "POST",
      headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": "{{ csrf_token() }}"
      },
      body: JSON.stringify({
          name: name,
          age: age,
          sex: g,
          height: h,
          weight: w,
          region: region,
          diet: diet,
          picky: picky,
          allergies: allergies,
          activity: act
      })
  }).then(res => res.json()).then(data => {
      showToast('✓ '+name+"'s plan generated successfully!");
      window.location.href = "{{ route('child-profile') }}";
  }).catch(e => {
      console.error(e);
      btn.innerHTML = '✓ Generate Plan →';
      btn.disabled = false;
      showToast('⚠️ Error generating plan');
  });
}

function showToast(msg){const t=document.getElementById('toast');t.textContent=msg;t.classList.add('on');setTimeout(()=>t.classList.remove('on'),3400);}

// Auto-select first phase
window.addEventListener('DOMContentLoaded',()=>{selP(document.querySelector('.pp'));});
</script>
@endpush

@endsection
