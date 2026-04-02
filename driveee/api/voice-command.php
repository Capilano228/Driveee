<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$command = strtolower($data['command'] ?? '');
$db = new Database();

$response = ['success' => true, 'response' => ''];

if (strpos($command, 'драйвик') !== false) {
    $response['response'] = 'Драйвик слушает! Чем могу помочь?';
    $response['wake'] = true;
} 
elseif (strpos($command, 'закажи такси') !== false || strpos($command, 'закажи на') !== false) {
    preg_match('/(?:закажи такси|закажи на)\s+(.+?)(?:\s+сколько будет стоить|\s*$)/', $command, $matches);
    
    if (isset($matches[1])) {
        $address = trim($matches[1]);
        $response['response'] = "Ищу такси до адреса: $address. Подтвердите заказ.";
        $response['address'] = $address;
        $response['action'] = 'order';
    } else {
        $response['response'] = 'Скажите адрес назначения. Например: "Закажи такси на Тверскую 10"';
    }
}
elseif (strpos($command, 'квесты') !== false) {
    $response['response'] = 'У вас есть квесты: Первая поездка, Ранняя пташка, Золотой час. Выполняйте их и получайте приоритетные поездки!';
}
elseif (strpos($command, 'приоритет') !== false) {
    if (isset($_SESSION['user_id'])) {
        $passengerData = $db->getPassengerData($_SESSION['user_id']);
        $response['response'] = "У вас {$passengerData['priority_rides']} приоритетных поездок.";
    } else {
        $response['response'] = 'Войдите в аккаунт, чтобы использовать приоритетные поездки.';
    }
}
else {
    $response['response'] = 'Извините, я не понял команду. Скажите "Драйвик" чтобы активировать меня.';
}

echo json_encode($response);
?>