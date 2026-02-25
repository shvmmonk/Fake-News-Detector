<?php
require_once 'includes/db.php';
$pageTitle = 'Verify Article';
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
        // Check if already verified — update karo instead of insert
        $exists = $pdo->prepare("SELECT COUNT(*) FROM verifications WHERE article_id=?");
        $exists->execute([$article_id]);

        if ($exists->fetchColumn() > 0) {
            // Already verified — UPDATE karo
            $stmt = $pdo->prepare("UPDATE verifications SET checked_by=?, verdict=?, confidence_score=?, explanation=?, verified_at=NOW() WHERE article_id=?");
            $stmt->execute([$checked_by, $verdict, $confidence, $explanation, $article_id]);
            $ver_id = $pdo->prepare("SELECT verification_id FROM verifications WHERE article_id=?");
            $ver_id->execute([$article_id]);
            $ver_id = $ver_id->fetchColumn();
        } else {
            // Naya insert karo
            $stmt = $pdo->prepare("INSERT INTO verifications (article_id, checked_by, verdict, confidence_score, explanation) VALUES (?,?,?,?,?)");
            $stmt->execute([$article_id, $checked_by, $verdict, $confidence, $explanation]);
            $ver_id = $pdo->lastInsertId();
        }

        if ($ev_type && $ev_desc) {
            $pdo->prepare("INSERT INTO evidence (verification_id, evidence_type, description, source_url) VALUES (?,?,?,?)")
                ->execute([$ver_id, $ev_type, $ev_desc, $ev_url ?: null]);
        }
        $success = "✅ Done! <a href='article_detail.php?id=$article_id' style='color:var(--green)'>View result →</a>";
    }
}

// Saare articles — verified + unverified dono
$articles = $pdo->query("
    SELECT a.article_id, a.title, c.name AS category, v.verdict
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.category_id
    LEFT JOIN verifications v ON a.article_id = v.article_id
    ORDER BY a.created_at DESC
")->fetchAll();

$checkers = $pdo->query("
    SELECT user_id, username FROM users 
    WHERE role IN ('fact_checker','admin') 
    ORDER BY username
")->fetchAll();

require_once 'includes/header.php';
?>

<div class="wrapper">
  <div class="page-header">
    <h1>Verify Article</h1>
    <p>SUBMIT A FACT-CHECK VERDICT</p>
  </div>

  <?php if($success): ?>
  <div class="alert alert-success"><?= $success ?></div>
  <?php endif; ?>
  <?php if($error): ?>
  <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="grid-2">

    <!-- LEFT: FORM -->
    <div class="card">
      <div class="card-header"><div class="card-title">Verification Form</div></div>
      <div class="card-body">
        <form method="POST">

          <div class="form-group">
            <label>Select Article *</label>
            <select name="article_id" id="articleSelect" required>
              <option value="">Choose article...</option>
              <?php foreach($articles as $a): ?>
              <option value="<?= $a['article_id'] ?>" <?= $preselect==$a['article_id']?'selected':'' ?>>
                #<?= str_pad($a['article_id'],3,'0',STR_PAD_LEFT) ?> — <?= htmlspecialchars(substr($a['title'],0,50)) ?>...
                <?= $a['verdict'] ? '['.strtoupper($a['verdict']).']' : '[NEW]' ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Fact Checker *</label>
            <select name="checked_by" required>
              <option value="">Select checker...</option>
              <?php foreach($checkers as $c): ?>
              <option value="<?= $c['user_id'] ?>"><?= htmlspecialchars($c['username']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- AI CHECK BOX -->
          <div style="margin-bottom:18px;padding:16px;background:rgba(79,179,255,0.05);border:1px solid rgba(79,179,255,0.2);border-radius:10px;">
            <div style="font-size:0.68rem;color:var(--blue);margin-bottom:10px;letter-spacing:2px;">🤖 GEMINI AI ASSISTANT</div>
            <button type="button" id="aiBtn" onclick="checkWithAI()" class="btn" style="background:rgba(79,179,255,0.15);color:var(--blue);border:1px solid rgba(79,179,255,0.3);width:100%;font-size:0.78rem;">
              ✨ Check with Gemini AI
            </button>
            <div id="aiResult" style="margin-top:12px;display:none;"></div>
          </div>

          <div class="grid-2">
            <div class="form-group">
              <label>Verdict *</label>
              <select name="verdict" id="verdictSelect" required>
                <option value="">Select...</option>
                <option value="fake">🔴 FAKE</option>
                <option value="real">🟢 REAL</option>
                <option value="misleading">🟡 MISLEADING</option>
                <option value="unverified">⚪ UNVERIFIED</option>
              </select>
            </div>
            <div class="form-group">
              <label>Confidence (0-100) *</label>
              <input type="number" name="confidence_score" id="confidenceInput" min="0" max="100" value="80">
            </div>
          </div>

          <div class="form-group">
            <label>Explanation *</label>
            <textarea name="explanation" id="explanationInput" placeholder="Why this verdict?"></textarea>
          </div>

          <div style="border-top:1px solid var(--border);padding-top:16px;margin-bottom:16px;">
            <div style="color:var(--muted);font-size:0.68rem;letter-spacing:2px;margin-bottom:14px;">EVIDENCE (OPTIONAL)</div>
            <div class="form-group">
              <label>Evidence Type</label>
              <select name="evidence_type">
                <option value="">None</option>
                <option value="supporting">Supporting</option>
                <option value="contradicting">Contradicting</option>
              </select>
            </div>
            <div class="form-group">
              <label>Evidence Description</label>
              <textarea name="evidence_desc" style="min-height:60px" placeholder="Describe evidence..."></textarea>
            </div>
            <div class="form-group">
              <label>Evidence URL</label>
              <input type="url" name="evidence_url" placeholder="https://...">
            </div>
          </div>

          <button type="submit" class="btn btn-primary" style="width:100%;">
            Submit Verification
          </button>

        </form>
      </div>
    </div>

    <!-- RIGHT: ALL ARTICLES LIST -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">All Articles</div>
        <span style="color:var(--muted);font-size:0.75rem;"><?= count($articles) ?> total</span>
      </div>
      <?php if(empty($articles)): ?>
        <div style="padding:30px;text-align:center;color:var(--muted);">No articles found!</div>
      <?php else: foreach($articles as $a): ?>
      <div style="padding:12px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;">
        <span class="text-muted text-sm" style="min-width:28px;">#<?= str_pad($a['article_id'],3,'0',STR_PAD_LEFT) ?></span>
        <div style="flex:1;">
          <div style="font-size:0.78rem;"><?= htmlspecialchars(substr($a['title'],0,38)) ?>...</div>
          <div style="font-size:0.62rem;color:var(--muted);margin-top:2px;"><?= htmlspecialchars($a['category'] ?? 'Uncategorized') ?></div>
        </div>
        <?php if($a['verdict']): ?>
          <span class="badge b-<?= $a['verdict'] ?>" style="font-size:0.55rem;"><?= strtoupper($a['verdict']) ?></span>
        <?php else: ?>
          <span class="badge b-unverified" style="font-size:0.55rem;">NEW</span>
        <?php endif; ?>
        <a href="?article_id=<?= $a['article_id'] ?>" class="btn btn-ghost" style="font-size:0.62rem;padding:3px 8px;">Pick</a>
      </div>
      <?php endforeach; endif; ?>
    </div>

  </div>
</div>

<script>
function checkWithAI() {
  var articleId = document.getElementById('articleSelect').value;
  if (!articleId) {
    alert('Pehle article select karo!');
    return;
  }

  var btn = document.getElementById('aiBtn');
  var resultDiv = document.getElementById('aiResult');

  btn.textContent = '⏳ AI check kar raha hai...';
  btn.disabled = true;
  resultDiv.style.display = 'none';

  fetch('ai_check.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'article_id=' + articleId
  })
  .then(r => r.json())
  .then(data => {
    btn.textContent = '✨ Check with Gemini AI';
    btn.disabled = false;

    if (data.error) {
      resultDiv.innerHTML = '<div style="color:var(--accent);font-size:0.78rem;">❌ Error: ' + data.error + '</div>';
      resultDiv.style.display = 'block';
      return;
    }

    var color = data.verdict === 'fake'       ? 'var(--accent)' :
                data.verdict === 'real'       ? 'var(--green)'  :
                data.verdict === 'misleading' ? 'var(--yellow)' : 'var(--muted)';

    resultDiv.innerHTML = `
      <div style="background:rgba(0,0,0,0.3);border-radius:8px;padding:14px;border-left:3px solid ${color};margin-top:10px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
          <span style="font-family:'Syne',sans-serif;font-size:1rem;font-weight:800;color:${color}">
            ${data.verdict.toUpperCase()}
          </span>
          <span style="font-size:0.7rem;color:var(--muted);">Confidence: ${data.confidence}%</span>
        </div>
        <div style="font-size:0.75rem;color:var(--text);line-height:1.5;margin-bottom:10px;">${data.reason}</div>
        <button type="button" onclick="applyAISuggestion('${data.verdict}', ${data.confidence}, \`${data.reason}\`)"
          style="background:${color};color:#000;border:none;padding:6px 16px;border-radius:6px;font-size:0.72rem;cursor:pointer;font-weight:700;">
          ✓ Apply This Suggestion
        </button>
      </div>
    `;
    resultDiv.style.display = 'block';
  })
  .catch(err => {
    btn.textContent = '✨ Check with Gemini AI';
    btn.disabled = false;
    resultDiv.innerHTML = '<div style="color:var(--accent);font-size:0.78rem;">❌ Connection error! Check API key.</div>';
    resultDiv.style.display = 'block';
  });
}

function applyAISuggestion(verdict, confidence, reason) {
  document.getElementById('verdictSelect').value    = verdict;
  document.getElementById('confidenceInput').value  = confidence;
  document.getElementById('explanationInput').value = 'AI (Gemini) Analysis: ' + reason;
}
</script>

<?php require_once 'includes/footer.php'; ?>
