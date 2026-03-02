<?php
require_once 'includes/db.php';
require_once 'lang.php';
$pageTitle = 'Dashboard';

$stats = $pdo->query("
    SELECT COUNT(*) AS total,
        SUM(CASE WHEN v.verdict='fake'       THEN 1 ELSE 0 END) AS fake_count,
        SUM(CASE WHEN v.verdict='real'       THEN 1 ELSE 0 END) AS real_count,
        SUM(CASE WHEN v.verdict='misleading' THEN 1 ELSE 0 END) AS misleading_count
    FROM articles a
    LEFT JOIN verifications v ON a.article_id = v.article_id
")->fetch();

$recent = $pdo->query("
    SELECT a.article_id, a.title, a.author, a.created_at,
           c.name AS category, s.name AS source,
           v.verdict, v.confidence_score, u.username AS checker
    FROM articles a
    LEFT JOIN categories    c ON a.category_id = c.category_id
    LEFT JOIN sources       s ON a.source_id   = s.source_id
    LEFT JOIN verifications v ON a.article_id  = v.article_id
    LEFT JOIN users         u ON v.checked_by  = u.user_id
    ORDER BY a.created_at DESC LIMIT 6
")->fetchAll();

$checkers = $pdo->query("
    SELECT u.username, COUNT(v.verification_id) AS checked,
           SUM(CASE WHEN v.verdict='fake' THEN 1 ELSE 0 END) AS fakes_found
    FROM users u JOIN verifications v ON u.user_id = v.checked_by
    GROUP BY u.user_id ORDER BY checked DESC
")->fetchAll();

$category_stats = $pdo->query("
    SELECT c.name, COUNT(a.article_id) AS total,
           SUM(CASE WHEN v.verdict='fake' THEN 1 ELSE 0 END) AS fake_count,
           SUM(CASE WHEN v.verdict='real' THEN 1 ELSE 0 END) AS real_count
    FROM categories c
    LEFT JOIN articles a ON c.category_id = a.category_id
    LEFT JOIN verifications v ON a.article_id = v.article_id
    GROUP BY c.category_id ORDER BY total DESC
")->fetchAll();

// NEW: Monthly trend — articles added per month (last 6 months)
$monthly = $pdo->query("
    SELECT DATE_FORMAT(a.created_at,'%b %Y') AS month_label,
           COUNT(*)                           AS total,
           SUM(CASE WHEN v.verdict='fake' THEN 1 ELSE 0 END) AS fake_count,
           SUM(CASE WHEN v.verdict='real' THEN 1 ELSE 0 END) AS real_count
    FROM articles a
    LEFT JOIN verifications v ON a.article_id = v.article_id
    WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(a.created_at,'%Y-%m')
    ORDER BY MIN(a.created_at) ASC
")->fetchAll();

// NEW: Source credibility vs fake count
$source_stats = $pdo->query("
    SELECT s.name, s.credibility_score,
           COUNT(a.article_id) AS article_count,
           SUM(CASE WHEN v.verdict='fake' THEN 1 ELSE 0 END) AS fake_count
    FROM sources s
    LEFT JOIN articles a ON s.source_id = a.source_id
    LEFT JOIN verifications v ON a.article_id = v.article_id
    GROUP BY s.source_id
    ORDER BY s.credibility_score ASC
    LIMIT 8
")->fetchAll();

$pending_reports = $pdo->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn();

// News Feed
$newsfeed = $pdo->query("
    SELECT a.article_id, a.title, a.content, a.author, a.published_at, a.url,
           c.name AS category, s.name AS source, s.credibility_score,
           v.verdict, v.confidence_score
    FROM articles a
    LEFT JOIN categories    c ON a.category_id = c.category_id
    LEFT JOIN sources       s ON a.source_id   = s.source_id
    LEFT JOIN verifications v ON a.article_id  = v.article_id
    ORDER BY a.created_at DESC LIMIT 20
")->fetchAll();

require_once 'includes/header.php';

$cat_colors = [
    'Politics'   => '#e63232',
    'Health'     => '#00c853',
    'Science'    => '#2979ff',
    'Technology' => '#aa00ff',
    'Sports'     => '#ff6d00',
    'Finance'    => '#ffd600',
];

// Prepare JS data arrays
$monthly_labels = json_encode(array_column($monthly, 'month_label'));
$monthly_fake   = json_encode(array_map('intval', array_column($monthly, 'fake_count')));
$monthly_real   = json_encode(array_map('intval', array_column($monthly, 'real_count')));
$monthly_total  = json_encode(array_map('intval', array_column($monthly, 'total')));

$src_labels = json_encode(array_column($source_stats, 'name'));
$src_cred   = json_encode(array_map('intval', array_column($source_stats, 'credibility_score')));
$src_fake   = json_encode(array_map('intval', array_column($source_stats, 'fake_count')));

$cat_labels = json_encode(array_column($category_stats, 'name'));
$cat_total  = json_encode(array_map('intval', array_column($category_stats, 'total')));
$cat_fake   = json_encode(array_map('intval', array_column($category_stats, 'fake_count')));
$cat_real   = json_encode(array_map('intval', array_column($category_stats, 'real_count')));
?>

<style>
/* HERO */
.hero {
  position: relative;
  z-index: 10;
  background: linear-gradient(135deg, #0d0d0d 0%, #1a0000 50%, #0d0d0d 100%);
  border-bottom: 1px solid #2a2a2a;
  padding: 60px 32px;
  overflow: hidden;
  text-align: center;
}
.hero::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at center, rgba(230,50,50,0.12) 0%, transparent 70%);
}
.hero-eyebrow {
  font-family: 'Oswald', sans-serif;
  font-size: 0.65rem;
  letter-spacing: 5px;
  color: var(--accent);
  text-transform: uppercase;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
}
.hero-eyebrow::before,.hero-eyebrow::after{content:'';width:40px;height:1px;background:var(--accent);}
.hero h1{font-family:'Playfair Display',serif;font-size:clamp(2.5rem,6vw,5rem);font-weight:900;line-height:1.05;margin-bottom:20px;position:relative;z-index:1;}
.hero h1 em{color:var(--accent);font-style:italic;}
.hero-sub{font-family:'Oswald',sans-serif;font-size:0.8rem;letter-spacing:3px;color:var(--muted);margin-bottom:32px;}
.hero-actions{display:flex;gap:12px;justify-content:center;position:relative;z-index:1;}
.hero-divider{width:60px;height:3px;background:var(--accent);margin:0 auto 20px;}

/* STATS */
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

/* MAGAZINE GRID */
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
.mag-card-featured{grid-column:span 2;border-right:1px solid var(--border);}
.mag-card-featured .mag-title{font-size:1.5rem;line-height:1.3;}
.mag-verdict-bar{height:3px;position:absolute;bottom:0;left:0;right:0;}

/* CHARTS */
.chart-section-title {
  font-family:'Oswald',sans-serif;font-size:0.6rem;letter-spacing:4px;
  color:var(--accent);text-transform:uppercase;margin-bottom:24px;
  display:flex;align-items:center;gap:12px;
}
.chart-section-title::after{content:'';flex:1;height:1px;background:var(--border);}
.charts-4col{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:32px;}
.charts-full{margin-bottom:32px;}
.chart-card{background:var(--card);border:1px solid var(--border);border-radius:4px;overflow:hidden;position:relative;}
.chart-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--accent),transparent);}
.chart-card-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.chart-card-title{font-family:'Oswald',sans-serif;font-size:0.78rem;font-weight:600;letter-spacing:2px;text-transform:uppercase;}
.chart-card-sub{font-family:'Oswald',sans-serif;font-size:0.6rem;letter-spacing:1px;color:var(--muted);}
.chart-body{padding:20px;position:relative;}
.chart-body canvas{max-height:260px;}

/* DONUT (legacy, kept for compatibility) */
.donut-section{display:flex;align-items:center;gap:24px;padding:24px;}
.donut-legend-item{display:flex;align-items:center;gap:10px;margin-bottom:14px;}
.donut-legend-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;}
.donut-legend-label{font-family:'Oswald',sans-serif;font-size:0.7rem;letter-spacing:1px;color:var(--muted);flex:1;text-transform:uppercase;}
.donut-legend-val{font-family:'Playfair Display',serif;font-size:1.2rem;font-weight:700;}

/* FEED */
.feed-card:hover{background:rgba(255,255,255,0.025) !important;}
.feed-tab{background:transparent;border:1px solid var(--border);color:var(--muted);font-family:'Oswald',sans-serif;font-size:0.62rem;letter-spacing:2px;text-transform:uppercase;padding:4px 10px;border-radius:20px;cursor:pointer;transition:all 0.2s;}
.feed-tab:hover,.feed-tab.active{background:var(--accent);border-color:var(--accent);color:#fff;}
.feed-btn{font-size:0.62rem;padding:4px 10px;border-radius:6px;cursor:pointer;text-decoration:none;font-family:'Oswald',sans-serif;letter-spacing:1px;transition:all 0.15s;border:none;}
.feed-btn-ai{background:rgba(79,179,255,0.1);color:var(--blue);border:1px solid rgba(79,179,255,0.25);}
.feed-btn-ai:hover{background:rgba(79,179,255,0.2);}
.feed-btn-read{background:rgba(230,50,50,0.1);color:var(--accent);border:1px solid rgba(230,50,50,0.25);}
.feed-btn-read:hover{background:rgba(230,50,50,0.2);}
.feed-btn-source{background:rgba(255,255,255,0.05);color:var(--muted);border:1px solid var(--border);}
.feed-btn-source:hover{color:var(--text);}
.feed-btn-wa{background:rgba(37,211,102,0.12);color:#25d366;border:1px solid rgba(37,211,102,0.3);}
.feed-btn-wa:hover{background:rgba(37,211,102,0.22);}

@media(max-width:900px){.magazine-grid{grid-template-columns:1fr 1fr;}.mag-card-featured{grid-column:span 2;}.stats-row-new{grid-template-columns:repeat(2,1fr);}.charts-4col{grid-template-columns:1fr;}}
@media(max-width:600px){.magazine-grid{grid-template-columns:1fr;}.mag-card-featured{grid-column:span 1;}.stats-row-new{grid-template-columns:1fr 1fr;}}
</style>

<!-- HERO -->
<div class="hero">
  <div class="hero-eyebrow">AI-Powered Fact Checking</div>
  <h1>Fighting <em>Fake News</em><br>One Article at a Time</h1>
  <div class="hero-divider"></div>
  <div class="hero-sub">REAL-TIME VERIFICATION · AI ASSISTED · DATABASE DRIVEN</div>
  <div class="hero-actions">
    <a href="add_article.php" class="btn btn-primary">+ Submit Article</a>
    <a href="verify.php"      class="btn btn-green">✓ Verify Now</a>
    <a href="articles.php"    class="btn btn-ghost">Browse All →</a>
  </div>
</div>

<div class="wrapper">

  <?php if($pending_reports > 0): ?>
  <div class="alert alert-error mb-20">
    ⚠️ <?= $pending_reports ?> pending report(s) need review!
    <a href="reports.php" style="color:var(--accent);margin-left:8px;">View Reports →</a>
  </div>
  <?php endif; ?>

  <!-- STATS -->
  <?php
  $total    = $stats['total'] ?: 1;
  $fake_pct = round(($stats['fake_count']/$total)*100);
  $real_pct = round(($stats['real_count']/$total)*100);
  $mis_pct  = round(($stats['misleading_count']/$total)*100);
  ?>
  <div class="stats-row-new">
    <div class="stat-new r">
      <div class="stat-new-label">Fake Articles</div>
      <div class="stat-new-val"><?= $stats['fake_count'] ?? 0 ?></div>
      <div class="stat-new-bar"><div class="stat-new-bar-fill" style="width:<?= $fake_pct ?>%"></div></div>
      <div class="stat-new-ghost"><?= $stats['fake_count'] ?? 0 ?></div>
    </div>
    <div class="stat-new g">
      <div class="stat-new-label">Verified Real</div>
      <div class="stat-new-val"><?= $stats['real_count'] ?? 0 ?></div>
      <div class="stat-new-bar"><div class="stat-new-bar-fill" style="width:<?= $real_pct ?>%"></div></div>
      <div class="stat-new-ghost"><?= $stats['real_count'] ?? 0 ?></div>
    </div>
    <div class="stat-new y">
      <div class="stat-new-label">Misleading</div>
      <div class="stat-new-val"><?= $stats['misleading_count'] ?? 0 ?></div>
      <div class="stat-new-bar"><div class="stat-new-bar-fill" style="width:<?= $mis_pct ?>%"></div></div>
      <div class="stat-new-ghost"><?= $stats['misleading_count'] ?? 0 ?></div>
    </div>
    <div class="stat-new b">
      <div class="stat-new-label">Total Articles</div>
      <div class="stat-new-val"><?= $stats['total'] ?? 0 ?></div>
      <div class="stat-new-bar"><div class="stat-new-bar-fill" style="width:100%"></div></div>
      <div class="stat-new-ghost"><?= $stats['total'] ?? 0 ?></div>
    </div>
  </div>

  <!-- MAGAZINE CARDS -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
    <div style="font-family:'Oswald',sans-serif;font-size:0.65rem;letter-spacing:3px;color:var(--muted);text-transform:uppercase;">Latest Articles</div>
    <a href="articles.php" class="btn btn-ghost" style="font-size:0.65rem;padding:6px 14px;">View All →</a>
  </div>
  <div class="magazine-grid">
    <?php foreach($recent as $i => $row):
      $v = $row['verdict'] ?? 'unverified';
      $vcolor = $v=='fake'?'var(--accent)':($v=='real'?'var(--green)':($v=='misleading'?'var(--yellow)':'var(--muted)'));
      $cat = $row['category'] ?? 'General';
      $catcolor = $cat_colors[$cat] ?? '#777';
    ?>
    <div class="mag-card <?= $i==0?'mag-card-featured':'' ?>">
      <div class="mag-card-top">
        <span class="mag-cat" style="background:<?= $catcolor ?>22;color:<?= $catcolor ?>;border:1px solid <?= $catcolor ?>44"><?= htmlspecialchars($cat) ?></span>
        <span class="mag-num">#<?= str_pad($row['article_id'],3,'0',STR_PAD_LEFT) ?></span>
      </div>
      <a href="article_detail.php?id=<?= $row['article_id'] ?>" class="mag-title">
        <?= htmlspecialchars($i==0 ? $row['title'] : substr($row['title'],0,80).'...') ?>
      </a>
      <div class="mag-meta">
        <span class="mag-source"><?= htmlspecialchars($row['source'] ?? '—') ?></span>
        <span class="badge b-<?= $v ?>"><?= strtoupper($v) ?></span>
      </div>
      <?php if($row['confidence_score']): ?>
      <div class="cbar-wrap" style="margin-top:4px;">
        <div class="cbar"><div class="cbar-fill" style="width:<?= $row['confidence_score'] ?>%;background:<?= $vcolor ?>"></div></div>
        <div class="cbar-num"><?= $row['confidence_score'] ?>%</div>
      </div>
      <?php endif; ?>
      <div class="mag-verdict-bar" style="background:<?= $vcolor ?>;opacity:0.4;"></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ══════════════════════════════════
       ANALYTICS CHARTS SECTION
  ══════════════════════════════════ -->
  <div class="chart-section-title">📊 Analytics & Insights</div>

  <!-- Row 1: Doughnut + Line Chart -->
  <div class="charts-4col">

    <!-- Verdict Doughnut -->
    <div class="chart-card">
      <div class="chart-card-header">
        <div class="chart-card-title">Verdict Distribution</div>
        <div class="chart-card-sub">All verified articles</div>
      </div>
      <div class="chart-body" style="display:flex;align-items:center;gap:20px;">
        <div style="flex:1;max-width:180px;"><canvas id="verdictDoughnut"></canvas></div>
        <div style="flex:1;">
          <div class="donut-legend-item">
            <div class="donut-legend-dot" style="background:var(--accent)"></div>
            <div class="donut-legend-label">Fake</div>
            <div class="donut-legend-val" style="color:var(--accent)"><?= $stats['fake_count'] ?? 0 ?></div>
          </div>
          <div class="donut-legend-item">
            <div class="donut-legend-dot" style="background:var(--green)"></div>
            <div class="donut-legend-label">Real</div>
            <div class="donut-legend-val" style="color:var(--green)"><?= $stats['real_count'] ?? 0 ?></div>
          </div>
          <div class="donut-legend-item">
            <div class="donut-legend-dot" style="background:var(--yellow)"></div>
            <div class="donut-legend-label">Misleading</div>
            <div class="donut-legend-val" style="color:var(--yellow)"><?= $stats['misleading_count'] ?? 0 ?></div>
          </div>
          <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--border);">
            <div style="font-family:'Oswald',sans-serif;font-size:0.6rem;letter-spacing:2px;color:var(--muted);">FAKE RATE</div>
            <div style="font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:900;color:var(--accent);"><?= $fake_pct ?>%</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Monthly Trend Line Chart -->
    <div class="chart-card">
      <div class="chart-card-header">
        <div class="chart-card-title">Monthly Detection Trend</div>
        <div class="chart-card-sub">Last 6 months</div>
      </div>
      <div class="chart-body"><canvas id="monthlyLine"></canvas></div>
    </div>

  </div>

  <!-- Row 2: Category Grouped Bar -->
  <div class="charts-full">
    <div class="chart-card">
      <div class="chart-card-header">
        <div class="chart-card-title">Category Breakdown — Fake vs Real</div>
        <div class="chart-card-sub">Articles per category with verdict split</div>
      </div>
      <div class="chart-body"><canvas id="categoryBar" style="max-height:220px;"></canvas></div>
    </div>
  </div>

  <!-- Row 3: Source Credibility Radar + Horizontal Bar -->
  <div class="charts-4col">

    <!-- Source Credibility Bar -->
    <div class="chart-card">
      <div class="chart-card-header">
        <div class="chart-card-title">Source Credibility Scores</div>
        <div class="chart-card-sub">Lower = less trusted</div>
      </div>
      <div class="chart-body"><canvas id="sourceBar"></canvas></div>
    </div>

    <!-- Fake by Source Pie -->
    <div class="chart-card">
      <div class="chart-card-header">
        <div class="chart-card-title">Fake Articles by Source</div>
        <div class="chart-card-sub">Which sources publish most misinformation</div>
      </div>
      <div class="chart-body"><canvas id="sourceFakePie"></canvas></div>
    </div>

  </div>

  <!-- TOP CHECKERS + QUICK ACTIONS -->
  <div class="grid-2 mb-20">
    <div class="card">
      <div class="card-header"><div class="card-title">Top Fact Checkers</div></div>
      <table>
        <thead><tr><th>Username</th><th>Verified</th><th>Fakes Found</th></tr></thead>
        <tbody>
        <?php foreach($checkers as $c): ?>
        <tr>
          <td style="color:var(--blue);font-family:'Oswald',sans-serif;letter-spacing:1px;"><?= htmlspecialchars($c['username']) ?></td>
          <td style="font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:700;"><?= $c['checked'] ?></td>
          <td style="color:var(--accent);font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:700;"><?= $c['fakes_found'] ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card">
      <div class="card-header"><div class="card-title">Quick Actions</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:10px;">
        <a href="add_article.php" class="btn btn-primary" style="text-align:center;">+ Submit New Article</a>
        <a href="verify.php"      class="btn btn-green"   style="text-align:center;">✓ Verify an Article</a>
        <a href="articles.php"    class="btn btn-ghost"   style="text-align:center;">📰 Browse All Articles</a>
        <a href="reports.php"     class="btn btn-ghost"   style="text-align:center;">🚨 View Reports <?= $pending_reports>0?"($pending_reports)":'' ?></a>
        <a href="whatsapp_share.php" class="btn" style="text-align:center;background:rgba(37,211,102,0.12);color:#25d366;border:1px solid rgba(37,211,102,0.35);">📲 Share Fact Check on WhatsApp</a>
        <a href="news_short.php"  class="btn btn-ghost"   style="text-align:center;">📰 News in Short</a>
      </div>
    </div>
  </div>

  <!-- INSHORTS FEED -->
  <div style="margin-top:40px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
      <div style="display:flex;align-items:center;gap:14px;">
        <div style="width:4px;height:28px;background:var(--accent);border-radius:2px;"></div>
        <div>
          <div style="font-family:'Oswald',sans-serif;font-size:0.6rem;letter-spacing:4px;color:var(--accent);text-transform:uppercase;margin-bottom:2px;">Latest Updates</div>
          <div style="font-family:'Playfair Display',serif;font-size:1.3rem;font-weight:700;">News in Short</div>
        </div>
      </div>
      <div style="display:flex;gap:8px;align-items:center;">
        <div id="feedFilterBar" style="display:flex;gap:6px;flex-wrap:wrap;">
          <button onclick="filterFeed('all')"        class="feed-tab active" data-cat="all">All</button>
          <button onclick="filterFeed('Politics')"   class="feed-tab" data-cat="Politics">Politics</button>
          <button onclick="filterFeed('Health')"     class="feed-tab" data-cat="Health">Health</button>
          <button onclick="filterFeed('Technology')" class="feed-tab" data-cat="Technology">Tech</button>
          <button onclick="filterFeed('Science')"    class="feed-tab" data-cat="Science">Science</button>
          <button onclick="filterFeed('Sports')"     class="feed-tab" data-cat="Sports">Sports</button>
          <button onclick="filterFeed('Finance')"    class="feed-tab" data-cat="Finance">Finance</button>
        </div>
      </div>
    </div>

    <div id="newsFeedContainer" style="display:flex;flex-direction:column;gap:1px;background:var(--border);border:1px solid var(--border);border-radius:8px;overflow:hidden;">
    <?php
    $feed_cat_colors=['Politics'=>'#e63232','Health'=>'#00c853','Science'=>'#2979ff','Technology'=>'#aa00ff','Sports'=>'#ff6d00','Finance'=>'#ffd600','Entertainment'=>'#ff4081','World'=>'#00bcd4','Environment'=>'#8bc34a','Education'=>'#ff9800'];
    foreach($newsfeed as $nf):
      $nv=$nf['verdict']??'unverified';
      $ncat=$nf['category']??'General';
      $nccolor=$feed_cat_colors[$ncat]??'#777';
      $nvcolor=$nv=='fake'?'#e63232':($nv=='real'?'#00c853':($nv=='misleading'?'#ffd600':'#666'));
      $words=explode(' ',strip_tags($nf['content']));
      $summary=implode(' ',array_slice($words,0,60));
      if(count($words)>60)$summary.='...';
      $pub_date=$nf['published_at']?date('d M Y',strtotime($nf['published_at'])):'Recent';
    ?>
    <div class="feed-card" data-cat="<?= htmlspecialchars($ncat) ?>"
         style="background:var(--card);padding:18px 24px;display:flex;gap:20px;align-items:flex-start;transition:background 0.15s;position:relative;overflow:hidden;">
      <div style="position:absolute;left:0;top:0;bottom:0;width:3px;background:<?= $nccolor ?>;opacity:0.7;"></div>
      <div style="flex:1;padding-left:8px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;flex-wrap:wrap;">
          <span style="background:<?= $nccolor ?>18;color:<?= $nccolor ?>;border:1px solid <?= $nccolor ?>33;font-size:0.58rem;font-family:'Oswald',sans-serif;letter-spacing:2px;padding:2px 8px;border-radius:20px;text-transform:uppercase;"><?= htmlspecialchars($ncat) ?></span>
          <span style="background:<?= $nvcolor ?>18;color:<?= $nvcolor ?>;border:1px solid <?= $nvcolor ?>33;font-size:0.58rem;font-family:'Oswald',sans-serif;letter-spacing:2px;padding:2px 8px;border-radius:20px;text-transform:uppercase;"><?= $nv==='unverified'?'⚪ UNVERIFIED':($nv==='fake'?'🔴 FAKE':($nv==='real'?'🟢 REAL':'🟡 '.strtoupper($nv))) ?></span>
          <span style="color:var(--muted);font-size:0.62rem;margin-left:auto;"><?= $pub_date ?></span>
        </div>
        <div style="font-family:'Playfair Display',serif;font-size:0.88rem;font-weight:700;line-height:1.4;margin-bottom:8px;color:var(--text);"><?= htmlspecialchars($nf['title']) ?></div>
        <div class="feed-summary" style="font-size:0.76rem;color:var(--muted);line-height:1.65;margin-bottom:12px;"><?= htmlspecialchars($summary) ?></div>
        <div class="ai-summary-box" id="ai-<?= $nf['article_id'] ?>" style="display:none;background:rgba(79,179,255,0.05);border:1px solid rgba(79,179,255,0.2);border-radius:8px;padding:12px;margin-bottom:12px;font-size:0.76rem;line-height:1.65;color:var(--text);">
          <div style="font-size:0.6rem;color:var(--blue);letter-spacing:2px;margin-bottom:6px;">🤖 AI SUMMARY</div>
          <div class="ai-summary-text">Loading...</div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
          <span style="font-size:0.65rem;color:var(--muted);">
            <?= $nf['source']?'📰 '.htmlspecialchars($nf['source']):'' ?>
            <?= $nf['author']&&$nf['author']!='Unknown'?' · '.htmlspecialchars(substr($nf['author'],0,30)):'' ?>
          </span>
          <div style="display:flex;gap:6px;margin-left:auto;">
            <button onclick="toggleAISummary(<?= $nf['article_id'] ?>)" class="feed-btn feed-btn-ai">✨ AI Summary</button>
            <a href="article_detail.php?id=<?= $nf['article_id'] ?>" class="feed-btn feed-btn-read">Read More →</a>
            <a href="whatsapp_share.php?id=<?= $nf['article_id'] ?>" class="feed-btn feed-btn-wa">📲 Share</a>
            <?php if($nf['url']): ?>
            <a href="<?= htmlspecialchars($nf['url']) ?>" target="_blank" class="feed-btn feed-btn-source">↗ Source</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php if($nf['confidence_score']): ?>
      <div style="display:flex;flex-direction:column;align-items:center;gap:4px;flex-shrink:0;">
        <svg width="48" height="48" viewBox="0 0 36 36" style="transform:rotate(-90deg)">
          <circle cx="18" cy="18" r="15" fill="none" stroke="#2a2a2a" stroke-width="3"/>
          <circle cx="18" cy="18" r="15" fill="none" stroke="<?= $nvcolor ?>" stroke-width="3"
            stroke-dasharray="<?= round(($nf['confidence_score']/100)*94.2) ?> 94.2" stroke-linecap="round"/>
        </svg>
        <div style="font-size:0.6rem;color:var(--muted);text-align:center;margin-top:-4px;"><?= $nf['confidence_score'] ?>%</div>
        <div style="font-size:0.52rem;color:var(--muted);letter-spacing:1px;">CONF.</div>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php if(empty($newsfeed)): ?>
    <div style="padding:60px;text-align:center;color:var(--muted);background:var(--card);">
      <div style="font-size:2rem;margin-bottom:12px;">📰</div>
      <div>No articles yet. <a href="add_article.php" style="color:var(--accent);">Add some →</a></div>
    </div>
    <?php endif; ?>
    </div>

    <div style="text-align:center;margin-top:16px;">
      <a href="articles.php" class="btn btn-ghost" style="font-size:0.72rem;">View All Articles →</a>
    </div>
  </div>

</div><!-- /wrapper -->

<!-- Chart.js CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
// ── Shared chart defaults ──────────────────────────────
Chart.defaults.color = '#777';
Chart.defaults.borderColor = 'rgba(42,42,42,0.8)';
Chart.defaults.font.family = "'Oswald', sans-serif";

const ACCENT = '#e63232';
const GREEN  = '#00c853';
const YELLOW = '#ffd600';
const BLUE   = '#2979ff';
const PURPLE = '#aa00ff';
const ORANGE = '#ff6d00';
const MUTED  = '#333';

// ── 1. VERDICT DOUGHNUT ──────────────────────────────
new Chart(document.getElementById('verdictDoughnut'), {
  type: 'doughnut',
  data: {
    labels: ['Fake', 'Real', 'Misleading'],
    datasets: [{
      data: [<?= (int)$stats['fake_count'] ?>, <?= (int)$stats['real_count'] ?>, <?= (int)$stats['misleading_count'] ?>],
      backgroundColor: ['rgba(230,50,50,0.85)', 'rgba(0,200,83,0.85)', 'rgba(255,214,0,0.85)'],
      borderColor: ['#e63232', '#00c853', '#ffd600'],
      borderWidth: 2,
      hoverOffset: 6,
    }]
  },
  options: {
    cutout: '72%',
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#141414',
        borderColor: '#2a2a2a',
        borderWidth: 1,
        titleFont: { family: "'Oswald'", size: 11, letterSpacing: 2 },
        bodyFont: { family: "'Playfair Display'", size: 16, weight: '900' },
        callbacks: {
          label: ctx => '  ' + ctx.parsed + ' articles'
        }
      }
    },
    animation: { animateRotate: true, duration: 1200, easing: 'easeInOutQuart' }
  }
});

// ── 2. MONTHLY TREND LINE ────────────────────────────
new Chart(document.getElementById('monthlyLine'), {
  type: 'line',
  data: {
    labels: <?= $monthly_labels ?>,
    datasets: [
      {
        label: 'Total',
        data: <?= $monthly_total ?>,
        borderColor: BLUE,
        backgroundColor: 'rgba(41,121,255,0.08)',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: BLUE,
        tension: 0.4,
        fill: true,
      },
      {
        label: 'Fake',
        data: <?= $monthly_fake ?>,
        borderColor: ACCENT,
        backgroundColor: 'rgba(230,50,50,0.06)',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: ACCENT,
        tension: 0.4,
        fill: true,
      },
      {
        label: 'Real',
        data: <?= $monthly_real ?>,
        borderColor: GREEN,
        backgroundColor: 'rgba(0,200,83,0.06)',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: GREEN,
        tension: 0.4,
        fill: true,
      }
    ]
  },
  options: {
    responsive: true,
    interaction: { mode: 'index', intersect: false },
    scales: {
      x: {
        grid: { color: 'rgba(42,42,42,0.6)' },
        ticks: { font: { size: 10 }, letterSpacing: 1 }
      },
      y: {
        beginAtZero: true,
        grid: { color: 'rgba(42,42,42,0.6)' },
        ticks: { stepSize: 1, font: { size: 10 } }
      }
    },
    plugins: {
      legend: {
        position: 'bottom',
        labels: { boxWidth: 10, padding: 16, font: { size: 10 }, usePointStyle: true }
      },
      tooltip: {
        backgroundColor: '#141414',
        borderColor: '#2a2a2a',
        borderWidth: 1,
      }
    },
    animation: { duration: 1400, easing: 'easeInOutQuart' }
  }
});

// ── 3. CATEGORY GROUPED BAR ──────────────────────────
new Chart(document.getElementById('categoryBar'), {
  type: 'bar',
  data: {
    labels: <?= $cat_labels ?>,
    datasets: [
      {
        label: 'Fake',
        data: <?= $cat_fake ?>,
        backgroundColor: 'rgba(230,50,50,0.75)',
        borderColor: ACCENT,
        borderWidth: 1,
        borderRadius: 2,
      },
      {
        label: 'Real',
        data: <?= $cat_real ?>,
        backgroundColor: 'rgba(0,200,83,0.75)',
        borderColor: GREEN,
        borderWidth: 1,
        borderRadius: 2,
      },
      {
        label: 'Total',
        data: <?= $cat_total ?>,
        backgroundColor: 'rgba(41,121,255,0.2)',
        borderColor: BLUE,
        borderWidth: 1,
        borderRadius: 2,
      }
    ]
  },
  options: {
    responsive: true,
    interaction: { mode: 'index', intersect: false },
    scales: {
      x: { grid: { color: 'rgba(42,42,42,0.6)' }, ticks: { font: { size: 10 } } },
      y: { beginAtZero: true, grid: { color: 'rgba(42,42,42,0.6)' }, ticks: { stepSize: 1 } }
    },
    plugins: {
      legend: {
        position: 'bottom',
        labels: { boxWidth: 10, padding: 16, font: { size: 10 }, usePointStyle: true }
      },
      tooltip: { backgroundColor: '#141414', borderColor: '#2a2a2a', borderWidth: 1 }
    },
    animation: { duration: 1200 }
  }
});

// ── 4. SOURCE CREDIBILITY HORIZONTAL BAR ─────────────
new Chart(document.getElementById('sourceBar'), {
  type: 'bar',
  data: {
    labels: <?= $src_labels ?>,
    datasets: [{
      label: 'Credibility Score',
      data: <?= $src_cred ?>,
      backgroundColor: <?= $src_cred ?>.map(v =>
        v > 70 ? 'rgba(0,200,83,0.7)' : v > 40 ? 'rgba(255,214,0,0.7)' : 'rgba(230,50,50,0.7)'
      ),
      borderColor: <?= $src_cred ?>.map(v =>
        v > 70 ? GREEN : v > 40 ? YELLOW : ACCENT
      ),
      borderWidth: 1,
      borderRadius: 2,
    }]
  },
  options: {
    indexAxis: 'y',
    responsive: true,
    scales: {
      x: { min: 0, max: 100, grid: { color: 'rgba(42,42,42,0.6)' }, ticks: { font: { size: 10 } } },
      y: { grid: { display: false }, ticks: { font: { size: 10 } } }
    },
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#141414',
        borderColor: '#2a2a2a',
        borderWidth: 1,
        callbacks: { label: ctx => '  Score: ' + ctx.parsed.x + '/100' }
      }
    },
    animation: { duration: 1200 }
  }
});

// ── 5. FAKE BY SOURCE PIE ────────────────────────────
const srcFakeData = <?= $src_fake ?>;
const srcLabels   = <?= $src_labels ?>;
const pieColors   = ['#e63232','#ff6b35','#ffd600','#aa00ff','#2979ff','#00c853','#ff4081','#00bcd4'];

new Chart(document.getElementById('sourceFakePie'), {
  type: 'pie',
  data: {
    labels: srcLabels,
    datasets: [{
      data: srcFakeData,
      backgroundColor: pieColors.map(c => c + 'cc'),
      borderColor: pieColors,
      borderWidth: 2,
      hoverOffset: 8,
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: {
        position: 'bottom',
        labels: { boxWidth: 10, padding: 12, font: { size: 9 }, usePointStyle: true }
      },
      tooltip: {
        backgroundColor: '#141414',
        borderColor: '#2a2a2a',
        borderWidth: 1,
        callbacks: { label: ctx => '  ' + ctx.parsed + ' fake articles' }
      }
    },
    animation: { animateRotate: true, duration: 1400, easing: 'easeInOutQuart' }
  }
});

// ── Feed & AI Summary JS ─────────────────────────────
function filterFeed(cat) {
  document.querySelectorAll('.feed-tab').forEach(t => t.classList.toggle('active', t.dataset.cat === cat));
  document.querySelectorAll('.feed-card').forEach(card => {
    card.style.display = (cat === 'all' || card.dataset.cat === cat) ? 'flex' : 'none';
  });
}
const aiCache = {};
function toggleAISummary(articleId) {
  const box = document.getElementById('ai-' + articleId);
  const btn = box.previousElementSibling.querySelector('.feed-btn-ai');
  if (box.style.display !== 'none') { box.style.display='none'; btn.textContent='✨ AI Summary'; return; }
  box.style.display='block'; btn.textContent='⏳ Loading...'; btn.disabled=true;
  if (aiCache[articleId]) { box.querySelector('.ai-summary-text').innerHTML=aiCache[articleId]; btn.textContent='✨ AI Summary'; btn.disabled=false; return; }
  fetch('ai_check.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'article_id='+articleId+'&mode=summary'})
  .then(r=>r.json()).then(data=>{
    btn.textContent='✨ AI Summary'; btn.disabled=false;
    if(data.error){box.querySelector('.ai-summary-text').innerHTML='<span style="color:var(--accent)">⚠ '+data.error+'</span>';return;}
    const color=data.verdict==='fake'?'#e63232':(data.verdict==='real'?'#00c853':'#ffd600');
    const html=`<strong style="color:${color}">${data.verdict.toUpperCase()} (${data.confidence}% confidence)</strong><br><br>${data.reason}`;
    aiCache[articleId]=html; box.querySelector('.ai-summary-text').innerHTML=html;
  }).catch(()=>{btn.textContent='✨ AI Summary';btn.disabled=false;box.querySelector('.ai-summary-text').innerHTML='<span style="color:var(--accent)">⚠ Connection error</span>';});
}

// ── Update masthead stats ────────────────────────────
document.addEventListener('DOMContentLoaded',function(){
  const el=id=>document.getElementById(id);
  if(el('ms-fake'))  el('ms-fake').textContent  = '<?= (int)$stats['fake_count'] ?>';
  if(el('ms-real'))  el('ms-real').textContent  = '<?= (int)$stats['real_count'] ?>';
  if(el('ms-mis'))   el('ms-mis').textContent   = '<?= (int)$stats['misleading_count'] ?>';
  if(el('ms-total')) el('ms-total').textContent = '<?= (int)$stats['total'] ?>';
});
</script>

<?php require_once 'includes/footer.php'; ?>
