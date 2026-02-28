<?php
require_once 'includes/db.php';
require_once 'lang.php';
$pageTitle = $t['verify'];
$success = $error = '';
$preselect = intval($_GET['article_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $article_id  = intval($_POST['article_id']);
    $checked_by  = intval($_POST['checked_by']);
    $verdict     = $_POST['verdict'];
    $confidence  = intval($_POST['confidence_score']);
    $explanation = trim($_POST['explanation']);
    $ev_type     = $_POST['evidence_type'] ?? '';
    $ev_desc     = trim($_POST['evidence_desc'] ?? '');
    $ev_url      = trim($_POST['evidence_url'] ?? '');

    if (!$article_id || !$checked_by || !$verdict || !$explanation) {
        $error = "All required fields must be filled.";
    } else {
        $exists = $pdo->prepare("SELECT COUNT(*) FROM verifications WHERE article_id=?");
        $exists->execute([$article_id]);
        if ($exists->fetchColumn() > 0) {
            $stmt = $pdo->prepare("UPDATE verifications SET checked_by=?, verdict=?, confidence_score=?, explanation=?, verified_at=NOW() WHERE article_id=?");
            $stmt->execute([$checked_by, $verdict, $confidence, $explanation, $article_id]);
            $ver_id = $pdo->prepare("SELECT verification_id FROM verifications WHERE article_id=?");
            $ver_id->execute([$article_id]);
            $ver_id = $ver_id->fetchColumn();
        } else {
            $stmt = $pdo->prepare("INSERT INTO verifications (article_id, checked_by, verdict, confidence_score, explanation) VALUES (?,?,?,?,?)");
            $stmt->execute([$article_id, $checked_by, $verdict, $confidence, $explanation]);
            $ver_id = $pdo->lastInsertId();
        }
        if ($ev_type && $ev_desc) {
            $pdo->prepare("INSERT INTO evidence (verification_id, evidence_type, description, source_url) VALUES (?,?,?,?)")
                ->execute([$ver_id, $ev_type, $ev_desc, $ev_url ?: null]);
        }
        $success = "✅ ".$t['verified']."! <a href='article_detail.php?id=$article_id' style='color:var(--green)'>".$t['view']." →</a>";
    }
}

$articles = $pdo->query("
    SELECT a.article_id, a.title, c.name AS category, v.verdict
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.category_id
    LEFT JOIN verifications v ON a.article_id = v.article_id
    ORDER BY a.created_at DESC
")->fetchAll();

$checkers = $pdo->query("SELECT user_id, username FROM users WHERE role IN ('fact_checker','admin') ORDER BY username")->fetchAll();
require_once 'includes/header.php';
?>

<div class="wrapper">
  <div class="page-header">
    <div>
      <h1><?= $t['verify'] ?></h1>
      <p><?= strtoupper($t['submit_verif']) ?></p>
    </div>
  </div>

  <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
  <?php if($error):   ?><div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="grid-2">
    <div class="card">
      <div class="card-header"><div class="card-title"><?= $t['verify'] ?></div></div>
      <div class="card-body">
        <form method="POST">
          <div class="form-group">
            <label><?= $t['select_article'] ?> *</label>
            <select name="article_id" id="articleSelect" required>
              <option value=""><?= $t['choose_article'] ?></option>
              <?php foreach($articles as $a): ?>
              <option value="<?= $a['article_id'] ?>" <?= $preselect==$a['article_id']?'selected':'' ?>>
                #<?= str_pad($a['article_id'],3,'0',STR_PAD_LEFT) ?> — <?= htmlspecialchars(substr($a['title'],0,50)) ?>...
                <?= $a['verdict'] ? '['.strtoupper($t[$a['verdict']] ?? $a['verdict']).']' : '['.$t['unverified'].']' ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label><?= $t['fact_checker'] ?> *</label>
            <select name="checked_by" required>
              <option value=""><?= $t['select_checker'] ?></option>
              <?php foreach($checkers as $c): ?>
              <option value="<?= $c['user_id'] ?>"><?= htmlspecialchars($c['username']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div style="margin-bottom:18px;padding:16px;background:rgba(79,179,255,0.05);border:1px solid rgba(79,179,255,0.2);border-radius:4px;">
            <div style="font-size:0.68rem;color:var(--blue);margin-bottom:10px;letter-spacing:2px;"><?= $t['ai_assistant'] ?></div>
            <button type="button" id="aiBtn" onclick="checkWithAI()" class="btn" style="background:rgba(79,179,255,0.15);color:var(--blue);border:1px solid rgba(79,179,255,0.3);width:100%;font-size:0.78rem;">
              <?= $t['check_ai'] ?>
            </button>
            <div id="aiResult" style="margin-top:12px;display:none;"></div>
          </div>

          <div class="grid-2">
            <div class="form-group">
              <label><?= $t['verdict'] ?> *</label>
              <select name="verdict" id="verdictSelect" required>
                <option value="">Select...</option>
                <option value="fake">🔴 <?= strtoupper($t['fake']) ?></option>
                <option value="real">🟢 <?= strtoupper($t['real']) ?></option>
                <option value="misleading">🟡 <?= strtoupper($t['misleading']) ?></option>
                <option value="unverified">⚪ <?= strtoupper($t['unverified']) ?></option>
              </select>
            </div>
            <div class="form-group">
              <label><?= $t['confidence'] ?> (0-100) *</label>
              <input type="number" name="confidence_score" id="confidenceInput" min="0" max="100" value="80">
            </div>
          </div>

          <div class="form-group">
            <label><?= $t['explanation'] ?> *</label>
            <textarea name="explanation" id="explanationInput" placeholder="<?= $t['why_verdict'] ?>"></textarea>
          </div>

          <div style="border-top:1px solid var(--border);padding-top:16px;margin-bottom:16px;">
            <div style="color:var(--muted);font-size:0.68rem;letter-spacing:2px;margin-bottom:14px;"><?= strtoupper($t['evidence']) ?></div>
            <div class="form-group">
              <label><?= $t['ev_type'] ?></label>
              <select name="evidence_type">
                <option value="">None</option>
                <option value="supporting"><?= $t['supporting'] ?></option>
                <option value="contradicting"><?= $t['contradicting'] ?></option>
              </select>
            </div>
            <div class="form-group">
              <label><?= $t['ev_desc'] ?></label>
              <textarea name="evidence_desc" style="min-height:60px" placeholder="<?= $t['ev_desc'] ?>..."></textarea>
            </div>
            <div class="form-group">
              <label><?= $t['ev_url'] ?></label>
              <input type="url" name="evidence_url" placeholder="https://...">
            </div>
          </div>

          <button type="submit" class="btn btn-primary" style="width:100%;"><?= $t['submit_verif'] ?></button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <div class="card-title"><?= $t['all_articles'] ?></div>
        <span style="color:var(--muted);font-size:0.75rem;"><?= count($articles) ?> <?= $t['total_label'] ?></span>
      </div>
      <?php if(empty($articles)): ?>
        <div style="padding:30px;text-align:center;color:var(--muted);"><?= $t['no_articles'] ?></div>
      <?php else: foreach($articles as $a): ?>
      <div style="padding:12px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;">
        <span class="text-muted text-sm" style="min-width:28px;">#<?= str_pad($a['article_id'],3,'0',STR_PAD_LEFT) ?></span>
        <div style="flex:1;">
          <div style="font-size:0.78rem;"><?= htmlspecialchars(substr($a['title'],0,38)) ?>...</div>
          <div style="font-size:0.62rem;color:var(--muted);margin-top:2px;"><?= htmlspecialchars($a['category'] ?? 'Uncategorized') ?></div>
        </div>
        <?php if($a['verdict']): ?>
          <span class="badge b-<?= $a['verdict'] ?>" style="font-size:0.55rem;"><?= strtoupper($t[$a['verdict']] ?? $a['verdict']) ?></span>
        <?php else: ?>
          <span class="badge b-unverified" style="font-size:0.55rem;"><?= strtoupper($t['unverified']) ?></span>
        <?php endif; ?>
        <a href="?article_id=<?= $a['article_id'] ?>" class="btn btn-ghost" style="font-size:0.62rem;padding:3px 8px;"><?= $t['view'] ?></a>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<?php /* ── AI ANCHOR COMPONENT ── */ require_once 'includes/anchor.php' /* HACKATHON EDITION */; ?>

<script>
function checkWithAI() {
  var articleId = document.getElementById('articleSelect').value;
  if (!articleId) { alert('<?= $t['choose_article'] ?>'); return; }
  var btn = document.getElementById('aiBtn');
  var resultDiv = document.getElementById('aiResult');
  btn.textContent = '⏳ AI...';
  btn.disabled = true;
  resultDiv.style.display = 'none';

  fetch('ai_check.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'article_id=' + articleId
  })
  .then(r => r.json())
  .then(data => {
    btn.textContent = '<?= $t['check_ai'] ?>';
    btn.disabled = false;

    if (data.error) {
      resultDiv.innerHTML = '<div style="color:var(--accent);font-size:0.78rem;">❌ ' + data.error + '</div>';
      resultDiv.style.display = 'block';
      return;
    }

    var color = data.verdict==='fake'?'var(--accent)':data.verdict==='real'?'var(--green)':'var(--yellow)';
    resultDiv.innerHTML = `
      <div style="background:rgba(0,0,0,0.3);border-radius:4px;padding:14px;border-left:3px solid ${color};margin-top:10px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
          <span style="font-family:'Playfair Display',serif;font-size:1rem;font-weight:900;color:${color}">${data.verdict.toUpperCase()}</span>
          <span style="font-size:0.7rem;color:var(--muted);"><?= $t['confidence'] ?>: ${data.confidence}%</span>
        </div>
        <div style="font-size:0.78rem;color:var(--text);line-height:1.5;margin-bottom:10px;">${data.reason}</div>
        <div style="display:flex;gap:8px;">
          <button type="button" onclick="applyAISuggestion('${data.verdict}',${data.confidence},\`${data.reason}\`)"
            style="background:${color};color:#000;border:none;padding:6px 16px;border-radius:2px;font-size:0.72rem;cursor:pointer;font-weight:700;flex:1;">
            <?= $t['apply_suggestion'] ?>
          </button>
          <button type="button" onclick="openAnchorFromAI('${data.verdict}',${data.confidence},\`${data.reason}\`, ${articleId})"
            style="background:rgba(230,50,50,0.12);color:var(--accent);border:1px solid rgba(230,50,50,0.35);padding:6px 16px;border-radius:2px;font-size:0.72rem;cursor:pointer;font-weight:700;display:flex;align-items:center;gap:6px;">
            <svg viewBox="0 0 40 40" width="12" height="12"><circle cx="20" cy="14" r="8" fill="currentColor"/><rect x="12" y="24" width="16" height="16" rx="4" fill="currentColor"/></svg>
            AI ANCHOR
          </button>
        </div>
      </div>`;
    resultDiv.style.display = 'block';
  })
  .catch(() => {
    btn.textContent = '<?= $t['check_ai'] ?>';
    btn.disabled = false;
    resultDiv.innerHTML = '<div style="color:var(--accent);font-size:0.78rem;">❌ Connection error!</div>';
    resultDiv.style.display = 'block';
  });
}

function applyAISuggestion(verdict, confidence, reason) {
  document.getElementById('verdictSelect').value    = verdict;
  document.getElementById('confidenceInput').value  = confidence;
  document.getElementById('explanationInput').value = 'AI: ' + reason;
}

function openAnchorFromAI(verdict, confidence, reason, articleId) {
  // Get the selected article title from the dropdown
  var select = document.getElementById('articleSelect');
  var title  = select.options[select.selectedIndex]
                  ? select.options[select.selectedIndex].text.replace(/^#\d+ — /, '').replace(/\[.*\]$/, '').trim()
                  : 'Article #' + articleId;

  FakeAnchor.speak({
    verdict:    verdict,
    confidence: confidence,
    title:      title,
    reason:     reason,
    corrected:  null,
    sources: [
      'Reuters — reuters.com',
      'BBC News — bbc.com/news',
      'Associated Press — apnews.com',
      'PIB Fact Check — pib.gov.in',
      'WHO — who.int'
    ]
  });
}
</script>

<?php require_once 'includes/footer.php'; ?>
