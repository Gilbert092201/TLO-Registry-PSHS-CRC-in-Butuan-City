<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $pdo = get_db();
            $stmt = $pdo->prepare('SELECT id, username, password_hash, full_name FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            $error = 'Unable to reach the database. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In · TLO Registry · PSHS Caraga</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">

  <div class="auth-wrap">
    <div class="auth-panel auth-brand">
      <div class="brand-mark" aria-hidden="true">
        <div class="brand-mark" aria-hidden="true">
          <img src="assets/img/logo.png" alt="PSHS Caraga Region Campus Logo" class="seal-svg">
        </div>
      </div>
      <p class="eyebrow">Republic of the Philippines</p>
      <h1>Philippine Science High School<br><span>Caraga Region Campus</span></h1>
      <p class="brand-sub">Butuan City &middot; Technology Licensing Office</p>
      <div class="brand-divider"></div>
      <p class="brand-desc">The TLO Registry tracks every intellectual property filing &mdash; patents, trademarks, copyrights, industrial designs and utility models &mdash; from application to technology transfer.</p>
      <ul class="brand-stats">
        <li><span>5</span>IP types tracked</li>
        <li><span>4</span>transfer modes</li>
      </ul>
    </div>

    <div class="auth-panel auth-form-panel">
      <div class="auth-form-inner">
        <p class="eyebrow eyebrow-dark">Registry Access</p>
        <h2>Sign in</h2>
        <p class="auth-lead">Enter your TLO Registry credentials to continue.</p>

        <?php if ($error): ?>
          <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['registered'])): ?>
          <div class="alert alert-success">Account created. You may sign in now.</div>
        <?php endif; ?>

        <form method="POST" class="auth-form" autocomplete="off">
          <label class="field">
            <span>Username</span>
            <input type="text" name="username" required autofocus placeholder="e.g. jdelacruz">
          </label>
          <label class="field">
            <span>Password</span>
            <input type="password" name="password" required placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
          </label>
          <button type="submit" class="btn btn-primary btn-block">Sign in</button>
        </form>

        <p class="auth-switch">No account yet? <a href="signup.php">Create one</a></p>
      </div>
    </div>
  </div>

</body>
</html>
