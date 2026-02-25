<?php
require_once 'includes/db.php';
$pageTitle = 'Articles';

$search   = $_GET['search']   ?? '';
$category = $_GET['category'] ?? '';
$verdict  = $_GET['verdict']  ?? '';
$where = ["1=1"]; $params = [];
if ($search)   { $where[] = "(a.title LIKE ? OR a.author LIKE ?)"; $params[]="%$search%"; $params[]="%$search%"; }
if ($category) { $where[] = "a.category_id = ?"; $params[] = $category; }
if ($verdict)  { $where[] = "v.verdict = ?";     $params[] = $verdict; }

$stmt = $pdo->prepare("
    SELECT a.article_id, a.title, a.author, c.name AS category,
           s.name AS source, v.verdict, v.confidence_score, u.username AS checker
    FROM articles a
    LEFT JOIN categories    c ON a.category_id = c.category_id
    LEFT JOIN sources       s ON a.source_id   = s.source_id
    LEFT JOIN verifications v ON a.article_id  = v.article_id
    LEFT JOIN users         u ON v.checked_by  = u.user_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY a.created_at DESC
");
$stmt->execute($params);
$articles = $stmt->fetchAll();
$cats = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

require_once 'includes/header.php';
?>
<div class="wrapper">
  <div class="page-header">
    <h1>All Articles</h1>
    <p><?= count($articles) ?> ARTICLES FOUND</p>
  </div>

  <div class="card mb-20">
    <div class="card-body" style="padding:16px 24px">
      <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div class="form-group" style="margin:0;flex:2;min-width:180px;">
          <label>Search</label>
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Title or author...">
        </div>
        <div class="form-group" style="margin:0;flex:1;min-width:130px;">
          <label>Category</label>
          <select name="category">
            <option value="">All</option>
            <?php foreach($cats as $cat): ?>
            <option value="<?= $cat['category_id'] ?>" <?= $category==$cat['category_id']?'selected':'' ?>><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin:0;flex:1;min-width:120px;">
          <label>Verdict</label>
          <select name="verdict">
            <option value="">All</option>
            <option value="fake"       <?= $verdict=='fake'?'selected':'' ?>>Fake</option>
            <option value="real"       <?= $verdict=='real'?'selected':'' ?>>Real</option>
            <option value="misleading" <?= $verdict=='misleading'?'selected':'' ?>>Misleading</option>
          </select>
        </div>
        <div style="display:flex;gap:8px;">
          <button type="submit" class="btn btn-primary">Filter</button>
          <a href="articles.php" class="btn btn-ghost">Clear</a>
        </div>
      </form>
    </div>
  </div>

  <div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
    <a href="add_article.php" class="btn btn-green">+ Submit Article</a>
  </div>

  <div class="card">
    <div class="tbl-wrap">
      <table>
        <thead><tr><th>#</th><th>Title</th><th>Author</th><th>Category</th><th>Source</th><th>Verdict</th><th>Confidence</th><th>Action</th></tr></thead>
        <tbody>
        <?php if(empty($articles)): ?>
          <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:40px;">No articles found.</td></tr>
        <?php else: foreach($articles as $row): ?>
        <?php $v=$row['verdict']??'unverified'; ?>
        <tr>
          <td class="text-muted text-sm"><?= str_pad($row['article_id'],3,'0',STR_PAD_LEFT) ?></td>
          <td class="td-title" title="<?= htmlspecialchars($row['title']) ?>"><?= htmlspecialchars(substr($row['title'],0,45)) ?>...</td>
          <td class="text-muted text-sm"><?= htmlspecialchars($row['author'] ?? '—') ?></td>
          <td class="text-sm"><?= htmlspecialchars($row['category'] ?? '—') ?></td>
          <td class="text-muted text-sm"><?= htmlspecialchars($row['source'] ?? '—') ?></td>
          <td><span class="badge b-<?= $v ?>"><?= strtoupper($v) ?></span></td>
          <td>
            <?php if($row['confidence_score']): ?>
            <div class="cbar-wrap">
              <div class="cbar"><div class="cbar-fill" style="width:<?= $row['confidence_score'] ?>%;background:<?= $v=='fake'?'var(--accent)':($v=='real'?'var(--green)':'var(--yellow)') ?>"></div></div>
              <div class="cbar-num"><?= $row['confidence_score'] ?>%</div>
            </div>
            <?php else: echo '—'; endif; ?>
          </td>
          <td><a href="article_detail.php?id=<?= $row['article_id'] ?>" class="btn btn-ghost" style="font-size:0.65rem;padding:4px 10px;">View</a></td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
