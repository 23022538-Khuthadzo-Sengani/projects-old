<?php
$host = "localhost";
$user = "root";
$password = ""; // use your MySQL root password if any
$dbname = "student_records";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>