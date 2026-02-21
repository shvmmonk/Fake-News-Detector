<?php
require_once 'includes/db.php';
$pageTitle = 'Report Article';
$id = intval($_GET['id'] ?? 0);
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $article_id  = intval($_POST['article_id']);
    $reported_by = intval($_POST['reported_by']);
    $reason      = trim($_POST['reason'] ?? '');
    if (!$reason) { $error = "Please provide a reason."; }
    else {
        $pdo->prepare("INSERT INTO reports (article_id, reported_by, reason) VALUES (?,?,?)")->execute([$article_id, $reported_by, $reason]);
        $success = "Report submitted! Our team will review it soon.";
    }
}

$article = null;
if ($id) { $stmt=$pdo->prepare("SELECT article_id,title FROM articles WHERE article_id=?"); $stmt->execute([$id]); $article=$stmt->fetch(); }
$articles = $pdo->query("SELECT article_id, title FROM articles ORDER BY title")->fetchAll();
$users    = $pdo->query("SELECT user_id, username FROM users WHERE role='user' ORDER BY username")->fetchAll();
require_once 'includes/header.php';
?>
<div class="wrapper">
  <div style="margin-bottom:20px;"><a href="<?= $id?'article_detail.php?id='.$id:'articles.php' ?>" class="btn btn-ghost" style="font-size:0.7rem;padding:6px 12px;">← Back</a></div>
  <div class="page-header"><h1>🚨 Report Article</h1><p>SUBMIT A REPORT FOR SUSPICIOUS CONTENT</p></div>

  <div style="max-width:580px">
    <?php if($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if($error):   ?><div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card">
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="article_id" value="<?= $article?$article['article_id']:'' ?>">
          <div class="form-group">
            <label>Article</label>
            <?php if($article): ?>
            <input type="text" value="<?= htmlspecialchars($article['title']) ?>" disabled style="opacity:0.6">
            <?php else: ?>
            <select name="article_id" required>
              <option value="">Select article...</option>
              <?php foreach($articles as $a): ?><option value="<?= $a['article_id'] ?>"><?= htmlspecialchars(substr($a['title'],0,60)) ?>...</option><?php endforeach; ?>
            </select>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label>Reporting As</label>
            <select name="reported_by" required>
              <option value="">Select user...</option>
              <?php foreach($users as $u): ?><option value="<?= $u['user_id'] ?>"><?= htmlspecialchars($u['username']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Reason *</label><textarea name="reason" placeholder="Why is this article suspicious?"><?= htmlspecialchars($_POST['reason']??'') ?></textarea></div>
          <button type="submit" class="btn btn-primary" style="background:var(--accent);">Submit Report</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
