<?php
// --- 1. Старт сессии (самое первое!) ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 2. Очистка буфера ---
if (ob_get_level()) {
    ob_clean();
}

// --- 3. Заголовки ---
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// --- 4. Проверка авторизации ---
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Не авторизован'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = $_SESSION['user'];
$userRole = $user['role'] ?? '';
$userId = $user['id'];

// Разрешённые роли для чтения
$allowed_read_roles = ['admin', 'senior_nurse', 'director', 'employee'];
if (!in_array($userRole, $allowed_read_roles)) {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещён'], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 5. Подключение к БД ---
$root = $_SERVER['DOCUMENT_ROOT'];
$databasePath = $root . '/src/Config/Database.php';

if (!file_exists($databasePath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Файл Database.php не найден'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $databasePath;

try {
    $database = new Database();
    $pdo = $database->pdo;
    if (!$pdo) {
        throw new Exception('Не удалось получить соединение PDO');
    }
} catch (Exception $e) {
    error_log("DB init failed in positions.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка подключения к базе данных'], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 6. Получение входных данных ---
$action = null;
$updates = [];

$rawBody = file_get_contents('php://input');
if (!empty($rawBody)) {
    $input = json_decode($rawBody, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Некорректный JSON в теле запроса'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $action = $input['action'] ?? null;
    $updates = $input['updates'] ?? [];
}

// Если action не передан — по умолчанию 'list'
$action = $action ?? $_GET['action'] ?? 'list';

try {
    // === ДЕЙСТВИЕ: Получить список должностей ===
    if ($action === 'list') {
        $stmt = $pdo->query("SELECT code, title, salary FROM positions ORDER BY title");
        $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formattedPositions = array_map(function ($pos) {
            return [
                'code' => $pos['code'],
                'title' => $pos['title'],
                'salary' => (float)$pos['salary']
            ];
        }, $positions);

        echo json_encode([
            'success' => true,
            'count' => count($formattedPositions),
            'positions' => $formattedPositions
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // === ДЕЙСТВИЕ: Обновить оклады (массовое обновление) ===
    if ($action === 'update_salary_batch') {
        // Только admin может обновлять
        if ($userRole !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Доступ запрещён. Требуется роль "admin"'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!is_array($updates) || empty($updates)) {
            http_response_code(400);
            echo json_encode(['error' => 'Не переданы данные для обновления: updates[]'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("UPDATE positions SET salary = ?, updated_at = CURRENT_TIMESTAMP WHERE code = ?");

            $updatedCount = 0;
            foreach ($updates as $item) {
                $code = trim($item['code'] ?? '');
                $salary = $item['salary'] ?? null;

                if (empty($code)) {
                    $pdo->rollback();
                    http_response_code(400);
                    echo json_encode(['error' => 'Код должности не указан'], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                if (!is_numeric($salary) || (float)$salary < 0) {
                    $pdo->rollback();
                    http_response_code(400);
                    echo json_encode(['error' => "Неверный оклад для должности {$code}"], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $salary = (float)$salary;

                $result = $stmt->execute([$salary, $code]);
                if (!$result) {
                    $pdo->rollback();
                    error_log("Ошибка обновления оклада: код={$code}, salary={$salary}");
                    http_response_code(500);
                    echo json_encode(['error' => 'Ошибка при обновлении записи в БД'], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $updatedCount++;
            }

            $pdo->commit();
            error_log("✅ [POSITIONS] Обновлено окладов: {$updatedCount}");

            echo json_encode([
                'success' => true,
                'updated' => $updatedCount,
                'message' => "Оклады успешно обновлены: {$updatedCount} записей"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Exception $e) {
            $pdo->rollback();
            error_log("Ошибка в update_salary_batch: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка транзакции базы данных'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // === Ошибка: Неизвестное действие ===
    http_response_code(400);
    echo json_encode([
        'error' => 'Неизвестное действие',
        'available_actions' => ['list', 'update_salary_batch'],
        'received_action' => $action,
        'method' => $_SERVER['REQUEST_METHOD'],
        'user_role' => $userRole,
        'user_id' => $userId
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    error_log("Неожиданная ошибка в positions.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Внутренняя ошибка сервера',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
