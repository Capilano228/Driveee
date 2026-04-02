<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$auth = new Auth();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
    if (strlen($phone) == 10) $phone = '7' . $phone;
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($phone, $password)) {
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    } else {
        $error = 'Неверный номер или пароль';
    }
}

if ($auth->isLoggedIn()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Вход - DRIVEEE</title>
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
        .auth-header {
            background: #1a1a1a;
            padding: 20px;
            text-align: center;
        }
        .auth-header h1 { color: #00cc44; font-size: 28px; }
        .auth-content { padding: 30px 20px; }
        .error {
            background: #ffebee;
            color: #ff4444;
            padding: 12px;
            border-radius: 16px;
            margin-bottom: 20px;
            text-align: center;
        }
        .input-group {
            margin-bottom: 16px;
        }
        .input-group input {
            width: 100%;
            padding: 16px;
            border: 1px solid #e0e0e0;
            border-radius: 30px;
            font-size: 16px;
            background: #f5f5f5;
        }
        .login-btn {
            width: 100%;
            padding: 16px;
            background: #00cc44;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .register-link a {
            color: #00cc44;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="phone">
    <div class="dynamic-island">
        <span class="time" id="currentTime">9:41</span>
    </div>
    <div class="auth-header">
        <h1>DRIVEEE</h1>
    </div>
    <div class="auth-content">
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="input-group">
                <input type="tel" name="phone" placeholder="+7 (___) ___-__-__" required>
            </div>
            <div class="input-group">
                <input type="password" name="password" placeholder="Пароль" required>
            </div>
            <button type="submit" class="login-btn">Войти</button>
        </form>
        <div class="register-link">
            Нет аккаунта? <a href="register.php">Зарегистрироваться</a>
        </div>
    </div>
</div>
<script>
function updateTime() {
    const now = new Date();
    document.getElementById('currentTime').textContent = now.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
}
setInterval(updateTime, 1000);
updateTime();
</script>
</body>
</html>