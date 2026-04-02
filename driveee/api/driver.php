<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$db = new Database();

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Авторизуйтесь']);
    exit;
}

$user = $auth->getCurrentUser();
if ($user['user_type'] != 'driver') {
    echo json_encode(['success' => false, 'message' => 'Только для водителей']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

if ($action === 'toggle_online') {
    $driverData = $db->getDriverData($_SESSION['user_id']);
    $newStatus = $driverData['is_online'] ? 0 : 1;
    $db->setDriverOnline($_SESSION['user_id'], $newStatus);
    echo json_encode(['success' => true, 'is_online' => $newStatus]);
} elseif ($action === 'update_location') {
    $lat = $data['lat'] ?? null;
    $lng = $data['lng'] ?? null;
    if ($lat && $lng) {
        $db->updateDriverLocation($_SESSION['user_id'], $lat, $lng);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>