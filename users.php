<?php
require_once 'includes/db.php';
$pageTitle = 'Users';
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $role     = $_POST['role']          ?? 'user';
    $password = trim($_POST['password'] ?? '');
    if (!$username || !$email || !$password) { $error = "All fields required."; }
    else {
        try {
            $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?,?,?,?)")
                ->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
            $success = "User '$username' created!";
        } catch (PDOException $e) { $error = "Username or email already exists."; }
    }
}

$users = $pdo->query("
    SELECT u.*, COUNT(v.verification_id) AS verifications_done
    FROM users u LEFT JOIN verifications v ON u.user_id = v.checked_by
    GROUP BY u.user_id ORDER BY u.created_at ASC
")->fetchAll();

require_once 'includes/header.php';
?>
<div class="wrapper">
  <div class="page-header"><h1>Users</h1><p>MANAGE ADMINS, FACT CHECKERS AND USERS</p></div>

  <?php if($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if($error):   ?><div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card mb-20">
    <div class="card-header"><div class="card-title">All Users (<?= count($users) ?>)</div></div>
    <div class="tbl-wrap">
      <table>
        <thead><tr><th>#</th><th>Username</th><th>Email</th><th>Role</th><th>Verifications</th><th>Joined</th></tr></thead>
        <tbody>
        <?php foreach($users as $u): ?>
        <tr>
          <td class="text-muted text-sm"><?= $u['user_id'] ?></td>
          <td style="font-weight:500"><?= htmlspecialchars($u['username']) ?></td>
          <td class="text-muted text-sm"><?= htmlspecialchars($u['email']) ?></td>
          <td><span class="badge b-<?= $u['role'] ?>"><?= strtoupper(str_replace('_',' ',$u['role'])) ?></span></td>
          <td style="color:<?= $u['verifications_done']>0?'var(--blue)':'var(--muted)' ?>"><?= $u['verifications_done'] ?></td>
          <td class="text-muted text-sm"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div style="max-width:540px">
    <div class="card">
      <div class="card-header"><div class="card-title">+ Add New User</div></div>
      <div class="card-body">
        <form method="POST">
          <div class="grid-2">
            <div class="form-group"><label>Username *</label><input type="text" name="username" placeholder="e.g. checker_ravi"></div>
            <div class="form-group"><label>Email *</label><input type="email" name="email" placeholder="ravi@example.com"></div>
          </div>
          <div class="grid-2">
            <div class="form-group"><label>Password *</label><input type="password" name="password" placeholder="Password"></div>
            <div class="form-group"><label>Role</label><select name="role"><option value="user">User</option><option value="fact_checker">Fact Checker</option><option value="admin">Admin</option></select></div>
          </div>
          <button type="submit" class="btn btn-primary">Create User</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>