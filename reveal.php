<?php
// Returns the picked name for a user, but only for the admin account.
require 'config.php';
header('Content-Type: application/json');
require_login();

if (empty($_SESSION['is_admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Not allowed.']);
    exit;
}

$target = (int)($_GET['user_id'] ?? 0);
$stmt = $con->prepare('SELECT u.username, p.picked_name
                       FROM users u
                       JOIN picks p ON p.user_id = u.id
                       WHERE u.id = ?');
$stmt->bind_param('i', $target);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();

if (!$r) {
    echo json_encode(['error' => 'No pick for that user.']);
    exit;
}

echo json_encode(['username' => $r['username'], 'picked' => $r['picked_name']]);