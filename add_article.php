<?php
require_once 'includes/db.php';
$pageTitle = 'Submit Article';
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']       ?? '');
    $content     = trim($_POST['content']     ?? '');
    $author      = trim($_POST['author']      ?? '');
    $url         = trim($_POST['url']         ?? '');
    $source_id   = intval($_POST['source_id']   ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    if (!$title || !$content) { $error = "Title and Content are required."; }
    else {
        $stmt = $pdo->prepare("INSERT INTO articles (title, content, author, url, source_id, category_id, submitted_by, published_at) VALUES (?,?,?,?,?,?,4,NOW())");
        $stmt->execute([$title, $content, $author ?: null, $url ?: null, $source_id ?: null, $category_id ?: null]);
        $new_id = $pdo->lastInsertId();
        $success = "Article submitted! <a href='article_detail.php?id=$new_id' style='color:var(--green)'>View it →</a>";
    }
}

$sources    = $pdo->query("SELECT * FROM sources    ORDER BY name")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
require_once 'includes/header.php';
?>
<div class="wrapper">
  <div style="margin-bottom:20px;"><a href="articles.php" class="btn btn-ghost" style="font-size:0.7rem;padding:6px 12px;">← Back</a></div>
  <div class="page-header"><h1>Submit Article</h1><p>ADD A NEW ARTICLE FOR FACT-CHECKING</p></div>

  <div style="max-width:700px">
    <?php if($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>
    <?php if($error):   ?><div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- ══════════════════════════════════════
         SCREENSHOT AI CHECK SECTION
    ══════════════════════════════════════ -->
    <div class="card mb-20" id="screenshotCard">
      <div class="card-header">
        <div class="card-title">📸 Screenshot AI Check</div>
        <span style="font-size:0.62rem;color:var(--muted);letter-spacing:1px;">PASTE · DRAG & DROP · UPLOAD</span>
      </div>
      <div class="card-body" style="padding:20px 24px;">

        <!-- Drop / Paste Zone -->
        <div id="dropZone"
          style="
            border: 2px dashed var(--border);
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: rgba(255,255,255,0.02);
            position: relative;
          ">
          <input type="file" id="fileInput" accept="image/*"
            style="position:absolute;inset:0;opacity:0;cursor:pointer;">
          <div id="dropIcon" style="font-size:2.5rem;margin-bottom:10px;">📋</div>
          <div style="font-family:'Oswald',sans-serif;font-size:0.75rem;letter-spacing:2px;color:var(--muted);">
            CTRL+V TO PASTE &nbsp;·&nbsp; DRAG IMAGE HERE &nbsp;·&nbsp; CLICK TO UPLOAD
          </div>
          <div style="font-size:0.65rem;color:var(--muted);margin-top:6px;">
            Screenshot of WhatsApp, Twitter, Facebook, News Website — sab supported
          </div>
        </div>

        <!-- Image Preview -->
        <div id="imgPreviewWrap" style="display:none;margin-top:16px;position:relative;">
          <img id="imgPreview" style="max-width:100%;max-height:320px;border-radius:6px;border:1px solid var(--border);display:block;">
          <button onclick="clearImage()"
            style="position:absolute;top:8px;right:8px;background:rgba(0,0,0,0.7);border:none;color:#fff;border-radius:50%;width:28px;height:28px;cursor:pointer;font-size:0.9rem;">✕</button>
        </div>

        <!-- Analyze Button -->
        <div id="analyzeSection" style="display:none;margin-top:16px;">
          <button type="button" id="analyzeBtn" onclick="analyzeScreenshot()"
            style="
              width:100%;padding:12px;
              background:linear-gradient(135deg,rgba(230,50,50,0.15),rgba(230,50,50,0.05));
              border:1px solid rgba(230,50,50,0.3);
              color:var(--accent);
              font-family:'Oswald',sans-serif;
              font-size:0.8rem;letter-spacing:2px;
              border-radius:6px;cursor:pointer;
              transition:all 0.2s;
            ">
            🤖 ANALYSE WITH GROQ AI
          </button>
        </div>

        <!-- AI Result Box -->
        <div id="aiResultBox" style="display:none;margin-top:16px;"></div>

      </div>
    </div>

    <!-- ══════════════════════════════════════
         MANUAL FORM
    ══════════════════════════════════════ -->
    <div class="card">
      <div class="card-header"><div class="card-title">Article Details</div></div>
      <div class="card-body">
        <form method="POST" id="articleForm">
          <div class="form-group">
            <label>Title *</label>
            <input type="text" name="title" id="fTitle" value="<?= htmlspecialchars($_POST['title']??'') ?>" placeholder="Article headline...">
          </div>
          <div class="form-group">
            <label>Content *</label>
            <textarea name="content" id="fContent" placeholder="Article content or summary..."><?= htmlspecialchars($_POST['content']??'') ?></textarea>
          </div>
          <div class="grid-2">
            <div class="form-group">
              <label>Author</label>
              <input type="text" name="author" id="fAuthor" value="<?= htmlspecialchars($_POST['author']??'') ?>" placeholder="Author name">
            </div>
            <div class="form-group">
              <label>Original URL</label>
              <input type="url" name="url" value="<?= htmlspecialchars($_POST['url']??'') ?>" placeholder="https://...">
            </div>
          </div>
          <div class="grid-2">
            <div class="form-group">
              <label>Source</label>
              <select name="source_id">
                <option value="">Select source...</option>
                <?php foreach($sources as $s): ?>
                <option value="<?= $s['source_id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= $s['credibility_score'] ?>/100)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Category</label>
              <select name="category_id">
                <option value="">Select category...</option>
                <?php foreach($categories as $c): ?>
                <option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div style="display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary">Submit Article</button>
            <a href="articles.php" class="btn btn-ghost">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
let currentImageBase64 = null;

const dropZone   = document.getElementById('dropZone');
const fileInput  = document.getElementById('fileInput');
const previewWrap= document.getElementById('imgPreviewWrap');
const imgPreview = document.getElementById('imgPreview');
const analyzeSection = document.getElementById('analyzeSection');
const aiResultBox    = document.getElementById('aiResultBox');

// ── Load image from File object ──────────────
function loadImageFile(file) {
  if (!file || !file.type.startsWith('image/')) {
    alert('Sirf image files supported hain!');
    return;
  }
  const reader = new FileReader();
  reader.onload = function(e) {
    currentImageBase64 = e.target.result;
    imgPreview.src     = e.target.result;
    previewWrap.style.display  = 'block';
    analyzeSection.style.display = 'block';
    aiResultBox.style.display  = 'none';
    dropZone.style.border      = '2px dashed rgba(230,50,50,0.5)';
    document.getElementById('dropIcon').textContent = '🖼️';
  };
  reader.readAsDataURL(file);
}

// ── Click to upload ──────────────────────────
fileInput.addEventListener('change', function() {
  if (this.files[0]) loadImageFile(this.files[0]);
});

// ── Drag & Drop ──────────────────────────────
dropZone.addEventListener('dragover', function(e) {
  e.preventDefault();
  this.style.borderColor = 'var(--accent)';
  this.style.background  = 'rgba(230,50,50,0.05)';
});
dropZone.addEventListener('dragleave', function() {
  this.style.borderColor = 'var(--border)';
  this.style.background  = 'rgba(255,255,255,0.02)';
});
dropZone.addEventListener('drop', function(e) {
  e.preventDefault();
  this.style.borderColor = 'var(--border)';
  this.style.background  = 'rgba(255,255,255,0.02)';
  const file = e.dataTransfer.files[0];
  if (file) loadImageFile(file);
});

// ── Paste (Ctrl+V) ───────────────────────────
document.addEventListener('paste', function(e) {
  const items = e.clipboardData?.items;
  if (!items) return;
  for (let item of items) {
    if (item.type.startsWith('image/')) {
      const file = item.getAsFile();
      if (file) {
        loadImageFile(file);
        // Scroll to screenshot card
        document.getElementById('screenshotCard').scrollIntoView({ behavior:'smooth' });
      }
      break;
    }
  }
});

// ── Clear image ──────────────────────────────
function clearImage() {
  currentImageBase64           = null;
  imgPreview.src               = '';
  previewWrap.style.display    = 'none';
  analyzeSection.style.display = 'none';
  aiResultBox.style.display    = 'none';
  dropZone.style.border        = '2px dashed var(--border)';
  document.getElementById('dropIcon').textContent = '📋';
  fileInput.value = '';
}

// ── Analyze with Groq AI ─────────────────────
function analyzeScreenshot() {
  if (!currentImageBase64) return;

  const btn     = document.getElementById('analyzeBtn');
  btn.textContent = '⏳ AI Analyse kar raha hai...';
  btn.disabled    = true;

  aiResultBox.style.display = 'block';
  aiResultBox.innerHTML = `
    <div style="background:rgba(79,179,255,0.05);border:1px solid rgba(79,179,255,0.2);border-radius:8px;padding:18px;text-align:center;">
      <div style="font-size:1.5rem;margin-bottom:8px;">🤖</div>
      <div style="font-family:'Oswald',sans-serif;font-size:0.7rem;letter-spacing:2px;color:var(--blue);">GROQ AI READING SCREENSHOT...</div>
      <div style="font-size:0.68rem;color:var(--muted);margin-top:6px;">Text extract ho raha hai, thoda wait karo...</div>
    </div>`;

  fetch('ai_image_check.php', {
    method:  'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body:    'image=' + encodeURIComponent(currentImageBase64)
  })
  .then(r => r.json())
  .then(data => {
    btn.textContent = '🤖 ANALYSE WITH GROQ AI';
    btn.disabled    = false;

    if (data.error) {
      aiResultBox.innerHTML = `
        <div style="background:rgba(230,50,50,0.08);border:1px solid rgba(230,50,50,0.3);border-radius:8px;padding:16px;">
          <div style="color:var(--accent);font-size:0.82rem;">⚠ ${data.error}</div>
          ${data.raw ? '<div style="color:var(--muted);font-size:0.7rem;margin-top:8px;">' + data.raw + '</div>' : ''}
        </div>`;
      return;
    }

    const v   = data.verdict || 'unverified';
    const col = v==='fake' ? 'var(--accent)' : (v==='real' ? 'var(--green)' : 'var(--yellow)');
    const emoji = v==='fake' ? '🔴' : (v==='real' ? '🟢' : '🟡');

    aiResultBox.innerHTML = `
      <div style="background:rgba(0,0,0,0.3);border:1px solid ${col}33;border-left:4px solid ${col};border-radius:8px;padding:20px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
          <span style="font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800;color:${col}">${emoji} ${v.toUpperCase()}</span>
          <span style="background:${col}20;color:${col};border:1px solid ${col}40;font-family:'Oswald',sans-serif;font-size:0.6rem;letter-spacing:2px;padding:3px 10px;border-radius:20px;">
            ${data.confidence}% CONFIDENCE
          </span>
        </div>

        <div style="font-size:0.78rem;color:#ccc;line-height:1.65;margin-bottom:16px;padding:12px;background:rgba(255,255,255,0.03);border-radius:6px;">
          ${data.reason}
        </div>

        ${data.title ? `
        <div style="margin-bottom:8px;">
          <div style="font-family:'Oswald',sans-serif;font-size:0.58rem;letter-spacing:2px;color:var(--muted);margin-bottom:4px;">EXTRACTED TITLE</div>
          <div style="font-size:0.82rem;color:var(--text);">${data.title}</div>
        </div>` : ''}

        <button type="button" onclick="applyToForm()"
          style="
            margin-top:14px;width:100%;padding:10px;
            background:${col};color:${v==='real'?'#000':'#fff'};
            border:none;border-radius:6px;
            font-family:'Oswald',sans-serif;font-size:0.78rem;
            letter-spacing:2px;cursor:pointer;font-weight:700;
            transition:opacity 0.2s;
          "
          onmouseover="this.style.opacity='0.85'"
          onmouseout="this.style.opacity='1'">
          ✓ FORM MEIN AUTO-FILL KARO
        </button>
      </div>`;

    // Store for auto-fill
    window._aiData = data;
  })
  .catch(err => {
    btn.textContent = '🤖 ANALYSE WITH GROQ AI';
    btn.disabled    = false;
    aiResultBox.innerHTML = `
      <div style="background:rgba(230,50,50,0.08);border:1px solid rgba(230,50,50,0.3);border-radius:8px;padding:16px;">
        <div style="color:var(--accent);font-size:0.82rem;">⚠ Connection error. ai_image_check.php check karo.</div>
      </div>`;
  });
}

// ── Auto fill form from AI result ────────────
function applyToForm() {
  const d = window._aiData;
  if (!d) return;
  if (d.title)   document.getElementById('fTitle').value   = d.title;
  if (d.content) document.getElementById('fContent').value = d.content;
  if (d.source && d.source !== 'Unknown')
                 document.getElementById('fAuthor').value  = d.source;

  // Scroll to form
  document.getElementById('articleForm').scrollIntoView({ behavior: 'smooth' });

  // Flash effect on filled fields
  ['fTitle','fContent','fAuthor'].forEach(id => {
    const el = document.getElementById(id);
    if (el && el.value) {
      el.style.transition     = 'border-color 0.3s';
      el.style.borderBottom   = '2px solid var(--green)';
      setTimeout(() => { el.style.borderBottom = ''; }, 2000);
    }
  });
}
</script>

<?php require_once 'includes/footer.php'; ?>
