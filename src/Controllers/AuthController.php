<?php
// --- filename: src/Controllers/AuthController.php ---
require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Core/Auth.php';

class AuthController {
    private $db;
    private $auth;

    public function __construct() {
        $database = new Database();
        $this->db = $database->pdo;
        $this->auth = new Auth($this->db);
    }

    public function login() {
        // Защита от CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            echo json_encode(['error' => 'Неверный токен безопасности']);
            return;
        }

        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($phone) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Телефон и пароль обязательны']);
            return;
        }

        if ($this->auth->login($phone, $password)) {
            echo json_encode(['success' => true, 'redirect' => '/index.html']);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Неверный телефон или пароль']);
        }
    }

    public function logout() {
        $this->auth->logout();
        header('Location: /login.html');
        exit;
    }
}
