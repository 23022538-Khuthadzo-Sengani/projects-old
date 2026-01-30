<?php
// config.php
session_start();

$dsn = 'mysql:host=localhost;dbname=univen_marks;charset=utf8mb4';
$dbUser = 'root';           // change for production
$dbPass = '';               // change for production

$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
  $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
  http_response_code(500);
  exit('Database connection error.');
}

function require_login() {
  if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
  }
}

function require_role($role) {
  require_login();
  if (empty($_SESSION['user']['role']) || $_SESSION['user']['role'] !== $role) {
    http_response_code(403);
    exit('Forbidden');
  }
}

function h($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }