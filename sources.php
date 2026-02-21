<?php
require_once 'includes/db.php';
$pageTitle = 'Sources';
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $url     = trim($_POST['url'] ?? '');
    $score   = intval($_POST['score'] ?? 50);
    $country = trim($_POST['country'] ?? '');
    if (!$name) { $error = "Source name is required."; }
    else {
        $pdo->prepare("INSERT INTO sources (name, website_url, credibility_score, country) VALUES (?,?,?,?)")
            ->execute([$name, $url ?: null, $score, $country ?: null]);
        $success = "Source added!";
    }
}

$sources = $pdo->query("
    SELECT s.*, COUNT(a.article_id) AS article_count,
           SUM(CASE WHEN v.verdict='fake' THEN 1 ELSE 0 END) AS fake_count
    FROM sources s
    LEFT JOIN articles a ON s.source_id = a.source_id
    LEFT JOIN verifications v ON a.article_id = v.article_id
    GROUP BY s.source_id ORDER BY s.credibility_score DESC
")->fetchAll();

require_once 'includes/header.php';
?>
<div class="wrapper">
  <div class="page-header"><h1>News Sources</h1><p>CREDIBILITY SCORES & TRACK RECORD</p></div>

  <?php if($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if($error):   ?><div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card mb-20">
    <div class="tbl-wrap">
      <table>
        <thead><tr><th>#</th><th>Source</th><th>Country</th><th>Credibility</th><th>Articles</th><th>Fakes</th><th>Website</th></tr></thead>
        <tbody>
        <?php foreach($sources as $s): $sc=$s['credibility_score']; ?>
        <tr>
          <td class="text-muted text-sm"><?= $s['source_id'] ?></td>
          <td style="font-weight:500"><?= htmlspecialchars($s['name']) ?></td>
          <td class="text-muted text-sm"><?= htmlspecialchars($s['country'] ?? '—') ?></td>
          <td>
            <div class="cbar-wrap">
              <div class="cbar"><div class="cbar-fill" style="width:<?= $sc ?>%;background:<?= $sc>70?'var(--green)':($sc>40?'var(--yellow)':'var(--accent)') ?>"></div></div>
              <div class="cbar-num" style="color:<?= $sc>70?'var(--green)':($sc>40?'var(--yellow)':'var(--accent)') ?>"><?= $sc ?></div>
            </div>
          </td>
          <td style="color:var(--blue)"><?= $s['article_count'] ?></td>
          <td style="color:<?= $s['fake_count']>0?'var(--accent)':'var(--muted)' ?>"><?= $s['fake_count']??0 ?></td>
          <td><?= $s['website_url']?"<a href='{$s['website_url']}' target='_blank' style='color:var(--blue);font-size:0.7rem;'>↗ Visit</a>":'—' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div style="max-width:580px">
    <div class="card">
      <div class="card-header"><div class="card-title">+ Add New Source</div></div>
      <div class="card-body">
        <form method="POST">
          <div class="grid-2">
            <div class="form-group"><label>Source Name *</label><input type="text" name="name" placeholder="e.g. Reuters"></div>
            <div class="form-group"><label>Country</label><input type="text" name="country" placeholder="e.g. India"></div>
          </div>
          <div class="form-group"><label>Website URL</label><input type="url" name="url" placeholder="https://..."></div>
          <div class="form-group">
            <label>Credibility Score: <span id="sv">50</span></label>
            <input type="range" name="score" min="0" max="100" value="50" style="width:100%;accent-color:var(--accent)" oninput="document.getElementById('sv').textContent=this.value">
          </div>
          <button type="submit" class="btn btn-primary">Add Source</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>