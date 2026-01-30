<?php include 'db.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Staff List</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="navbar">
    <div><a href="index.php">Home</a></div>
    <div>Staff</div>
</div>

<div class="container">
    <h2>Staff List</h2>
    <table border="1" cellpadding="10" cellspacing="0">
        <tr>
            <th>Staff Number</th>
            <th>Name</th>
            <th>Surname</th>
            <th>Title</th>
            <th>Age</th>
            <th>Department</th>
            <th>Salary</th>
        </tr>
        <?php
        $sql = "SELECT staffNumber, name, surname, title, age, department_Name, department.salary 
                FROM staff 
                JOIN department ON staff.department_no = department.department_no";
        $result = $conn->query($sql);

        while ($row = $result->fetch_assoc()):
        ?>
            <tr>
                <td><?= $row['staffNumber'] ?></td>
                <td><?= $row['name'] ?></td>
                <td><?= $row['surname'] ?></td>
                <td><?= $row['title'] ?></td>
                <td><?= $row['age'] ?></td>
                <td><?= $row['department_Name'] ?></td>
                <td>R <?= number_format($row['salary'], 2) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
