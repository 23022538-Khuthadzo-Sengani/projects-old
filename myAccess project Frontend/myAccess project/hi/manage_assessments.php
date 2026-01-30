<?php
require 'config.php';
require_role('lecturer');

$module_id = (int)($_GET['module_id'] ?? 0);
$mod = $pdo->prepare('SELECT * FROM modules WHERE id = ? AND lecturer_id = ?');
$mod->execute([$module_id, $_SESSION['user']['id']]);
$module = $mod->fetch();
if (!$module) { http_response_code(404); exit('Module not found'); }

// Handle add/update/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['add'])) {
    $name = trim($_POST['name'] ?? '');
    $weight = (float)($_POST['weight'] ?? 0);
    $max = (float)($_POST['max_score'] ?? 100);
    if ($name !== '' && $weight > 0 && $max > 0) {
      $pos = (int)($pdo->query('SELECT COALESCE(MAX(position),0)+1 AS p FROM assessments WHERE module_id='.(int)$module_id)->fetch()['p']);
      $stmt = $pdo->prepare('INSERT INTO assessments (module_id, name, weight, max_score, position) VALUES (?,?,?,?,?)');
      $stmt->execute([$module_id, $name, $weight, $max, $pos]);
    }
  } elseif (isset($_POST['update'])) {
    foreach (($_POST['assess'] ?? []) as $id => $row) {
      $id = (int)$id;
      $name = trim($row['name'] ?? '');
      $weight = (float)($row['weight'] ?? 0);
      $max = (float)($row['max_score'] ?? 100);
      $pos = (int)($row['position'] ?? 1);
      $stmt = $pdo->prepare('UPDATE assessments SET name=?, weight=?, max_score=?, position=? WHERE id=? AND module_id=?');
      $stmt->execute([$name, $weight, $max, $pos, $id, $module_id]);
    }
  } elseif (isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    $stmt = $pdo->prepare('DELETE FROM assessments WHERE id=? AND module_id=?');
    $stmt->execute([$id, $module_id]);
  }
  header('Location: manage_assessments.php?module_id=' . $module_id);
  exit;
}

$rows = $pdo->prepare('SELECT * FROM assessments WHERE module_id = ? ORDER BY position, id');
$rows->execute([$module_id]);
$assessments = $rows->fetchAll();

$totalWeight = array_sum(array_map(fn($a) => (float)$a['weight'], $assessments));

include 'header.php';
?>
<div class="card">
  <h2>Assessments — <?= h($module['code']) ?>: <?= h($module['name']) ?></h2>
  <p>Total weight: <strong><?= number_format($totalWeight, 2) ?>%</strong> (aim for 100%)</p>

  <form method="post">
    <table class="table">
      <thead><tr>
        <th>Order</th><th>Name</th><th>Weight %</th><th>Max Score</th><th>Delete</th>
      </tr></thead>
      <tbody>
      <?php foreach ($assessments as $a): ?>
        <tr>
          <td><input class="input" name="assess[<?= (int)$a['id'] ?>][position]" type="number" value="<?= (int)$a['position'] ?>" style="width:80px"></td>
          <td><input class="input" name="assess[<?= (int)$a['id'] ?>][name]" value="<?= h($a['name']) ?>"></td>
          <td><input class="input" name="assess[<?= (int)$a['id'] ?>][weight]" type="number" step="0.01" value="<?= h($a['weight']) ?>" style="width:120px"></td>
          <td><input class="input" name="assess[<?= (int)$a['id'] ?>][max_score]" type="number" step="0.01" value="<?= h($a['max_score']) ?>" style="width:120px"></td>
          <td>
            <button class="secondary" name="delete_id" value="<?= (int)$a['id'] ?>" onclick="return confirm('Delete this assessment? Marks will also be removed.');">Delete</button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <button type="submit" name="update" value="1">Save Changes</button>
  </form>
</div>

<div class="card">
  <h2>Add Assessment</h2>
  <form method="post" class="form-row">
    <input class="input" name="name" placeholder="e.g., Test 1" required />
    <input class="input" type="number" step="0.01" name="weight" placeholder="Weight %" required />
    <input class="input" type="number" step="0.01" name="max_score" placeholder="Max Score (e.g., 100)" value="100" required />
    <button type="submit" name="add" value="1">Add</button>
  </form>
</div>

<div class="card">
  <a href="lecturer_dashboard.php" class="btn">Back</a>
</div>

<?php include 'footer.php'; ?>