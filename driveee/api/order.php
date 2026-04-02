<?php
// Включаем отображение ошибок для диагностики
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Очищаем буфер вывода
if (ob_get_level()) ob_clean();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$auth = new Auth();
$db = new Database();

// Проверка авторизации
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Необходимо авторизоваться'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = $auth->getCurrentUser();
if (!$user || $user['user_type'] != 'passenger') {
    echo json_encode(['success' => false, 'message' => 'Только пассажиры могут заказывать'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Получаем данные
$input = file_get_contents('php://input');
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Нет данных для заказа'], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode($input, true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Ошибка формата данных: ' . json_last_error_msg()], JSON_UNESCAPED_UNICODE);
    exit;
}

// Извлекаем данные
$pickupAddress = isset($data['pickup_address']) ? trim($data['pickup_address']) : '';
$dropoffAddress = isset($data['dropoff_address']) ? trim($data['dropoff_address']) : '';
$pickupLat = isset($data['pickup_lat']) ? floatval($data['pickup_lat']) : 62.027;
$pickupLng = isset($data['pickup_lng']) ? floatval($data['pickup_lng']) : 129.732;
$dropoffLat = isset($data['dropoff_lat']) ? floatval($data['dropoff_lat']) : 62.027;
$dropoffLng = isset($data['dropoff_lng']) ? floatval($data['dropoff_lng']) : 129.732;
$rideType = isset($data['ride_type']) ? $data['ride_type'] : 'taxi';
$offeredPrice = isset($data['offered_price']) ? intval($data['offered_price']) : 300;
$isPriority = isset($data['is_priority']) ? boolval($data['is_priority']) : false;

// Валидация
if (empty($pickupAddress) || empty($dropoffAddress)) {
    echo json_encode(['success' => false, 'message' => 'Укажите адрес посадки и назначения'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($offeredPrice <= 0) {
    echo json_encode(['success' => false, 'message' => 'Укажите корректную сумму'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Создаем заказ
$orderId = $db->createOrder(
    $_SESSION['user_id'],
    $pickupAddress,
    $pickupLat,
    $pickupLng,
    $dropoffAddress,
    $dropoffLat,
    $dropoffLng,
    $rideType,
    $offeredPrice,
    $isPriority
);

if ($orderId) {
    echo json_encode([
        'success' => true, 
        'message' => 'Заказ создан', 
        'order_id' => $orderId
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Ошибка при создании заказа в базе данных'
    ], JSON_UNESCAPED_UNICODE);
}
?>