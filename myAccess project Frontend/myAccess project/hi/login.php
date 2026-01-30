<?php // login.php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
  $stmt->execute([$username]);
  $user = $stmt->fetch();

  if ($user && hash('sha256', $password) === $user['password_hash']) {
    $_SESSION['user'] = [
      'id' => (int)$user['id'],
      'username' => $user['username'],
      'full_name' => $user['full_name'],
      'role' => $user['role'],
    ];
    header('Location: ' . ($user['role'] === 'lecturer' ? 'lecturer_dashboard.php' : 'student_dashboard.php'));
    exit;
  }
  $error = 'Invalid credentials';
}
include 'header.php';
?>
<div class="card">
  <h2>Sign in</h2>
  <?php if (!empty($error)): ?><div class="notice"><?= h($error) ?></div><?php endif; ?>
  <form method="post">
    <div class="form-row">
      <input class="input" name="username" placeholder="Username" required />
      <input class="input" type="password" name="password" placeholder="Password" required />
    </div>
    <button type="submit">Login</button>
  </form>
</div>
<?php include 'footer.php'; ?>