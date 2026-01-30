<?php include 'db.php';?>
<!DOCTYPE php>
<php lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet"  href="main.css">
</head>
<body>
    <h1>Student Dashboard</h1>

  <div class="cards">
    <?php
    // Total
    $total = $conn->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()['total'];

    // Gender Count
    $male = $conn->query("SELECT COUNT(*) AS male FROM students WHERE Gender = 'Male'")->fetch_assoc()['male'];
    $female = $conn->query("SELECT COUNT(*) AS female FROM students WHERE Gender = 'Female'")->fetch_assoc()['female'];

    // Average
    $avg = $conn->query("SELECT AVG(Average) AS avg_score FROM students")->fetch_assoc()['avg_score'];

    // Top Student
    $top = $conn->query("SELECT FullNames, Average FROM students ORDER BY Average DESC LIMIT 1")->fetch_assoc();

    // Lowest Student
    $low = $conn->query("SELECT FullNames, Average FROM students ORDER BY Average ASC LIMIT 1")->fetch_assoc();

    // Median Score
    $medianResult = $conn->query("SELECT Average FROM students ORDER BY Average");
    $scores = [];
    while($row = $medianResult->fetch_assoc()) {
      $scores[] = $row['Average'];
    }
    $middle = floor(count($scores) / 2);
    $median = count($scores) % 2 == 0 ? ($scores[$middle - 1] + $scores[$middle]) / 2 : $scores[$middle];

    // Distinctions
    $distinction = $conn->query("SELECT COUNT(*) AS distinctions FROM students WHERE Average >= 75")->fetch_assoc()['distinctions'];
    ?>

    <div class="card"><strong>Total Students:</strong><br><?= $total ?></div>
    <div class="card"><strong>Male Students:</strong><br><?= $male ?></div>
    <div class="card"><strong>Female Students:</strong><br><?= $female ?></div>
    <div class="card"><strong>Average Score:</strong><br><?= round($avg, 2) ?></div>
    <div class="card"><strong>Top Student:</strong><br><?= $top['FullNames'] . " (" . $top['Average'] . ")" ?></div>
    <div class="card"><strong>Lowest Student:</strong><br><?= $low['FullNames'] . " (" . $low['Average'] . ")" ?></div>
    <div class="card"><strong>Median Score:</strong><br><?= $median ?></div>
    <div class="card"><strong>Distinctions (≥75):</strong><br><?= $distinction ?></div>
  </div>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Student Number</th>
        <th>Full Names</th>
        <th>Gender</th>
        <th>Age</th>
        <th>Average</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $students = $conn->query("SELECT * FROM students ORDER BY ID");
      while ($row = $students->fetch_assoc()) {
        echo "<tr>
          <td>{$row['ID']}</td>
          <td>{$row['StudentNumber']}</td>
          <td>{$row['FullNames']}</td>
          <td>{$row['Gender']}</td>
          <td>{$row['Age']}</td>
          <td>{$row['Average']}</td>
        </tr>";
      }
      ?>
    </tbody>
  </table>
</body>
</php>