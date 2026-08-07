<?php
// login.php — halaman login sederhana
require __DIR__ . '/config.php';

session_name($AUTH_SESSION_NAME);
session_start();

$error = '';

// Logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// Sudah login -> langsung ke index
if (!empty($_SESSION['logged_in'])) {
    header('Location: index.php');
    exit;
}

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    if ($user === $AUTH_USER && $pass === $AUTH_PASS) {
        session_regenerate_id(true);
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $user;
        header('Location: index.php');
        exit;
    }
    $error = 'Username atau password salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — Voiceover Generator</title>
<style>
  :root { color-scheme: dark; }
  * { box-sizing: border-box; }
  body { font-family: system-ui, sans-serif; background: #0f1117; color: #e6e6e6; margin: 0;
         display: flex; align-items: center; justify-content: center; min-height: 100vh; }
  .box { width: 100%; max-width: 360px; background: #171a23; border: 1px solid #2a2f3d;
         border-radius: 12px; padding: 32px; }
  h1 { font-size: 20px; margin: 0 0 4px; text-align: center; }
  p.sub { color: #8b93a7; margin: 0 0 24px; text-align: center; font-size: 13px; }
  label { display: block; font-size: 13px; font-weight: 600; margin: 14px 0 6px; color: #b6bdcc; }
  input { width: 100%; background: #0f1117; color: #e6e6e6; border: 1px solid #2a2f3d;
          border-radius: 8px; padding: 10px 12px; font-size: 14px; }
  button { width: 100%; margin-top: 22px; padding: 12px; font-size: 15px; font-weight: 700;
           background: #4f8cff; color: #fff; border: 0; border-radius: 8px; cursor: pointer; }
  button:hover { background: #6ba0ff; }
  .err { margin-top: 14px; font-size: 13px; color: #ff6b6b; text-align: center; }
</style>
</head>
<body>
<div class="box">
  <h1>Voiceover Generator</h1>
  <p class="sub">Silakan login untuk melanjutkan</p>

  <?php if ($error): ?>
    <div class="err"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="post" action="login.php">
    <label for="username">Username</label>
    <input id="username" name="username" type="text" autocomplete="username" required autofocus>

    <label for="password">Password</label>
    <input id="password" name="password" type="password" autocomplete="current-password" required>

    <button type="submit">Login</button>
  </form>
</div>
</body>
</html>
