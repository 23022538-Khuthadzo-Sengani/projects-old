<?php
require 'config.php';
require_role('lecturer');

$module_id = (int)($_GET['module_id'] ?? 0);
$mod = $pdo->prepare('SELECT * FROM modules WHERE id = ? AND lecturer_id = ?');
$mod->execute([$module_id, $_SESSION['user']['id']]);
$module = $mod->fetch();
if (!$module) { http_response_code(404); exit('Module not found'); }

$ass = $pdo->prepare('SELECT * FROM assessments WHERE module_id = ? ORDER BY position, id');
$ass->execute([$module_id]);
$assessments = $ass->fetchAll();

$enr = $pdo->prepare('SELECT u.id, u.full_name FROM enrollments e JOIN users u ON u.id = e.student_id WHERE e.module_id = ? ORDER BY u.full_name');
$enr->execute([$module_id]);
$students = $enr->fetchAll();

$marks = $pdo->prepare('SELECT m.student_id, m.assessment_id, m.score FROM marks m JOIN assessments a ON a.id=m.assessment_id WHERE a.module_id=?');
$marks->execute([$module_id]);
$grid = [];
foreach ($marks as $r) { $grid[$r['student_id']][$r['assessment_id']] = (float)$r['score']; }

include 'header.php';
?>
<div class="card">
  <h2>Marks Matrix — <?= h($module['code']) ?>: <?= h($module['name']) ?></h2>
  <?php if (!$assessments): ?>
    <div class="notice">No assessments yet.</div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Student</th>
          <?php foreach ($assessments as $a): ?>
            <th><?= h($a['name']) ?> (<?= h($a['weight']) ?>%)</th>
          <?php endforeach; ?>
          <th>Total %</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($students as $s): 
          $total = 0.0;
        ?>
          <tr>
            <td><?= h($s['full_name']) ?></td>
            <?php foreach ($assessments as $a):
              $score = $grid[$s['id']][$a['id']] ?? null;
              $percent = ($score !== null && $a['max_score'] > 0) ? ($score / $a['max_score']) * (float)$a['weight'] : 0;
              $total += $percent;
            ?>
              <td><?= $score === null ? '-' : h($score) ?></td>
            <?php endforeach; ?>
            <td><strong><?= number_format($total, 2) ?>%</strong></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<div class="card">
  <a href="lecturer_dashboard.php" class="btn">Back</a>
</div>
<?php include 'footer.php'; ?>