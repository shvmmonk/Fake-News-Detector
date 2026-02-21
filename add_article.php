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

    <div class="card">
      <div class="card-header"><div class="card-title">Article Details</div></div>
      <div class="card-body">
        <form method="POST">
          <div class="form-group"><label>Title *</label><input type="text" name="title" value="<?= htmlspecialchars($_POST['title']??'') ?>" placeholder="Article headline..."></div>
          <div class="form-group"><label>Content *</label><textarea name="content" placeholder="Article content or summary..."><?= htmlspecialchars($_POST['content']??'') ?></textarea></div>
          <div class="grid-2">
            <div class="form-group"><label>Author</label><input type="text" name="author" value="<?= htmlspecialchars($_POST['author']??'') ?>" placeholder="Author name"></div>
            <div class="form-group"><label>Original URL</label><input type="url" name="url" value="<?= htmlspecialchars($_POST['url']??'') ?>" placeholder="https://..."></div>
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
<?php require_once 'includes/footer.php'; ?>