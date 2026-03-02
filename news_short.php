<?php
require_once 'includes/db.php';

$category = $_GET['cat'] ?? 'all';
$where = $category !== 'all' ? "WHERE c.name = " . $pdo->quote($category) : '';

$articles = $pdo->query("
    SELECT a.article_id, a.title, a.content, a.author, a.published_at, a.url,
           c.name AS category, s.name AS source, s.credibility_score,
           v.verdict, v.confidence_score, v.explanation
    FROM articles a
    LEFT JOIN categories    c ON a.category_id = c.category_id
    LEFT JOIN sources       s ON a.source_id   = s.source_id
    LEFT JOIN verifications v ON a.article_id  = v.article_id
    $where
    ORDER BY a.created_at DESC
    LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);

// Category config: [accent-hex, dark-bg, mid-bg, emoji, watermark-text, pattern-type]
$catCfg = [
  'Politics'     => ['#e63232','#1a0000','#200505','🏛','POLITICS'],
  'Health'       => ['#00e87a','#001a08','#012010','💊','HEALTH'],
  'Science'      => ['#4fb3ff','#00081a','#001228','🔬','SCIENCE'],
  'Technology'   => ['#c061ff','#0d0014','#180025','💻','TECH'],
  'Sports'       => ['#ff6d00','#1a0800','#2a1200','🏏','SPORTS'],
  'Finance'      => ['#ffd600','#1a1500','#252000','📈','FINANCE'],
  'Entertainment'=> ['#ff4081','#1a000d','#280018','🎬','ENTERTAIN'],
  'World'        => ['#00c8e8','#00131a','#002028','🌍','WORLD'],
  'Environment'  => ['#8bc34a','#041a00','#082800','🌿','ENVIRON'],
  'Education'    => ['#ff9800','#0d0900','#1a1200','📚','EDUCATION'],
  'General'      => ['#e63232','#0a0a0a','#131313','📰','NEWS'],
];

// For each article, pre-build SVG shape data (deterministic, no rand())
function artSVG(int $seed, string $ac, string $bgD, string $bgM): string {
  // LCG for deterministic "random" positions
  $lcg = fn(int &$s): int => ($s = (($s * 1664525 + 1013904223) & 0x7FFFFFFF));

  $s = $seed * 9001 + 1;
  $shapes = '';

  // 6 geometric shapes
  for ($i = 0; $i < 6; $i++) {
    $x   = $lcg($s) % 100;
    $y   = $lcg($s) % 100;
    $sz  = 10 + ($lcg($s) % 45);
    $op  = round((0.03 + $i * 0.015), 3);
    if ($i % 3 === 0) {
      $shapes .= "<circle cx='{$x}%' cy='{$y}%' r='{$sz}%' fill='{$ac}' opacity='{$op}'/>";
    } elseif ($i % 3 === 1) {
      $rx = max(0, $x - $sz/2); $ry = max(0, $y - $sz/3);
      $shapes .= "<rect x='{$rx}%' y='{$ry}%' width='" . ($sz*1.6) . "%' height='" . ($sz*1.1) . "%' fill='{$ac}' opacity='{$op}' rx='1'/>";
    } else {
      $x2 = $lcg($s) % 100; $y2 = $lcg($s) % 100;
      $shapes .= "<line x1='{$x}%' y1='{$y}%' x2='{$x2}%' y2='{$y2}%' stroke='{$ac}' stroke-width='0.5%' opacity='" . ($op*2) . "'/>";
    }
  }

  // Fixed diagonal lines
  $shapes .= "<line x1='55%' y1='0%' x2='100%' y2='40%' stroke='{$ac}' stroke-width='0.35%' opacity='0.07'/>";
  $shapes .= "<line x1='0%' y1='60%' x2='45%' y2='100%' stroke='{$ac}' stroke-width='0.25%' opacity='0.05'/>";

  $uid = "g{$seed}";
  return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid slice" style="position:absolute;inset:0;width:100%;height:100%">
  <defs>
    <linearGradient id="bg{$uid}" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$bgD}"/>
      <stop offset="100%" stop-color="{$bgM}"/>
    </linearGradient>
    <radialGradient id="gw{$uid}" cx="50%" cy="115%" r="75%">
      <stop offset="0%" stop-color="{$ac}" stop-opacity="0.2"/>
      <stop offset="100%" stop-color="transparent" stop-opacity="0"/>
    </radialGradient>
  </defs>
  <rect width="100" height="100" fill="url(#bg{$uid})"/>
  {$shapes}
  <rect width="100" height="100" fill="url(#gw{$uid})"/>
</svg>
SVG;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>News in Short · FakeGuard</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Instrument+Serif:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{width:100%;height:100%;overflow:hidden;background:#050505;color:#f0f0f0;
  font-family:'DM Sans',sans-serif;-webkit-tap-highlight-color:transparent;user-select:none;}
:root{--fake:#ff2525;--real:#00e87a;--miss:#ffd000;--unver:#555;--blue:#4fb3ff;--bdr:rgba(255,255,255,0.07);}

/* ── TOPBAR ─────────────────────────────────── */
#topbar{position:fixed;top:0;left:0;right:0;z-index:200;height:50px;display:flex;align-items:center;
  gap:12px;padding:0 16px;background:linear-gradient(to bottom,rgba(5,5,5,1),transparent);}
#tb-back{width:34px;height:34px;border-radius:50%;border:1px solid var(--bdr);
  background:rgba(255,255,255,0.06);display:flex;align-items:center;justify-content:center;
  color:#fff;text-decoration:none;font-size:0.9rem;transition:background 0.2s;}
#tb-back:hover{background:rgba(255,255,255,0.12);}
#tb-brand{font-family:'Bebas Neue',sans-serif;font-size:1rem;letter-spacing:4px;flex:1;}
#tb-brand em{color:var(--fake);font-style:normal;}
#tb-count{font-size:0.6rem;color:rgba(255,255,255,0.38);letter-spacing:2px;}

/* ── PROGRESS ───────────────────────────────── */
#prog{position:fixed;top:0;left:0;height:2px;width:0;z-index:300;
  background:linear-gradient(90deg,var(--fake),#ff8800);
  transition:width 0.36s ease;box-shadow:0 0 10px rgba(255,37,37,0.7);}

/* ── CAT BAR ────────────────────────────────── */
#catbar{position:fixed;bottom:64px;left:50%;transform:translateX(-50%);z-index:200;
  display:flex;gap:5px;padding:4px 8px;
  background:rgba(8,8,8,0.94);border:1px solid var(--bdr);backdrop-filter:blur(24px);
  border-radius:40px;overflow-x:auto;scrollbar-width:none;max-width:calc(100vw - 24px);}
#catbar::-webkit-scrollbar{display:none;}
.ct{flex-shrink:0;padding:5px 13px;border-radius:30px;font-size:0.58rem;
  font-weight:500;letter-spacing:1px;color:rgba(255,255,255,0.4);
  text-decoration:none;transition:all 0.2s;white-space:nowrap;border:1px solid transparent;}
.ct.active{background:rgba(255,37,37,0.15);color:var(--fake);border-color:rgba(255,37,37,0.3);}
.ct:hover:not(.active){color:rgba(255,255,255,0.8);}

/* ── VIEWPORT + CARDS ───────────────────────── */
#viewport{position:fixed;inset:0;overflow:hidden;}
#wrap{position:relative;width:100%;height:100%;}
.nc{position:absolute;inset:0;transform:translateY(105%);visibility:hidden;will-change:transform;}
.nc.anim{transition:transform 0.44s cubic-bezier(0.22,1,0.36,1),visibility 0.44s;}
.nc.active{transform:translateY(0)!important;visibility:visible!important;}
.nc.prev{transform:translateY(-105%)!important;visibility:hidden!important;}

/* ── CANVAS (generative bg) ─────────────────── */
.nc-canvas{position:absolute;inset:0;overflow:hidden;}
.nc-vignette{position:absolute;inset:0;
  background:
    radial-gradient(ellipse 90% 65% at 50% 115%,rgba(5,5,5,0.98) 0%,rgba(5,5,5,0.65) 50%,transparent 100%),
    linear-gradient(to top,rgba(5,5,5,0.97) 0%,rgba(5,5,5,0.7) 32%,rgba(5,5,5,0.15) 62%,transparent 100%);}

/* ── TOP ACCENT LINE ────────────────────────── */
.nc-edge{position:absolute;top:0;left:0;right:0;height:3px;}

/* ── WATERMARK (big ghost category word) ─────── */
.nc-wm{
  position:absolute;top:52px;right:14px;
  font-family:'Bebas Neue',sans-serif;
  font-size:clamp(5rem,16vw,10rem);
  line-height:0.82;text-align:right;
  color:rgba(255,255,255,0.028);
  pointer-events:none;letter-spacing:-1px;
  text-transform:uppercase;word-break:break-all;
  max-height:52%;overflow:hidden;
}

/* ── CONTENT PANEL ──────────────────────────── */
.nc-panel{position:absolute;left:0;right:0;bottom:0;padding:18px 22px 138px;
  display:flex;flex-direction:column;gap:10px;}

/* pills row */
.nc-pills{display:flex;gap:7px;flex-wrap:wrap;align-items:center;}
.nc-cat{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;
  border-radius:20px;font-size:0.58rem;font-weight:600;letter-spacing:1.5px;
  text-transform:uppercase;border:1px solid currentColor;opacity:0.9;}
.nc-verd{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;
  border-radius:20px;font-size:0.6rem;font-weight:700;letter-spacing:1.5px;
  text-transform:uppercase;background:rgba(0,0,0,0.5);backdrop-filter:blur(6px);
  border:1px solid currentColor;}
.nc-pct{margin-left:auto;font-size:0.6rem;font-weight:600;opacity:0.65;letter-spacing:1px;}

/* title */
.nc-title{font-family:'Instrument Serif',serif;
  font-size:clamp(1.22rem,4.6vw,2rem);line-height:1.18;
  text-shadow:0 2px 28px rgba(0,0,0,0.95);font-weight:400;}

/* divider */
.nc-hr{height:1px;background:rgba(255,255,255,0.08);}

/* summary */
.nc-sum{font-size:0.83rem;line-height:1.72;color:rgba(255,255,255,0.65);
  font-weight:300;overflow:hidden;max-height:82px;transition:max-height 0.38s ease;}
.nc-sum.open{max-height:480px;}
.nc-more{font-size:0.58rem;letter-spacing:1.5px;color:var(--fake);
  cursor:pointer;background:none;border:none;font-family:'DM Sans',sans-serif;padding:0;}

/* meta */
.nc-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.nc-src{font-size:0.63rem;color:rgba(255,255,255,0.38);display:flex;align-items:center;gap:5px;}
.nc-dot{width:5px;height:5px;border-radius:50%;flex-shrink:0;}
.nc-date{font-size:0.6rem;color:rgba(255,255,255,0.28);margin-left:auto;}

/* ── AI SHEET ───────────────────────────────── */
.nc-ai{position:absolute;left:0;right:0;bottom:-100%;padding:20px 22px 150px;z-index:20;
  background:linear-gradient(to top,rgba(5,5,5,0.99) 82%,transparent);
  transition:bottom 0.42s cubic-bezier(0.22,1,0.36,1);}
.nc-ai.open{bottom:0;}
.nc-ai-lbl{font-size:0.55rem;letter-spacing:3px;color:var(--blue);margin-bottom:10px;}
.nc-ai-body{font-size:0.82rem;line-height:1.72;color:rgba(255,255,255,0.76);}
.nc-ai-x{position:absolute;top:14px;right:18px;width:28px;height:28px;border-radius:50%;
  background:rgba(255,255,255,0.07);border:1px solid var(--bdr);color:rgba(255,255,255,0.38);
  cursor:pointer;font-size:0.75rem;display:flex;align-items:center;justify-content:center;}

/* ── BOTTOM BAR ─────────────────────────────── */
#bar{position:fixed;bottom:0;left:0;right:0;z-index:200;height:64px;
  display:flex;align-items:center;gap:7px;padding:0 14px;
  background:linear-gradient(to top,rgba(5,5,5,1) 55%,transparent);}
.bb{display:inline-flex;align-items:center;gap:5px;padding:0 12px;height:36px;
  border-radius:10px;font-size:0.64rem;font-weight:500;letter-spacing:0.5px;
  cursor:pointer;border:1px solid var(--bdr);background:rgba(255,255,255,0.05);
  color:rgba(255,255,255,0.42);transition:all 0.18s;text-decoration:none;
  white-space:nowrap;font-family:'DM Sans',sans-serif;}
.bb:hover{color:#fff;background:rgba(255,255,255,0.1);}
.bb.ai{background:rgba(79,179,255,0.1);border-color:rgba(79,179,255,0.3);color:var(--blue);}
.bb.wa{background:rgba(37,211,102,0.1);border-color:rgba(37,211,102,0.28);color:#25d366;}
.bb.bm{color:var(--miss);}
.bb.bm.on{background:rgba(255,208,0,0.15);border-color:rgba(255,208,0,0.35);}
.sp{flex:1;}
.nb{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;
  background:rgba(255,255,255,0.05);border:1px solid var(--bdr);color:rgba(255,255,255,0.38);
  cursor:pointer;font-size:1.1rem;transition:all 0.18s;}
.nb:hover{color:#fff;background:rgba(255,255,255,0.12);}
.nb:disabled{opacity:0.18;cursor:default;pointer-events:none;}

/* ── TOAST ──────────────────────────────────── */
#toast{position:fixed;bottom:76px;left:50%;transform:translateX(-50%) translateY(14px);
  background:rgba(14,14,14,0.96);border:1px solid var(--bdr);backdrop-filter:blur(20px);
  border-radius:10px;padding:9px 18px;font-size:0.72rem;color:#fff;opacity:0;
  pointer-events:none;transition:all 0.28s;z-index:500;white-space:nowrap;}
#toast.show{opacity:1;transform:translateX(-50%) translateY(0);}

/* ── SWIPE HINT ─────────────────────────────── */
#hint{position:fixed;bottom:76px;left:50%;transform:translateX(-50%);
  font-size:0.55rem;color:rgba(255,255,255,0.32);letter-spacing:3px;
  pointer-events:none;z-index:100;animation:hgo 4.5s ease forwards;
  display:flex;flex-direction:column;align-items:center;gap:5px;}
.h-a{width:26px;height:26px;border-radius:50%;border:1px solid rgba(255,255,255,0.12);
  display:flex;align-items:center;justify-content:center;font-size:0.75rem;
  animation:bou 1.3s ease infinite;}
@keyframes bou{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}
@keyframes hgo{0%{opacity:0;transform:translateX(-50%) translateY(8px)}12%{opacity:1;transform:translateX(-50%) translateY(0)}75%{opacity:1}100%{opacity:0}}

/* ── EMPTY ──────────────────────────────────── */
.empty{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;color:rgba(255,255,255,0.3);}
.empty span{font-size:3rem;}
.empty p{font-family:'Instrument Serif',serif;font-size:1.4rem;}
.empty a{color:var(--fake);font-size:0.8rem;letter-spacing:1px;}

/* ── MOBILE ─────────────────────────────────── */
@media(max-width:480px){
  .nc-panel{padding-bottom:145px;}
  .nc-title{font-size:clamp(1.1rem,5.5vw,1.55rem);}
  .nc-wm{font-size:clamp(3.5rem,14vw,6.5rem);}
  #catbar{bottom:70px;}
}
</style>
</head>
<body>

<div id="prog"></div>
<div id="topbar">
  <a href="index.php" id="tb-back">←</a>
  <div id="tb-brand">NEWS IN <em>SHORT</em></div>
  <div id="tb-count">— / —</div>
</div>

<div id="viewport">
<div id="wrap">

<?php if (empty($articles)): ?>
<div class="empty"><span>📰</span><p>No stories found.</p><a href="?cat=all">Show all →</a></div>
<?php else:
foreach ($articles as $i => $a):
  $cat  = $a['category'] ?? 'General';
  $cfg  = $catCfg[$cat]  ?? $catCfg['General'];
  [$ac, $bgD, $bgM, $icon, $wm] = $cfg;

  $v    = $a['verdict']  ?? 'unverified';
  $vc   = $v==='fake'?'var(--fake)':($v==='real'?'var(--real)':($v==='misleading'?'var(--miss)':'var(--unver)'));
  $vhex = $v==='fake'?'#ff2525':($v==='real'?'#00e87a':($v==='misleading'?'#ffd000':'#444'));
  $vem  = $v==='fake'?'🚨':($v==='real'?'✅':($v==='misleading'?'⚠️':'🔍'));
  $conf = (int)($a['confidence_score'] ?? 0);
  $cred = (int)($a['credibility_score'] ?? 50);
  $credColor = $cred>70?'#00e87a':($cred>40?'#ffd000':'#ff2525');
  $words = explode(' ', strip_tags($a['content']));
  $short = implode(' ', array_slice($words, 0, 55)) . (count($words)>55?'...':'');
  $svg = artSVG($a['article_id'], $ac, $bgD, $bgM);
?>
<div class="nc" id="card-<?= $i ?>">

  <div class="nc-canvas">
    <?= $svg ?>
    <div class="nc-vignette"></div>
  </div>

  <div class="nc-edge" style="background:<?= $vhex ?>;box-shadow:0 0 16px <?= $vhex ?>77;"></div>

  <div class="nc-wm"><?= htmlspecialchars($wm) ?></div>

  <div class="nc-panel">
    <div class="nc-pills">
      <div class="nc-cat" style="color:<?= $ac ?>;border-color:<?= $ac ?>55;background:<?= $ac ?>10;">
        <?= $icon ?> <?= htmlspecialchars($cat) ?>
      </div>
      <div class="nc-verd" style="color:<?= $vhex ?>;border-color:<?= $vhex ?>55;">
        <?= $vem ?> <?= strtoupper($v) ?>
      </div>
      <?php if($conf>0): ?>
      <div class="nc-pct" style="color:<?= $vhex ?>;"><?= $conf ?>%</div>
      <?php endif; ?>
    </div>

    <div class="nc-title"><?= htmlspecialchars($a['title']) ?></div>
    <div class="nc-hr"></div>

    <div class="nc-sum" id="sum-<?= $i ?>"><?= htmlspecialchars($short) ?></div>
    <button class="nc-more" id="tog-<?= $i ?>" onclick="expand(<?= $i ?>)">▾ READ MORE</button>

    <div class="nc-meta">
      <?php if($a['source']): ?>
      <div class="nc-src">
        <div class="nc-dot" style="background:<?= $credColor ?>;" title="<?= $cred ?>/100"></div>
        <?= htmlspecialchars($a['source']) ?>
      </div>
      <?php endif; ?>
      <?php if($a['author'] && $a['author']!=='Unknown'): ?>
      <span style="font-size:0.6rem;color:rgba(255,255,255,0.28);">· <?= htmlspecialchars(substr($a['author'],0,22)) ?></span>
      <?php endif; ?>
      <div class="nc-date"><?= $a['published_at']?date('d M Y',strtotime($a['published_at'])):'Recent' ?></div>
    </div>
  </div>

  <div class="nc-ai" id="ai-<?= $i ?>">
    <button class="nc-ai-x" onclick="closeAI(<?= $i ?>)">✕</button>
    <div class="nc-ai-lbl">🤖 AI FACT CHECK — GROQ LLaMA</div>
    <div class="nc-ai-body" id="aibd-<?= $i ?>"></div>
  </div>

</div>
<?php endforeach; endif; ?>
</div>
</div>

<div id="hint"><div class="h-a">↑</div><span>SWIPE UP</span></div>

<div id="catbar">
<?php
$cats=['all'=>'🔥 All','Politics'=>'🏛 Politics','Health'=>'💊 Health',
  'Technology'=>'💻 Tech','Science'=>'🔬 Science','Sports'=>'🏏 Sports',
  'Finance'=>'📈 Finance','World'=>'🌍 World','Entertainment'=>'🎬 Ent.',
  'Environment'=>'🌿 Eco','Education'=>'📚 Edu'];
foreach($cats as $k=>$l):
  echo '<a href="?cat='.urlencode($k).'" class="ct '.($category===$k?'active':'').'">'.$l.'</a>';
endforeach;
?>
</div>

<div id="toast"></div>

<div id="bar">
  <button class="bb ai" id="aiBtn" onclick="triggerAI()">✨ AI Check</button>
  <a class="bb" id="readBtn" href="#" target="_blank">📖 Full Article</a>
  <a class="bb wa" id="waBtn" href="#" target="_blank">📲 Share</a>
  <button class="bb bm" id="bmBtn" onclick="toggleBM()">🔖</button>
  <div class="sp"></div>
  <button class="nb" id="btnPrev" onclick="go(-1)" disabled>↑</button>
  <button class="nb" id="btnNext" onclick="go(1)">↓</button>
</div>

<script>
const A=<?= json_encode(array_map(fn($a)=>[
  'id'=>$a['article_id'],'title'=>$a['title'],
  'source'=>$a['source']??'','author'=>$a['author']??'',
  'category'=>$a['category']??'General','verdict'=>$a['verdict']??'unverified',
  'confidence'=>(int)($a['confidence_score']??0),'url'=>$a['url']??'',
  'date'=>$a['published_at']?date('d M Y',strtotime($a['published_at'])):'Recent',
], $articles), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
const N=A.length;
let cur=0,aiCache={},exp={},bm=JSON.parse(localStorage.getItem('fg_bm')||'{}'),aiOpen=false;
const $=id=>document.getElementById(id);

function updateUI(){
  const a=A[cur];if(!a)return;
  $('tb-count').textContent=(cur+1)+' / '+N;
  $('prog').style.width=((cur+1)/N*100)+'%';
  $('readBtn').href='article_detail.php?id='+a.id;
  $('waBtn').href='whatsapp_share.php?id='+a.id;
  $('btnPrev').disabled=cur===0;
  $('btnNext').disabled=cur===N-1;
  const b=$('bmBtn');
  b.classList.toggle('on',!!bm[a.id]);
  b.textContent=bm[a.id]?'🔖 Saved':'🔖';
}

function go(dir){
  const next=cur+dir;
  if(next<0||next>=N)return;
  const ai=$('ai-'+cur);
  if(ai&&aiOpen){ai.classList.remove('open');aiOpen=false;$('aiBtn').textContent='✨ AI Check';}
  const o=$('card-'+cur),n=$('card-'+next);
  if(!o||!n)return;
  [o,n].forEach(c=>c.classList.add('anim'));
  if(dir>0){
    n.style.transform='translateY(105%)';n.style.visibility='visible';
    n.classList.remove('prev','active');
    requestAnimationFrame(()=>requestAnimationFrame(()=>{
      o.classList.remove('active');o.classList.add('prev');
      n.style.transform='';n.classList.add('active');
    }));
  } else {
    n.style.transform='translateY(-105%)';n.style.visibility='visible';
    n.classList.remove('prev','active');
    requestAnimationFrame(()=>requestAnimationFrame(()=>{
      o.style.transform='translateY(105%)';o.classList.remove('active');
      n.style.transform='';n.classList.add('active');
    }));
    setTimeout(()=>{o.style.transform='';o.style.visibility='';o.classList.remove('anim');},520);
  }
  cur=next;updateUI();
}

// Touch
let ty=0,tx=0,td=false,tm=false;
const vp=$('viewport');
vp.addEventListener('touchstart',e=>{ty=e.touches[0].clientY;tx=e.touches[0].clientX;td=true;tm=false;},{passive:true});
vp.addEventListener('touchmove',e=>{if(Math.abs(e.touches[0].clientY-ty)>8)tm=true;},{passive:true});
vp.addEventListener('touchend',e=>{
  if(!td||!tm)return;td=false;
  const dy=ty-e.changedTouches[0].clientY,dx=tx-e.changedTouches[0].clientX;
  if(Math.abs(dy)>Math.abs(dx)&&Math.abs(dy)>48)go(dy>0?1:-1);
},{passive:true});

document.addEventListener('keydown',e=>{
  if(e.key==='ArrowDown'||e.key===' '){e.preventDefault();go(1);}
  if(e.key==='ArrowUp'){e.preventDefault();go(-1);}
  if(e.key==='Escape')closeAI(cur);
});

let wl=false;
vp.addEventListener('wheel',e=>{if(wl)return;wl=true;go(e.deltaY>0?1:-1);setTimeout(()=>wl=false,680);},{passive:true});

function expand(i){
  exp[i]=!exp[i];
  $('sum-'+i).classList.toggle('open',exp[i]);
  $('tog-'+i).textContent=exp[i]?'▴ LESS':'▾ READ MORE';
}

function triggerAI(){
  const a=A[cur],sheet=$('ai-'+cur),body=$('aibd-'+cur),btn=$('aiBtn');
  if(aiOpen){sheet.classList.remove('open');aiOpen=false;btn.textContent='✨ AI Check';return;}
  sheet.classList.add('open');aiOpen=true;btn.textContent='✨ Hide AI';
  if(aiCache[cur]){body.innerHTML=aiCache[cur];return;}
  body.innerHTML='<span style="color:var(--blue);font-size:0.74rem;letter-spacing:1px">⏳ ANALYSING WITH GROQ AI…</span>';
  fetch('ai_check.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'article_id='+a.id})
  .then(r=>r.json()).then(data=>{
    if(data.error){body.innerHTML='<span style="color:var(--fake)">⚠ '+data.error+'</span>';return;}
    const col=data.verdict==='fake'?'var(--fake)':(data.verdict==='real'?'var(--real)':'var(--miss)');
    const html=`<div style="display:flex;align-items:baseline;gap:10px;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid rgba(255,255,255,0.07)">
      <span style="font-family:'Bebas Neue',sans-serif;font-size:1.3rem;letter-spacing:2px;color:${col}">${data.verdict.toUpperCase()}</span>
      <span style="font-size:0.6rem;color:rgba(255,255,255,0.35);letter-spacing:1px">${data.confidence}% CONFIDENCE</span>
    </div><div style="font-size:0.82rem;line-height:1.72;color:rgba(255,255,255,0.75)">${data.reason}</div>`;
    aiCache[cur]=html;body.innerHTML=html;
  }).catch(()=>{body.innerHTML='<span style="color:var(--fake)">⚠ Connection error</span>';});
}

function closeAI(i){
  const s=$('ai-'+i);if(s)s.classList.remove('open');
  aiOpen=false;$('aiBtn').textContent='✨ AI Check';
}

function toggleBM(){
  const a=A[cur];
  if(bm[a.id])delete bm[a.id];
  else bm[a.id]={id:a.id,title:a.title,verdict:a.verdict,ts:Date.now()};
  localStorage.setItem('fg_bm',JSON.stringify(bm));
  toast(bm[a.id]?'🔖 Bookmarked!':'Bookmark removed');
  updateUI();
}

function toast(msg){
  const t=$('toast');t.textContent=msg;t.classList.add('show');
  setTimeout(()=>t.classList.remove('show'),2200);
}

(function(){
  const f=$('card-0');if(!f)return;
  f.style.transition='none';f.classList.add('active');
  f.offsetHeight;f.style.transition='';
  setTimeout(()=>document.querySelectorAll('.nc').forEach(c=>c.classList.add('anim')),80);
  updateUI();
})();
</script>
</body>
</html>
