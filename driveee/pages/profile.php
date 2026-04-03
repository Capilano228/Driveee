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
    $extra = $db->getPassengerData($user['id']);
} else {
    $extra = $db->getDriverData($user['id']);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Профиль - DRIVEEE</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{background:#1a1a1a;font-family:Arial;display:flex;justify-content:center;align-items:center;min-height:100vh}
        .phone{width:380px;height:700px;background:#fff;border-radius:44px;overflow:auto}
        .header{background:#1a1a1a;padding:20px;display:flex;align-items:center;gap:16px}
        .back{background:none;border:none;font-size:24px;cursor:pointer;color:#fff}
        .header h1{color:#fff;font-size:20px;flex:1}
        .content{padding:20px}
        .avatar{width:100px;height:100px;background:#00cc44;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:48px;margin:0 auto 20px}
        .card{background:#f5f5f5;border-radius:20px;padding:16px;margin-bottom:20px}
        .row{display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid #ddd}
        .row:last-child{border-bottom:none}
        .logout{width:100%;padding:16px;background:#ff4444;color:#fff;border:none;border-radius:30px;font-weight:bold;cursor:pointer}
        .streak{background:linear-gradient(135deg,#ff6600,#ff4400);color:#fff;padding:20px;border-radius:20px;text-align:center;margin-bottom:20px}
        .streak-value{font-size:48px;font-weight:bold}
    </style>
</head>
<body>
<div class="phone">
    <div class="header">
        <button class="back" onclick="location.href='/index.php'">←</button>
        <h1>Профиль</h1>
        <div style="width:30px"></div>
    </div>
    <div class="content">
        <div class="avatar"><?=mb_substr($user['full_name'],0,1,'UTF-8')?></div>
        <h2 style="text-align:center"><?=htmlspecialchars($user['full_name'])?></h2>
        <p style="text-align:center;color:#888;margin-bottom:20px"><?=$user['user_type']=='passenger'?'Пассажир':'Водитель'?></p>
        
        <?php if($user['user_type']=='passenger'):?>
        <div class="streak">
            <div>🔥 Огонек без опозданий</div>
            <div class="streak-value"><?=$extra['streak']??0?></div>
        </div>
        <?php endif;?>
        
        <div class="card">
            <div class="row"><span>📱 Телефон</span><span><?=htmlspecialchars($user['phone'])?></span></div>
            <div class="row"><span>📅 Регистрация</span><span><?=date('d.m.Y',strtotime($user['created_at']))?></span></div>
            <?php if($user['user_type']=='passenger'):?>
            <div class="row"><span>⭐ Приоритетных</span><span><?=$extra['priority_rides']??0?></span></div>
            <div class="row"><span>🚗 Поездок</span><span><?=$extra['total_rides']??0?></span></div>
            <?php else:?>
            <div class="row"><span>💰 Драйвикоины</span><span><?=$extra['loyalty_points']??0?></span></div>
            <div class="row"><span>🚗 Поездок</span><span><?=$extra['total_rides']??0?></span></div>
            <div class="row"><span>⭐ Рейтинг</span><span><?=$extra['rating']??5.0?></span></div>
            <?php endif;?>
        </div>
        
        <button class="logout" onclick="if(confirm('Выйти?'))location.href='?logout=1'">Выйти</button>
    </div>
</div>
</body>
</html>