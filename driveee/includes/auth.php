<?php
require_once __DIR__ . '/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function login($phone, $password) {
        $user = $this->db->getUserByPhone($phone);
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_phone'] = $user['phone'];
            return true;
        }
        return false;
    }
    
    public function logout() {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        return $this->db->getUser($_SESSION['user_id']);
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . SITE_URL . '/pages/login.php');
            exit;
        }
    }
    
    public function requirePassenger() {
        $this->requireLogin();
        if ($_SESSION['user_type'] != 'passenger') {
            header('Location: ' . SITE_URL . '/index.php');
            exit;
        }
    }
    
    public function requireDriver() {
        $this->requireLogin();
        if ($_SESSION['user_type'] != 'driver') {
            header('Location: ' . SITE_URL . '/index.php');
            exit;
        }
    }
}
?>