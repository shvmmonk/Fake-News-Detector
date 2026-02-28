<?php
require_once 'includes/db.php';
require_once 'lang.php';
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
  <div style="margin-bottom:20px;">
    <a href="articles.php" class="btn btn-ghost" style="font-size:0.7rem;padding:6px 12px;"><?= $t['back'] ?></a>
  </div>

  <div class="grid-2">
    <div>
      <div class="card mb-20">
        <div class="card-header">
          <div class="card-title"><?= $t['articles'] ?> #<?= str_pad($id,3,'0',STR_PAD_LEFT) ?></div>
          <?php $v=$verification['verdict']??'unverified'; ?>
          <span class="badge b-<?= $v ?>" style="font-size:0.75rem;padding:5px 12px;"><?= strtoupper($t[$v] ?? $v) ?></span>
        </div>
        <div class="card-body">
          <h2 style="font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:700;margin-bottom:14px;line-height:1.4;"><?= htmlspecialchars($article['title']) ?></h2>
          <p style="color:var(--muted);font-size:0.85rem;line-height:1.7;"><?= nl2br(htmlspecialchars($article['content'])) ?></p>
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
        <div class="card-header"><div class="card-title"><?= strtoupper($t['source']) ?></div></div>
        <div class="card-body">
          <div class="detail-row"><div class="detail-label"><?= $t['author'] ?></div><div><?= htmlspecialchars($article['author']??'—') ?></div></div>
          <div class="detail-row"><div class="detail-label"><?= $t['category'] ?></div><div><?= htmlspecialchars($article['category']??'—') ?></div></div>
          <div class="detail-row"><div class="detail-label"><?= $t['source'] ?></div><div><?= htmlspecialchars($article['source']??'—') ?></div></div>
          <div class="detail-row">
            <div class="detail-label"><?= $t['credibility'] ?></div>
            <div>
              <?php if($article['credibility_score']): $sc=$article['credibility_score']; ?>
              <div class="cbar-wrap">
                <div class="cbar"><div class="cbar-fill" style="width:<?= $sc ?>%;background:<?= $sc>70?'var(--green)':($sc>40?'var(--yellow)':'var(--accent)') ?>"></div></div>
                <div class="cbar-num"><?= $sc ?>/100</div>
              </div>
              <?php else: echo '—'; endif; ?>
            </div>
          </div>
          <div class="detail-row"><div class="detail-label"><?= $t['submitted_by'] ?></div><div class="text-muted"><?= htmlspecialchars($article['submitted_by_user']??'—') ?></div></div>
          <?php if($article['url']): ?>
          <div class="detail-row"><div class="detail-label">URL</div><div><a href="<?= htmlspecialchars($article['url']) ?>" target="_blank" style="color:var(--blue);font-size:0.72rem;"><?= $t['view_original'] ?></a></div></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div>
      <?php if($verification): ?>
      <div class="card mb-20">
        <div class="card-header">
          <div class="card-title"><?= $t['verified'] ?></div>
          <!-- ✅ AI ANCHOR TRIGGER BUTTON -->
          <button onclick="FakeAnchor.show()" style="background:rgba(230,50,50,0.12);border:1px solid rgba(230,50,50,0.35);color:var(--accent);font-family:'Oswald',sans-serif;font-size:0.62rem;letter-spacing:2px;padding:6px 14px;cursor:pointer;display:flex;align-items:center;gap:6px;">
            <svg viewBox="0 0 40 40" width="14" height="14"><circle cx="20" cy="14" r="8" fill="currentColor"/><rect x="12" y="24" width="16" height="16" rx="4" fill="currentColor"/><rect x="17" y="32" width="6" height="6" rx="1" fill="#e63232"/></svg>
            AI ANCHOR
          </button>
        </div>
        <div class="card-body">
          <div class="detail-row"><div class="detail-label"><?= $t['verdict'] ?></div><div><span class="badge b-<?= $verification['verdict'] ?>"><?= strtoupper($t[$verification['verdict']] ?? $verification['verdict']) ?></span></div></div>
          <div class="detail-row">
            <div class="detail-label"><?= $t['confidence'] ?></div>
            <div class="cbar-wrap">
              <div class="cbar"><div class="cbar-fill" style="width:<?= $verification['confidence_score'] ?>%;background:<?= $verification['verdict']=='fake'?'var(--accent)':($verification['verdict']=='real'?'var(--green)':'var(--yellow)') ?>"></div></div>
              <div class="cbar-num"><?= $verification['confidence_score'] ?>%</div>
            </div>
          </div>
          <div class="detail-row"><div class="detail-label"><?= $t['checked_by'] ?></div><div style="color:var(--blue)"><?= htmlspecialchars($verification['checker_name']) ?></div></div>
          <div class="detail-row"><div class="detail-label"><?= $t['verified_at'] ?></div><div class="text-muted"><?= date('d M Y, H:i', strtotime($verification['verified_at'])) ?></div></div>
          <div class="detail-row"><div class="detail-label"><?= $t['explanation'] ?></div><div style="font-size:0.85rem;line-height:1.6;"><?= nl2br(htmlspecialchars($verification['explanation'])) ?></div></div>
        </div>
      </div>

      <?php if($evidence): ?>
      <div class="card mb-20">
        <div class="card-header"><div class="card-title"><?= $t['evidence'] ?> (<?= count($evidence) ?>)</div></div>
        <?php foreach($evidence as $ev): ?>
        <div style="padding:14px 20px;border-bottom:1px solid var(--border);">
          <span class="badge <?= $ev['evidence_type']=='supporting'?'b-real':'b-fake' ?>" style="margin-bottom:8px;display:inline-block;"><?= $ev['evidence_type']=='supporting'?'✓ '.$t['supporting']:'✗ '.$t['contradicting'] ?></span>
          <p style="font-size:0.85rem;line-height:1.5;"><?= htmlspecialchars($ev['description']) ?></p>
          <?php if($ev['source_url']): ?><a href="<?= htmlspecialchars($ev['source_url']) ?>" target="_blank" style="font-size:0.68rem;color:var(--blue);"><?= $t['view_original'] ?></a><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php else: ?>
      <div class="card mb-20">
        <div class="card-body" style="text-align:center;padding:40px;">
          <div style="font-size:2rem;margin-bottom:12px;">🔍</div>
          <div style="color:var(--muted);font-size:0.85rem;margin-bottom:16px;"><?= $t['not_verified'] ?></div>
          <a href="verify.php?article_id=<?= $id ?>" class="btn btn-primary"><?= $t['verify_this'] ?></a>
        </div>
      </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-body" style="text-align:center;padding:20px;">
          <div style="color:var(--muted);font-size:0.72rem;margin-bottom:12px;"><?= $t['report_sub'] ?></div>
          <a href="report_article.php?id=<?= $id ?>" class="btn btn-ghost" style="border-color:rgba(230,50,50,0.3);color:var(--accent);"><?= $t['report_this'] ?></a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php /* ── AI ANCHOR COMPONENT ── */ require_once 'includes/anchor.php' /* HACKATHON EDITION */; ?>

<?php if($verification): ?>
<script>
// Pre-load anchor with this article's verification data
document.addEventListener('DOMContentLoaded', function() {

  // Show the floating trigger button (bottom-right)
  var triggerBtn = document.getElementById('anchorTriggerBtn');
  if (triggerBtn) triggerBtn.style.display = 'flex';

  // Feed the anchor with real data from DB
  FakeAnchor.speak({
    verdict:    '<?= $verification['verdict'] ?>',
    confidence: <?= intval($verification['confidence_score']) ?>,
    title:      <?= json_encode($article['title']) ?>,
    reason:     <?= json_encode($verification['explanation']) ?>,
    corrected:  <?= json_encode(
      $verification['verdict'] === 'fake'
        ? 'Based on verified sources, this claim has been found to be false. Cross-reference with trusted outlets before sharing.'
        : ($verification['verdict'] === 'real'
            ? 'This article has been verified as accurate against multiple reliable sources.'
            : 'This article contains some misleading elements. Read carefully and verify key claims independently.')
    ) ?>,
    sources: [
      'Reuters — reuters.com',
      'BBC News — bbc.com/news',
      'Associated Press — apnews.com',
      'PIB Fact Check — pib.gov.in',
      'WHO — who.int'
    ]
  });
});
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
