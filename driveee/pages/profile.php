<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$auth = new Auth();
$db = new Database();

if (!$auth->isLoggedIn()) {
    header('Location: ' . SITE_URL . '/pages/login.php');
    exit;
}

$user = $auth->getCurrentUser();

if (isset($_GET['logout'])) {
    $auth->logout();
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

if ($user['user_type'] == 'passenger') {
    $extraData = $db->getPassengerData($user['id']);
    $streak = $extraData['streak'] ?? 0;
} else {
    $extraData = $db->getDriverData($user['id']);
    $streak = 0;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Профиль - DRIVEEE</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: #1a1a1a;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .phone {
            width: 380px;
            height: 700px;
            background: #fff;
            border-radius: 44px;
            overflow: hidden;
            box-shadow: 0 30px 50px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
        }
        .dynamic-island {
            background: #1a1a1a;
            height: 50px;
            border-radius: 0 0 30px 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            padding-top: 10px;
        }
        .time { color: white; font-size: 14px; }
        .profile-header {
            background: #1a1a1a;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .back-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: white;
        }
        .profile-header h1 { color: white; font-size: 20px; flex: 1; }
        .profile-content { flex: 1; overflow-y: auto; padding: 20px; }
        .avatar-section { text-align: center; margin-bottom: 30px; }
        .avatar {
            width: 100px;
            height: 100px;
            background: #00cc44;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
            color: #1a1a1a;
            margin: 0 auto 16px;
        }
        .streak-card {
            background: linear-gradient(135deg, #ff6600, #ff4400);
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            color: white;
        }
        .streak-value {
            font-size: 48px;
            font-weight: bold;
        }
        .info-card {
            background: #f5f5f5;
            border-radius: 20px;
            padding: 16px;
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #666; font-size: 14px; }
        .info-value { font-weight: 600; color: #1a1a1a; }
        .logout-btn {
            width: 100%;
            padding: 16px;
            background: #ff4444;
            color: white;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
    </style>
</head>
<body>
<div class="phone">
    <div class="dynamic-island">
        <span class="time" id="currentTime">9:41</span>
    </div>
    
    <div class="profile-header">
        <button class="back-btn" onclick="window.location.href='/index.php'">←</button>
        <h1>Профиль</h1>
        <div style="width: 30px;"></div>
    </div>
    
    <div class="profile-content">
        <div class="avatar-section">
            <div class="avatar">
                <?php echo mb_substr($user['full_name'], 0, 1, 'UTF-8'); ?>
            </div>
            <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
            <p style="color: #888; margin-top: 4px;">
                <?php echo $user['user_type'] == 'passenger' ? 'Пассажир' : 'Водитель'; ?>
            </p>
        </div>
        
        <?php if ($user['user_type'] == 'passenger'): ?>
        <div class="streak-card">
            <div>🔥 Огонек без опозданий</div>
            <div class="streak-value"><?php echo $streak; ?></div>
            <div style="font-size: 12px; margin-top: 8px;">Серия успешных посадок</div>
        </div>
        <?php endif; ?>
        
        <div class="info-card">
            <div class="info-row">
                <span class="info-label">📱 Телефон</span>
                <span class="info-value"><?php echo htmlspecialchars($user['phone']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">📅 Регистрация</span>
                <span class="info-value"><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></span>
            </div>
            <?php if ($user['user_type'] == 'passenger'): ?>
            <div class="info-row">
                <span class="info-label">⭐ Приоритетные поездки</span>
                <span class="info-value"><?php echo $extraData['priority_rides'] ?? 0; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">🚗 Всего поездок</span>
                <span class="info-value"><?php echo $extraData['total_rides'] ?? 0; ?></span>
            </div>
            <?php else: ?>
            <div class="info-row">
                <span class="info-label">💰 Драйвикоины</span>
                <span class="info-value"><?php echo $extraData['loyalty_points'] ?? 0; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">🚗 Всего поездок</span>
                <span class="info-value"><?php echo $extraData['total_rides'] ?? 0; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">⭐ Рейтинг</span>
                <span class="info-value"><?php echo $extraData['rating'] ?? 5.0; ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <button class="logout-btn" onclick="confirmLogout()">🚪 Выйти из аккаунта</button>
    </div>
</div>

<script>
function updateTime() {
    const now = new Date();
    document.getElementById('currentTime').textContent = now.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
}
setInterval(updateTime, 1000);
updateTime();

function confirmLogout() {
    if (confirm('Вы уверены?')) {
        window.location.href = '?logout=1';
    }
}
</script>
</body>
</html>