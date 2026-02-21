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
    SELECT a.article_id, a.title, c.name AS category, s.name AS source,
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

$pending_reports = $pdo->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn();

require_once 'includes/header.php';
?>
<div class="wrapper">
  <div class="page-header">
    <h1>Dashboard</h1>
    <p>FAKE NEWS DETECTION SYSTEM — OVERVIEW</p>
  </div>

  <div class="stats-row">
    <div class="stat r"><div class="stat-label">Fake Articles</div><div class="stat-val"><?= $stats['fake_count'] ?? 0 ?></div></div>
    <div class="stat g"><div class="stat-label">Verified Real</div><div class="stat-val"><?= $stats['real_count'] ?? 0 ?></div></div>
    <div class="stat y"><div class="stat-label">Misleading</div><div class="stat-val"><?= $stats['misleading_count'] ?? 0 ?></div></div>
    <div class="stat b"><div class="stat-label">Total Articles</div><div class="stat-val"><?= $stats['total'] ?? 0 ?></div></div>
  </div>

  <?php if($pending_reports > 0): ?>
  <div class="alert alert-error mb-20">⚠️ <?= $pending_reports ?> pending report(s)! <a href="reports.php" style="color:var(--accent)">View →</a></div>
  <?php endif; ?>

  <div class="grid-2 mb-20">
    <div class="card">
      <div class="card-header"><div class="card-title">Top Fact Checkers</div></div>
      <table>
        <thead><tr><th>Username</th><th>Verified</th><th>Fakes Found</th></tr></thead>
        <tbody>
        <?php foreach($checkers as $c): ?>
        <tr>
          <td style="color:var(--blue)"><?= htmlspecialchars($c['username']) ?></td>
          <td><?= $c['checked'] ?></td>
          <td style="color:var(--accent)"><?= $c['fakes_found'] ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card">
      <div class="card-header"><div class="card-title">Quick Actions</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:12px;">
        <a href="add_article.php" class="btn btn-primary">+ Submit New Article</a>
        <a href="verify.php"      class="btn btn-green">✓ Verify an Article</a>
        <a href="articles.php"    class="btn btn-ghost">📰 View All Articles</a>
        <a href="reports.php"     class="btn btn-ghost">🚨 View Reports</a>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title">Recent Articles</div>
      <a href="articles.php" class="btn btn-ghost" style="font-size:0.7rem;padding:6px 14px;">View All →</a>
    </div>
    <div class="tbl-wrap">
      <table>
        <thead><tr><th>#</th><th>Title</th><th>Category</th><th>Source</th><th>Verdict</th><th>Confidence</th><th>Checker</th></tr></thead>
        <tbody>
        <?php foreach($recent as $row): ?>
        <tr>
          <td class="text-muted text-sm"><?= str_pad($row['article_id'],3,'0',STR_PAD_LEFT) ?></td>
          <td><a href="article_detail.php?id=<?= $row['article_id'] ?>" class="td-title" style="color:var(--text);text-decoration:none;display:block;" title="<?= htmlspecialchars($row['title']) ?>"><?= htmlspecialchars(substr($row['title'],0,50)) ?>...</a></td>
          <td class="text-muted text-sm"><?= htmlspecialchars($row['category'] ?? '—') ?></td>
          <td class="text-muted text-sm"><?= htmlspecialchars($row['source'] ?? '—') ?></td>
          <td><?php $v=$row['verdict']??'unverified'; ?><span class="badge b-<?= $v ?>"><?= strtoupper($v) ?></span></td>
          <td>
            <?php if($row['confidence_score']): ?>
            <div class="cbar-wrap">
              <div class="cbar"><div class="cbar-fill" style="width:<?= $row['confidence_score'] ?>%;background:<?= $row['verdict']=='fake'?'var(--accent)':($row['verdict']=='real'?'var(--green)':'var(--yellow)') ?>"></div></div>
              <div class="cbar-num"><?= $row['confidence_score'] ?>%</div>
            </div>
            <?php else: echo '—'; endif; ?>
          </td>
          <td class="text-muted text-sm"><?= htmlspecialchars($row['checker'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>