<?php
require 'config.php';
require 'app_settings.php';
if (!empty($_SESSION['uid'])) { header('Location: dashboard.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $m = trim($_POST['email'] ?? '');
    $stmt = $con->prepare('SELECT id, username, email, password_hash, is_admin FROM users WHERE email=?');
    $stmt->bind_param('s', $m);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['is_admin'] = (int)(strtolower($user['email']) === strtolower(ADMIN_EMAIL));
        header('Location: dashboard.php'); exit;
    }
    $error = 'Wrong email or password.';
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Log in · Christmas Wheel</title>
<link rel="stylesheet" href="style.css"></head>
<body class="auth"><div class="snow"></div>
<form class="card" method="post">
  <h1>🎄 Log in</h1>
  <?php if (isset($_GET['registered'])): ?>
    <p class="msg ok">Account created. You can log in now.</p>
  <?php endif; ?>
  <?php if ($error): ?><p class="msg err"><?= e($error) ?></p><?php endif; ?>
  <label>Email <input type="email" name="email" placeholder="user@gmail.com" required value="<?= e($_POST['email'] ?? '') ?>"></label>
  <label>Password <input type="password" name="password" required></label>
  <button>Log in</button>
  <a href="register.php">Create an account</a>
</form></body></html>