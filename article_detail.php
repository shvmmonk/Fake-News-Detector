<?php
require_once 'includes/db.php';
$pageTitle = 'Article Detail';
$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: articles.php'); exit; }

$stmt = $pdo->prepare("SELECT a.*, c.name AS category, s.name AS source, s.credibility_score, u.username AS submitted_by_user FROM articles a LEFT JOIN categories c ON a.category_id=c.category_id LEFT JOIN sources s ON a.source_id=s.source_id LEFT JOIN users u ON a.submitted_by=u.user_id WHERE a.article_id=?");
$stmt->execute([$id]);
$article = $stmt->fetch();
if (!$article) { header('Location: articles.php'); exit; }

$stmt = $pdo->prepare("SELECT v.*, u.username AS checker_name FROM verifications v JOIN users u ON v.checked_by=u.user_id WHERE v.article_id=?");
$stmt->execute([$id]);
$verification = $stmt->fetch();

$evidence = [];
if ($verification) {
    $stmt = $pdo->prepare("SELECT * FROM evidence WHERE verification_id=?");
    $stmt->execute([$verification['verification_id']]);
    $evidence = $stmt->fetchAll();
}

$stmt = $pdo->prepare("SELECT t.name FROM tags t JOIN article_tags at2 ON t.tag_id=at2.tag_id WHERE at2.article_id=?");
$stmt->execute([$id]);
$tags = $stmt->fetchAll(PDO::FETCH_COLUMN);

require_once 'includes/header.php';
?>
<div class="wrapper">
  <div style="margin-bottom:20px;"><a href="articles.php" class="btn btn-ghost" style="font-size:0.7rem;padding:6px 12px;">← Back to Articles</a></div>

  <div class="grid-2">
    <div>
      <div class="card mb-20">
        <div class="card-header">
          <div class="card-title">Article #<?= str_pad($id,3,'0',STR_PAD_LEFT) ?></div>
          <?php $v=$verification['verdict']??'unverified'; ?>
          <span class="badge b-<?= $v ?>" style="font-size:0.75rem;padding:5px 12px;"><?= strtoupper($v) ?></span>
        </div>
        <div class="card-body">
          <h2 style="font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700;margin-bottom:14px;line-height:1.4;"><?= htmlspecialchars($article['title']) ?></h2>
          <p style="color:var(--muted);font-size:0.78rem;line-height:1.7;"><?= nl2br(htmlspecialchars($article['content'])) ?></p>
          <?php if($tags): ?>
          <div style="margin-top:14px;display:flex;flex-wrap:wrap;gap:6px;">
            <?php foreach($tags as $tag): ?>
            <span style="background:rgba(79,179,255,0.08);border:1px solid rgba(79,179,255,0.2);color:var(--blue);font-size:0.62rem;padding:3px 9px;border-radius:20px;">#<?= htmlspecialchars($tag) ?></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><div class="card-title">Details</div></div>
        <div class="card-body">
          <div class="detail-row"><div class="detail-label">Author</div><div><?= htmlspecialchars($article['author']??'—') ?></div></div>
          <div class="detail-row"><div class="detail-label">Category</div><div><?= htmlspecialchars($article['category']??'—') ?></div></div>
          <div class="detail-row"><div class="detail-label">Source</div><div><?= htmlspecialchars($article['source']??'—') ?></div></div>
          <div class="detail-row">
            <div class="detail-label">Credibility</div>
            <div>
              <?php if($article['credibility_score']): $sc=$article['credibility_score']; ?>
              <div class="cbar-wrap">
                <div class="cbar"><div class="cbar-fill" style="width:<?= $sc ?>%;background:<?= $sc>70?'var(--green)':($sc>40?'var(--yellow)':'var(--accent)') ?>"></div></div>
                <div class="cbar-num"><?= $sc ?>/100</div>
              </div>
              <?php else: echo '—'; endif; ?>
            </div>
          </div>
          <div class="detail-row"><div class="detail-label">Submitted By</div><div class="text-muted"><?= htmlspecialchars($article['submitted_by_user']??'—') ?></div></div>
          <?php if($article['url']): ?>
          <div class="detail-row"><div class="detail-label">URL</div><div><a href="<?= htmlspecialchars($article['url']) ?>" target="_blank" style="color:var(--blue);font-size:0.72rem;">↗ View Original</a></div></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div>
      <?php if($verification): ?>
      <div class="card mb-20">
        <div class="card-header"><div class="card-title">Verification Result</div></div>
        <div class="card-body">
          <div class="detail-row"><div class="detail-label">Verdict</div><div><span class="badge b-<?= $verification['verdict'] ?>"><?= strtoupper($verification['verdict']) ?></span></div></div>
          <div class="detail-row">
            <div class="detail-label">Confidence</div>
            <div class="cbar-wrap">
              <div class="cbar"><div class="cbar-fill" style="width:<?= $verification['confidence_score'] ?>%;background:<?= $verification['verdict']=='fake'?'var(--accent)':($verification['verdict']=='real'?'var(--green)':'var(--yellow)') ?>"></div></div>
              <div class="cbar-num"><?= $verification['confidence_score'] ?>%</div>
            </div>
          </div>
          <div class="detail-row"><div class="detail-label">Checked By</div><div style="color:var(--blue)"><?= htmlspecialchars($verification['checker_name']) ?></div></div>
          <div class="detail-row"><div class="detail-label">Verified At</div><div class="text-muted"><?= date('d M Y, H:i', strtotime($verification['verified_at'])) ?></div></div>
          <div class="detail-row"><div class="detail-label">Explanation</div><div style="font-size:0.78rem;line-height:1.6;"><?= nl2br(htmlspecialchars($verification['explanation'])) ?></div></div>
        </div>
      </div>

      <?php if($evidence): ?>
      <div class="card mb-20">
        <div class="card-header"><div class="card-title">Evidence (<?= count($evidence) ?>)</div></div>
        <?php foreach($evidence as $ev): ?>
        <div style="padding:14px 20px;border-bottom:1px solid var(--border);">
          <span class="badge <?= $ev['evidence_type']=='supporting'?'b-real':'b-fake' ?>" style="margin-bottom:8px;display:inline-block;"><?= $ev['evidence_type']=='supporting'?'✓ Supporting':'✗ Contradicting' ?></span>
          <p style="font-size:0.78rem;line-height:1.5;"><?= htmlspecialchars($ev['description']) ?></p>
          <?php if($ev['source_url']): ?><a href="<?= htmlspecialchars($ev['source_url']) ?>" target="_blank" style="font-size:0.68rem;color:var(--blue);">↗ Source</a><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php else: ?>
      <div class="card mb-20">
        <div class="card-body" style="text-align:center;padding:40px;">
          <div style="font-size:2rem;margin-bottom:12px;">🔍</div>
          <div style="color:var(--muted);font-size:0.8rem;margin-bottom:16px;">Not verified yet.</div>
          <a href="verify.php?article_id=<?= $id ?>" class="btn btn-primary">Verify This Article</a>
        </div>
      </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-body" style="text-align:center;padding:20px;">
          <div style="color:var(--muted);font-size:0.72rem;margin-bottom:12px;">Think this is suspicious?</div>
          <a href="report_article.php?id=<?= $id ?>" class="btn btn-ghost" style="border-color:rgba(255,62,62,0.3);color:var(--accent);">🚨 Report This Article</a>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>