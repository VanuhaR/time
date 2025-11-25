<?php
// --- src/Core/Auth.php ---
// Класс для аутентификации и управления сессиями

class Auth {
    private $pdo;

    // Карта нормализации ролей: входные значения → стандартизированные
    private $roleMap = [
        // Администраторы
        'admin'         => 'admin',
        'administrator' => 'admin',
        'админ'         => 'admin',
        'администратор' => 'admin',

        // Директора
        'director'      => 'director',
        'директор'      => 'director',
        'dir'           => 'director',

        // Медсёстры
        'senior_nurse'  => 'senior_nurse',
        'seniornurse'   => 'senior_nurse',
        'nurse'         => 'senior_nurse',
        'медсестра'     => 'senior_nurse',
        'старшая медсестра' => 'senior_nurse',

        // Обычные сотрудники
        'employee'      => 'employee',
        'работник'      => 'employee',
        'user'          => 'employee',
        'worker'        => 'employee',
        'сотрудник'     => 'employee'
    ];

    public function __construct($pdo) {
        $this->pdo = $pdo;

        // Проверяем, что сессия уже запущена
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new Exception("Сессия не запущена. Вызовите session_start() перед созданием Auth.");
        }
    }

    /**
     * Попытка входа по телефону и паролю
     * @param string $phone
     * @param string $password
     * @return bool
     */
    public function login($phone, $password) {
        // Очищаем телефон от всего, кроме цифр
        $phone = preg_replace('/\D/', '', $phone);

        // Логируем попытку входа
        error_log("🔐 [AUTH] Попытка входа: +7 {$phone}");

        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, 
                    full_name, 
                    phone, 
                    password_hash, 
                    role, 
                    gender, 
                    position_code, 
                    hire_date, 
                    created_at
                FROM employees 
                WHERE phone = ? 
                LIMIT 1
            ");
            $stmt->execute([$phone]);
            $user = $stmt->fetch();

            if (!$user) {
                error_log("❌ [AUTH] Пользователь не найден: +7 {$phone}");
                return false;
            }

            if (!password_verify($password, $user['password_hash'])) {
                error_log("❌ [AUTH] Неверный пароль для: {$user['full_name']} (ID: {$user['id']})");
                return false;
            }

            // Убираем хэш пароля из сессии
            unset($user['password_hash']);

            // Нормализуем роль
            $originalRole = $user['role'] ?? 'employee';
            $user['role'] = $this->normalizeRole($originalRole);
            error_log("✅ [AUTH] Вход успешен: {$user['full_name']} (ID: {$user['id']}, роль: $originalRole → {$user['role']})");

            // Сохраняем в сессию
            $_SESSION['user'] = $user;
            $_SESSION['last_activity'] = time();

            return true;

        } catch (Exception $e) {
            error_log("❌ [AUTH] Ошибка входа: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Нормализация роли
     * @param string $role
     * @return string
     */
    private function normalizeRole($role) {
        $role = strtolower(trim($role));
        return $this->roleMap[$role] ?? 'employee'; // по умолчанию — employee
    }

    /**
     * Выход из системы
     */
    public function logout() {
        error_log("👋 [AUTH] Пользователь вышел: " . ($_SESSION['user']['full_name'] ?? 'unknown'));
        $_SESSION = [];
        session_destroy();

        // Удаляем cookie сессии
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
    }

    /**
     * Проверка, авторизован ли пользователь
     * @return bool
     */
    public function isLoggedIn() {
        return isset($_SESSION['user']) && $this->validateSession();
    }

    /**
     * Проверка прав по ролям
     * @param array $roles Например: ['admin', 'director']
     * @return bool
     */
    public function checkRole($roles = []) {
        if (!$this->isLoggedIn()) return false;
        return in_array($_SESSION['user']['role'], $roles);
    }

    /**
     * Получение данных текущего пользователя
     * @return array|null
     */
    public function user() {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Получение CSRF-токена
     * @return string
     */
    public function csrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Проверка активности сессии (15 минут)
     * @return bool
     */
    private function validateSession() {
        $last = $_SESSION['last_activity'] ?? 0;
        if (time() - $last > 900) { // 15 минут
            $this->logout();
            return false;
        }
        $_SESSION['last_activity'] = time();
        return true;
    }
}
