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

$data = json_decode(file_get_contents('php://input'), true);
$questId = $data['quest_id'] ?? null;
$claim = $data['claim'] ?? false;

if ($claim && $questId) {
    $result = $db->checkAndCompleteQuest($_SESSION['user_id'], $questId);
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Награда получена!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Квест не выполнен']);
    }
    exit;
}

$quests = $db->getActiveQuests($_SESSION['user_id']);
echo json_encode(['success' => true, 'quests' => $quests]);
?>