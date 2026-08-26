<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$full_name = $username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if ($full_name === '' || $username === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } elseif (strlen($username) < 4) {
        $error = 'Username must be at least 4 characters.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $pdo = get_db();
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'That username is already taken.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, full_name) VALUES (?, ?, ?)');
                $stmt->execute([$username, $hash, $full_name]);
                header('Location: login.php?registered=1');
                exit;
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
<title>Create Account · TLO Registry · PSHS Caraga</title>
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
      <p class="brand-desc">Register for an account to log new IP filings, monitor application status, and generate transfer reports.</p>
    </div>

    <div class="auth-panel auth-form-panel">
      <div class="auth-form-inner">
        <p class="eyebrow eyebrow-dark">Registry Access</p>
        <h2>Create account</h2>
        <p class="auth-lead">Set up credentials to start logging IP entries.</p>

        <?php if ($error): ?>
          <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="auth-form" autocomplete="off">
          <label class="field">
            <span>Full name</span>
            <input type="text" name="full_name" required value="<?= htmlspecialchars($full_name) ?>" placeholder="Juan Dela Cruz">
          </label>
          <label class="field">
            <span>Username</span>
            <input type="text" name="username" required value="<?= htmlspecialchars($username) ?>" placeholder="e.g. jdelacruz">
          </label>
          <label class="field">
            <span>Password</span>
            <input type="password" name="password" required placeholder="At least 6 characters">
          </label>
          <label class="field">
            <span>Confirm password</span>
            <input type="password" name="confirm_password" required placeholder="Re-enter password">
          </label>
          <button type="submit" class="btn btn-primary btn-block">Create account</button>
        </form>

        <p class="auth-switch">Already registered? <a href="login.php">Sign in</a></p>
      </div>
    </div>
  </div>

</body>
</html>
