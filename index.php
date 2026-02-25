<?php
require_once 'includes/db.php';
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

$pending_reports = $pdo->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn();

// News Feed (Inshorts style) — latest 20 articles
$newsfeed = $pdo->query("
    SELECT a.article_id, a.title, a.content, a.author, a.published_at, a.url,
           c.name AS category, s.name AS source, s.credibility_score,
           v.verdict, v.confidence_score
    FROM articles a
    LEFT JOIN categories    c ON a.category_id = c.category_id
    LEFT JOIN sources       s ON a.source_id   = s.source_id
    LEFT JOIN verifications v ON a.article_id  = v.article_id
    ORDER BY a.created_at DESC
    LIMIT 20
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
.hero-eyebrow::before,
.hero-eyebrow::after {
  content: '';
  width: 40px;
  height: 1px;
  background: var(--accent);
}
.hero h1 {
  font-family: 'Playfair Display', serif;
  font-size: clamp(2.5rem, 6vw, 5rem);
  font-weight: 900;
  line-height: 1.05;
  margin-bottom: 20px;
  position: relative;
  z-index: 1;
}
.hero h1 em {
  color: var(--accent);
  font-style: italic;
}
.hero-sub {
  font-family: 'Oswald', sans-serif;
  font-size: 0.8rem;
  letter-spacing: 3px;
  color: var(--muted);
  margin-bottom: 32px;
}
.hero-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
  position: relative;
  z-index: 1;
}
.hero-divider {
  width: 60px;
  height: 3px;
  background: var(--accent);
  margin: 0 auto 20px;
}

/* STATS UPGRADED */
.stats-row-new {
  display: grid;
  grid-template-columns: repeat(4,1fr);
  gap: 0;
  border: 1px solid var(--border);
  margin-bottom: 36px;
  border-radius: 4px;
  overflow: hidden;
}
.stat-new {
  padding: 28px 24px;
  border-right: 1px solid var(--border);
  position: relative;
  overflow: hidden;
  transition: background 0.2s;
}
.stat-new:last-child { border-right: none; }
.stat-new:hover { background: rgba(255,255,255,0.02); }
.stat-new-label {
  font-family: 'Oswald', sans-serif;
  font-size: 0.6rem;
  letter-spacing: 3px;
  color: var(--muted);
  text-transform: uppercase;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.stat-new-label::before {
  content: '';
  width: 20px;
  height: 2px;
}
.stat-new.r .stat-new-label::before { background: var(--accent); }
.stat-new.g .stat-new-label::before { background: var(--green); }
.stat-new.y .stat-new-label::before { background: var(--yellow); }
.stat-new.b .stat-new-label::before { background: var(--blue); }
.stat-new-val {
  font-family: 'Playfair Display', serif;
  font-size: 4rem;
  font-weight: 900;
  line-height: 1;
}
.stat-new.r .stat-new-val { color: var(--accent); }
.stat-new.g .stat-new-val { color: var(--green); }
.stat-new.y .stat-new-val { color: var(--yellow); }
.stat-new.b .stat-new-val { color: var(--text); }
.stat-new-bar {
  margin-top: 14px;
  height: 2px;
  background: var(--border);
  border-radius: 0;
}
.stat-new-bar-fill { height: 100%; }
.stat-new.r .stat-new-bar-fill { background: var(--accent); }
.stat-new.g .stat-new-bar-fill { background: var(--green); }
.stat-new.y .stat-new-bar-fill { background: var(--yellow); }
.stat-new.b .stat-new-bar-fill { background: var(--blue); }
.stat-new-ghost {
  position: absolute;
  right: 10px;
  bottom: -15px;
  font-family: 'Playfair Display', serif;
  font-size: 7rem;
  font-weight: 900;
  opacity: 0.04;
  line-height: 1;
}

/* MAGAZINE CARDS */
.magazine-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 1px;
  background: var(--border);
  border: 1px solid var(--border);
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 32px;
}
.mag-card {
  background: var(--card);
  padding: 24px;
  position: relative;
  transition: background 0.2s;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.mag-card:hover { background: #1e1e1e; }
.mag-card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.mag-cat {
  font-family: 'Oswald', sans-serif;
  font-size: 0.6rem;
  font-weight: 600;
  letter-spacing: 2px;
  text-transform: uppercase;
  padding: 3px 8px;
  border-radius: 2px;
}
.mag-num {
  font-family: 'Oswald', sans-serif;
  font-size: 0.65rem;
  color: var(--muted);
  letter-spacing: 1px;
}
.mag-title {
  font-family: 'Playfair Display', serif;
  font-size: 1rem;
  font-weight: 700;
  line-height: 1.4;
  color: var(--text);
  text-decoration: none;
  display: block;
}
.mag-title:hover { color: var(--accent); }
.mag-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: auto;
}
.mag-source {
  font-size: 0.7rem;
  color: var(--muted);
  font-family: 'Oswald', sans-serif;
  letter-spacing: 1px;
}
.mag-card-featured {
  grid-column: span 2;
  border-right: 1px solid var(--border);
}
.mag-card-featured .mag-title {
  font-size: 1.5rem;
  line-height: 1.3;
}
.mag-verdict-bar {
  height: 3px;
  position: absolute;
  bottom: 0; left: 0; right: 0;
}

/* CHART */
.chart-container {
  padding: 24px;
}
.chart-bar-group {
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.chart-bar-item {
  display: flex;
  align-items: center;
  gap: 12px;
}
.chart-bar-label {
  font-family: 'Oswald', sans-serif;
  font-size: 0.7rem;
  letter-spacing: 1px;
  color: var(--text);
  min-width: 90px;
  text-transform: uppercase;
}
.chart-bar-track {
  flex: 1;
  height: 6px;
  background: var(--border);
  border-radius: 0;
  overflow: hidden;
}
.chart-bar-fill {
  height: 100%;
  border-radius: 0;
  transition: width 1s ease;
}
.chart-bar-val {
  font-family: 'Oswald', sans-serif;
  font-size: 0.7rem;
  color: var(--muted);
  min-width: 20px;
  text-align: right;
}

/* DONUT */
.donut-section {
  display: flex;
  align-items: center;
  gap: 24px;
  padding: 24px;
}
.donut-legend-item {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
}
.donut-legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}
.donut-legend-label {
  font-family: 'Oswald', sans-serif;
  font-size: 0.7rem;
  letter-spacing: 1px;
  color: var(--muted);
  flex: 1;
  text-transform: uppercase;
}
.donut-legend-val {
  font-family: 'Playfair Display', serif;
  font-size: 1.2rem;
  font-weight: 700;
}

@media(max-width:900px) {
  .magazine-grid { grid-template-columns: 1fr 1fr; }
  .mag-card-featured { grid-column: span 2; }
  .stats-row-new { grid-template-columns: repeat(2,1fr); }
}
@media(max-width:600px) {
  .magazine-grid { grid-template-columns: 1fr; }
  .mag-card-featured { grid-column: span 1; }
  .stats-row-new { grid-template-columns: 1fr 1fr; }
}
</style>

<!-- HERO SECTION -->
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

  <!-- UPGRADED STATS -->
  <?php
  $total = $stats['total'] ?: 1;
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

  <!-- MAGAZINE ARTICLE CARDS -->
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
        <span class="mag-cat" style="background:<?= $catcolor ?>22;color:<?= $catcolor ?>;border:1px solid <?= $catcolor ?>44">
          <?= htmlspecialchars($cat) ?>
        </span>
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

  <!-- CHARTS ROW -->
  <div class="grid-2 mb-20">

    <!-- CATEGORY CHART -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">Category Breakdown</div>
      </div>
      <div class="chart-container">
        <div class="chart-bar-group">
        <?php foreach($category_stats as $cs):
          $catcolor = $cat_colors[$cs['name']] ?? '#777';
          $pct = $stats['total'] > 0 ? round(($cs['total']/$stats['total'])*100) : 0;
        ?>
        <div class="chart-bar-item">
          <div class="chart-bar-label" style="color:<?= $catcolor ?>"><?= htmlspecialchars($cs['name']) ?></div>
          <div class="chart-bar-track">
            <div class="chart-bar-fill" style="width:<?= $pct ?>%;background:<?= $catcolor ?>"></div>
          </div>
          <div class="chart-bar-val"><?= $cs['total'] ?></div>
        </div>
        <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- VERDICT DONUT -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">Verdict Distribution</div>
      </div>
      <div class="donut-section">
        <svg width="140" height="140" viewBox="0 0 42 42" style="flex-shrink:0;transform:rotate(-90deg)">
          <circle cx="21" cy="21" r="15.915" fill="none" stroke="#2a2a2a" stroke-width="5"/>
          <?php
          $total_v = ($stats['fake_count']+$stats['real_count']+$stats['misleading_count']) ?: 1;
          $fp = ($stats['fake_count']/$total_v)*100;
          $rp = ($stats['real_count']/$total_v)*100;
          $mp = ($stats['misleading_count']/$total_v)*100;
          ?>
          <circle cx="21" cy="21" r="15.915" fill="none" stroke="#e63232" stroke-width="5"
            stroke-dasharray="<?= $fp ?> <?= 100-$fp ?>" stroke-dashoffset="0"/>
          <circle cx="21" cy="21" r="15.915" fill="none" stroke="#00c853" stroke-width="5"
            stroke-dasharray="<?= $rp ?> <?= 100-$rp ?>" stroke-dashoffset="-<?= $fp ?>"/>
          <circle cx="21" cy="21" r="15.915" fill="none" stroke="#ffd600" stroke-width="5"
            stroke-dasharray="<?= $mp ?> <?= 100-$mp ?>" stroke-dashoffset="-<?= $fp+$rp ?>"/>
        </svg>
        <div style="flex:1">
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
        </div>
      </div>
    </div>
  </div>

  <!-- TOP CHECKERS + QUICK ACTIONS -->
  <div class="grid-2">
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
        <a href="news_short.php" class="btn btn-primary" style="text-align:center;">📰 News in Short</a>
      </div>
    </div>
  </div>



  <!-- ══════════════════════════════════════════════
       INSHORTS-STYLE NEWS FEED SECTION
  ══════════════════════════════════════════════ -->
  <div style="margin-top:40px;">

    <!-- Section Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
      <div style="display:flex;align-items:center;gap:14px;">
        <div style="width:4px;height:28px;background:var(--accent);border-radius:2px;"></div>
        <div>
          <div style="font-family:'Oswald',sans-serif;font-size:0.6rem;letter-spacing:4px;color:var(--accent);text-transform:uppercase;margin-bottom:2px;">Latest Updates</div>
          <div style="font-family:'Playfair Display',serif;font-size:1.3rem;font-weight:700;">News in Short</div>
        </div>
      </div>
      <div style="display:flex;gap:8px;align-items:center;">
        <!-- Category Filter Tabs -->
        <div id="feedFilterBar" style="display:flex;gap:6px;flex-wrap:wrap;">
          <button onclick="filterFeed('all')"       class="feed-tab active" data-cat="all">All</button>
          <button onclick="filterFeed('Politics')"  class="feed-tab" data-cat="Politics">Politics</button>
          <button onclick="filterFeed('Health')"    class="feed-tab" data-cat="Health">Health</button>
          <button onclick="filterFeed('Technology')" class="feed-tab" data-cat="Technology">Tech</button>
          <button onclick="filterFeed('Science')"   class="feed-tab" data-cat="Science">Science</button>
          <button onclick="filterFeed('Sports')"    class="feed-tab" data-cat="Sports">Sports</button>
          <button onclick="filterFeed('Finance')"   class="feed-tab" data-cat="Finance">Finance</button>
        </div>
      </div>
    </div>

    <!-- Feed Cards Container -->
    <div id="newsFeedContainer" style="display:flex;flex-direction:column;gap:1px;background:var(--border);border:1px solid var(--border);border-radius:8px;overflow:hidden;">

      <?php
      $feed_cat_colors = [
        'Politics'=>'#e63232','Health'=>'#00c853','Science'=>'#2979ff',
        'Technology'=>'#aa00ff','Sports'=>'#ff6d00','Finance'=>'#ffd600',
        'Entertainment'=>'#ff4081','World'=>'#00bcd4','Environment'=>'#8bc34a','Education'=>'#ff9800'
      ];
      foreach($newsfeed as $nf):
        $nv      = $nf['verdict'] ?? 'unverified';
        $ncat    = $nf['category'] ?? 'General';
        $nccolor = $feed_cat_colors[$ncat] ?? '#777';
        $nvcolor = $nv=='fake'?'#e63232':($nv=='real'?'#00c853':($nv=='misleading'?'#ffd600':'#666'));

        // Auto-summary: first 60 words of content
        $words   = explode(' ', strip_tags($nf['content']));
        $summary = implode(' ', array_slice($words, 0, 60));
        if(count($words) > 60) $summary .= '...';

        $pub_date = $nf['published_at'] ? date('d M Y', strtotime($nf['published_at'])) : 'Recent';
      ?>
      <div class="feed-card" data-cat="<?= htmlspecialchars($ncat) ?>"
           style="background:var(--card);padding:18px 24px;display:flex;gap:20px;align-items:flex-start;transition:background 0.15s;position:relative;overflow:hidden;">

        <!-- Left accent bar -->
        <div style="position:absolute;left:0;top:0;bottom:0;width:3px;background:<?= $nccolor ?>;opacity:0.7;"></div>

        <!-- Main Content -->
        <div style="flex:1;padding-left:8px;">

          <!-- Top row: category + verdict + date -->
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;flex-wrap:wrap;">
            <span style="background:<?= $nccolor ?>18;color:<?= $nccolor ?>;border:1px solid <?= $nccolor ?>33;font-size:0.58rem;font-family:'Oswald',sans-serif;letter-spacing:2px;padding:2px 8px;border-radius:20px;text-transform:uppercase;"><?= htmlspecialchars($ncat) ?></span>
            <span style="background:<?= $nvcolor ?>18;color:<?= $nvcolor ?>;border:1px solid <?= $nvcolor ?>33;font-size:0.58rem;font-family:'Oswald',sans-serif;letter-spacing:2px;padding:2px 8px;border-radius:20px;text-transform:uppercase;"><?= $nv==='unverified'?'⚪ UNVERIFIED':($nv==='fake'?'🔴 FAKE':($nv==='real'?'🟢 REAL':'🟡 '.strtoupper($nv))) ?></span>
            <span style="color:var(--muted);font-size:0.62rem;margin-left:auto;"><?= $pub_date ?></span>
          </div>

          <!-- Title -->
          <div style="font-family:'Syne',sans-serif;font-size:0.88rem;font-weight:700;line-height:1.4;margin-bottom:8px;color:var(--text);">
            <?= htmlspecialchars($nf['title']) ?>
          </div>

          <!-- Summary (60 words auto) -->
          <div class="feed-summary" style="font-size:0.76rem;color:var(--muted);line-height:1.65;margin-bottom:12px;">
            <?= htmlspecialchars($summary) ?>
          </div>

          <!-- AI Summary Box (hidden by default) -->
          <div class="ai-summary-box" id="ai-<?= $nf['article_id'] ?>" style="display:none;background:rgba(79,179,255,0.05);border:1px solid rgba(79,179,255,0.2);border-radius:8px;padding:12px;margin-bottom:12px;font-size:0.76rem;line-height:1.65;color:var(--text);">
            <div style="font-size:0.6rem;color:var(--blue);letter-spacing:2px;margin-bottom:6px;">🤖 AI SUMMARY</div>
            <div class="ai-summary-text">Loading...</div>
          </div>

          <!-- Bottom row: source + actions -->
          <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <span style="font-size:0.65rem;color:var(--muted);">
              <?= $nf['source'] ? '📰 '.htmlspecialchars($nf['source']) : '' ?>
              <?= $nf['author'] && $nf['author']!='Unknown' ? ' · '.htmlspecialchars(substr($nf['author'],0,30)) : '' ?>
            </span>
            <div style="display:flex;gap:6px;margin-left:auto;">
              <button onclick="toggleAISummary(<?= $nf['article_id'] ?>)" class="feed-btn feed-btn-ai" title="AI Summary">
                ✨ AI Summary
              </button>
              <a href="article_detail.php?id=<?= $nf['article_id'] ?>" class="feed-btn feed-btn-read">
                Read More →
              </a>
              <?php if($nf['url']): ?>
              <a href="<?= htmlspecialchars($nf['url']) ?>" target="_blank" class="feed-btn feed-btn-source">
                ↗ Source
              </a>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Confidence meter (right side, only if verified) -->
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

    <!-- Load More -->
    <div style="text-align:center;margin-top:16px;">
      <a href="articles.php" class="btn btn-ghost" style="font-size:0.72rem;">View All Articles →</a>
    </div>

  </div>

</div><!-- end wrapper -->

<!-- ══ STYLES FOR NEWS FEED ══ -->
<style>
.feed-card:hover { background: rgba(255,255,255,0.025) !important; }
.feed-tab {
  background: transparent;
  border: 1px solid var(--border);
  color: var(--muted);
  font-family: 'Oswald', sans-serif;
  font-size: 0.62rem;
  letter-spacing: 2px;
  text-transform: uppercase;
  padding: 4px 10px;
  border-radius: 20px;
  cursor: pointer;
  transition: all 0.2s;
}
.feed-tab:hover, .feed-tab.active {
  background: var(--accent);
  border-color: var(--accent);
  color: #fff;
}
.feed-btn {
  font-size: 0.62rem;
  padding: 4px 10px;
  border-radius: 6px;
  cursor: pointer;
  text-decoration: none;
  font-family: 'Oswald', sans-serif;
  letter-spacing: 1px;
  transition: all 0.15s;
  border: none;
}
.feed-btn-ai {
  background: rgba(79,179,255,0.1);
  color: var(--blue);
  border: 1px solid rgba(79,179,255,0.25);
}
.feed-btn-ai:hover { background: rgba(79,179,255,0.2); }
.feed-btn-read {
  background: rgba(230,50,50,0.1);
  color: var(--accent);
  border: 1px solid rgba(230,50,50,0.25);
}
.feed-btn-read:hover { background: rgba(230,50,50,0.2); }
.feed-btn-source {
  background: rgba(255,255,255,0.05);
  color: var(--muted);
  border: 1px solid var(--border);
}
.feed-btn-source:hover { color: var(--text); }
</style>

<script>
// ── Category Filter ──────────────────────────
function filterFeed(cat) {
  document.querySelectorAll('.feed-tab').forEach(t => {
    t.classList.toggle('active', t.dataset.cat === cat);
  });
  document.querySelectorAll('.feed-card').forEach(card => {
    const show = cat === 'all' || card.dataset.cat === cat;
    card.style.display = show ? 'flex' : 'none';
  });
}

// ── AI Summary Toggle ────────────────────────
const aiCache = {};
function toggleAISummary(articleId) {
  const box = document.getElementById('ai-' + articleId);
  const btn = box.previousElementSibling.querySelector('.feed-btn-ai');

  if (box.style.display !== 'none') {
    box.style.display = 'none';
    btn.textContent = '✨ AI Summary';
    return;
  }

  box.style.display = 'block';
  btn.textContent = '⏳ Loading...';
  btn.disabled = true;

  if (aiCache[articleId]) {
    box.querySelector('.ai-summary-text').innerHTML = aiCache[articleId];
    btn.textContent = '✨ AI Summary';
    btn.disabled = false;
    return;
  }

  fetch('ai_check.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'article_id=' + articleId + '&mode=summary'
  })
  .then(r => r.json())
  .then(data => {
    btn.textContent = '✨ AI Summary';
    btn.disabled = false;
    if (data.error) {
      box.querySelector('.ai-summary-text').innerHTML = '<span style="color:var(--accent)">⚠ ' + data.error + '</span>';
      return;
    }
    const color = data.verdict==='fake'?'#e63232':(data.verdict==='real'?'#00c853':'#ffd600');
    const html = `<strong style="color:${color}">${data.verdict.toUpperCase()} (${data.confidence}% confidence)</strong><br><br>${data.reason}`;
    aiCache[articleId] = html;
    box.querySelector('.ai-summary-text').innerHTML = html;
  })
  .catch(() => {
    btn.textContent = '✨ AI Summary';
    btn.disabled = false;
    box.querySelector('.ai-summary-text').innerHTML = '<span style="color:var(--accent)">⚠ Connection error</span>';
  });
}
</script>

<?php require_once 'includes/footer.php'; ?>
