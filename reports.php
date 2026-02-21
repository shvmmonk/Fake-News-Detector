<?php
require_once 'includes/db.php';
$pageTitle = 'Reports';

if (isset($_GET['dismiss'])) { $pdo->prepare("UPDATE reports SET status='dismissed' WHERE report_id=?")->execute([$_GET['dismiss']]); header('Location: reports.php'); exit; }
if (isset($_GET['review']))  { $pdo->prepare("UPDATE reports SET status='reviewed'  WHERE report_id=?")->execute([$_GET['review']]);  header('Location: reports.php'); exit; }

$filter = $_GET['filter'] ?? 'all';
$where  = $filter !== 'all' ? "WHERE r.status = " . $pdo->quote($filter) : '';
$reports = $pdo->query("SELECT r.*, a.title AS article_title, u.username AS reporter FROM reports r JOIN articles a ON r.article_id=a.article_id JOIN users u ON r.reported_by=u.user_id $where ORDER BY r.reported_at DESC")->fetchAll();
$counts  = $pdo->query("SELECT status, COUNT(*) AS c FROM reports GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

require_once 'includes/header.php';
?>
<div class="wrapper">
  <div class="page-header"><h1>Reports</h1><p>USER SUBMITTED SUSPICIOUS ARTICLE REPORTS</p></div>

  <div style="display:flex;gap:8px;margin-bottom:20px;">
    <a href="?filter=all"       class="btn <?= $filter=='all'?'btn-primary':'btn-ghost' ?>" style="font-size:0.72rem;">All (<?= array_sum($counts) ?>)</a>
    <a href="?filter=pending"   class="btn <?= $filter=='pending'?'btn-primary':'btn-ghost' ?>" style="font-size:0.72rem;">Pending (<?= $counts['pending']??0 ?>)</a>
    <a href="?filter=reviewed"  class="btn <?= $filter=='reviewed'?'btn-primary':'btn-ghost' ?>" style="font-size:0.72rem;">Reviewed (<?= $counts['reviewed']??0 ?>)</a>
    <a href="?filter=dismissed" class="btn <?= $filter=='dismissed'?'btn-primary':'btn-ghost' ?>" style="font-size:0.72rem;">Dismissed (<?= $counts['dismissed']??0 ?>)</a>
  </div>

  <div class="card">
    <div class="tbl-wrap">
      <table>
        <thead><tr><th>#</th><th>Article</th><th>Reporter</th><th>Reason</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($reports)): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:40px;">No reports found.</td></tr>
        <?php else: foreach($reports as $r): ?>
        <tr>
          <td class="text-muted text-sm"><?= $r['report_id'] ?></td>
          <td><a href="article_detail.php?id=<?= $r['article_id'] ?>" style="color:var(--blue);font-size:0.78rem;"><?= htmlspecialchars(substr($r['article_title'],0,40)) ?>...</a></td>
          <td class="text-muted text-sm"><?= htmlspecialchars($r['reporter']) ?></td>
          <td style="font-size:0.78rem;"><?= htmlspecialchars(substr($r['reason'],0,50)) ?>...</td>
          <td><span class="badge b-<?= $r['status'] ?>"><?= strtoupper($r['status']) ?></span></td>
          <td class="text-muted text-sm"><?= date('d M Y', strtotime($r['reported_at'])) ?></td>
          <td>
            <?php if($r['status']=='pending'): ?>
            <a href="?review=<?= $r['report_id'] ?>&filter=<?= $filter ?>"  class="btn btn-green"  style="font-size:0.62rem;padding:3px 8px;">✓</a>
            <a href="?dismiss=<?= $r['report_id'] ?>&filter=<?= $filter ?>" class="btn btn-ghost"  style="font-size:0.62rem;padding:3px 8px;">✗</a>
            <?php else: echo '<span class="text-muted">—</span>'; endif; ?>
          </td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>