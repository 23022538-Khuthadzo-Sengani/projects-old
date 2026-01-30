<?php
require 'config.php';
require_role('student');

$module_id = (int)($_GET['module_id'] ?? 0);
// Confirm the student is enrolled
$chk = $pdo->prepare('SELECT m.* FROM enrollments e JOIN modules m ON m.id=e.module_id WHERE e.student_id=? AND e.module_id=?');
$chk->execute([$_SESSION['user']['id'], $module_id]);
$module = $chk->fetch();
if (!$module) { http_response_code(404); exit('Not enrolled in this module'); }

$ass = $pdo->prepare('SELECT * FROM assessments WHERE module_id=? ORDER BY position, id');
$ass->execute([$module_id]);
$assessments = $ass->fetchAll();

$mk = $pdo->prepare('SELECT a.id AS assessment_id, a.name, a.weight, a.max_score, m.score
                     FROM assessments a
                     LEFT JOIN marks m ON m.assessment_id = a.id AND m.student_id = ?
                     WHERE a.module_id = ?
                     ORDER BY a.position, a.id');
$mk->execute([$_SESSION['user']['id'], $module_id]);
$rows = $mk->fetchAll();

$total = 0.0;

include 'header.php';
?>
<div class="card">
  <h2>Your Marks — <?= h($module['code']) ?>: <?= h($module['name']) ?></h2>
  <?php if (!$assessments): ?>
    <div class="notice">Marks will appear once your lecturer adds assessments.</div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr><th>Assessment</th><th>Weight %</th><th>Score</th><th>Max</th><th>Weighted %</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r):
          $weighted = ($r['score'] !== null && $r['max_score'] > 0) ? ($r['score'] / $r['max_score']) * (float)$r['weight'] : 0;
          $total += $weighted;
        ?>
          <tr>
            <td><?= h($r['name']) ?></td>
            <td><?= number_format($r['weight'], 2) ?></td>
            <td><?= $r['score'] === null ? '-' : h($r['score']) ?></td>
            <td><?= h($r['max_score']) ?></td>
            <td><?= number_format($weighted, 2) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr><th colspan="4" style="text-align:right">Total</th><th><?= number_format($total, 2) ?>%</th></tr>
      </tfoot>
    </table>
  <?php endif; ?>
</div>
<div class="card">
  <a href="student_dashboard.php" class="btn">Back</a>
</div>
<?php include 'footer.php'; ?>