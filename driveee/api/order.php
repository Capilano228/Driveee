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
if ($user['user_type'] != 'passenger') {
    echo json_encode(['success' => false, 'message' => 'Только пассажиры']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$orderId = $db->createOrder(
    $_SESSION['user_id'],
    $data['pickup_address'] ?? '',
    $data['pickup_lat'] ?? 62.027,
    $data['pickup_lng'] ?? 129.732,
    $data['dropoff_address'] ?? '',
    $data['dropoff_lat'] ?? 62.027,
    $data['dropoff_lng'] ?? 129.732,
    $data['ride_type'] ?? 'taxi',
    $data['offered_price'] ?? 300,
    $data['is_priority'] ?? false
);

if ($orderId) {
    if ($data['is_priority'] ?? false) {
        $db->usePriorityRide($_SESSION['user_id']);
    }
    echo json_encode(['success' => true, 'order_id' => $orderId]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка']);
}
?>