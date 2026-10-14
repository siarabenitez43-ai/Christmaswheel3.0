<?php
session_start();
// Make mysqli throw readable errors instead of returning false
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require __DIR__ . '/db.php';
$con->set_charset('utf8mb4');

function e($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function require_login() {
    if (empty($_SESSION['uid'])) { header('Location: login.php'); exit; }
}