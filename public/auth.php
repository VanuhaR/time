<?php
session_start();

// Логирование (по желанию, можно убрать)
function log_msg($msg) {
    $log = fopen(__DIR__ . '/auth_debug.log', 'a');
    fwrite($log, "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n");
    fclose($log);
}

// log_msg("=== AUTH.PHP: СТАРТ === POST");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /public/login.php');
    exit;
}

$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$csrfToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['csrf_token'] ?? '';

// Проверка CSRF
if (!hash_equals($sessionToken ?? '', $csrfToken ?? '')) {
    log_msg('❌ CSRF НЕ СОВПАЛ!');
    header('Location: /public/login.php?error=1');
    exit;
}

if (empty($phone) || empty($password)) {
    header('Location: /public/login.php?error=1');
    exit;
}

try {
    require_once __DIR__ . '/../src/Config/Database.php';
    require_once __DIR__ . '/../src/Core/Auth.php';

    $database = new Database();
    $auth = new Auth($database->pdo);

    if ($auth->login($phone, $password)) {
        $user = $auth->user();
        log_msg("✅ Успешный вход: {$user['full_name']} (ID: {$user['id']}, роль: {$user['role']})");

        // Все пользователи направляются в папку admin, но с нужной стартовой страницей
        switch ($user['role']) {
            case 'admin':
                $redirect = '/public/pages/admin/dashboard.php';
                break;
            case 'director':
                $redirect = '/public/pages/admin/dashboard.php'; // или можно на payroll/vacation
                break;
            case 'senior_nurse':
                $redirect = '/public/pages/admin/vacation.php';
                break;
            case 'employee':
                $redirect = '/public/pages/admin/dashboard.php';
                break;
            default:
                $redirect = '/public/login.php?error=unknown_role';
                break;
        }

        header("Location: $redirect");
        exit;
    } else {
        log_msg("❌ Неверный пароль: $phone");
        header('Location: /public/login.php?error=1');
        exit;
    }
} catch (Exception $e) {
    log_msg('❌ Ошибка: ' . $e->getMessage());
    log_msg('📝 Trace: ' . $e->getTraceAsString());
    header('Location: /public/login.php?error=server');
    exit;
}
