<?php
require 'config.php';
require_role('lecturer');

// Fetch modules for this lecturer
$stmt = $pdo->prepare('SELECT * FROM modules WHERE lecturer_id = ? ORDER BY code');
$stmt->execute([$_SESSION['user']['id']]);
$modules = $stmt->fetchAll();

include 'header.php';
?>
<div class="card">
  <h2>Your Modules</h2>
  <?php if (!$modules): ?>
    <p>You have no modules assigned.</p>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>Code</th><th>Name</th><th>Enrollments</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($modules as $m): 
          $c = $pdo->prepare('SELECT COUNT(*) AS c FROM enrollments WHERE module_id = ?');
          $c->execute([$m['id']]); $cnt = $c->fetch()['c'];
        ?>
          <tr>
            <td><?= h($m['code']) ?></td>
            <td><?= h($m['name']) ?></td>
            <td><span class="badge"><?= (int)$cnt ?> students</span></td>
            <td>
              <a href="manage_assessments.php?module_id=<?= (int)$m['id'] ?>">Assessments</a> |
              <a href="upload_marks.php?module_id=<?= (int)$m['id'] ?>">Upload Marks</a> |
              <a href="view_module_matrix.php?module_id=<?= (int)$m['id'] ?>">Marks Matrix</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<?php include 'footer.php'; ?>