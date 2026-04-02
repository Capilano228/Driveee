<?php
require_once __DIR__ . '/config.php';

class Database {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDB();
    }
    
    public function getUser($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    public function getUserByPhone($phone) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        return $stmt->fetch();
    }
    
    public function createUser($phone, $password, $fullName, $userType) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("INSERT INTO users (phone, password_hash, full_name, user_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$phone, $passwordHash, $fullName, $userType]);
            $userId = $this->pdo->lastInsertId();
            
            if ($userType == 'passenger') {
                $stmt = $this->pdo->prepare("INSERT INTO passengers (user_id) VALUES (?)");
                $stmt->execute([$userId]);
            } else {
                $stmt = $this->pdo->prepare("INSERT INTO drivers (user_id, loyalty_points) VALUES (?, 0)");
                $stmt->execute([$userId]);
            }
            $this->pdo->commit();
            return $userId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
    
    public function getPassengerData($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM passengers WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    public function getDriverData($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM drivers WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    public function addPriorityRide($userId) {
        $stmt = $this->pdo->prepare("UPDATE passengers SET priority_rides = priority_rides + 1 WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
    
    public function setDriverOnline($driverId, $isOnline) {
        $stmt = $this->pdo->prepare("UPDATE drivers SET is_online = ? WHERE user_id = ?");
        return $stmt->execute([$isOnline, $driverId]);
    }
    
    public function addLoyaltyPoints($driverId, $points, $orderId, $type) {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("UPDATE drivers SET loyalty_points = loyalty_points + ? WHERE user_id = ?");
            $stmt->execute([$points, $driverId]);
            $stmt = $this->pdo->prepare("INSERT INTO loyalty_transactions (driver_id, order_id, points, transaction_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$driverId, $orderId, $points, $type]);
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
    
    public function createOrder($passengerId, $pickupAddress, $pickupLat, $pickupLng, $dropoffAddress, $dropoffLat, $dropoffLng, $rideType, $offeredPrice, $isPriority) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO orders (passenger_id, pickup_address, pickup_lat, pickup_lng, dropoff_address, dropoff_lat, dropoff_lng, ride_type, offered_price, is_priority, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([$passengerId, $pickupAddress, $pickupLat, $pickupLng, $dropoffAddress, $dropoffLat, $dropoffLng, $rideType, $offeredPrice, $isPriority ? 1 : 0]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("createOrder error: " . $e->getMessage());
            return false;
        }
    }
    
    public function assignDriverToOrder($orderId, $driverId, $finalPrice) {
        $stmt = $this->pdo->prepare("UPDATE orders SET driver_id = ?, status = 'accepted', final_price = ? WHERE id = ? AND status = 'pending'");
        return $stmt->execute([$driverId, $finalPrice, $orderId]);
    }
    
    public function updateOrderStatus($orderId, $status) {
        $stmt = $this->pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $orderId]);
    }
    
    public function getPendingOrders() {
        $stmt = $this->pdo->prepare("
            SELECT o.*, u.full_name as passenger_name, u.phone as passenger_phone 
            FROM orders o 
            JOIN users u ON o.passenger_id = u.id 
            WHERE o.status = 'pending' 
            ORDER BY o.is_priority DESC, o.created_at ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getDriverOrders($driverId) {
    $stmt = $this->pdo->prepare("
        SELECT o.*, u.full_name as passenger_name, u.phone as passenger_phone 
        FROM orders o 
        JOIN users u ON o.passenger_id = u.id 
        WHERE o.driver_id = ? AND o.status IN ('accepted', 'arrived', 'waiting_exit', 'passenger_onboard') 
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$driverId]);
    return $stmt->fetchAll();
}
    
    public function getOrder($orderId) {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        return $stmt->fetch();
    }
    
    public function getActiveQuests($userId) {
        $currentWeek = date('W');
        $stmt = $this->pdo->prepare("
            SELECT q.*, COALESCE(uq.progress, 0) as progress, 
            CASE WHEN uq.completed_at IS NOT NULL THEN 1 
                 WHEN COALESCE(uq.progress, 0) >= q.requirement_value THEN 2 
                 ELSE 0 END as status 
            FROM quests q 
            LEFT JOIN user_quests uq ON q.id = uq.quest_id AND uq.user_id = ? AND uq.week_number = ? 
            WHERE q.is_active = 1 
            ORDER BY q.id ASC
        ");
        $stmt->execute([$userId, $currentWeek]);
        return $stmt->fetchAll();
    }
    
    public function updateStreak($passengerId, $wasOnTime) {
        $stmt = $this->pdo->prepare("SELECT streak FROM passengers WHERE user_id = ?");
        $stmt->execute([$passengerId]);
        $currentStreak = $stmt->fetchColumn();
        
        if ($wasOnTime) {
            $newStreak = ($currentStreak ?: 0) + 1;
        } else {
            $newStreak = 0;
        }
        
        $stmt = $this->pdo->prepare("UPDATE passengers SET streak = ?, total_rides = total_rides + 1 WHERE user_id = ?");
        $stmt->execute([$newStreak, $passengerId]);
        
        return $newStreak;
    }
    public function updateDriverLocation($driverId, $lat, $lng) {
    $stmt = $this->pdo->prepare("UPDATE drivers SET last_lat = ?, last_lng = ?, last_location_update = NOW() WHERE user_id = ?");
    return $stmt->execute([$lat, $lng, $driverId]);
}

public function updateQuestProgress($passengerId, $orderId) {
    $currentWeek = date('W');
    // Обновляем прогресс квестов
    $stmt = $this->pdo->prepare("
        INSERT INTO user_quests (user_id, quest_id, progress, week_number) 
        SELECT ?, q.id, 1, ? FROM quests q WHERE q.requirement_type = 'rides_count' 
        ON DUPLICATE KEY UPDATE progress = progress + 1
    ");
    $stmt->execute([$passengerId, $currentWeek]);
    
    // Квест "Популярный пассажир"
    $stmt = $this->pdo->prepare("
        INSERT INTO user_quests (user_id, quest_id, progress, week_number) 
        SELECT ?, q.id, 1, ? FROM quests q WHERE q.requirement_type = 'driver_accept' 
        ON DUPLICATE KEY UPDATE progress = progress + 1
    ");
    $stmt->execute([$passengerId, $currentWeek]);
}
    
}

?>