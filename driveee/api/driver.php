<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'profile') {
    $driverId = $_GET['driver_id'] ?? 0;
    $driver = $db->getDriverData($driverId);
    $user = $db->getUser($driverId);
    if ($driver && $user) {
        echo json_encode(['success' => true, 'driver' => [
            'full_name' => $user['full_name'],
            'rating' => $driver['rating'],
            'total_rides' => $driver['total_rides'],
            'loyalty_points' => $driver['loyalty_points']
        ]]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

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

if (($data['action'] ?? '') === 'toggle_online') {
    $driver = $db->getDriverData($_SESSION['user_id']);
    $db->setDriverOnline($_SESSION['user_id'], $driver['is_online'] ? 0 : 1);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>