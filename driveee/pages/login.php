<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
    if (strlen($phone) == 10) $phone = '7' . $phone;
    
    if ($auth->login($phone, $_POST['password'] ?? '')) {
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    } else {
        $error = 'Неверный номер или пароль';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Вход - DRIVEEE</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{background:#1a1a1a;font-family:Arial;display:flex;justify-content:center;align-items:center;min-height:100vh}
        .phone{width:380px;height:700px;background:#fff;border-radius:44px;overflow:hidden;display:flex;flex-direction:column}
        .header{background:#1a1a1a;padding:20px;text-align:center}
        .header h1{color:#00cc44;font-size:28px}
        .content{padding:30px 20px;flex:1}
        .error{background:#ffebee;color:#ff4444;padding:12px;border-radius:16px;margin-bottom:20px}
        input{width:100%;padding:16px;border:1px solid #ddd;border-radius:30px;margin-bottom:16px;font-size:16px}
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
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit">Войти</button>
        </form>
        <div class="link"><a href="register.php">Нет аккаунта? Зарегистрироваться</a></div>
    </div>
</div>
</body>
</html>