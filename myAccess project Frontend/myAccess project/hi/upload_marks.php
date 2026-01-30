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

$enr = $pdo->prepare('SELECT u.id, u.full_name, u.username
                      FROM enrollments e JOIN users u ON u.id = e.student_id
                      WHERE e.module_id = ?
                      ORDER BY u.full_name');
$enr->execute([$module_id]);
$students = $enr->fetchAll();

$assessment_id = (int)($_GET['assessment_id'] ?? ($_POST['assessment_id'] ?? 0));
$activeAssessment = null;
foreach ($assessments as $a) if ((int)$a['id'] === $assessment_id) { $activeAssessment = $a; break; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $activeAssessment) {
  foreach (($_POST['score'] ?? []) as $student_id => $score) {
    $student_id = (int)$student_id;
    if ($score === '' || $score === null) continue;
    $score = (float)$score;

    // Upsert
    $ins = $pdo->prepare('INSERT INTO marks (assessment_id, student_id, score)
                          VALUES (?,?,?)
                          ON DUPLICATE KEY UPDATE score = VALUES(score)');
    $ins->execute([$assessment_id, $student_id, $score]);
  }
  header('Location: upload_marks.php?module_id=' . $module_id . '&assessment_id=' . $assessment_id);
  exit;
}

// Fetch existing marks for selected assessment
$marksByStudent = [];
if ($activeAssessment) {
  $mk = $pdo->prepare('SELECT student_id, score FROM marks WHERE assessment_id = ?');
  $mk->execute([$assessment_id]);
  foreach ($mk as $row) $marksByStudent[(int)$row['student_id']] = (float)$row['score'];
}

include 'header.php';
?>
<div class="card">
  <h2>Upload Marks — <?= h($module['code']) ?>: <?= h($module['name']) ?></h2>

  <?php if (!$assessments): ?>
    <div class="notice">No assessments yet. Please add assessments first.</div>
  <?php else: ?>
    <form method="get" class="form-row" action="upload_marks.php">
      <input type="hidden" name="module_id" value="<?= (int)$module_id ?>">
      <select name="assessment_id">
        <option value="">Select assessment…</option>
        <?php foreach ($assessments as $a): ?>
          <option value="<?= (int)$a['id'] ?>" <?= $assessment_id===(int)$a['id']?'selected':'' ?>>
            <?= h($a['name']) ?> (<?= h($a['weight']) ?>%)
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit">Load</button>
      <a class="btn" href="manage_assessments.php?module_id=<?= (int)$module_id ?>">Manage Assessments</a>
    </form>

    <?php if ($activeAssessment): ?>
      <p>Max score: <strong><?= h($activeAssessment['max_score']) ?></strong></p>
      <form method="post">
        <input type="hidden" name="assessment_id" value="<?= (int)$activeAssessment['id'] ?>">
        <table class="table">
          <thead><tr><th>Student</th><th>Username</th><th>Score</th></tr></thead>
          <tbody>
          <?php foreach ($students as $s): ?>
            <tr>
              <td><?= h($s['full_name']) ?></td>
              <td><?= h($s['username']) ?></td>
              <td>
                <input class="input" type="number" step="0.01" name="score[<?= (int)$s['id'] ?>]"
                       value="<?= isset($marksByStudent[$s['id']]) ? h($marksByStudent[$s['id']]) : '' ?>" style="width:140px">
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <button type="submit">Save Marks</button>
      </form>
    <?php endif; ?>
  <?php endif; ?>
</div>

<div class="card">
  <a href="lecturer_dashboard.php" class="btn">Back</a>
</div>
<?php include 'footer.php'; ?>