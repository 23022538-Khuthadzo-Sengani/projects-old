<?php include 'db.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Home</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="navbar">
    <div>Home</div>
    <div><a href="staff.php">Staff</a></div>
</div>

<div class="container">
    <?php
    // 1. Number of people in each department
    $sql1 = "SELECT department_Name, COUNT(*) as count 
             FROM staff 
             JOIN department ON staff.department_no = department.department_no 
             GROUP BY department_Name";
    $result1 = $conn->query($sql1);

    // 2. Department with most staff
    $sql2 = "SELECT department_Name, COUNT(*) as count 
             FROM staff 
             JOIN department ON staff.department_no = department.department_no 
             GROUP BY department_Name 
             ORDER BY count DESC LIMIT 1";
    $result2 = $conn->query($sql2);
    $topDepartment = $result2->fetch_assoc();

    // 3. Number of doctors in each department
    $sql3 = "SELECT department_Name, COUNT(*) as count 
             FROM staff 
             JOIN department ON staff.department_no = department.department_no 
             WHERE title LIKE '%Doctor%' 
             GROUP BY department_Name";
    $result3 = $conn->query($sql3);
    ?>

    <!-- Card 1: People in each department -->
    <div class="card">
        <h3>People in each Department:</h3>
        <?php while($row = $result1->fetch_assoc()): ?>
            <p><strong><?= $row['department_Name'] ?>:</strong> <?= $row['count'] ?> staff</p>
        <?php endwhile; ?>
    </div>

    <!-- Card 2: Department with most staff -->
    <div class="card">
        <h3>Department with Most Staff:</h3>
        <p><strong><?= $topDepartment['department_Name'] ?>:</strong> <?= $topDepartment['count'] ?> staff</p>
    </div>

    <!-- Card 3: Number of Doctors in each department -->
    <div class="card">
        <h3>Doctors in Each Department:</h3>
        <?php while($row = $result3->fetch_assoc()): ?>
            <p><strong><?= $row['department_Name'] ?>:</strong> <?= $row['count'] ?> doctor(s)</p>
        <?php endwhile; ?>
    </div>
</div>

</body>
</html>
