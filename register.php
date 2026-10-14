<?php
require 'config.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $m = trim($_POST['email'] ?? '');
    $p = $_POST['password'] ?? '';
    if ($u === '' || !filter_var($m, FILTER_VALIDATE_EMAIL) || strlen($p) < 6) {
        $error = 'Enter a username, a valid email, and a password of at least 6 characters.';
    } else {
        $stmt = $con->prepare('SELECT 1 FROM users WHERE username=? OR email=?');
        $stmt->bind_param('ss', $u, $m);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $error = 'That username or email is already registered.';
        } else {
            $hash = password_hash($p, PASSWORD_DEFAULT);
            $ins = $con->prepare('INSERT INTO users (username, email, password_hash) VALUES (?,?,?)');
            $ins->bind_param('sss', $u, $m, $hash);
            $ins->execute();
            header('Location: login.php?registered=1'); exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Register · Christmas Wheel</title>
<link rel="stylesheet" href="style.css"></head>
<body class="auth"><div class="snow"></div>
<form class="card" method="post">
  <h1>Create your account</h1>
  <?php if ($error): ?><p class="msg err"><?= e($error) ?></p><?php endif; ?>
  <label>Username <input name="username" required value="<?= e($_POST['username'] ?? '') ?>"></label>
  <label>Email <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
  <label>Password <input type="password" name="password" required minlength="6"></label>
  <button>Register</button>
  <a href="login.php">Already have an account?</a>
</form></body></html>