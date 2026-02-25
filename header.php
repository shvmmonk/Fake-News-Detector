<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FakeGuard — <?= $pageTitle ?? 'Dashboard' ?></title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Oswald:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;600&display=swap" rel="stylesheet">
<style>
:root {
  --bg:      #0d0d0d;
  --surface: #141414;
  --card:    #1a1a1a;
  --border:  #2a2a2a;
  --accent:  #e63232;
  --accent2: #ff6b35;
  --green:   #00c853;
  --yellow:  #ffd600;
  --blue:    #2979ff;
  --text:    #f0f0f0;
  --muted:   #777;
  --paper:   #f5f0e8;
}

* { margin:0; padding:0; box-sizing:border-box; }

/* CUSTOM CURSOR */
* { cursor: none !important; }
#cursor {
  width: 12px; height: 12px;
  background: var(--accent);
  border-radius: 50%;
  position: fixed;
  pointer-events: none;
  z-index: 99999;
  transition: transform 0.1s;
  mix-blend-mode: difference;
}
#cursor-ring {
  width: 36px; height: 36px;
  border: 1px solid rgba(230,50,50,0.5);
  border-radius: 50%;
  position: fixed;
  pointer-events: none;
  z-index: 99998;
  transition: all 0.15s ease;
}

body {
  background: var(--bg);
  color: var(--text);
  font-family: 'Source Sans 3', sans-serif;
  min-height: 100vh;
  overflow-x: hidden;
}

/* SCANLINE OVERLAY */
body::after {
  content: '';
  position: fixed;
  inset: 0;
  background: repeating-linear-gradient(
    0deg,
    transparent,
    transparent 2px,
    rgba(0,0,0,0.03) 2px,
    rgba(0,0,0,0.03) 4px
  );
  pointer-events: none;
  z-index: 9997;
}

/* NOISE TEXTURE */
body::before {
  content: '';
  position: fixed;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.03'/%3E%3C/svg%3E");
  pointer-events: none;
  z-index: 0;
  opacity: 0.4;
}

/* ===== TOP BAR ===== */
.topbar {
  background: var(--accent);
  padding: 6px 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: relative;
  z-index: 200;
}
.topbar-left {
  font-family: 'Oswald', sans-serif;
  font-size: 0.7rem;
  font-weight: 600;
  letter-spacing: 3px;
  color: #fff;
}
.topbar-right {
  font-family: 'Oswald', sans-serif;
  font-size: 0.65rem;
  letter-spacing: 2px;
  color: rgba(255,255,255,0.85);
}
.live-dot {
  display: inline-block;
  width: 7px; height: 7px;
  background: #fff;
  border-radius: 50%;
  margin-right: 6px;
  animation: blink 1s infinite;
}
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.2} }

/* ===== MASTHEAD ===== */
.masthead {
  background: var(--bg);
  border-bottom: 3px solid var(--accent);
  padding: 18px 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: relative;
  z-index: 200;
}
.masthead-logo {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  text-decoration: none;
}
.masthead-logo .logo-main {
  font-family: 'Playfair Display', serif;
  font-size: 2.8rem;
  font-weight: 900;
  color: var(--text);
  line-height: 1;
  letter-spacing: -1px;
}
.masthead-logo .logo-main span {
  color: var(--accent);
  font-style: italic;
}
.masthead-logo .logo-sub {
  font-family: 'Oswald', sans-serif;
  font-size: 0.6rem;
  letter-spacing: 4px;
  color: var(--muted);
  margin-top: 3px;
  text-transform: uppercase;
}
.masthead-stats {
  display: flex;
  gap: 28px;
  align-items: center;
}
.mstat {
  text-align: center;
  padding: 0 20px;
  border-left: 1px solid var(--border);
}
.mstat:first-child { border-left: none; }
.mstat-val {
  font-family: 'Playfair Display', serif;
  font-size: 1.8rem;
  font-weight: 700;
  line-height: 1;
}
.mstat-val.red    { color: var(--accent); }
.mstat-val.green  { color: var(--green); }
.mstat-val.yellow { color: var(--yellow); }
.mstat-label {
  font-family: 'Oswald', sans-serif;
  font-size: 0.55rem;
  letter-spacing: 2px;
  color: var(--muted);
  text-transform: uppercase;
  margin-top: 2px;
}

/* ===== BREAKING TICKER ===== */
.ticker-wrap {
  background: #111;
  border-bottom: 1px solid var(--border);
  padding: 10px 0;
  overflow: hidden;
  position: relative;
  z-index: 200;
}
.ticker-label {
  position: absolute;
  left: 0; top: 0; bottom: 0;
  background: var(--accent);
  display: flex;
  align-items: center;
  padding: 0 18px;
  font-family: 'Oswald', sans-serif;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 2px;
  color: #fff;
  z-index: 10;
  white-space: nowrap;
}
.ticker-label::after {
  content: '';
  position: absolute;
  right: -12px; top: 0; bottom: 0;
  width: 0;
  border-style: solid;
  border-width: 20px 0 20px 12px;
  border-color: transparent transparent transparent var(--accent);
}
.ticker-content {
  margin-left: 140px;
  overflow: hidden;
}
.ticker-inner {
  display: flex;
  gap: 80px;
  animation: tickerAnim 30s linear infinite;
  white-space: nowrap;
}
.ticker-item {
  font-family: 'Oswald', sans-serif;
  font-size: 0.78rem;
  letter-spacing: 1px;
  color: var(--text);
  display: flex;
  align-items: center;
  gap: 10px;
}
.ticker-item .verdict-tag {
  font-size: 0.6rem;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 3px;
}
.verdict-tag.fake       { background: var(--accent); color: #fff; }
.verdict-tag.real       { background: var(--green);  color: #000; }
.verdict-tag.misleading { background: var(--yellow); color: #000; }
@keyframes tickerAnim {
  0%   { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}

/* ===== NAV ===== */
nav {
  position: sticky;
  top: 0;
  z-index: 500;
  background: rgba(13,13,13,0.97);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--border);
  padding: 0 32px;
  display: flex;
  align-items: center;
  height: 52px;
}
.nav-links {
  display: flex;
  gap: 0;
  flex: 1;
}
.nav-links a {
  color: var(--muted);
  text-decoration: none;
  font-family: 'Oswald', sans-serif;
  font-size: 0.8rem;
  font-weight: 500;
  letter-spacing: 2px;
  padding: 0 20px;
  height: 52px;
  display: flex;
  align-items: center;
  border-bottom: 3px solid transparent;
  transition: all 0.2s;
  text-transform: uppercase;
  position: relative;
}
.nav-links a::before {
  content: '';
  position: absolute;
  bottom: -1px; left: 0; right: 0;
  height: 3px;
  background: var(--accent);
  transform: scaleX(0);
  transition: transform 0.2s;
}
.nav-links a:hover { color: var(--text); }
.nav-links a:hover::before { transform: scaleX(1); }
.nav-links a.active { color: var(--text); }
.nav-links a.active::before { transform: scaleX(1); }
.nav-right {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-left: auto;
}
.nav-badge {
  background: var(--accent);
  color: #fff;
  font-family: 'Oswald', sans-serif;
  font-size: 0.6rem;
  font-weight: 700;
  padding: 4px 12px;
  letter-spacing: 2px;
}
.nav-time {
  font-family: 'Oswald', sans-serif;
  font-size: 0.7rem;
  color: var(--muted);
  letter-spacing: 1px;
}

/* ===== FLOATING HEADLINES ===== */
#bgHeadlines {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 1;
  overflow: hidden;
  opacity: 0.03;
}
.floating-headline {
  position: absolute;
  color: #fff;
  font-family: 'Playfair Display', serif;
  font-weight: 900;
  white-space: nowrap;
  animation: floatUp var(--dur) linear infinite;
  animation-delay: var(--delay);
}
@keyframes floatUp {
  0%   { transform: translateY(110vh) rotate(var(--rot)); opacity:0; }
  5%   { opacity: 1; }
  95%  { opacity: 1; }
  100% { transform: translateY(-150px) rotate(var(--rot)); opacity:0; }
}

/* ===== WRAPPER ===== */
.wrapper {
  position: relative;
  z-index: 10;
  max-width: 1300px;
  margin: 0 auto;
  padding: 40px 32px;
}

/* ===== PAGE HEADER ===== */
.page-header {
  margin-bottom: 36px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
}
.page-header h1 {
  font-family: 'Playfair Display', serif;
  font-weight: 900;
  font-size: 2.5rem;
  line-height: 1;
  color: var(--text);
}
.page-header h1 em {
  color: var(--accent);
  font-style: italic;
}
.page-header p {
  font-family: 'Oswald', sans-serif;
  color: var(--muted);
  font-size: 0.65rem;
  letter-spacing: 3px;
  margin-top: 6px;
}

/* ===== CARDS ===== */
.card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 4px;
  overflow: hidden;
  position: relative;
}
.card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 2px;
  background: linear-gradient(90deg, var(--accent), transparent);
}
.card-header {
  padding: 16px 24px;
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.card-title {
  font-family: 'Oswald', sans-serif;
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 2px;
  text-transform: uppercase;
  color: var(--text);
}
.card-body { padding: 24px; }

/* ===== STATS ===== */
.stats-row {
  display: grid;
  grid-template-columns: repeat(4,1fr);
  gap: 16px;
  margin-bottom: 32px;
}
.stat {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 4px;
  padding: 20px 24px;
  position: relative;
  overflow: hidden;
  transition: transform 0.2s, border-color 0.2s;
}
.stat:hover { transform: translateY(-3px); border-color: var(--accent); }
.stat::after {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
}
.stat.r::after { background: var(--accent); }
.stat.g::after { background: var(--green); }
.stat.y::after { background: var(--yellow); }
.stat.b::after { background: var(--blue); }
.stat-label {
  font-family: 'Oswald', sans-serif;
  font-size: 0.6rem;
  color: var(--muted);
  letter-spacing: 3px;
  text-transform: uppercase;
  margin-bottom: 10px;
}
.stat-val {
  font-family: 'Playfair Display', serif;
  font-size: 3rem;
  font-weight: 900;
  line-height: 1;
}
.stat.r .stat-val { color: var(--accent); }
.stat.g .stat-val { color: var(--green); }
.stat.y .stat-val { color: var(--yellow); }
.stat.b .stat-val { color: var(--blue); }
.stat-bg-num {
  position: absolute;
  right: 10px;
  bottom: -10px;
  font-family: 'Playfair Display', serif;
  font-size: 6rem;
  font-weight: 900;
  opacity: 0.04;
  line-height: 1;
}

/* ===== TABLE ===== */
.tbl-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
thead tr { border-bottom: 2px solid var(--accent); }
th {
  padding: 12px 18px;
  text-align: left;
  font-family: 'Oswald', sans-serif;
  font-size: 0.62rem;
  letter-spacing: 2px;
  color: var(--muted);
  text-transform: uppercase;
}
td {
  padding: 14px 18px;
  font-size: 0.85rem;
  border-bottom: 1px solid rgba(42,42,42,0.8);
  vertical-align: middle;
}
tbody tr:last-child td { border-bottom: none; }
tbody tr { transition: background 0.15s; }
tbody tr:hover { background: rgba(230,50,50,0.03); }
.td-title {
  max-width: 280px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-family: 'Source Sans 3', sans-serif;
}

/* ===== BADGES ===== */
.badge {
  display: inline-block;
  padding: 3px 10px;
  font-family: 'Oswald', sans-serif;
  font-size: 0.62rem;
  font-weight: 600;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  border-radius: 2px;
}
.b-fake        { background: rgba(230,50,50,0.15);  color: var(--accent); border: 1px solid rgba(230,50,50,0.4); }
.b-real        { background: rgba(0,200,83,0.12);   color: var(--green);  border: 1px solid rgba(0,200,83,0.4); }
.b-misleading  { background: rgba(255,214,0,0.12);  color: var(--yellow); border: 1px solid rgba(255,214,0,0.4); }
.b-unverified  { background: rgba(119,119,119,0.1); color: var(--muted);  border: 1px solid rgba(119,119,119,0.3); }
.b-pending     { background: rgba(255,214,0,0.12);  color: var(--yellow); border: 1px solid rgba(255,214,0,0.4); }
.b-reviewed    { background: rgba(0,200,83,0.12);   color: var(--green);  border: 1px solid rgba(0,200,83,0.4); }
.b-dismissed   { background: rgba(119,119,119,0.1); color: var(--muted);  border: 1px solid rgba(119,119,119,0.3); }
.b-admin       { background: rgba(230,50,50,0.15);  color: var(--accent); border: 1px solid rgba(230,50,50,0.4); }
.b-fact_checker{ background: rgba(41,121,255,0.12); color: var(--blue);   border: 1px solid rgba(41,121,255,0.4); }
.b-user        { background: rgba(119,119,119,0.1); color: var(--muted);  border: 1px solid rgba(119,119,119,0.3); }

/* ===== CONFIDENCE BAR ===== */
.cbar-wrap { display:flex; align-items:center; gap:8px; }
.cbar { flex:1; height:3px; background:var(--border); border-radius:0; min-width:50px; }
.cbar-fill { height:100%; }
.cbar-num { font-family:'Oswald',sans-serif; font-size:0.72rem; color:var(--muted); min-width:28px; text-align:right; }

/* ===== FORMS ===== */
.form-group { margin-bottom:18px; }
.form-group label {
  display:block;
  font-family:'Oswald',sans-serif;
  font-size:0.62rem;
  letter-spacing:2px;
  color:var(--muted);
  text-transform:uppercase;
  margin-bottom:8px;
}
.form-group input,
.form-group select,
.form-group textarea {
  width:100%;
  background: #111;
  border: 1px solid var(--border);
  border-bottom: 2px solid var(--border);
  color:var(--text);
  font-family:'Source Sans 3',sans-serif;
  font-size:0.9rem;
  padding:10px 14px;
  border-radius:2px;
  outline:none;
  transition:border-color 0.2s;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  border-bottom-color: var(--accent);
  background: #141414;
}
.form-group textarea { resize:vertical; min-height:90px; }
.form-group select option { background:#1a1a1a; }

/* ===== BUTTONS ===== */
.btn {
  display:inline-block;
  padding:10px 24px;
  font-family:'Oswald',sans-serif;
  font-size:0.78rem;
  font-weight:600;
  letter-spacing:2px;
  text-transform:uppercase;
  cursor:pointer;
  border:none;
  transition:all 0.2s;
  text-decoration:none;
  border-radius:2px;
  position:relative;
  overflow:hidden;
}
.btn::after {
  content:'';
  position:absolute;
  inset:0;
  background:rgba(255,255,255,0);
  transition:background 0.2s;
}
.btn:hover::after { background:rgba(255,255,255,0.08); }
.btn-primary { background:var(--accent); color:#fff; }
.btn-ghost { background:transparent; color:var(--muted); border:1px solid var(--border); }
.btn-ghost:hover { border-color:var(--accent); color:var(--text); }
.btn-green { background:var(--green); color:#000; }

/* ===== ALERTS ===== */
.alert { padding:14px 18px; border-radius:2px; font-size:0.85rem; margin-bottom:20px; border-left:4px solid; }
.alert-success { background:rgba(0,200,83,0.08); border-color:var(--green); color:var(--green); }
.alert-error   { background:rgba(230,50,50,0.08); border-color:var(--accent); color:var(--accent); }

/* ===== GRID ===== */
.grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
.mt-20  { margin-top:20px; }
.mb-20  { margin-bottom:20px; }
.text-muted { color:var(--muted); }
.text-sm    { font-size:0.78rem; }

/* ===== DETAIL ROW ===== */
.detail-row {
  display:flex; gap:16px;
  padding:12px 0;
  border-bottom:1px solid rgba(42,42,42,0.6);
  font-size:0.85rem;
}
.detail-row:last-child { border-bottom:none; }
.detail-label {
  font-family:'Oswald',sans-serif;
  color:var(--muted);
  min-width:140px;
  font-size:0.65rem;
  letter-spacing:2px;
  text-transform:uppercase;
  padding-top:2px;
}

/* ===== MUSIC BUTTON ===== */
@keyframes pulse {
  0%   { box-shadow:0 0 0 0 rgba(230,50,50,0.7); }
  70%  { box-shadow:0 0 0 14px rgba(230,50,50,0); }
  100% { box-shadow:0 0 0 0 rgba(230,50,50,0); }
}
#musicBtn {
  position:fixed; bottom:28px; right:28px; z-index:9999;
  background:var(--accent); color:#fff; border:none;
  width:56px; height:56px; border-radius:50%;
  font-size:1.3rem; cursor:pointer !important;
  display:flex; align-items:center; justify-content:center;
  box-shadow:0 4px 24px rgba(230,50,50,0.5);
  animation:pulse 2s infinite;
  transition:transform 0.2s;
}
#musicBtn:hover { transform:scale(1.1); }

@media(max-width:768px) {
  .stats-row { grid-template-columns:repeat(2,1fr); }
  .grid-2 { grid-template-columns:1fr; }
  .masthead { flex-direction:column; gap:16px; }
  .masthead-stats { flex-wrap:wrap; justify-content:center; }
  nav { padding:0 16px; }
  .wrapper { padding:24px 16px; }
}
</style>
</head>
<body>

<!-- CUSTOM CURSOR -->
<div id="cursor"></div>
<div id="cursor-ring"></div>

<!-- TOP BAR -->
<div class="topbar">
  <div class="topbar-left">
    <span class="live-dot"></span>
    LIVE FACT-CHECKING
  </div>
  <div class="topbar-right" id="topDate"></div>
</div>

<!-- MASTHEAD -->
<div class="masthead">
  <a class="masthead-logo" href="index.php">
    <div class="logo-main">Fake<span>Guard</span></div>
    <div class="logo-sub">The Truth Always Wins</div>
  </a>
  <div class="masthead-stats">
    <div class="mstat">
      <div class="mstat-val red" id="ms-fake">—</div>
      <div class="mstat-label">Fake</div>
    </div>
    <div class="mstat">
      <div class="mstat-val green" id="ms-real">—</div>
      <div class="mstat-label">Real</div>
    </div>
    <div class="mstat">
      <div class="mstat-val yellow" id="ms-mis">—</div>
      <div class="mstat-label">Misleading</div>
    </div>
    <div class="mstat">
      <div class="mstat-val" id="ms-total" style="color:var(--text)">—</div>
      <div class="mstat-label">Total</div>
    </div>
  </div>
</div>

<!-- BREAKING NEWS TICKER -->
<div class="ticker-wrap">
  <div class="ticker-label">🔴 BREAKING</div>
  <div class="ticker-content">
    <div class="ticker-inner" id="tickerInner">
      <span class="ticker-item">⚡ COVID Vaccine Causes Magnetism <span class="verdict-tag fake">FAKE</span></span>
      <span class="ticker-item">⚡ 5G Towers Spread Coronavirus <span class="verdict-tag fake">FAKE</span></span>
      <span class="ticker-item">⚡ Scientists Discover Water on Mars <span class="verdict-tag real">REAL</span></span>
      <span class="ticker-item">⚡ India GDP Grows at 7.2% in Q3 <span class="verdict-tag real">REAL</span></span>
      <span class="ticker-item">⚡ Eating Garlic Cures Cancer <span class="verdict-tag fake">FAKE</span></span>
      <span class="ticker-item">⚡ Election Results Rigged in UP <span class="verdict-tag misleading">MISLEADING</span></span>
      <span class="ticker-item">⚡ COVID Vaccine Causes Magnetism <span class="verdict-tag fake">FAKE</span></span>
      <span class="ticker-item">⚡ 5G Towers Spread Coronavirus <span class="verdict-tag fake">FAKE</span></span>
      <span class="ticker-item">⚡ Scientists Discover Water on Mars <span class="verdict-tag real">REAL</span></span>
      <span class="ticker-item">⚡ India GDP Grows at 7.2% in Q3 <span class="verdict-tag real">REAL</span></span>
      <span class="ticker-item">⚡ Eating Garlic Cures Cancer <span class="verdict-tag fake">FAKE</span></span>
      <span class="ticker-item">⚡ Election Results Rigged in UP <span class="verdict-tag misleading">MISLEADING</span></span>
    </div>
  </div>
</div>

<!-- STICKY NAV -->
<nav>
  <div class="nav-links">
    <a href="index.php"    <?= basename($_SERVER['PHP_SELF'])=='index.php'   ?'class="active"':'' ?>>Dashboard</a>
    <a href="articles.php" <?= basename($_SERVER['PHP_SELF'])=='articles.php'?'class="active"':'' ?>>Articles</a>
    <a href="verify.php"   <?= basename($_SERVER['PHP_SELF'])=='verify.php'  ?'class="active"':'' ?>>Verify</a>
    <a href="sources.php"  <?= basename($_SERVER['PHP_SELF'])=='sources.php' ?'class="active"':'' ?>>Sources</a>
    <a href="reports.php"  <?= basename($_SERVER['PHP_SELF'])=='reports.php' ?'class="active"':'' ?>>Reports</a>
    <a href="users.php"    <?= basename($_SERVER['PHP_SELF'])=='users.php'   ?'class="active"':'' ?>>Users</a>
  </div>
  <div class="nav-right">
    <div class="nav-time" id="navTime"></div>
    <div class="nav-badge">DBMS PROJECT</div>
  </div>
</nav>

<!-- FLOATING BACKGROUND HEADLINES -->
<div id="bgHeadlines"></div>

<!-- MUSIC -->
<audio id="bgMusic" src="/Projects/fake news/news.mp3" loop></audio>
<button id="musicBtn" onclick="toggleMusic()">🔇</button>

<script>
// CURSOR
var cur = document.getElementById('cursor');
var ring = document.getElementById('cursor-ring');
document.addEventListener('mousemove', function(e) {
  cur.style.left  = (e.clientX - 6)  + 'px';
  cur.style.top   = (e.clientY - 6)  + 'px';
  ring.style.left = (e.clientX - 18) + 'px';
  ring.style.top  = (e.clientY - 18) + 'px';
});

// DATE TIME
function updateTime() {
  var now = new Date();
  var opts = { weekday:'long', year:'numeric', month:'long', day:'numeric' };
  document.getElementById('topDate').textContent = now.toLocaleDateString('en-IN', opts);
  var t = now.toLocaleTimeString('en-IN', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
  var el = document.getElementById('navTime');
  if(el) el.textContent = t;
}
updateTime();
setInterval(updateTime, 1000);

// FLOATING HEADLINES
var headlines = [
  "FAKE NEWS DETECTED","BREAKING: MISINFORMATION ALERT",
  "FACT CHECK: FALSE","VERIFIED: REAL NEWS",
  "MISLEADING CONTENT FOUND","AI VERIFIED ✓",
  "UNVERIFIED CLAIM","FACT CHECK IN PROGRESS",
  "BREAKING NEWS","LIVE VERIFICATION",
  "THE TRUTH ALWAYS WINS","STOP FAKE NEWS",
];
var sizes = ['1rem','1.3rem','1.6rem','2rem','0.9rem'];
var bgDiv = document.getElementById('bgHeadlines');
for (var i = 0; i < 20; i++) {
  var span = document.createElement('div');
  span.className = 'floating-headline';
  span.textContent = headlines[Math.floor(Math.random() * headlines.length)];
  var left  = Math.random() * 100;
  var dur   = (Math.random() * 20 + 15) + 's';
  var delay = '-' + (Math.random() * 20) + 's';
  var rot   = (Math.random() * 20 - 10) + 'deg';
  var size  = sizes[Math.floor(Math.random() * sizes.length)];
  span.style.cssText = 'left:'+left+'%;--dur:'+dur+';--delay:'+delay+';--rot:'+rot+';font-size:'+size;
  bgDiv.appendChild(span);
}

// MUSIC
var music   = document.getElementById('bgMusic');
var btn     = document.getElementById('musicBtn');
var musicOn = localStorage.getItem('musicOn') !== 'false';
// Remember playback position across pages
var savedTime = parseFloat(localStorage.getItem('musicTime') || '0');

function playMusic() {
  music.volume = 0.3;
  if (savedTime > 0) music.currentTime = savedTime;
  music.play().then(function() {
    btn.textContent = '🔊';
    localStorage.setItem('musicOn','true');
  }).catch(function(){
    btn.textContent = '🔇';
  });
}

function toggleMusic() {
  if (music.paused) {
    playMusic();
    localStorage.setItem('musicOn','true');
  } else {
    music.pause();
    btn.textContent = '🔇';
    localStorage.setItem('musicOn','false');
  }
}

// Save position every second so next page can resume from same spot
setInterval(function() {
  if (!music.paused) {
    localStorage.setItem('musicTime', music.currentTime);
  }
}, 1000);

// Pause when tab is hidden, resume when tab is visible
document.addEventListener('visibilitychange', function() {
  if (document.hidden) {
    if (!music.paused) {
      localStorage.setItem('musicTime', music.currentTime);
      music.pause();
      // Keep btn as 🔊 so we know it was playing before hide
    }
  } else {
    if (localStorage.getItem('musicOn') !== 'false') {
      savedTime = parseFloat(localStorage.getItem('musicTime') || '0');
      playMusic();
    }
  }
});

window.addEventListener('load', function() {
  savedTime = parseFloat(localStorage.getItem('musicTime') || '0');
  if (musicOn) {
    music.volume = 0.3;
    if (savedTime > 0) music.currentTime = savedTime;
    music.play().then(function(){ btn.textContent='🔊'; }).catch(function(){
      document.addEventListener('click', function fc(e) {
        if(e.target.id !== 'musicBtn') { playMusic(); document.removeEventListener('click',fc); }
      });
    });
  } else {
    btn.textContent = '🔇';
  }
});
</script>
