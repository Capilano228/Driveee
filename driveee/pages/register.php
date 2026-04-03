<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

$db = new Database();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
    if (strlen($phone) == 10) $phone = '7' . $phone;
    $password = $_POST['password'] ?? '';
    $fullName = $_POST['full_name'] ?? '';
    $userType = $_POST['user_type'] ?? 'passenger';
    
    if ($db->getUserByPhone($phone)) {
        $error = 'Номер уже зарегистрирован';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль минимум 6 символов';
    } elseif (empty($fullName)) {
        $error = 'Введите имя';
    } else {
        $userId = $db->createUser($phone, $password, $fullName, $userType);
        if ($userId) {
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_type'] = $userType;
            $_SESSION['user_name'] = $fullName;
            header('Location: ' . SITE_URL . '/index.php');
            exit;
        } else {
            $error = 'Ошибка регистрации';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Регистрация - DRIVEEE</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{background:#1a1a1a;font-family:Arial;display:flex;justify-content:center;align-items:center;min-height:100vh}
        .phone{width:380px;height:700px;background:#fff;border-radius:44px;overflow:auto}
        .header{background:#1a1a1a;padding:20px;text-align:center}
        .header h1{color:#00cc44;font-size:28px}
        .content{padding:30px 20px}
        .error{background:#ffebee;color:#ff4444;padding:12px;border-radius:16px;margin-bottom:20px}
        input,select{width:100%;padding:16px;border:1px solid #ddd;border-radius:30px;margin-bottom:16px;font-size:16px}
        button{width:100%;padding:16px;background:#00cc44;border:none;border-radius:30px;font-size:16px;font-weight:bold;cursor:pointer}
        .link{text-align:center;margin-top:20px}
        .link a{color:#00cc44;text-decoration:none}
    </style>
</head>
<body>
<div class="phone">
    <div class="header"><h1>DRIVEEE</h1></div>
    <div class="content">
        <?php if($error):?><div class="error"><?=$error?></div><?php endif;?>
        <form method="POST">
            <input type="tel" name="phone" placeholder="+7 (___) ___-__-__" required>
            <input type="password" name="password" placeholder="Пароль (мин. 6 символов)" required>
            <input type="text" name="full_name" placeholder="Полное имя" required>
            <select name="user_type">
                <option value="passenger">Пассажир</option>
                <option value="driver">Водитель</option>
            </select>
            <button type="submit">Зарегистрироваться</button>
        </form>
        <div class="link"><a href="login.php">Уже есть аккаунт? Войти</a></div>
    </div>
</div>
</body>
</html>