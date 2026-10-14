<?php
// The winner is decided on the SERVER so it can't be rigged from the browser.
require 'config.php';
header('Content-Type: application/json');
if (empty($_SESSION['uid']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(401); echo json_encode(['error' => 'Please log in.']); exit;
}
$uid = (int)$_SESSION['uid'];
$username = $_SESSION['username'];

try {
    $con->begin_transaction();

    // One pick per user
    $stmt = $con->prepare('SELECT 1 FROM picks WHERE user_id=?');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        $con->rollback();
        echo json_encode(['error' => 'You already picked a name.']); exit;
    }

    // Wheel = every name still in the master list (minus the user's own name)
    $stmt = $con->prepare('SELECT id, name FROM names WHERE LOWER(name)<>LOWER(?) ORDER BY id FOR UPDATE');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $wheel = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if (!$wheel) {
        $con->rollback();
        echo json_encode(['error' => 'No names left on the wheel.']); exit;
    }

    $index = random_int(0, count($wheel) - 1);
    $picked = $wheel[$index];

    // Automatic removal: delete from the master list, save the result
    $del = $con->prepare('DELETE FROM names WHERE id=?');
    $del->bind_param('i', $picked['id']);
    $del->execute();
    $ins = $con->prepare('INSERT INTO picks (user_id, picked_name) VALUES (?, ?)');
    $ins->bind_param('is', $uid, $picked['name']);
    $ins->execute();

    $con->commit();
} catch (Throwable $ex) {
    $con->rollback();
    echo json_encode(['error' => 'Could not save your pick. Please try again.']); exit;
}

echo json_encode(['index' => $index, 'count' => count($wheel), 'name' => $picked['name']]);