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

$catKeywords = [
    'Politics'=>'government parliament politics news','Health'=>'medicine hospital health doctor',
    'Science'=>'science laboratory research space','Technology'=>'technology computer digital innovation',
    'Sports'=>'sports cricket stadium athletes','Finance'=>'finance money stock market economy',
    'Entertainment'=>'entertainment cinema film','World'=>'world city travel international',
    'Environment'=>'nature environment climate green','Education'=>'education university books students','General'=>'news journalism media',
];

$articlesJson = [];
foreach ($articles as $a) {
    $words = explode(' ', strip_tags($a['content']));
    $short = implode(' ', array_slice($words, 0, 60));
    if (count($words) > 60) $short .= '...';
    $long  = implode(' ', array_slice($words, 0, 120));
    if (count($words) > 120) $long .= '...';
    $cat = $a['category'] ?? 'General';
    $titleWords = preg_replace('/[^a-zA-Z ]/', ' ', strip_tags($a['title']));
    $stop = ['the','a','an','is','in','on','at','to','of','and','or','for','with','was','are','has','have','this','that','its','it','be','by','as','from','not','says','said','new','over','after','will','also','but'];
    $titleArr = array_filter(explode(' ', strtolower($titleWords)), fn($w) => strlen($w) > 3 && !in_array($w, $stop));
    $topWords = implode(' ', array_slice(array_values($titleArr), 0, 3));
    $catKw = $catKeywords[$cat] ?? 'news';
    $imgQuery = substr(trim($topWords . ' ' . $catKw), 0, 70);
    $articlesJson[] = [
        'id'=>$a['article_id'],'title'=>$a['title'],'short'=>$short,'long'=>$long,
        'source'=>$a['source']??'','author'=>$a['author']??'','category'=>$cat,
        'verdict'=>$a['verdict']??'unverified','confidence'=>(int)($a['confidence_score']??0),
        'explanation'=>$a['explanation']??'','url'=>$a['url']??'',
        'date'=>$a['published_at']?date('d M Y',strtotime($a['published_at'])):'Recent',
        'cred'=>(int)($a['credibility_score']??50),
        'img'=>'image_proxy.php?id='.$a['article_id'].'&q='.urlencode($imgQuery).'&w=800&h=600',
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>News in Short · FakeGuard</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,700;0,9..144,900;1,9..144,700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
/* ══ RESET & BASE ══════════════════════════════════════════ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{
  --bg:#050505;--glass:rgba(8,8,8,0.82);--glass2:rgba(15,15,15,0.75);
  --bdr:rgba(255,255,255,0.07);--text:#f2f2f2;--muted:rgba(255,255,255,0.45);
  --fake:#ff2d2d;--real:#00e87a;--miss:#ffd000;--unver:rgba(255,255,255,0.35);
  --blue:#4fb3ff;--radius:20px;
}
html,body{width:100%;height:100%;overflow:hidden;background:var(--bg);
  color:var(--text);font-family:'DM Sans',sans-serif;
  -webkit-tap-highlight-color:transparent;user-select:none;}

/* ══ TOPBAR ════════════════════════════════════════════════ */
#topbar{
  position:fixed;top:0;left:0;right:0;z-index:300;
  display:flex;align-items:center;gap:12px;
  padding:0 18px;height:52px;
  background:linear-gradient(to bottom,rgba(5,5,5,0.98),transparent);
}
#topbar-back{
  width:36px;height:36px;border-radius:50%;
  background:rgba(255,255,255,0.07);border:1px solid var(--bdr);
  display:flex;align-items:center;justify-content:center;
  color:var(--text);text-decoration:none;font-size:1rem;
  transition:background 0.2s;flex-shrink:0;
}
#topbar-back:hover{background:rgba(255,255,255,0.14);}
#topbar-brand{font-family:'Bebas Neue',sans-serif;font-size:1rem;letter-spacing:4px;flex:1;}
#topbar-brand span{color:var(--fake);}
#topbar-meta{font-size:0.6rem;color:var(--muted);letter-spacing:2px;font-family:'DM Sans',sans-serif;}

/* ══ READING PROGRESS ══════════════════════════════════════ */
#readProgress{
  position:fixed;top:0;left:0;height:3px;width:0%;
  background:linear-gradient(90deg,var(--fake),#ff8c00);
  z-index:400;transition:width 0.4s ease;
  box-shadow:0 0 12px rgba(255,45,45,0.6);
}

/* ══ CAT BAR ═══════════════════════════════════════════════ */
#catBar{
  position:fixed;bottom:68px;left:50%;transform:translateX(-50%);
  z-index:300;display:flex;gap:6px;
  background:rgba(8,8,8,0.88);border:1px solid var(--bdr);
  backdrop-filter:blur(20px);
  border-radius:40px;padding:5px 8px;
  overflow-x:auto;scrollbar-width:none;max-width:calc(100vw - 32px);
}
#catBar::-webkit-scrollbar{display:none;}
.ct{
  flex-shrink:0;padding:5px 14px;border-radius:30px;
  font-size:0.58rem;font-weight:500;letter-spacing:1px;
  color:var(--muted);text-decoration:none;
  transition:all 0.22s;white-space:nowrap;border:1px solid transparent;
}
.ct:hover{color:var(--text);}
.ct.active{
  background:rgba(255,45,45,0.15);color:var(--fake);
  border-color:rgba(255,45,45,0.35);
}

/* ══ VIEWPORT ══════════════════════════════════════════════ */
#viewport{position:fixed;inset:0;overflow:hidden;touch-action:pan-y;}
#cardsWrap{position:relative;width:100%;height:100%;}

/* ══ CARD ══════════════════════════════════════════════════ */
.nc{
  position:absolute;inset:0;
  transform:translateY(105%);visibility:hidden;
  will-change:transform;
}
.nc.anim{transition:transform 0.42s cubic-bezier(0.22,1,0.36,1),visibility 0.42s;}
.nc.active{transform:translateY(0)!important;visibility:visible!important;}
.nc.prev{transform:translateY(-105%)!important;visibility:hidden!important;}

/* Full-bleed image bg */
.nc-bg{
  position:absolute;inset:0;
  background:#0a0a0a;
}
.nc-img{
  position:absolute;inset:0;width:100%;height:100%;
  object-fit:cover;opacity:0;transition:opacity 1s ease;
  transform:scale(1.04);transition:opacity 1s ease,transform 8s ease;
}
.nc-img.vis{opacity:1;transform:scale(1);}
/* Dark gradient over image */
.nc-gradient{
  position:absolute;inset:0;
  background:
    linear-gradient(to top,rgba(5,5,5,0.97) 0%,rgba(5,5,5,0.7) 40%,rgba(5,5,5,0.2) 70%,transparent 100%);
}
/* Colored verdict glow at bottom */
.nc-verdict-glow{
  position:absolute;bottom:0;left:0;right:0;height:180px;
  opacity:0.12;transition:opacity 0.5s;
  pointer-events:none;
}

/* ── CONTENT PANEL ── */
.nc-panel{
  position:absolute;left:0;right:0;bottom:0;
  padding:0 20px 140px;
  display:flex;flex-direction:column;gap:10px;
}

/* Category pill */
.nc-cat{
  display:inline-flex;align-items:center;gap:6px;
  padding:5px 12px;border-radius:30px;
  font-size:0.58rem;font-weight:600;letter-spacing:2px;
  text-transform:uppercase;align-self:flex-start;
  border:1px solid currentColor;
  opacity:0.9;
}

/* Verdict pill */
.nc-verdict{
  display:inline-flex;align-items:center;gap:6px;
  padding:5px 14px;border-radius:30px;
  font-size:0.62rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;
  background:rgba(0,0,0,0.5);backdrop-filter:blur(8px);
  border:1px solid currentColor;align-self:flex-start;
}

/* Title */
.nc-title{
  font-family:'Fraunces',serif;font-optical-sizing:auto;
  font-size:clamp(1.2rem,4.5vw,2rem);font-weight:900;line-height:1.2;
  text-shadow:0 2px 20px rgba(0,0,0,0.8);
}

/* Divider */
.nc-divider{height:1px;background:var(--bdr);margin:2px 0;}

/* Summary text */
.nc-summary{
  font-size:0.82rem;line-height:1.72;color:rgba(255,255,255,0.72);
  font-weight:300;max-height:100px;overflow:hidden;
  transition:max-height 0.4s ease;
}
.nc-summary.expanded{max-height:600px;}

/* Read more toggle */
.nc-read-toggle{
  font-size:0.6rem;color:var(--fake);letter-spacing:1.5px;
  cursor:pointer;align-self:flex-start;background:none;border:none;
  font-family:'DM Sans',sans-serif;padding:0;
}

/* Meta row */
.nc-meta{
  display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:2px;
}
.nc-source{font-size:0.65rem;color:var(--muted);display:flex;align-items:center;gap:5px;}
.nc-date{font-size:0.6rem;color:var(--muted);margin-left:auto;}
.nc-cred-dot{width:6px;height:6px;border-radius:50%;flex-shrink:0;}

/* Confidence arc */
.nc-conf{
  position:absolute;top:60px;right:18px;
  width:52px;height:52px;
  display:flex;align-items:center;justify-content:center;
}
.nc-conf-num{
  position:absolute;font-size:0.65rem;font-weight:700;
  font-family:'DM Sans',sans-serif;text-align:center;line-height:1;
}
.nc-conf-label{
  position:absolute;top:33px;font-size:0.38rem;
  letter-spacing:1px;color:var(--muted);text-transform:uppercase;
}

/* AI panel — slides up from bottom */
.nc-ai{
  position:absolute;left:0;right:0;bottom:-100%;
  padding:20px 20px 160px;
  background:linear-gradient(to top,rgba(5,5,5,0.99) 80%,transparent);
  backdrop-filter:blur(20px);
  transition:bottom 0.4s cubic-bezier(0.22,1,0.36,1);
  z-index:10;
}
.nc-ai.open{bottom:0;}
.nc-ai-label{font-size:0.55rem;color:var(--blue);letter-spacing:3px;margin-bottom:10px;font-family:'DM Sans',sans-serif;}
.nc-ai-text{font-size:0.82rem;line-height:1.7;color:rgba(255,255,255,0.8);}
.nc-ai-close{
  position:absolute;top:14px;right:16px;
  background:rgba(255,255,255,0.07);border:1px solid var(--bdr);
  color:var(--muted);width:28px;height:28px;border-radius:50%;
  font-size:0.75rem;cursor:pointer;display:flex;align-items:center;justify-content:center;
}

/* ══ BOTTOM ACTION BAR ═════════════════════════════════════ */
#actionBar{
  position:fixed;bottom:0;left:0;right:0;z-index:300;
  height:68px;display:flex;align-items:center;gap:8px;
  padding:0 14px;
  background:linear-gradient(to top,rgba(5,5,5,0.98) 70%,transparent);
}
.ab-btn{
  display:flex;align-items:center;gap:5px;
  padding:0 13px;height:38px;border-radius:12px;
  font-size:0.65rem;font-weight:500;letter-spacing:0.5px;
  cursor:pointer;border:1px solid var(--bdr);
  background:rgba(255,255,255,0.05);color:var(--muted);
  transition:all 0.18s;text-decoration:none;white-space:nowrap;
  backdrop-filter:blur(12px);font-family:'DM Sans',sans-serif;
}
.ab-btn:hover{color:var(--text);background:rgba(255,255,255,0.1);border-color:rgba(255,255,255,0.2);}
.ab-btn.ai-active{background:rgba(79,179,255,0.12);border-color:rgba(79,179,255,0.35);color:var(--blue);}
.ab-btn.wa-btn{background:rgba(37,211,102,0.1);border-color:rgba(37,211,102,0.3);color:#25d366;}
.ab-btn.bm-btn{background:rgba(255,208,0,0.08);border-color:rgba(255,208,0,0.25);color:var(--miss);}
.ab-btn.bm-btn.saved{background:rgba(255,208,0,0.2);color:var(--miss);}
.ab-spacer{flex:1;}
.nav-pair{display:flex;gap:6px;}
.nav-btn{
  width:38px;height:38px;border-radius:12px;
  display:flex;align-items:center;justify-content:center;
  background:rgba(255,255,255,0.06);border:1px solid var(--bdr);
  color:var(--muted);cursor:pointer;font-size:1.1rem;
  transition:all 0.18s;backdrop-filter:blur(12px);
}
.nav-btn:hover{color:var(--text);background:rgba(255,255,255,0.12);}
.nav-btn:disabled{opacity:0.2;cursor:default;pointer-events:none;}

/* ══ SWIPE HINT ════════════════════════════════════════════ */
#swipeHint{
  position:fixed;bottom:78px;left:50%;transform:translateX(-50%);
  font-size:0.55rem;color:var(--muted);letter-spacing:3px;
  pointer-events:none;animation:hintOut 4s ease forwards;
  z-index:200;display:flex;flex-direction:column;align-items:center;gap:6px;
}
.hint-arrow{
  width:28px;height:28px;border-radius:50%;
  background:rgba(255,255,255,0.06);border:1px solid var(--bdr);
  display:flex;align-items:center;justify-content:center;font-size:0.8rem;
  animation:arrowBounce 1.2s ease infinite;
}
@keyframes arrowBounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}
@keyframes hintOut{0%{opacity:0;transform:translateX(-50%) translateY(10px)}10%{opacity:1;transform:translateX(-50%) translateY(0)}75%{opacity:1}100%{opacity:0}}

/* ══ BOOKMARK TOAST ════════════════════════════════════════ */
#bmToast{
  position:fixed;bottom:80px;left:50%;transform:translateX(-50%) translateY(20px);
  background:rgba(20,20,20,0.95);border:1px solid var(--bdr);
  backdrop-filter:blur(20px);border-radius:12px;padding:10px 20px;
  font-size:0.72rem;color:var(--text);letter-spacing:0.5px;
  opacity:0;pointer-events:none;transition:all 0.3s;z-index:500;white-space:nowrap;
}
#bmToast.show{opacity:1;transform:translateX(-50%) translateY(0);}

/* ══ EMPTY STATE ═══════════════════════════════════════════ */
.empty{
  position:absolute;inset:0;display:flex;flex-direction:column;
  align-items:center;justify-content:center;gap:16px;color:var(--muted);
}
.empty-icon{font-size:3.5rem;}
.empty-title{font-family:'Fraunces',serif;font-size:1.4rem;font-weight:700;}

/* ══ MOBILE TWEAKS ═════════════════════════════════════════ */
@media(max-width:480px){
  .nc-title{font-size:clamp(1.1rem,5.5vw,1.6rem);}
  .nc-panel{padding-bottom:155px;}
  #catBar{bottom:75px;}
}

/* ══ CARD NUMBER BUG ═══════════════════════════════════════ */
.nc-num-badge{
  position:absolute;top:60px;left:18px;
  font-family:'Bebas Neue',sans-serif;font-size:2.5rem;line-height:1;
  color:rgba(255,255,255,0.06);pointer-events:none;letter-spacing:2px;
}

/* ══ SWIPE GESTURE TRACK INDICATOR ════════════════════════ */
.swipe-track{
  position:absolute;right:8px;top:50%;transform:translateY(-50%);
  display:flex;flex-direction:column;gap:3px;align-items:center;
  z-index:5;pointer-events:none;
}
.swipe-dot{
  width:3px;border-radius:2px;background:rgba(255,255,255,0.15);
  transition:all 0.3s;
}
.swipe-dot.cur{background:var(--fake);box-shadow:0 0 6px var(--fake);}
</style>
</head>
<body>

<!-- Reading Progress -->
<div id="readProgress"></div>

<!-- Topbar -->
<div id="topbar">
  <a href="index.php" id="topbar-back">←</a>
  <div id="topbar-brand">NEWS IN <span>SHORT</span></div>
  <div id="topbar-meta" id="topCount">1 / <?= count($articles) ?></div>
</div>

<!-- Viewport -->
<div id="viewport">
<div id="cardsWrap">

<?php if (empty($articles)): ?>
<div class="empty">
  <div class="empty-icon">📰</div>
  <div class="empty-title">No stories here</div>
  <a href="?cat=all" style="color:var(--fake);font-size:0.8rem;letter-spacing:1px;">Show all →</a>
</div>
<?php else:
$catColors=['Politics'=>'#e63232','Health'=>'#00c853','Science'=>'#2979ff','Technology'=>'#aa00ff','Sports'=>'#ff6d00','Finance'=>'#ffd600','Entertainment'=>'#ff4081','World'=>'#00bcd4','Environment'=>'#8bc34a','Education'=>'#ff9800','General'=>'#888'];
$catEmoji=['Politics'=>'🏛','Health'=>'💊','Science'=>'🔬','Technology'=>'💻','Sports'=>'🏏','Finance'=>'📈','Entertainment'=>'🎬','World'=>'🌍','Environment'=>'🌿','Education'=>'📚','General'=>'📰'];
foreach($articles as $i=>$a):
  $cat=$a['category']??'General';$cc=$catColors[$cat]??'#888';$ce=$catEmoji[$cat]??'📰';
  $v=$a['verdict']??'unverified';
  $vc=$v==='fake'?'var(--fake)':($v==='real'?'var(--real)':($v==='misleading'?'var(--miss)':'var(--unver)'));
  $vhex=$v==='fake'?'#ff2d2d':($v==='real'?'#00e87a':($v==='misleading'?'#ffd000':'#555'));
  $vemoji=$v==='fake'?'🚨':($v==='real'?'✅':($v==='misleading'?'⚠️':'🔍'));
  $conf=(int)($a['confidence_score']??0);
  $confDash=round(($conf/100)*125.66);
  $words=explode(' ',strip_tags($a['content']));
  $short=implode(' ',array_slice($words,0,55));if(count($words)>55)$short.='...';
  $cred=(int)($a['credibility_score']??50);
  $credColor=$cred>70?'#00e87a':($cred>40?'#ffd000':'#ff2d2d');
?>
<div class="nc" id="card-<?= $i ?>">
  <!-- BG Image -->
  <div class="nc-bg">
    <img class="nc-img" id="img-<?= $i ?>" alt="<?= htmlspecialchars($cat) ?>">
    <div class="nc-gradient"></div>
    <div class="nc-verdict-glow" style="background:radial-gradient(ellipse at bottom,<?= $vhex ?>,transparent);"></div>
  </div>

  <!-- Big card number watermark -->
  <div class="nc-num-badge"><?= str_pad($i+1,2,'0',STR_PAD_LEFT) ?></div>

  <!-- Confidence Arc (top right) -->
  <?php if($conf > 0): ?>
  <div class="nc-conf">
    <svg width="52" height="52" viewBox="0 0 52 52">
      <circle cx="26" cy="26" r="20" fill="rgba(0,0,0,0.5)" stroke="rgba(255,255,255,0.06)" stroke-width="2"/>
      <circle cx="26" cy="26" r="20" fill="none" stroke="<?= $vhex ?>" stroke-width="3"
        stroke-dasharray="<?= $confDash ?> 125.66" stroke-linecap="round"
        transform="rotate(-90 26 26)" stroke-opacity="0.85"/>
    </svg>
    <div class="nc-conf-num" style="color:<?= $vhex ?>"><?= $conf ?>%</div>
    <div class="nc-conf-label">conf</div>
  </div>
  <?php endif; ?>

  <!-- Swipe track dots (right side) -->
  <div class="swipe-track" id="track-<?= $i ?>"></div>

  <!-- Content Panel -->
  <div class="nc-panel">
    <!-- Category + Verdict row -->
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
      <div class="nc-cat" style="color:<?= $cc ?>;border-color:<?= $cc ?>55;background:<?= $cc ?>12;">
        <?= $ce ?> <?= htmlspecialchars($cat) ?>
      </div>
      <div class="nc-verdict" style="color:<?= $vhex ?>;border-color:<?= $vhex ?>55;">
        <?= $vemoji ?> <?= strtoupper($v) ?>
      </div>
    </div>

    <!-- Title -->
    <div class="nc-title"><?= htmlspecialchars($a['title']) ?></div>

    <!-- Thin divider -->
    <div class="nc-divider"></div>

    <!-- Summary -->
    <div class="nc-summary" id="sum-<?= $i ?>"><?= htmlspecialchars($short) ?></div>
    <button class="nc-read-toggle" onclick="toggleExpand(<?= $i ?>)" id="toggle-<?= $i ?>">▾ READ MORE</button>

    <!-- AI Box (slides up from this panel) -->
    <div class="nc-ai" id="ai-<?= $i ?>">
      <button class="nc-ai-close" onclick="closeAI(<?= $i ?>)">✕</button>
      <div class="nc-ai-label">🤖 AI FACT CHECK</div>
      <div class="nc-ai-text" id="aitext-<?= $i ?>"></div>
    </div>

    <!-- Meta -->
    <div class="nc-meta">
      <?php if($a['source']): ?>
      <div class="nc-source">
        <div class="nc-cred-dot" style="background:<?= $credColor ?>;" title="Credibility: <?= $cred ?>/100"></div>
        <?= htmlspecialchars($a['source']) ?>
        <span style="font-size:0.5rem;color:var(--muted);opacity:0.6;">· <?= $cred ?>/100</span>
      </div>
      <?php endif; ?>
      <?php if($a['author'] && $a['author']!='Unknown'): ?>
      <div style="font-size:0.6rem;color:var(--muted);">by <?= htmlspecialchars(substr($a['author'],0,22)) ?></div>
      <?php endif; ?>
      <div class="nc-date"><?= $a['published_at'] ? date('d M Y',strtotime($a['published_at'])) : 'Recent' ?></div>
    </div>
  </div>
</div>
<?php endforeach; endif; ?>

</div><!-- /cardsWrap -->
</div><!-- /viewport -->

<!-- Swipe hint -->
<div id="swipeHint">
  <div class="hint-arrow">↑</div>
  <span>SWIPE UP</span>
</div>

<!-- Category bar -->
<div id="catBar">
<?php
$cats=['all'=>'🔥 All','Politics'=>'🏛 Politics','Health'=>'💊 Health','Technology'=>'💻 Tech',
       'Science'=>'🔬 Science','Sports'=>'🏏 Sports','Finance'=>'📈 Finance',
       'World'=>'🌍 World','Entertainment'=>'🎬 Ent.','Environment'=>'🌿 Eco','Education'=>'📚 Edu'];
foreach($cats as $key=>$label):
  $active=($category===$key)?'active':'';
?>
<a href="?cat=<?= urlencode($key) ?>" class="ct <?= $active ?>"><?= $label ?></a>
<?php endforeach; ?>
</div>

<!-- Bookmark toast -->
<div id="bmToast">🔖 Bookmarked!</div>

<!-- Action Bar -->
<div id="actionBar">
  <button class="ab-btn ai-active" id="aiBtnMain" onclick="triggerAI()">✨ AI Check</button>
  <a class="ab-btn" id="readMoreBtn" href="article_detail.php" target="_blank">📖 Full Article</a>
  <a class="ab-btn wa-btn" id="waBtn" href="whatsapp_share.php" target="_blank">📲 Share</a>
  <button class="ab-btn bm-btn" id="bmBtn" onclick="toggleBookmark()">🔖</button>
  <div class="ab-spacer"></div>
  <div class="nav-pair">
    <button class="nav-btn" id="btnPrev" onclick="navigate(-1)" disabled>↑</button>
    <button class="nav-btn" id="btnNext" onclick="navigate(1)">↓</button>
  </div>
</div>

<script>
/* ══════════════════════════════════════════════
   Data & State
══════════════════════════════════════════════ */
const ARTS   = <?= json_encode($articlesJson, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
const TOTAL  = ARTS.length;
let cur      = 0;
let aiCache  = {};
let expanded = {};
let bookmarks= JSON.parse(localStorage.getItem('fg_bookmarks')||'{}');
let imgDone  = new Set();
let aiOpen   = false;

/* ══ IMAGE LOADER ══════════════════════════════ */
function loadImg(i){
  if(i<0||i>=TOTAL||imgDone.has(i))return;
  imgDone.add(i);
  const img=document.getElementById('img-'+i);
  if(!img)return;
  img.onload=()=>img.classList.add('vis');
  img.src=ARTS[i].img;
}

/* ══ SWIPE TRACK DOTS ══════════════════════════ */
function buildTrack(cardIdx){
  const track=document.getElementById('track-'+cardIdx);
  if(!track||TOTAL>20)return;
  track.innerHTML='';
  for(let i=0;i<Math.min(TOTAL,15);i++){
    const d=document.createElement('div');
    d.className='swipe-dot'+(i===cardIdx?' cur':'');
    const h=i===cardIdx?18:6;
    d.style.height=h+'px';
    track.appendChild(d);
  }
}

/* ══ UI UPDATE ═════════════════════════════════ */
function updateUI(){
  const a=ARTS[cur];if(!a)return;
  // Topbar
  document.getElementById('topbar-meta').textContent=(cur+1)+' / '+TOTAL;
  // Progress bar
  document.getElementById('readProgress').style.width=((cur+1)/TOTAL*100)+'%';
  // Action bar links
  document.getElementById('readMoreBtn').href='article_detail.php?id='+a.id;
  document.getElementById('waBtn').href='whatsapp_share.php?id='+a.id;
  // Buttons
  document.getElementById('btnPrev').disabled=cur===0;
  document.getElementById('btnNext').disabled=cur===TOTAL-1;
  // Bookmark state
  const bm=document.getElementById('bmBtn');
  bm.classList.toggle('saved',!!bookmarks[a.id]);
  bm.textContent=bookmarks[a.id]?'🔖 Saved':'🔖';
  // Preload images
  loadImg(cur);
  setTimeout(()=>loadImg(cur+1),600);
  setTimeout(()=>loadImg(cur+2),1200);
  // Build track on current card
  buildTrack(cur);
}

/* ══ NAVIGATE ══════════════════════════════════ */
function navigate(dir){
  const next=cur+dir;
  if(next<0||next>=TOTAL)return;
  // Close AI if open
  const curAI=document.getElementById('ai-'+cur);
  if(curAI)curAI.classList.remove('open');
  aiOpen=false;

  const oldC=document.getElementById('card-'+cur);
  const newC=document.getElementById('card-'+next);
  if(!oldC||!newC)return;

  oldC.classList.add('anim');newC.classList.add('anim');

  if(dir>0){
    newC.style.transform='translateY(105%)';newC.style.visibility='visible';
    newC.classList.remove('prev','active');
    requestAnimationFrame(()=>requestAnimationFrame(()=>{
      oldC.classList.remove('active');oldC.classList.add('prev');
      newC.style.transform='';newC.classList.add('active');
    }));
  } else {
    newC.style.transform='translateY(-105%)';newC.style.visibility='visible';
    newC.classList.remove('prev','active');
    requestAnimationFrame(()=>requestAnimationFrame(()=>{
      oldC.style.transform='translateY(105%)';oldC.classList.remove('active');
      newC.style.transform='';newC.classList.add('active');
    }));
    setTimeout(()=>{oldC.style.transform='';oldC.style.visibility='';oldC.classList.remove('anim');},500);
  }
  cur=next;updateUI();
}

/* ══ TOUCH / SWIPE ═════════════════════════════ */
let ty=0,tx=0,touching=false,moved=false;
const vp=document.getElementById('viewport');
vp.addEventListener('touchstart',e=>{ty=e.touches[0].clientY;tx=e.touches[0].clientX;touching=true;moved=false;},{passive:true});
vp.addEventListener('touchmove',e=>{if(Math.abs(e.touches[0].clientY-ty)>10)moved=true;},{passive:true});
vp.addEventListener('touchend',e=>{
  if(!touching||!moved)return;touching=false;
  const dy=ty-e.changedTouches[0].clientY;
  const dx=tx-e.changedTouches[0].clientX;
  if(Math.abs(dy)>Math.abs(dx)&&Math.abs(dy)>50)navigate(dy>0?1:-1);
},{passive:true});

/* ══ KEYBOARD ══════════════════════════════════ */
document.addEventListener('keydown',e=>{
  if(e.key==='ArrowDown'||e.key===' ')navigate(1);
  if(e.key==='ArrowUp')navigate(-1);
  if(e.key==='Escape'){const ai=document.getElementById('ai-'+cur);if(ai)ai.classList.remove('open');aiOpen=false;}
});

/* ══ MOUSE WHEEL ═══════════════════════════════ */
let wLock=false;
vp.addEventListener('wheel',e=>{if(wLock)return;wLock=true;navigate(e.deltaY>0?1:-1);setTimeout(()=>wLock=false,700);},{passive:true});

/* ══ EXPAND SUMMARY ════════════════════════════ */
function toggleExpand(i){
  const s=document.getElementById('sum-'+i);
  const t=document.getElementById('toggle-'+i);
  expanded[i]=!expanded[i];
  s.classList.toggle('expanded',expanded[i]);
  t.textContent=expanded[i]?'▴ SHOW LESS':'▾ READ MORE';
}

/* ══ AI CHECK ══════════════════════════════════ */
function triggerAI(){
  const a=ARTS[cur];
  const aiPanel=document.getElementById('ai-'+cur);
  const aiTextEl=document.getElementById('aitext-'+cur);
  const aiBtn=document.getElementById('aiBtnMain');

  if(aiOpen){aiPanel.classList.remove('open');aiOpen=false;aiBtn.textContent='✨ AI Check';return;}

  aiPanel.classList.add('open');aiOpen=true;aiBtn.textContent='✨ Hide AI';

  if(aiCache[cur]){aiTextEl.innerHTML=aiCache[cur];return;}

  aiTextEl.innerHTML='<span style="color:var(--blue);font-size:0.75rem;letter-spacing:1px;">⏳ GROQ AI ANALYSING...</span>';
  fetch('ai_check.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'article_id='+a.id})
  .then(r=>r.json()).then(data=>{
    if(data.error){
      aiTextEl.innerHTML=`<span style="color:var(--fake)">⚠ ${data.error}</span>`;return;
    }
    const col=data.verdict==='fake'?'var(--fake)':(data.verdict==='real'?'var(--real)':'var(--miss)');
    const bar=`<div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid rgba(255,255,255,0.07);">
      <span style="font-family:'Bebas Neue',sans-serif;font-size:1.2rem;color:${col};letter-spacing:2px;">${data.verdict.toUpperCase()}</span>
      <span style="font-size:0.6rem;color:var(--muted);letter-spacing:1px;">${data.confidence}% CONFIDENCE</span>
    </div>`;
    const html=bar+`<div style="font-size:0.8rem;line-height:1.72;color:rgba(255,255,255,0.78);">${data.reason}</div>`;
    aiCache[cur]=html;aiTextEl.innerHTML=html;
  })
  .catch(()=>{aiTextEl.innerHTML='<span style="color:var(--fake)">⚠ Connection error</span>';});
}

function closeAI(i){
  document.getElementById('ai-'+i).classList.remove('open');
  aiOpen=false;
  document.getElementById('aiBtnMain').textContent='✨ AI Check';
}

/* ══ BOOKMARK ══════════════════════════════════ */
function toggleBookmark(){
  const a=ARTS[cur];
  if(bookmarks[a.id]){delete bookmarks[a.id];}
  else{bookmarks[a.id]={id:a.id,title:a.title,verdict:a.verdict,date:new Date().toISOString()};}
  localStorage.setItem('fg_bookmarks',JSON.stringify(bookmarks));
  const bm=document.getElementById('bmBtn');
  bm.classList.toggle('saved',!!bookmarks[a.id]);
  bm.textContent=bookmarks[a.id]?'🔖 Saved':'🔖';
  showToast(bookmarks[a.id]?'🔖 Bookmarked!':'Bookmark removed');
}
function showToast(msg){
  const t=document.getElementById('bmToast');
  t.textContent=msg;t.classList.add('show');
  setTimeout(()=>t.classList.remove('show'),2200);
}

/* ══ INIT ═══════════════════════════════════════ */
(function init(){
  const first=document.getElementById('card-0');
  if(!first)return;
  first.style.transition='none';
  first.classList.add('active');
  first.offsetHeight;
  first.style.transition='';
  setTimeout(()=>document.querySelectorAll('.nc').forEach(c=>c.classList.add('anim')),100);
  updateUI();
})();
</script>
</body>
</html>