<?php
require 'config.php';
require_role('student');

// Student modules
$mods = $pdo->prepare('SELECT m.* FROM enrollments e JOIN modules m ON m.id = e.module_id WHERE e.student_id = ? ORDER BY m.code');
$mods->execute([$_SESSION['user']['id']]);
$modules = $mods->fetchAll();

include 'header.php';
?>
<div class="card">
  <h2>Your Modules</h2>
  <?php if (!$modules): ?>
    <p>No module enrollments found.</p>
  <?php else: ?>
    <ul>
      <?php foreach ($modules as $m): ?>
        <li><a href="student_marks.php?module_id=<?= (int)$m['id'] ?>"><?= h($m['code']) ?> — <?= h($m['name']) ?></a></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
<?php include 'footer.php'; ?>