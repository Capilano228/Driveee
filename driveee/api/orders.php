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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'pending' && $user['user_type'] === 'driver') {
        $orders = $db->getPendingOrders();
        echo json_encode(['success' => true, 'orders' => $orders]);
        exit;
    } 
    elseif ($action === 'my' && $user['user_type'] === 'driver') {
        $orders = $db->getDriverOrders($_SESSION['user_id']);
        echo json_encode(['success' => true, 'orders' => $orders]);
        exit;
    } 
    elseif ($action === 'check') {
        $orderId = $_GET['order_id'] ?? null;
        $order = $db->getOrder($orderId);
        if ($order) {
            echo json_encode(['success' => true, 'status' => $order['status'], 'driver_id' => $order['driver_id']]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    $orderId = $data['order_id'] ?? null;
    
    if ($action === 'accept' && $user['user_type'] === 'driver') {
        $order = $db->getOrder($orderId);
        if (!$order || $order['status'] !== 'pending') {
            echo json_encode(['success' => false, 'message' => 'Заказ уже принят']);
            exit;
        }
        $finalPrice = $data['final_price'] ?? $order['offered_price'];
        $db->assignDriverToOrder($orderId, $_SESSION['user_id'], $finalPrice);
        $hour = date('H');
        if ($hour >= 18 && $hour <= 20) {
            $db->addLoyaltyPoints($_SESSION['user_id'], 20, $orderId, 'bonus');
        }
        echo json_encode(['success' => true, 'message' => 'Заказ принят! Езжайте к пассажиру']);
        exit;
    }
    elseif ($action === 'arrived' && $user['user_type'] === 'driver') {
        $db->updateOrderStatus($orderId, 'arrived');
        echo json_encode(['success' => true, 'message' => 'Пассажир уведомлен, ждите']);
        exit;
    }
    elseif ($action === 'exit' && $user['user_type'] === 'passenger') {
        $db->updateOrderStatus($orderId, 'waiting_exit');
        echo json_encode(['success' => true, 'message' => 'Таймер запущен']);
        exit;
    }
    elseif ($action === 'passenger_onboard' && $user['user_type'] === 'passenger') {
        $db->updateOrderStatus($orderId, 'passenger_onboard');
        echo json_encode(['success' => true, 'message' => 'Пассажир сел, поехали!']);
        exit;
    }
    elseif ($action === 'complete') {
        $order = $db->getOrder($orderId);
        if ($order && $order['status'] == 'passenger_onboard') {
            // Обновляем статус заказа
            $db->updateOrderStatus($orderId, 'completed');
            
            // Начисляем бонусы водителю (всегда)
            $db->addLoyaltyPoints($order['driver_id'], 10, $orderId, 'ride_complete');
            
            // Начисляем бонусы пассажиру (огонек и квесты)
            $db->updateStreak($order['passenger_id'], true);
            $db->updateQuestProgress($order['passenger_id']);
            
            echo json_encode(['success' => true, 'message' => 'Поездка завершена!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Сначала отметьте что сели в такси']);
        }
        exit;
    }
    elseif ($action === 'cancel' && $user['user_type'] === 'driver') {
        $db->updateOrderStatus($orderId, 'cancelled');
        echo json_encode(['success' => true, 'message' => 'Заказ отменен']);
        exit;
    }
    elseif ($action === 'late') {
        $order = $db->getOrder($orderId);
        if ($order) {
            $db->updateStreak($order['passenger_id'], false);
            $db->updateOrderStatus($orderId, 'cancelled');
            echo json_encode(['success' => true]);
        }
        exit;
    }
    exit;
}
?>