<?php require_once __DIR__.'/../lang.php'; ?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang=='ar'?'rtl':'ltr' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FakeGuard — <?= $pageTitle ?? 'Dashboard' ?></title>

<!-- ══ PWA META TAGS ══════════════════════════════════════════ -->
<meta name="application-name" content="FakeGuard">
<meta name="description" content="AI-powered fake news detection and fact-checking. Verify before you share.">
<meta name="theme-color" content="#e63232">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="FakeGuard">
<meta name="msapplication-TileColor" content="#0d0d0d">
<meta name="msapplication-TileImage" content="/icons/icon-144.png">

<!-- Open Graph (makes WhatsApp previews look great) -->
<meta property="og:type"        content="website">
<meta property="og:title"       content="FakeGuard — Fake News Detector">
<meta property="og:description" content="AI-powered fake news detection. Verify before you share.">
<meta property="og:image"       content="/icons/icon-512.png">
<meta name="twitter:card"       content="summary">
<meta name="twitter:title"      content="FakeGuard">
<meta name="twitter:image"      content="/icons/icon-192.png">

<!-- PWA Manifest + Icons -->
<link rel="manifest" href="/manifest.json">
<link rel="icon" type="image/png" sizes="32x32"  href="/icons/favicon-32.png">
<link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png">
<link rel="apple-touch-icon" sizes="152x152" href="/icons/icon-152.png">
<link rel="apple-touch-icon" sizes="192x192" href="/icons/icon-192.png">
<link rel="apple-touch-icon" sizes="512x512" href="/icons/icon-512.png">
<!-- ══ END PWA ════════════════════════════════════════════════ -->

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Oswald:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;600&display=swap" rel="stylesheet">
<style>
:root {
  --bg:#0d0d0d; --surface:#141414; --card:#1a1a1a; --border:#2a2a2a;
  --accent:#e63232; --accent2:#ff6b35; --green:#00c853; --yellow:#ffd600;
  --blue:#2979ff; --text:#f0f0f0; --muted:#777; --paper:#f5f0e8;
}
*{margin:0;padding:0;box-sizing:border-box;}
*{cursor:none !important;}
#cursor{width:12px;height:12px;background:var(--accent);border-radius:50%;position:fixed;pointer-events:none;z-index:99999;transition:transform 0.1s;mix-blend-mode:difference;}
#cursor-ring{width:36px;height:36px;border:1px solid rgba(230,50,50,0.5);border-radius:50%;position:fixed;pointer-events:none;z-index:99998;transition:all 0.15s ease;}
body{background:var(--bg);color:var(--text);font-family:'Source Sans 3',sans-serif;min-height:100vh;overflow-x:hidden;}
body::after{content:'';position:fixed;inset:0;background:repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(0,0,0,0.03) 2px,rgba(0,0,0,0.03) 4px);pointer-events:none;z-index:9997;}
body::before{content:'';position:fixed;inset:0;pointer-events:none;z-index:0;opacity:0.4;}
.topbar{background:#111;padding:6px 32px;display:flex;align-items:center;justify-content:space-between;position:relative;z-index:200;}
.topbar-left{font-family:'Oswald',sans-serif;font-size:0.7rem;font-weight:600;letter-spacing:3px;color:#fff;display:flex;align-items:center;gap:8px;}
.topbar-right{font-family:'Oswald',sans-serif;font-size:0.65rem;letter-spacing:2px;color:rgba(255,255,255,0.85);}
.live-dot{display:inline-block;width:7px;height:7px;background:var(--accent);border-radius:50%;animation:blink 1s infinite;}
@keyframes blink{0%,100%{opacity:1}50%{opacity:0.2}}
.masthead{background:var(--bg);border-bottom:3px solid var(--accent);padding:18px 32px;display:flex;align-items:center;justify-content:space-between;position:relative;z-index:200;}
.masthead-logo{display:flex;flex-direction:column;align-items:flex-start;text-decoration:none;}
.masthead-logo .logo-main{font-family:'Playfair Display',serif;font-size:2.8rem;font-weight:900;color:var(--text);line-height:1;letter-spacing:-1px;}
.masthead-logo .logo-main span{color:var(--accent);font-style:italic;}
.masthead-logo .logo-sub{font-family:'Oswald',sans-serif;font-size:0.6rem;letter-spacing:4px;color:var(--muted);margin-top:3px;text-transform:uppercase;}
.masthead-stats{display:flex;gap:0;align-items:center;}
.mstat{text-align:center;padding:0 24px;border-left:1px solid var(--border);}
.mstat:first-child{border-left:none;}
.mstat-val{font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:700;line-height:1;}
.mstat-val.red{color:var(--accent);} .mstat-val.green{color:var(--green);} .mstat-val.yellow{color:var(--yellow);}
.mstat-label{font-family:'Oswald',sans-serif;font-size:0.55rem;letter-spacing:2px;color:var(--muted);text-transform:uppercase;margin-top:2px;}
.ticker-wrap{background:#111;border-bottom:1px solid var(--border);padding:10px 0;overflow:hidden;position:relative;z-index:200;}
.ticker-label{position:absolute;left:0;top:0;bottom:0;background:var(--accent);display:flex;align-items:center;padding:0 18px;font-family:'Oswald',sans-serif;font-size:0.7rem;font-weight:700;letter-spacing:2px;color:#fff;z-index:10;white-space:nowrap;}
.ticker-label::after{content:'';position:absolute;right:-12px;top:0;bottom:0;width:0;border-style:solid;border-width:20px 0 20px 12px;border-color:transparent transparent transparent var(--accent);}
.ticker-content{margin-left:140px;overflow:hidden;}
.ticker-inner{display:flex;gap:80px;animation:tickerAnim 30s linear infinite;white-space:nowrap;}
.ticker-item{font-family:'Oswald',sans-serif;font-size:0.78rem;letter-spacing:1px;color:var(--text);display:flex;align-items:center;gap:10px;}
.verdict-tag{font-size:0.6rem;font-weight:700;padding:2px 7px;border-radius:3px;}
.verdict-tag.fake{background:var(--accent);color:#fff;} .verdict-tag.real{background:var(--green);color:#000;} .verdict-tag.misleading{background:var(--yellow);color:#000;}
@keyframes tickerAnim{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}
nav{position:sticky;top:0;z-index:500;background:rgba(13,13,13,0.97);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:0 32px;display:flex;align-items:center;height:52px;}
.nav-links{display:flex;gap:0;flex:1;}
.nav-links a{color:var(--muted);text-decoration:none;font-family:'Oswald',sans-serif;font-size:0.8rem;font-weight:500;letter-spacing:2px;padding:0 20px;height:52px;display:flex;align-items:center;border-bottom:3px solid transparent;transition:all 0.2s;text-transform:uppercase;position:relative;}
.nav-links a::before{content:'';position:absolute;bottom:-1px;left:0;right:0;height:3px;background:var(--accent);transform:scaleX(0);transition:transform 0.2s;}
.nav-links a:hover{color:var(--text);}
.nav-links a:hover::before{transform:scaleX(1);}
.nav-links a.active{color:var(--text);}
.nav-links a.active::before{transform:scaleX(1);}
.nav-right{display:flex;align-items:center;gap:12px;margin-left:auto;}
.nav-badge{background:var(--accent);color:#fff;font-family:'Oswald',sans-serif;font-size:0.6rem;font-weight:700;padding:4px 12px;letter-spacing:2px;}
.nav-time{font-family:'Oswald',sans-serif;font-size:0.7rem;color:var(--muted);letter-spacing:1px;}
#bgHeadlines{position:fixed;inset:0;pointer-events:none;z-index:1;overflow:hidden;opacity:0.03;}
.floating-headline{position:absolute;color:#fff;font-family:'Playfair Display',serif;font-weight:900;white-space:nowrap;animation:floatUp var(--dur) linear infinite;animation-delay:var(--delay);}
@keyframes floatUp{0%{transform:translateY(110vh) rotate(var(--rot));opacity:0;}5%{opacity:1;}95%{opacity:1;}100%{transform:translateY(-150px) rotate(var(--rot));opacity:0;}}
.wrapper{position:relative;z-index:10;max-width:1300px;margin:0 auto;padding:40px 32px;}
.page-header{margin-bottom:36px;padding-bottom:20px;border-bottom:1px solid var(--border);display:flex;align-items:flex-end;justify-content:space-between;}
.page-header h1{font-family:'Playfair Display',serif;font-weight:900;font-size:2.5rem;line-height:1;color:var(--text);}
.page-header h1 em{color:var(--accent);font-style:italic;}
.page-header p{font-family:'Oswald',sans-serif;color:var(--muted);font-size:0.65rem;letter-spacing:3px;margin-top:6px;}
.card{background:var(--card);border:1px solid var(--border);border-radius:4px;overflow:hidden;position:relative;}
.card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--accent),transparent);}
.card-header{padding:16px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.card-title{font-family:'Oswald',sans-serif;font-size:0.8rem;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:var(--text);}
.card-body{padding:24px;}
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:28px;}
.stat{background:var(--card);border:1px solid var(--border);border-radius:4px;padding:20px 24px;position:relative;overflow:hidden;transition:transform 0.2s,border-color 0.2s;}
.stat:hover{transform:translateY(-3px);border-color:var(--accent);}
.stat::after{content:'';position:absolute;top:0;left:0;right:0;height:3px;}
.stat.r::after{background:var(--accent);} .stat.g::after{background:var(--green);} .stat.y::after{background:var(--yellow);} .stat.b::after{background:var(--blue);}
.stat-label{font-family:'Oswald',sans-serif;font-size:0.6rem;color:var(--muted);letter-spacing:3px;text-transform:uppercase;margin-bottom:10px;}
.stat-val{font-family:'Playfair Display',serif;font-size:3rem;font-weight:900;line-height:1;}
.stat.r .stat-val{color:var(--accent);} .stat.g .stat-val{color:var(--green);} .stat.y .stat-val{color:var(--yellow);} .stat.b .stat-val{color:var(--blue);}
.tbl-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
thead tr{border-bottom:2px solid var(--accent);}
th{padding:12px 18px;text-align:left;font-family:'Oswald',sans-serif;font-size:0.62rem;letter-spacing:2px;color:var(--muted);text-transform:uppercase;}
td{padding:14px 18px;font-size:0.85rem;border-bottom:1px solid rgba(42,42,42,0.8);vertical-align:middle;}
tbody tr:last-child td{border-bottom:none;}
tbody tr{transition:background 0.15s;}
tbody tr:hover{background:rgba(230,50,50,0.03);}
.td-title{max-width:280px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.badge{display:inline-block;padding:3px 10px;font-family:'Oswald',sans-serif;font-size:0.62rem;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;border-radius:2px;}
.b-fake{background:rgba(230,50,50,0.15);color:var(--accent);border:1px solid rgba(230,50,50,0.4);}
.b-real{background:rgba(0,200,83,0.12);color:var(--green);border:1px solid rgba(0,200,83,0.4);}
.b-misleading{background:rgba(255,214,0,0.12);color:var(--yellow);border:1px solid rgba(255,214,0,0.4);}
.b-unverified{background:rgba(119,119,119,0.1);color:var(--muted);border:1px solid rgba(119,119,119,0.3);}
.b-pending{background:rgba(255,214,0,0.12);color:var(--yellow);border:1px solid rgba(255,214,0,0.4);}
.b-reviewed{background:rgba(0,200,83,0.12);color:var(--green);border:1px solid rgba(0,200,83,0.4);}
.b-dismissed{background:rgba(119,119,119,0.1);color:var(--muted);border:1px solid rgba(119,119,119,0.3);}
.b-admin{background:rgba(230,50,50,0.15);color:var(--accent);border:1px solid rgba(230,50,50,0.4);}
.b-fact_checker{background:rgba(41,121,255,0.12);color:var(--blue);border:1px solid rgba(41,121,255,0.4);}
.b-user{background:rgba(119,119,119,0.1);color:var(--muted);border:1px solid rgba(119,119,119,0.3);}
.cbar-wrap{display:flex;align-items:center;gap:8px;}
.cbar{flex:1;height:3px;background:var(--border);border-radius:0;min-width:50px;}
.cbar-fill{height:100%;}
.cbar-num{font-family:'Oswald',sans-serif;font-size:0.72rem;color:var(--muted);min-width:28px;text-align:right;}
.form-group{margin-bottom:18px;}
.form-group label{display:block;font-family:'Oswald',sans-serif;font-size:0.62rem;letter-spacing:2px;color:var(--muted);text-transform:uppercase;margin-bottom:8px;}
.form-group input,.form-group select,.form-group textarea{width:100%;background:#111;border:1px solid var(--border);border-bottom:2px solid var(--border);color:var(--text);font-family:'Source Sans 3',sans-serif;font-size:0.9rem;padding:10px 14px;border-radius:2px;outline:none;transition:border-color 0.2s;}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-bottom-color:var(--accent);background:#141414;}
.form-group textarea{resize:vertical;min-height:90px;}
.form-group select option{background:#1a1a1a;}
.btn{display:inline-block;padding:10px 24px;font-family:'Oswald',sans-serif;font-size:0.78rem;font-weight:600;letter-spacing:2px;text-transform:uppercase;cursor:pointer;border:none;transition:all 0.2s;text-decoration:none;border-radius:2px;position:relative;overflow:hidden;}
.btn-primary{background:var(--accent);color:#fff;}
.btn-ghost{background:transparent;color:var(--muted);border:1px solid var(--border);}
.btn-ghost:hover{border-color:var(--accent);color:var(--text);}
.btn-green{background:var(--green);color:#000;}
.alert{padding:14px 18px;border-radius:2px;font-size:0.85rem;margin-bottom:20px;border-left:4px solid;}
.alert-success{background:rgba(0,200,83,0.08);border-color:var(--green);color:var(--green);}
.alert-error{background:rgba(230,50,50,0.08);border-color:var(--accent);color:var(--accent);}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
.mt-20{margin-top:20px;} .mb-20{margin-bottom:20px;}
.text-muted{color:var(--muted);} .text-sm{font-size:0.78rem;}
.detail-row{display:flex;gap:16px;padding:12px 0;border-bottom:1px solid rgba(42,42,42,0.6);font-size:0.85rem;}
.detail-row:last-child{border-bottom:none;}
.detail-label{font-family:'Oswald',sans-serif;color:var(--muted);min-width:140px;font-size:0.65rem;letter-spacing:2px;text-transform:uppercase;padding-top:2px;}
/* LANG SWITCHER */
.lang-switcher{position:relative;margin-right:8px;}
.lang-btn{background:transparent;border:1px solid var(--border);color:var(--muted);font-family:'Oswald',sans-serif;font-size:0.72rem;letter-spacing:1px;padding:5px 12px;border-radius:2px;cursor:pointer !important;transition:all 0.2s;}
.lang-btn:hover{border-color:var(--accent);color:var(--text);}
.lang-dropdown{display:none;position:absolute;top:110%;right:0;background:#1a1a1a;border:1px solid var(--border);border-radius:4px;min-width:160px;z-index:9999;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,0.4);}
.lang-dropdown a{display:block;padding:10px 16px;font-family:'Oswald',sans-serif;font-size:0.78rem;letter-spacing:1px;color:var(--muted) !important;text-decoration:none;transition:all 0.15s;border-bottom:1px solid var(--border);}
.lang-dropdown a:last-child{border-bottom:none;}
.lang-dropdown a:hover{background:rgba(230,50,50,0.08);color:var(--text) !important;padding-left:22px;}
.lang-dropdown.open{display:block;}
/* MUSIC */
@keyframes pulse{0%{box-shadow:0 0 0 0 rgba(230,50,50,0.7);}70%{box-shadow:0 0 0 14px rgba(230,50,50,0);}100%{box-shadow:0 0 0 0 rgba(230,50,50,0);}}
#musicBtn{position:fixed;bottom:28px;right:28px;z-index:9999;background:var(--accent);color:#fff;border:none;width:56px;height:56px;border-radius:50%;font-size:1.3rem;cursor:pointer !important;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 24px rgba(230,50,50,0.5);animation:pulse 2s infinite;transition:transform 0.2s;}
#musicBtn:hover{transform:scale(1.1);}
/* MAGAZINE */
.magazine-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:1px;background:var(--border);border:1px solid var(--border);border-radius:4px;overflow:hidden;margin-bottom:32px;}
.mag-card{background:var(--card);padding:24px;position:relative;transition:background 0.2s;display:flex;flex-direction:column;gap:10px;}
.mag-card:hover{background:#1e1e1e;}
.mag-card-top{display:flex;align-items:center;justify-content:space-between;}
.mag-cat{font-family:'Oswald',sans-serif;font-size:0.6rem;font-weight:600;letter-spacing:2px;text-transform:uppercase;padding:3px 8px;border-radius:2px;}
.mag-num{font-family:'Oswald',sans-serif;font-size:0.65rem;color:var(--muted);letter-spacing:1px;}
.mag-title{font-family:'Playfair Display',serif;font-size:1rem;font-weight:700;line-height:1.4;color:var(--text);text-decoration:none;display:block;}
.mag-title:hover{color:var(--accent);}
.mag-meta{display:flex;align-items:center;justify-content:space-between;margin-top:auto;}
.mag-source{font-size:0.7rem;color:var(--muted);font-family:'Oswald',sans-serif;letter-spacing:1px;}
.mag-card-featured{grid-column:span 2;}
.mag-card-featured .mag-title{font-size:1.5rem;line-height:1.3;}
.mag-verdict-bar{height:3px;position:absolute;bottom:0;left:0;right:0;}
/* CHART */
.chart-container{padding:24px;}
.chart-bar-group{display:flex;flex-direction:column;gap:14px;}
.chart-bar-item{display:flex;align-items:center;gap:12px;}
.chart-bar-label{font-family:'Oswald',sans-serif;font-size:0.7rem;letter-spacing:1px;color:var(--text);min-width:90px;text-transform:uppercase;}
.chart-bar-track{flex:1;height:6px;background:var(--border);border-radius:0;overflow:hidden;}
.chart-bar-fill{height:100%;border-radius:0;transition:width 1s ease;}
.chart-bar-val{font-family:'Oswald',sans-serif;font-size:0.7rem;color:var(--muted);min-width:20px;text-align:right;}
/* DONUT */
.donut-section{display:flex;align-items:center;gap:24px;padding:24px;}
.donut-legend-item{display:flex;align-items:center;gap:10px;margin-bottom:14px;}
.donut-legend-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;}
.donut-legend-label{font-family:'Oswald',sans-serif;font-size:0.7rem;letter-spacing:1px;color:var(--muted);flex:1;text-transform:uppercase;}
.donut-legend-val{font-family:'Playfair Display',serif;font-size:1.2rem;font-weight:700;}
/* STATS NEW */
.stats-row-new{display:grid;grid-template-columns:repeat(4,1fr);gap:0;border:1px solid var(--border);margin-bottom:36px;border-radius:4px;overflow:hidden;}
.stat-new{padding:28px 24px;border-right:1px solid var(--border);position:relative;overflow:hidden;transition:background 0.2s;}
.stat-new:last-child{border-right:none;}
.stat-new:hover{background:rgba(255,255,255,0.02);}
.stat-new-label{font-family:'Oswald',sans-serif;font-size:0.6rem;letter-spacing:3px;color:var(--muted);text-transform:uppercase;margin-bottom:12px;display:flex;align-items:center;gap:8px;}
.stat-new-label::before{content:'';width:20px;height:2px;}
.stat-new.r .stat-new-label::before{background:var(--accent);}
.stat-new.g .stat-new-label::before{background:var(--green);}
.stat-new.y .stat-new-label::before{background:var(--yellow);}
.stat-new.b .stat-new-label::before{background:var(--blue);}
.stat-new-val{font-family:'Playfair Display',serif;font-size:4rem;font-weight:900;line-height:1;}
.stat-new.r .stat-new-val{color:var(--accent);}
.stat-new.g .stat-new-val{color:var(--green);}
.stat-new.y .stat-new-val{color:var(--yellow);}
.stat-new.b .stat-new-val{color:var(--text);}
.stat-new-bar{margin-top:14px;height:2px;background:var(--border);}
.stat-new-bar-fill{height:100%;}
.stat-new.r .stat-new-bar-fill{background:var(--accent);}
.stat-new.g .stat-new-bar-fill{background:var(--green);}
.stat-new.y .stat-new-bar-fill{background:var(--yellow);}
.stat-new.b .stat-new-bar-fill{background:var(--blue);}
.stat-new-ghost{position:absolute;right:10px;bottom:-15px;font-family:'Playfair Display',serif;font-size:7rem;font-weight:900;opacity:0.04;line-height:1;}
/* HERO */
.hero{position:relative;z-index:10;background:linear-gradient(135deg,#0d0d0d 0%,#1a0000 50%,#0d0d0d 100%);border-bottom:1px solid #2a2a2a;padding:60px 32px;overflow:hidden;text-align:center;}
.hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at center,rgba(230,50,50,0.12) 0%,transparent 70%);}
.hero-eyebrow{font-family:'Oswald',sans-serif;font-size:0.65rem;letter-spacing:5px;color:var(--accent);text-transform:uppercase;margin-bottom:16px;display:flex;align-items:center;justify-content:center;gap:12px;}
.hero-eyebrow::before,.hero-eyebrow::after{content:'';width:40px;height:1px;background:var(--accent);}
.hero h1{font-family:'Playfair Display',serif;font-size:clamp(2.5rem,6vw,5rem);font-weight:900;line-height:1.05;margin-bottom:20px;position:relative;z-index:1;}
.hero h1 em{color:var(--accent);font-style:italic;}
.hero-sub{font-family:'Oswald',sans-serif;font-size:0.8rem;letter-spacing:3px;color:var(--muted);margin-bottom:32px;}
.hero-actions{display:flex;gap:12px;justify-content:center;position:relative;z-index:1;}
.hero-divider{width:60px;height:3px;background:var(--accent);margin:0 auto 20px;}
@media(max-width:900px){.magazine-grid{grid-template-columns:1fr 1fr;}.mag-card-featured{grid-column:span 2;}.stats-row-new{grid-template-columns:repeat(2,1fr);}}
@media(max-width:600px){.magazine-grid{grid-template-columns:1fr;}.mag-card-featured{grid-column:span 1;}.stats-row-new{grid-template-columns:1fr 1fr;}.masthead{flex-direction:column;gap:16px;}.grid-2{grid-template-columns:1fr;}}
</style>
</head>
<body>

<div id="cursor"></div>
<div id="cursor-ring"></div>

<div class="topbar">
  <div class="topbar-left"><span class="live-dot"></span><?= $t['live_fact'] ?></div>
  <div class="topbar-right" id="topDate"></div>
</div>

<div class="masthead">
  <a class="masthead-logo" href="index.php">
    <div class="logo-main">Fake<span>Guard</span></div>
    <div class="logo-sub"><?= $t['tagline'] ?></div>
  </a>
  <div class="masthead-stats">
    <div class="mstat"><div class="mstat-val red" id="ms-fake">—</div><div class="mstat-label"><?= $t['fake'] ?></div></div>
    <div class="mstat"><div class="mstat-val green" id="ms-real">—</div><div class="mstat-label"><?= $t['real'] ?></div></div>
    <div class="mstat"><div class="mstat-val yellow" id="ms-mis">—</div><div class="mstat-label"><?= $t['misleading'] ?></div></div>
    <div class="mstat"><div class="mstat-val" id="ms-total" style="color:var(--text)">—</div><div class="mstat-label"><?= $t['total'] ?></div></div>
  </div>
</div>

<div class="ticker-wrap">
  <div class="ticker-label"><?= $t['breaking'] ?></div>
  <div class="ticker-content">
    <div class="ticker-inner">
      <span class="ticker-item">⚡ COVID Vaccine Causes Magnetism <span class="verdict-tag fake"><?= strtoupper($t['fake']) ?></span></span>
      <span class="ticker-item">⚡ 5G Towers Spread Coronavirus <span class="verdict-tag fake"><?= strtoupper($t['fake']) ?></span></span>
      <span class="ticker-item">⚡ Scientists Discover Water on Mars <span class="verdict-tag real"><?= strtoupper($t['real']) ?></span></span>
      <span class="ticker-item">⚡ India GDP Grows at 7.2% in Q3 <span class="verdict-tag real"><?= strtoupper($t['real']) ?></span></span>
      <span class="ticker-item">⚡ Eating Garlic Cures Cancer <span class="verdict-tag fake"><?= strtoupper($t['fake']) ?></span></span>
      <span class="ticker-item">⚡ Election Results Rigged in UP <span class="verdict-tag misleading"><?= strtoupper($t['misleading']) ?></span></span>
      <span class="ticker-item">⚡ COVID Vaccine Causes Magnetism <span class="verdict-tag fake"><?= strtoupper($t['fake']) ?></span></span>
      <span class="ticker-item">⚡ 5G Towers Spread Coronavirus <span class="verdict-tag fake"><?= strtoupper($t['fake']) ?></span></span>
      <span class="ticker-item">⚡ Scientists Discover Water on Mars <span class="verdict-tag real"><?= strtoupper($t['real']) ?></span></span>
      <span class="ticker-item">⚡ Election Results Rigged in UP <span class="verdict-tag misleading"><?= strtoupper($t['misleading']) ?></span></span>
    </div>
  </div>
</div>

<nav>
  <div class="nav-links">
    <a href="index.php"    <?= basename($_SERVER['PHP_SELF'])=='index.php'   ?'class="active"':'' ?>><?= $t['dashboard'] ?></a>
    <a href="articles.php" <?= basename($_SERVER['PHP_SELF'])=='articles.php'?'class="active"':'' ?>><?= $t['articles'] ?></a>
    <a href="verify.php"   <?= basename($_SERVER['PHP_SELF'])=='verify.php'  ?'class="active"':'' ?>><?= $t['verify'] ?></a>
    <a href="sources.php"  <?= basename($_SERVER['PHP_SELF'])=='sources.php' ?'class="active"':'' ?>><?= $t['sources'] ?></a>
    <a href="reports.php"  <?= basename($_SERVER['PHP_SELF'])=='reports.php' ?'class="active"':'' ?>><?= $t['reports'] ?></a>
    <a href="users.php"    <?= basename($_SERVER['PHP_SELF'])=='users.php'   ?'class="active"':'' ?>><?= $t['users'] ?></a>
  </div>
  <div class="nav-right">
    <div class="nav-time" id="navTime"></div>
    <div class="lang-switcher">
      <button class="lang-btn" onclick="toggleLang()">🌐 <?= strtoupper($lang) ?></button>
      <div class="lang-dropdown" id="langDrop">
        <a href="?setlang=en">🇬🇧 English</a>
        <a href="?setlang=hi">🇮🇳 Hindi</a>
        <a href="?setlang=es">🇪🇸 Spanish</a>
        <a href="?setlang=fr">🇫🇷 French</a>
        <a href="?setlang=ar">🇸🇦 Arabic</a>
        <a href="?setlang=ja">🇯🇵 Japanese</a>
      </div>
    </div>
    <div class="nav-badge"><?= $t['dbms_project'] ?></div>
  </div>
</nav>

<div id="bgHeadlines"></div>
<audio id="bgMusic" src="/Projects/fake news/news.mp3" loop></audio>
<button id="musicBtn" onclick="toggleMusic()">🔇</button>

<script>
// CURSOR
var cur=document.getElementById('cursor'),ring=document.getElementById('cursor-ring');
document.addEventListener('mousemove',function(e){
  cur.style.left=(e.clientX-6)+'px';cur.style.top=(e.clientY-6)+'px';
  ring.style.left=(e.clientX-18)+'px';ring.style.top=(e.clientY-18)+'px';
});

// TIME
function updateTime(){
  var now=new Date();
  var el=document.getElementById('navTime');
  if(el) el.textContent=now.toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
  var de=document.getElementById('topDate');
  if(de) de.textContent=now.toLocaleDateString('en-IN',{weekday:'long',year:'numeric',month:'long',day:'numeric'});
}
updateTime();setInterval(updateTime,1000);

// FLOATING HEADLINES
var headlines=["FAKE NEWS DETECTED","BREAKING: MISINFORMATION","FACT CHECK: FALSE","VERIFIED: REAL NEWS","MISLEADING CONTENT","AI VERIFIED ✓","THE TRUTH WINS","STOP FAKE NEWS","LIVE VERIFICATION","BREAKING NEWS"];
var sizes=['1rem','1.3rem','1.6rem','2rem','0.9rem'];
var bgDiv=document.getElementById('bgHeadlines');
for(var i=0;i<20;i++){
  var span=document.createElement('div');
  span.className='floating-headline';
  span.textContent=headlines[Math.floor(Math.random()*headlines.length)];
  span.style.cssText='left:'+Math.random()*100+'%;--dur:'+(Math.random()*20+15)+'s;--delay:-'+(Math.random()*20)+'s;--rot:'+(Math.random()*20-10)+'deg;font-size:'+sizes[Math.floor(Math.random()*sizes.length)];
  bgDiv.appendChild(span);
}

// MUSIC
var music=document.getElementById('bgMusic'),btn=document.getElementById('musicBtn');
var musicOn=localStorage.getItem('musicOn')!=='false';
function playMusic(){music.volume=0.3;music.play().then(function(){btn.textContent='🔊';localStorage.setItem('musicOn','true');}).catch(function(){btn.textContent='🔇';});}
function toggleMusic(){if(music.paused){playMusic();localStorage.setItem('musicOn','true');}else{music.pause();btn.textContent='🔇';localStorage.setItem('musicOn','false');}}
window.addEventListener('load',function(){
  if(musicOn){music.volume=0.3;music.play().then(function(){btn.textContent='🔊';}).catch(function(){document.addEventListener('click',function fc(e){if(e.target.id!=='musicBtn'){playMusic();document.removeEventListener('click',fc);}});});}
  else{btn.textContent='🔇';}
});

// LANG DROPDOWN
function toggleLang(){document.getElementById('langDrop').classList.toggle('open');}
document.addEventListener('click',function(e){if(!e.target.closest('.lang-switcher'))document.getElementById('langDrop').classList.remove('open');});

// ── PWA SERVICE WORKER REGISTRATION ─────────────────────────
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function() {
    navigator.serviceWorker.register('/sw.js', { scope: '/' })
      .then(function(reg) {
        console.log('[FakeGuard] SW registered. Scope:', reg.scope);
        // Check for updates every 60 minutes
        setInterval(() => reg.update(), 3600000);
      })
      .catch(function(err) {
        console.warn('[FakeGuard] SW registration failed:', err);
      });
  });
}

// ── PWA INSTALL PROMPT ───────────────────────────────────────
let deferredInstallPrompt = null;

window.addEventListener('beforeinstallprompt', function(e) {
  e.preventDefault();
  deferredInstallPrompt = e;

  // Show install banner after 3 seconds if not already installed
  setTimeout(showInstallBanner, 3000);
});

function showInstallBanner() {
  if (localStorage.getItem('fg_pwa_installed') === 'true') return;
  if (document.getElementById('pwa-banner')) return;

  const banner = document.createElement('div');
  banner.id = 'pwa-banner';
  banner.innerHTML = `
    <div style="
      position:fixed;bottom:20px;left:50%;transform:translateX(-50%);
      background:#141414;border:1px solid #e63232;border-radius:12px;
      padding:14px 20px;display:flex;align-items:center;gap:14px;
      z-index:99999;box-shadow:0 8px 40px rgba(0,0,0,0.6),0 0 0 1px rgba(230,50,50,0.1);
      font-family:'Oswald',sans-serif;max-width:340px;width:calc(100vw - 32px);
      animation:bannerIn 0.4s cubic-bezier(0.22,1,0.36,1);
    ">
      <style>@keyframes bannerIn{from{transform:translateX(-50%) translateY(80px);opacity:0}to{transform:translateX(-50%) translateY(0);opacity:1}}</style>
      <img src="/icons/icon-96.png" width="40" height="40" style="border-radius:10px;flex-shrink:0;" onerror="this.style.display='none'">
      <div style="flex:1;">
        <div style="font-size:0.75rem;font-weight:600;letter-spacing:1px;color:#f0f0f0;margin-bottom:3px;">Install FakeGuard App</div>
        <div style="font-size:0.62rem;color:#777;letter-spacing:0.5px;">Add to Home Screen for fast access</div>
      </div>
      <div style="display:flex;gap:8px;flex-shrink:0;">
        <button onclick="triggerInstall()" style="
          background:#e63232;color:#fff;border:none;padding:7px 14px;
          border-radius:6px;font-family:'Oswald',sans-serif;font-size:0.65rem;
          font-weight:600;letter-spacing:1px;cursor:pointer;
        ">INSTALL</button>
        <button onclick="dismissInstall()" style="
          background:transparent;color:#555;border:1px solid #2a2a2a;padding:7px 10px;
          border-radius:6px;font-family:'Oswald',sans-serif;font-size:0.65rem;cursor:pointer;
        ">✕</button>
      </div>
    </div>
  `;
  document.body.appendChild(banner);
}

function triggerInstall() {
  if (!deferredInstallPrompt) return;
  deferredInstallPrompt.prompt();
  deferredInstallPrompt.userChoice.then(function(result) {
    if (result.outcome === 'accepted') {
      localStorage.setItem('fg_pwa_installed', 'true');
      console.log('[FakeGuard] PWA installed!');
    }
    deferredInstallPrompt = null;
    dismissInstall();
  });
}

function dismissInstall() {
  const b = document.getElementById('pwa-banner');
  if (b) b.remove();
  localStorage.setItem('fg_pwa_dismissed', Date.now());
}

// Track when installed as PWA
window.addEventListener('appinstalled', function() {
  localStorage.setItem('fg_pwa_installed', 'true');
  console.log('[FakeGuard] App was installed to home screen!');
  dismissInstall();
});
</script>
