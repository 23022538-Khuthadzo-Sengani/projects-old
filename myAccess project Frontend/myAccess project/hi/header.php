<?php // header.php ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>University of Venda — Creating Future Leaders</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
<header class="site-header">
  <div class="brand">
    <img src="assets/univen-logo.png" alt="University of Venda Logo" class="logo" />
    <div>
      <div class="title">University of Venda</div>
      <div class="subtitle">Creating Future Leaders</div>
    </div>
  </div>
  <nav class="nav">
    <?php if (!empty($_SESSION['user'])): ?>
      <span>Signed in as <?= h($_SESSION['user']['full_name']) ?> (<?= h($_SESSION['user']['role']) ?>)</span>
      <?php if ($_SESSION['user']['role'] === 'lecturer'): ?>
        <a href="lecturer_dashboard.php">Lecturer</a>
      <?php else: ?>
        <a href="student_dashboard.php">Student</a>
      <?php endif; ?>
      <a href="logout.php" class="btn">Logout</a>
    <?php else: ?>
      <a href="login.php" class="btn">Login</a>
    <?php endif; ?>
  </nav>
</header>
<main class="container"></main>