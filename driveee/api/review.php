<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$db = new Database();

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$db->addDriverReview($data['driver_id'] ?? 0, $_SESSION['user_id'], $data['rating'] ?? 5, $data['review'] ?? '');
echo json_encode(['success' => true]);
?>