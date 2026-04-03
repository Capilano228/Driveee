<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';

$db = new Database();
$auth = new Auth();

if (isset($_SESSION['user_id']) && !isset($_SESSION['user_phone'])) {
    $user = $db->getUser($_SESSION['user_id']);
    if ($user && is_array($user)) {
        $_SESSION['user_phone'] = $user['phone'] ?? '';
        $_SESSION['user_name'] = $user['full_name'] ?? '';
        $_SESSION['user_type'] = $user['user_type'] ?? '';
    }
}

$isLoggedIn = $auth->isLoggedIn();
$userData = null;
$passengerData = null;
$driverData = null;
$quests = [];
$streak = 0;

if ($isLoggedIn) {
    $userData = $auth->getCurrentUser();
    if ($userData && is_array($userData) && isset($userData['user_type'])) {
        if ($userData['user_type'] == 'passenger') {
            $passengerData = $db->getPassengerData($_SESSION['user_id']);
            $quests = $db->getActiveQuests($_SESSION['user_id']);
            $streak = $passengerData['streak'] ?? 0;
        } elseif ($userData['user_type'] == 'driver') {
            $driverData = $db->getDriverData($_SESSION['user_id']);
        }
    } else {
        session_destroy();
        $isLoggedIn = false;
    }
}

$yakutiaAddresses = [
    'Якутск, ул. Ленина, 1', 'Якутск, пр. Ленина, 15', 'Якутск, ул. Кирова, 10',
    'Якутск, ул. Октябрьская, 20', 'Якутск, ул. Курашова, 5', 'Якутск, ул. Петра Алексеева, 8',
    'Якутск, ул. Дзержинского, 12', 'Якутск, мкр. Марха, ул. Лесная, 3',
    'Якутск, ул. Чернышевского, 25', 'Мирный, ул. Советская, 10', 'Нерюнгри, пр. Дружбы, 15',
    'Алдан, ул. Космонавтов, 3', 'Алдан, ул. Ленина, 12', 'Ленск, ул. Ленина, 20'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>DRIVEEE - Такси Якутии</title>
    <script src="https://mapgl.2gis.com/api/js/v1"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { background: #1a1a1a; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .phone { width: 380px; height: 700px; background: #fff; border-radius: 44px; overflow: hidden; position: relative; box-shadow: 0 30px 50px rgba(0,0,0,0.3); display: flex; flex-direction: column; }
        .dynamic-island { background: #1a1a1a; height: 50px; border-radius: 0 0 30px 30px; display: flex; justify-content: center; align-items: center; padding-top: 10px; }
        .time { color: white; font-size: 14px; font-weight: 500; }
        .app-header { background: #1a1a1a; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; }
        .app-header h1 { font-size: 22px; font-weight: 700; color: #00cc44; cursor: pointer; }
        .user-badge { background: #2a2a2a; width: 40px; height: 40px; border-radius: 30px; display: flex; align-items: center; justify-content: center; font-weight: 600; color: white; cursor: pointer; position: relative; }
        .streak-flame { position: absolute; bottom: -5px; right: -5px; background: #ff6600; color: white; font-size: 10px; width: 18px; height: 18px; border-radius: 20px; display: flex; align-items: center; justify-content: center; }
        .app-content { flex: 1; overflow-y: auto; background: #f5f5f5; }
        .map-container { height: 280px; background: #e0e0e0; position: relative; }
        #map, #driverMap { width: 100%; height: 100%; }
        .find-me-btn { position: absolute; bottom: 12px; right: 12px; background: white; width: 44px; height: 44px; border-radius: 30px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.2); cursor: pointer; z-index: 1000; font-size: 20px; }
        .order-panel { background: white; margin: -20px 16px 16px 16px; border-radius: 24px; padding: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .stats-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #eee; }
        .priority-badge { background: #00cc44; padding: 8px 16px; border-radius: 30px; font-weight: 600; font-size: 14px; color: #1a1a1a; }
        .streak-badge { display: inline-flex; align-items: center; gap: 4px; background: #ff6600; padding: 4px 12px; border-radius: 20px; font-size: 12px; color: white; }
        .ride-types { display: flex; gap: 10px; margin-bottom: 16px; }
        .ride-type { flex: 1; padding: 10px; background: #f0f0f0; border: none; border-radius: 30px; font-weight: 500; cursor: pointer; }
        .ride-type.active { background: #00cc44; color: white; }
        .address-wrapper { position: relative; margin-bottom: 12px; }
        .address-input { background: #f5f5f5; border-radius: 16px; padding: 12px; display: flex; align-items: center; gap: 12px; }
        .address-input input { flex: 1; border: none; background: none; font-size: 15px; outline: none; }
        .suggestions { position: absolute; top: 100%; left: 0; right: 0; background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); z-index: 100; max-height: 200px; overflow-y: auto; display: none; }
        .suggestion-item { padding: 12px 16px; cursor: pointer; border-bottom: 1px solid #f0f0f0; }
        .price-input { width: 100%; padding: 14px; border: 1px solid #ddd; border-radius: 30px; margin: 16px 0; }
        .order-btn { width: 100%; padding: 16px; background: #00cc44; border: none; border-radius: 30px; font-size: 17px; font-weight: 700; cursor: pointer; }
        .order-status { margin-top: 16px; padding: 16px; background: #f0f0f0; border-radius: 16px; text-align: center; display: none; }
        .spinner { width: 30px; height: 30px; border: 3px solid #ddd; border-top-color: #00cc44; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 8px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .action-btn-mobile { width: 100%; padding: 14px; margin-top: 10px; border: none; border-radius: 30px; font-weight: bold; cursor: pointer; }
        .exit-btn { background: #ff6600; color: white; display: none; }
        .board-btn { background: #00cc44; color: #1a1a1a; display: none; }
        .complete-btn { background: #1a1a1a; color: white; display: none; }
        .timer-panel { margin-top: 16px; padding: 16px; background: linear-gradient(135deg, #ff6600, #ff4400); border-radius: 20px; text-align: center; display: none; }
        .timer-value { font-size: 32px; font-weight: bold; color: white; font-family: monospace; }
        .timer-label { color: rgba(255,255,255,0.8); font-size: 12px; }
        .riding-status { background: #00cc44; color: #1a1a1a; padding: 16px; border-radius: 20px; text-align: center; display: none; font-weight: bold; margin-top: 16px; }
        .success-ride { background: #00cc44; color: #1a1a1a; padding: 16px; border-radius: 20px; text-align: center; display: none; margin-top: 16px; font-weight: bold; }
        .quests-panel { background: white; margin: 0 16px 16px 16px; border-radius: 24px; padding: 16px; }
        .quest-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
        .quest-icon { font-size: 24px; }
        .quest-info { flex: 1; }
        .quest-title { font-weight: 600; font-size: 14px; }
        .quest-desc { font-size: 11px; color: #888; }
        .quest-progress { width: 100px; height: 4px; background: #e0e0e0; border-radius: 2px; overflow: hidden; margin-top: 6px; }
        .quest-progress-fill { height: 100%; background: #00cc44; border-radius: 2px; }
        .quest-reward { font-size: 12px; font-weight: 600; color: #00cc44; }
        .driver-stats { display: flex; gap: 12px; margin: 16px; }
        .driver-stat { flex: 1; background: white; padding: 16px; border-radius: 20px; text-align: center; cursor: pointer; }
        .driver-stat-value { font-size: 24px; font-weight: 700; color: #00cc44; }
        .online-btn { width: calc(100% - 32px); margin: 0 16px 16px 16px; padding: 14px; background: #00cc44; border: none; border-radius: 30px; font-weight: 600; cursor: pointer; }
        .orders-tabs { display: flex; gap: 10px; margin: 0 16px 16px 16px; }
        .tab { flex: 1; padding: 10px; background: #f0f0f0; border: none; border-radius: 30px; cursor: pointer; }
        .tab.active { background: #1a1a1a; color: white; }
        .orders-list { margin: 0 16px 16px 16px; max-height: 350px; overflow-y: auto; }
        .order-card { background: white; border-radius: 20px; padding: 14px; margin-bottom: 10px; border: 1px solid #eee; }
        .order-card.priority { background: linear-gradient(135deg, #fff, #e8fff0); border-left: 4px solid #ff6600; }
        .priority-tag { background: #ff6600; color: white; padding: 2px 10px; border-radius: 20px; font-size: 11px; margin-left: 8px; }
        .accept-btn { width: 100%; padding: 10px; background: #00cc44; border: none; border-radius: 30px; font-weight: 600; margin-top: 10px; cursor: pointer; }
        .action-btns { display: flex; gap: 10px; margin-top: 10px; }
        .action-btn { flex: 1; padding: 10px; border: none; border-radius: 30px; font-weight: 500; cursor: pointer; }
        .arrived-btn { background: #ff6600; color: white; }
        .driver-complete-btn { background: #1a1a1a; color: white; }
        .cancel-btn { background: #ff4444; color: white; }
        .notification { position: fixed; bottom: 20px; left: 20px; right: 20px; background: #1a1a1a; color: white; padding: 14px; border-radius: 30px; text-align: center; z-index: 1500; display: none; animation: slideUp 0.3s ease; }
        @keyframes slideUp { from { transform: translateY(100px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .review-modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; }
        .review-content { background: white; border-radius: 32px; padding: 24px; width: 300px; text-align: center; }
        .stars { display: flex; justify-content: center; gap: 10px; margin: 20px 0; }
        .star { font-size: 40px; cursor: pointer; color: #ccc; }
        .star.active { color: #ff6600; }
        .review-text { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 16px; margin: 16px 0; }
        .submit-review { background: #00cc44; border: none; padding: 12px; border-radius: 30px; font-weight: bold; width: 100%; cursor: pointer; }
        .driver-modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; }
        .driver-content { background: white; border-radius: 32px; padding: 24px; width: 300px; text-align: center; }
    </style>
</head>
<body>
<div class="phone">
    <div class="dynamic-island"><span class="time" id="currentTime">9:41</span></div>
    <div class="app-header">
        <h1 onclick="window.location.href='/index.php'">DRIVEEE</h1>
        <?php if ($isLoggedIn): ?>
        <div class="user-badge" onclick="window.location.href='/pages/profile.php'">
            <?php echo isset($userData['full_name']) ? mb_substr($userData['full_name'], 0, 1, 'UTF-8') : 'U'; ?>
            <?php if ($streak > 0): ?><span class="streak-flame"><?php echo $streak; ?></span><?php endif; ?>
        </div>
        <?php else: ?>
        <div class="user-badge" onclick="window.location.href='/pages/login.php'">→</div>
        <?php endif; ?>
    </div>
    
    <div class="app-content">
        <?php if (!$isLoggedIn): ?>
            <div style="padding: 40px 20px; text-align: center;">
                <div style="font-size: 60px;">🚗</div>
                <h2>Добро пожаловать</h2>
                <p>Такси по Якутии</p>
                <a href="/pages/register.php" style="display: block; background: #00cc44; color: #1a1a1a; text-decoration: none; padding: 14px; border-radius: 30px; font-weight: 600; margin-bottom: 10px;">Регистрация</a>
                <a href="/pages/login.php" style="display: block; background: #1a1a1a; color: white; text-decoration: none; padding: 14px; border-radius: 30px; font-weight: 600;">Вход</a>
            </div>
        <?php elseif ($userData['user_type'] == 'passenger'): ?>
            <div class="map-container"><div id="map"></div><div class="find-me-btn" onclick="findMyLocation()">📍</div></div>
            <div class="order-panel">
                <div class="stats-row">
                    <div class="priority-badge">⭐ <?php echo $passengerData['priority_rides'] ?? 0; ?> приоритетных</div>
                    <div class="streak-badge">🔥 Огонек: <?php echo $streak; ?></div>
                </div>
                <div class="ride-types">
                    <button class="ride-type active" data-type="taxi" onclick="selectRideType('taxi')">Такси</button>
                    <button class="ride-type" data-type="courier" onclick="selectRideType('courier')">Курьер</button>
                    <button class="ride-type" data-type="intercity" onclick="selectRideType('intercity')">Межгород</button>
                </div>
                <div class="address-wrapper">
                    <div class="address-input"><span>📍</span><input type="text" id="pickupAddress" placeholder="Откуда" autocomplete="off"><input type="hidden" id="pickupLat"><input type="hidden" id="pickupLng"></div>
                    <div id="pickupSuggestions" class="suggestions"></div>
                </div>
                <div class="address-wrapper">
                    <div class="address-input"><span>🏁</span><input type="text" id="dropoffAddress" placeholder="Куда" autocomplete="off"><input type="hidden" id="dropoffLat"><input type="hidden" id="dropoffLng"></div>
                    <div id="dropoffSuggestions" class="suggestions"></div>
                </div>
                <input type="number" id="ridePrice" class="price-input" placeholder="Сумма" value="300">
                <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;"><input type="checkbox" id="priorityCheckbox" <?php echo ($passengerData['priority_rides'] ?? 0) <= 0 ? 'disabled' : ''; ?>> Приоритетная поездка</label>
                <button class="order-btn" id="orderBtn">Заказать</button>
                <div id="orderStatus" class="order-status"><div class="spinner"></div><div id="statusMessage">Поиск водителя...</div></div>
                <button id="exitBtn" class="action-btn-mobile exit-btn">Выхожу</button>
                <button id="boardBtn" class="action-btn-mobile board-btn">Сел в такси</button>
                <button id="passengerCompleteBtn" class="action-btn-mobile complete-btn">Завершить поездку</button>
                <div id="timerPanel" class="timer-panel"><div class="timer-value" id="timerValue">5:00</div><div class="timer-label">У вас есть время, чтобы сесть в такси</div></div>
                <div id="ridingStatus" class="riding-status">Еду! Удачной поездки</div>
                <div id="successRide" class="success-ride">Поездка завершена! Огонек +1</div>
            </div>
            <div class="quests-panel">
                <h3>Еженедельные квесты</h3>
                <?php if (!empty($quests)): ?>
                    <?php foreach ($quests as $quest): ?>
                    <div class="quest-item">
                        <div class="quest-icon"><?php echo $quest['requirement_type'] == 'rides_count' ? '🚗' : '✅'; ?></div>
                        <div class="quest-info">
                            <div class="quest-title"><?php echo htmlspecialchars($quest['title']); ?></div>
                            <div class="quest-desc"><?php echo htmlspecialchars($quest['description']); ?></div>
                            <div class="quest-progress"><div class="quest-progress-fill" style="width: <?php echo min(100, ($quest['progress'] / $quest['requirement_value']) * 100); ?>%"></div></div>
                        </div>
                        <div class="quest-reward">+<?php echo $quest['reward_points']; ?> приор.</div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align:center;padding:20px;color:#888;">Загрузка квестов...</div>
                <?php endif; ?>
            </div>
        <?php elseif ($userData['user_type'] == 'driver'): ?>
            <div class="map-container"><div id="driverMap"></div><div class="find-me-btn" onclick="findDriverLocation()">📍</div></div>
            <div class="order-panel">
                <div class="stats-row">
                    <div class="priority-badge">💰 <?php echo $driverData['loyalty_points'] ?? 0; ?> Драйвикоинов</div>
                    <div class="streak-badge">⭐ <?php echo number_format($driverData['rating'] ?? 5, 1); ?></div>
                </div>
                <button class="online-btn" onclick="toggleOnline()"><span id="onlineStatus"><?php echo ($driverData['is_online'] ?? 0) ? 'На линии' : 'Выйти на линию'; ?></span></button>
                <div class="orders-tabs"><button class="tab active" onclick="showTab('available')">Доступные</button><button class="tab" onclick="showTab('active')">Мои заказы</button></div>
                <div id="availableOrders" class="orders-list"></div>
                <div id="myOrders" class="orders-list" style="display: none;"></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="reviewModal" class="review-modal">
    <div class="review-content">
        <h3>Оцените поездку</h3>
        <div class="stars" id="starRating">
            <span class="star" data-rating="1">★</span>
            <span class="star" data-rating="2">★</span>
            <span class="star" data-rating="3">★</span>
            <span class="star" data-rating="4">★</span>
            <span class="star" data-rating="5">★</span>
        </div>
        <textarea id="reviewText" class="review-text" rows="3" placeholder="Ваш отзыв..."></textarea>
        <button class="submit-review" onclick="submitReview()">Отправить отзыв</button>
    </div>
</div>

<div id="driverProfileModal" class="driver-modal">
    <div class="driver-content" id="driverProfileContent"></div>
</div>

<div id="notification" class="notification"></div>

<script>
let map, driverMap, userMarker, driverLocationMarker;
let currentOrderId = null, currentDriverId = null;
let checkInterval = null, currentRideType = 'taxi', timerInterval = null, timeLeft = 300;
let selectedRating = 5;
let isTimerActive = false;
const yakutiaAddresses = <?php echo json_encode($yakutiaAddresses); ?>;

// Звезды рейтинга
var stars = document.querySelectorAll('#starRating .star');
if(stars) {
    for(var i = 0; i < stars.length; i++) {
        stars[i].onclick = function() {
            selectedRating = parseInt(this.getAttribute('data-rating'));
            for(var s = 0; s < stars.length; s++) {
                if(s < selectedRating) stars[s].classList.add('active');
                else stars[s].classList.remove('active');
            }
        };
    }
}

function submitReview() {
    var review = document.getElementById('reviewText').value;
    fetch('/api/review.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ driver_id: currentDriverId, rating: selectedRating, review: review })
    }).then(function(r) { return r.json(); }).then(function(data) {
        if(data.success) {
            document.getElementById('reviewModal').style.display = 'none';
            showNotification('Спасибо за отзыв!');
            location.reload();
        }
    });
}

function showDriverProfile(driverId) {
    fetch('/api/driver.php?action=profile&driver_id=' + driverId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if(data.success) {
                document.getElementById('driverProfileContent').innerHTML = 
                    '<h3>' + escapeHtml(data.driver.full_name) + '</h3>' +
                    '<div style="margin:20px 0;"><div style="font-size:48px;color:#ff6600;">⭐ ' + data.driver.rating + '</div><div>рейтинг</div></div>' +
                    '<div>🚗 ' + data.driver.total_rides + ' поездок</div>' +
                    '<div>💰 ' + data.driver.loyalty_points + ' Драйвикоинов</div>' +
                    '<div>🚘 ' + (data.driver.car_model || 'Эконом') + '</div>' +
                    '<button onclick="document.getElementById(\'driverProfileModal\').style.display=\'none\'" style="margin-top:20px;padding:10px 20px;background:#00cc44;border:none;border-radius:30px;cursor:pointer;">Закрыть</button>';
                document.getElementById('driverProfileModal').style.display = 'flex';
            }
        });
}

// 2GIS КАРТА ПАССАЖИРА
function initMap() {
    map = new mapgl.Map('map', {
        center: [62.027, 129.732],
        zoom: 14,
        key: '5ee9d56d-f2f8-4b77-8433-c260e7b1601a' // демо-режим 2GIS
    });
    
    if (navigator.geolocation) {
        navigator.geolocation.watchPosition(function(pos) {
            if (userMarker) userMarker.destroy();
            userMarker = new mapgl.Marker(map, {
                coordinates: [pos.coords.latitude, pos.coords.longitude],
                icon: '📍'
            });
            map.setCenter([pos.coords.latitude, pos.coords.longitude]);
        }, null, { enableHighAccuracy: true });
    }
}

// 2GIS КАРТА ВОДИТЕЛЯ
function initDriverMap() {
    var driverMapDiv = document.getElementById('driverMap');
    if (driverMapDiv) {
        driverMap = new mapgl.Map('driverMap', {
            center: [62.027, 129.732],
            zoom: 14,
            key: 'demo'
        });
        
        if (navigator.geolocation) {
            navigator.geolocation.watchPosition(function(pos) {
                if (driverLocationMarker) driverLocationMarker.destroy();
                driverLocationMarker = new mapgl.Marker(driverMap, {
                    coordinates: [pos.coords.latitude, pos.coords.longitude],
                    icon: '🚗'
                });
                driverMap.setCenter([pos.coords.latitude, pos.coords.longitude]);
            }, null, { enableHighAccuracy: true });
        }
    }
}

function findMyLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(p) {
            map.setCenter([p.coords.latitude, p.coords.longitude]);
        });
    }
}

function findDriverLocation() {
    if (navigator.geolocation && driverMap) {
        navigator.geolocation.getCurrentPosition(function(p) {
            driverMap.setCenter([p.coords.latitude, p.coords.longitude]);
        });
    }
}

// ПОДСКАЗКИ АДРЕСОВ
function showAddressSuggestions(inputId, query) {
    var div = document.getElementById(inputId + 'Suggestions');
    if(!query || query.length < 2) { div.style.display = 'none'; return; }
    
    var filtered = yakutiaAddresses.filter(function(addr) {
        return addr.toLowerCase().indexOf(query.toLowerCase()) !== -1;
    });
    
    if(filtered.length === 0) { div.style.display = 'none'; return; }
    
    div.innerHTML = '';
    for(var i = 0; i < filtered.length; i++) {
        (function(address) {
            var item = document.createElement('div');
            item.className = 'suggestion-item';
            item.textContent = '📍 ' + address;
            item.onclick = function() { 
                document.getElementById(inputId).value = address; 
                div.style.display = 'none'; 
            };
            div.appendChild(item);
        })(filtered[i]);
    }
    div.style.display = 'block';
}

function selectRideType(type) { 
    currentRideType = type; 
    var btns = document.querySelectorAll('.ride-type');
    for(var i = 0; i < btns.length; i++) btns[i].classList.remove('active');
    document.querySelector('.ride-type[data-type="' + type + '"]').classList.add('active');
}

// ЗАКАЗ
function orderRide() {
    var pickup = document.getElementById('pickupAddress').value;
    var dropoff = document.getElementById('dropoffAddress').value;
    var price = document.getElementById('ridePrice').value;
    var isPriority = document.getElementById('priorityCheckbox') ? document.getElementById('priorityCheckbox').checked : false;
    var priorityRides = <?php echo $passengerData['priority_rides'] ?? 0; ?>;
    
    if(isPriority && priorityRides <= 0) { showNotification('Нет приоритетных поездок'); return; }
    if(!pickup || !dropoff) { showNotification('Укажите адреса'); return; }
    if(!price || price <= 0) { showNotification('Укажите сумму'); return; }
    
    document.getElementById('orderStatus').style.display = 'block';
    document.getElementById('statusMessage').innerHTML = '<div class="spinner"></div>Создание заказа...';
    
    fetch('/api/order.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({
            pickup_address:pickup, pickup_lat:62.027, pickup_lng:129.732,
            dropoff_address:dropoff, dropoff_lat:62.027, dropoff_lng:129.732,
            ride_type:currentRideType, offered_price:parseInt(price), is_priority:isPriority
        })
    }).then(function(r) { return r.json(); }).then(function(data){
        if(data.success) { 
            currentOrderId = data.order_id; 
            document.getElementById('statusMessage').innerHTML = '<div class="spinner"></div>Ищем водителя...'; 
            startOrderCheck(); 
        } else { 
            document.getElementById('orderStatus').style.display = 'none'; 
            showNotification(data.message); 
        }
    }).catch(function(e){ 
        document.getElementById('orderStatus').style.display = 'none'; 
        showNotification('Ошибка: ' + e.message); 
    });
}

function startOrderCheck() { if(checkInterval) clearInterval(checkInterval); checkInterval = setInterval(checkOrder, 2000); }

function checkOrder() {
    if(!currentOrderId) return;
    fetch('/api/orders.php?action=check&order_id=' + currentOrderId)
        .then(function(r) { return r.json(); })
        .then(function(data){
            if(data.status === 'accepted') {
                document.getElementById('statusMessage').innerHTML = 'Водитель найден! Он едет к вам';
                currentDriverId = data.driver_id;
            } 
            else if(data.status === 'arrived') {
                clearInterval(checkInterval);
                document.getElementById('orderStatus').style.display = 'none';
                document.getElementById('exitBtn').style.display = 'block';
                showNotification('Водитель подъехал! Нажмите "Выхожу"');
            } 
            else if(data.status === 'waiting_exit') {
                document.getElementById('exitBtn').style.display = 'none';
                document.getElementById('boardBtn').style.display = 'block';
                startTimer();
                showNotification('У вас 5 минут чтобы сесть в такси');
            }
            else if(data.status === 'passenger_onboard') {
                if(timerInterval) clearInterval(timerInterval);
                isTimerActive = false;
                document.getElementById('timerPanel').style.display = 'none';
                document.getElementById('ridingStatus').style.display = 'block';
                document.getElementById('boardBtn').style.display = 'none';
                document.getElementById('passengerCompleteBtn').style.display = 'block';
                showNotification('Поехали! Удачной поездки!');
            } 
            else if(data.status === 'completed') {
                if(timerInterval) clearInterval(timerInterval);
                document.getElementById('ridingStatus').style.display = 'none';
                document.getElementById('successRide').style.display = 'block';
                document.getElementById('passengerCompleteBtn').style.display = 'none';
                document.getElementById('reviewModal').style.display = 'flex';
            }
        });
}

// Выхожу
document.getElementById('exitBtn').onclick = function() {
    fetch('/api/orders.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action:'exit', order_id:currentOrderId}) })
        .then(function(r) { return r.json(); }).then(function(data){
            if(data.success) { 
                document.getElementById('exitBtn').style.display = 'none'; 
                document.getElementById('boardBtn').style.display = 'block';
                startTimer(); 
                showNotification('У вас 5 минут чтобы сесть в такси'); 
            }
        });
};

// Сел в такси
document.getElementById('boardBtn').onclick = function() {
    fetch('/api/orders.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action:'passenger_onboard', order_id:currentOrderId}) })
        .then(function(r) { return r.json(); }).then(function(data){
            if(data.success) { 
                if(timerInterval) clearInterval(timerInterval);
                isTimerActive = false;
                document.getElementById('timerPanel').style.display = 'none';
                document.getElementById('boardBtn').style.display = 'none';
                document.getElementById('passengerCompleteBtn').style.display = 'block';
                showNotification('Поехали!'); 
            }
        });
};

// Завершить поездку (пассажир)
document.getElementById('passengerCompleteBtn').onclick = function() {
    fetch('/api/orders.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action:'complete', order_id:currentOrderId, completed_by:'passenger'}) })
        .then(function(r) { return r.json(); }).then(function(data){
            if(data.success) { 
                showNotification('Поездка завершена!'); 
                document.getElementById('reviewModal').style.display = 'flex';
            }
        });
};

function startTimer() {
    if(isTimerActive) return;
    isTimerActive = true;
    timeLeft = 300;
    document.getElementById('timerPanel').style.display = 'block';
    updateTimerDisplay();
    if(timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(function(){
        timeLeft--;
        updateTimerDisplay();
        if(timeLeft <= 0){
            clearInterval(timerInterval);
            document.getElementById('timerPanel').style.display = 'none';
            isTimerActive = false;
            showNotification('Время вышло! Заказ отменен');
            fetch('/api/orders.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'late', order_id:currentOrderId})});
        }
    }, 1000);
}
function updateTimerDisplay() { var m = Math.floor(timeLeft/60), s = timeLeft%60; document.getElementById('timerValue').textContent = m + ':' + (s<10?'0'+s:s); }
function showNotification(msg) { var n = document.getElementById('notification'); n.textContent = msg; n.style.display = 'block'; setTimeout(function() { n.style.display = 'none'; }, 3000); }

// ВОДИТЕЛЬ
function loadAvailableOrders() {
    fetch('/api/orders.php?action=pending').then(function(r) { return r.json(); }).then(function(data){
        var container = document.getElementById('availableOrders');
        if(container && data.orders){
            if(data.orders.length === 0) container.innerHTML = '<div style="text-align:center;padding:20px;color:#888;">Нет заказов</div>';
            else {
                var html = '';
                for(var i = 0; i < data.orders.length; i++) {
                    var o = data.orders[i];
                    html += '<div class="order-card ' + (o.is_priority ? 'priority' : '') + '">';
                    html += '<div>📍 ' + escapeHtml(o.pickup_address) + '</div>';
                    html += '<div>🏁 ' + escapeHtml(o.dropoff_address) + '</div>';
                    html += '<div>💰 ' + o.offered_price + ' ₽ ' + (o.is_priority ? '<span class="priority-tag">ПРИОРИТЕТ</span>' : '') + '</div>';
                    html += '<div>👤 ' + escapeHtml(o.passenger_name) + '</div>';
                    html += '<button class="accept-btn" onclick="acceptOrder(' + o.id + ',' + o.offered_price + ')">Принять заказ</button>';
                    html += '</div>';
                }
                container.innerHTML = html;
            }
        }
    });
}

function loadMyOrders() {
    fetch('/api/orders.php?action=my').then(function(r) { return r.json(); }).then(function(data){
        var container = document.getElementById('myOrders');
        if(container && data.orders){
            if(data.orders.length === 0) container.innerHTML = '<div style="text-align:center;padding:20px;color:#888;">Нет активных заказов</div>';
            else {
                var html = '';
                for(var i = 0; i < data.orders.length; i++) {
                    var o = data.orders[i];
                    html += '<div class="order-card">';
                    html += '<div>📍 ' + escapeHtml(o.pickup_address) + '</div>';
                    html += '<div>🏁 ' + escapeHtml(o.dropoff_address) + '</div>';
                    html += '<div>💰 ' + (o.final_price || o.offered_price) + ' ₽</div>';
                    html += '<div>👤 ' + escapeHtml(o.passenger_name) + '</div>';
                    html += '<div class="action-btns">';
                    if(o.status === 'accepted') html += '<button class="action-btn arrived-btn" onclick="updateOrder(' + o.id + ',\'arrived\')">Подъехал</button>';
                    if(o.status === 'passenger_onboard') html += '<button class="action-btn driver-complete-btn" onclick="updateOrder(' + o.id + ',\'complete\', \'driver\')">Завершить поездку</button>';
                    html += '<button class="action-btn cancel-btn" onclick="updateOrder(' + o.id + ',\'cancel\')">Отменить</button>';
                    html += '</div></div>';
                }
                container.innerHTML = html;
            }
        }
    });
}

function acceptOrder(orderId, price){
    fetch('/api/orders.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'accept', order_id:orderId, final_price:price})})
        .then(function(r) { return r.json(); }).then(function(data){ 
            showNotification(data.message);
            if(data.success){ loadAvailableOrders(); loadMyOrders(); }
        });
}

function updateOrder(orderId, action, completedBy){
    fetch('/api/orders.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:action, order_id:orderId, completed_by:completedBy || 'driver'})})
        .then(function(r) { return r.json(); }).then(function(data){ 
            showNotification(data.message);
            if(data.success){ 
                if(action === 'complete') { 
                    setTimeout(function() { location.reload(); }, 1500);
                } else { 
                    loadMyOrders(); 
                } 
            } 
        });
}

function toggleOnline(){
    fetch('/api/driver.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'toggle_online'})})
        .then(function(r) { return r.json(); }).then(function(data){ if(data.success) location.reload(); });
}

function showTab(tab){
    var avail = document.getElementById('availableOrders'), my = document.getElementById('myOrders'), btns = document.querySelectorAll('.tab');
    for(var i = 0; i < btns.length; i++) btns[i].classList.remove('active');
    if(tab === 'available'){ avail.style.display = 'block'; my.style.display = 'none'; btns[0].classList.add('active'); loadAvailableOrders(); }
    else{ avail.style.display = 'none'; my.style.display = 'block'; btns[1].classList.add('active'); loadMyOrders(); }
}

function escapeHtml(str){ if(!str) return ''; return str.replace(/[&<>]/g, function(m){ if(m==='&') return '&amp;'; if(m==='<') return '&lt;'; if(m==='>') return '&gt;'; return m; }); }

document.addEventListener('DOMContentLoaded', function(){
    initMap(); initDriverMap();
    document.getElementById('orderBtn').onclick = orderRide;
    var pu = document.getElementById('pickupAddress'), dof = document.getElementById('dropoffAddress');
    if(pu){ pu.addEventListener('input', function(e) { showAddressSuggestions('pickup', e.target.value); }); pu.addEventListener('blur', function() { setTimeout(function() { document.getElementById('pickupSuggestions').style.display = 'none'; }, 300); }); }
    if(dof){ dof.addEventListener('input', function(e) { showAddressSuggestions('dropoff', e.target.value); }); dof.addEventListener('blur', function() { setTimeout(function() { document.getElementById('dropoffSuggestions').style.display = 'none'; }, 300); }); }
    <?php if($isLoggedIn && isset($userData) && is_array($userData) && $userData['user_type'] == 'driver'): ?>
    loadAvailableOrders(); loadMyOrders(); setInterval(loadAvailableOrders, 5000); setInterval(loadMyOrders, 5000);
    <?php endif; ?>
});
function updateTime(){ var now = new Date(); document.getElementById('currentTime').textContent = now.toLocaleTimeString('ru-RU',{hour:'2-digit',minute:'2-digit'}); }
setInterval(updateTime, 1000); updateTime();
</script>
</body>
</html>