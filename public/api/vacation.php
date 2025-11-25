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
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// --- 4. Проверка авторизации ---
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Не авторизован'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = $_SESSION['user'];
$userId = $user['id'];
$role = $user['role'] ?? '';
$fullName = $user['full_name'] ?? 'Unknown';
error_log("🎯 [VACATION] USER: $fullName (ID: $userId, role: $role)");

// --- 5. Проверка прав доступа ---
$allowed_roles = ['admin', 'senior_nurse', 'director', 'employee'];
if (!in_array($role, $allowed_roles)) {
    error_log("❌ [VACATION] Доступ запрещён: $role");
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещён'], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 6. Подключение к БД ---
try {
    require_once __DIR__ . '/../../src/Config/Database.php';
    $database = new Database();
    $pdo = $database->pdo;

    if (!$pdo) {
        error_log("❌ [VACATION] Не удалось подключиться к БД");
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка подключения к БД'], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (Exception $e) {
    error_log("❌ [VACATION] Ошибка подключения: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка сервера'], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 7. Получение входных данных ---
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? $input['action'] ?? '';
error_log("🎯 [VACATION] ACTION: $action");

try {
    // === ДЕЙСТВИЕ: Получить одобренные отпуска (для графика) ===
    if ($action === 'get_approved_vacations_for_year') {
        if (!in_array($role, ['admin', 'senior_nurse', 'director', 'employee'])) {
            error_log("❌ [VACATION] Доступ запрещён для $role");
            http_response_code(403);
            echo json_encode(['error' => 'Доступ запрещён для просмотра отпусков'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $year = (int)($_GET['year'] ?? date('Y'));
        $startOfYear = "$year-01-01";
        $endOfYear = "$year-12-31";

        $stmt = $pdo->prepare("
            SELECT vr.employee_id, vr.start_date, vr.end_date
            FROM vacation_requests vr
            WHERE vr.status = 'approved'
              AND vr.end_date >= ?
              AND vr.start_date <= ?
        ");
        $stmt->execute([$startOfYear, $endOfYear]);
        $vacations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $vacationMap = [];
        foreach ($vacations as $v) {
            $start = new DateTime($v['start_date']);
            $end = new DateTime($v['end_date']);
            $interval = new DateInterval('P1D');
            $period = new DatePeriod($start, $interval, $end->modify('+1 day'));

            foreach ($period as $date) {
                $dateKey = $date->format('Y-m-d');
                $vacationMap[$v['employee_id']][$dateKey] = 'ОТ';
            }
        }

        error_log("✅ [VACATION] Отпуска загружены: " . count($vacations));
        echo json_encode($vacationMap, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // === ДЕЙСТВИЕ: Получить список отпусков ===
    if ($action === 'list') {
        $year = (int)($_GET['year'] ?? date('Y'));
        $startOfYear = "$year-01-01";
        $endOfYear = "$year-12-31";

        $employee_id = $input['employee_id'] ?? $_GET['employee_id'] ?? null;
        if ($role === 'employee') {
            $employee_id = $userId;
        } else {
            $employee_id = $employee_id ? (int)$employee_id : null;
        }

        if ($employee_id !== null) {
            $stmt = $pdo->prepare("
                SELECT vr.id, vr.employee_id, vr.start_date, vr.end_date, vr.status, e.full_name
                FROM vacation_requests vr
                JOIN employees e ON e.id = vr.employee_id
                WHERE vr.employee_id = ? 
                  AND vr.end_date >= ? 
                  AND vr.start_date <= ?
                ORDER BY vr.start_date
            ");
            $stmt->execute([$employee_id, $startOfYear, $endOfYear]);
        } else {
            $stmt = $pdo->prepare("
                SELECT vr.id, vr.employee_id, vr.start_date, vr.end_date, vr.status, e.full_name
                FROM vacation_requests vr
                JOIN employees e ON e.id = vr.employee_id
                WHERE vr.end_date >= ? 
                  AND vr.start_date <= ?
                ORDER BY e.full_name, vr.start_date
            ");
            $stmt->execute([$startOfYear, $endOfYear]);
        }

        $vacations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'year' => $year,
            'employee_id' => $employee_id,
            'total' => count($vacations),
            'vacations' => array_map(fn($v) => [
                'id' => (int)$v['id'],
                'employee_id' => (int)$v['employee_id'],
                'full_name' => $v['full_name'],
                'start_date' => $v['start_date'],
                'end_date' => $v['end_date'],
                'status' => $v['status']
            ], $vacations)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // === ДЕЙСТВИЕ: Получить свои отпуска ===
    if ($action === 'get_my_vacations') {
        $year = (int)($_GET['year'] ?? date('Y'));
        $startOfYear = "$year-01-01";
        $endOfYear = "$year-12-31";

        $stmt = $pdo->prepare("
            SELECT id, start_date, end_date, status 
            FROM vacation_requests 
            WHERE employee_id = ? 
              AND end_date >= ? 
              AND start_date <= ?
            ORDER BY start_date
        ");
        $stmt->execute([$userId, $startOfYear, $endOfYear]);
        $vacations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'year' => $year,
            'vacations' => array_map(fn($v) => [
                'id' => (int)$v['id'],
                'start_date' => $v['start_date'],
                'end_date' => $v['end_date'],
                'status' => $v['status']
            ], $vacations)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // === ДЕЙСТВИЕ: Создать отпуск ===
    if ($action === 'create') {
        $employee_id = $input['employee_id'] ?? null;
        $start_date = $input['start_date'] ?? null;
        $end_date = $input['end_date'] ?? null;
        $status = $input['status'] ?? 'pending';

        if ($role === 'employee') {
            $employee_id = $userId;
        } elseif (!in_array($role, ['admin', 'senior_nurse', 'director'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Нет прав на создание отпуска'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!$employee_id || !$start_date || !$end_date) {
            http_response_code(400);
            echo json_encode(['error' => 'Не хватает данных'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        if ($end < $start) {
            http_response_code(400);
            echo json_encode(['error' => 'Дата окончания раньше начала'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO vacation_requests (employee_id, start_date, end_date, status, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $result = $stmt->execute([$employee_id, $start_date, $end_date, $status]);

        if ($result) {
            $newId = $pdo->lastInsertId();
            error_log("✅ [VACATION] Отпуск создан: ID=$newId");
            echo json_encode([
                'success' => true,
                'id' => (int)$newId,
                'message' => 'Отпуск добавлен'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Не удалось добавить отпуск'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // === ДЕЙСТВИЕ: Обновить отпуск ===
    if ($action === 'update') {
        $id = $input['id'] ?? null;
        $employee_id = $input['employee_id'] ?? null;
        $start_date = $input['start_date'] ?? null;
        $end_date = $input['end_date'] ?? null;

        if (!$id || !$employee_id || !$start_date || !$end_date) {
            http_response_code(400);
            echo json_encode(['error' => 'Не хватает данных'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($role === 'employee') {
            $stmt = $pdo->prepare("SELECT employee_id FROM vacation_requests WHERE id = ?");
            $stmt->execute([$id]);
            $db_emp_id = $stmt->fetchColumn();
            if ($db_emp_id != $userId) {
                http_response_code(403);
                echo json_encode(['error' => 'Изменение чужого отпуска запрещено'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        } elseif (!in_array($role, ['admin', 'senior_nurse', 'director'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Нет прав на редактирование'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE vacation_requests 
            SET employee_id = ?, start_date = ?, end_date = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $result = $stmt->execute([$employee_id, $start_date, $end_date, $id]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Отпуск обновлён'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Не удалось обновить'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // === ДЕЙСТВИЕ: Удалить отпуск ===
    if ($action === 'delete') {
        $id = $input['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Не указан ID'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $pdo->prepare("SELECT employee_id FROM vacation_requests WHERE id = ?");
        $stmt->execute([$id]);
        $employee_id = $stmt->fetchColumn();

        if (!$employee_id) {
            http_response_code(404);
            echo json_encode(['error' => 'Отпуск не найден'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($role === 'employee' && $employee_id != $userId) {
            http_response_code(403);
            echo json_encode(['error' => 'Удаление чужого отпуска запрещено'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM vacation_requests WHERE id = ?");
        $result = $stmt->execute([$id]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Отпуск удалён'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Не удалось удалить'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // === ДЕЙСТВИЕ: Массовое создание ===
    if ($action === 'bulk_create') {
        if (!in_array($role, ['admin', 'senior_nurse', 'director'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Нет прав на импорт'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $vacations = $input['vacations'] ?? [];
        $imported = 0;

        if (!is_array($vacations) || empty($vacations)) {
            http_response_code(400);
            echo json_encode(['error' => 'Нет данных для импорта'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO vacation_requests (employee_id, start_date, end_date, status, created_at)
            VALUES (?, ?, ?, 'approved', NOW())
        ");

        foreach ($vacations as $v) {
            $emp_id = $v['employee_id'] ?? null;
            $start = $v['start_date'] ?? null;
            $end = $v['end_date'] ?? null;

            if ($emp_id && $start && $end) {
                $start_dt = new DateTime($start);
                $end_dt = new DateTime($end);
                if ($end_dt >= $start_dt) {
                    $stmt->execute([$emp_id, $start, $end]) && $imported++;
                }
            }
        }

        echo json_encode([
            'success' => true,
            'imported' => $imported,
            'message' => "Импортировано {$imported} отпусков"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // === Неизвестное действие ===
    error_log("❓ [VACATION] Неизвестное действие: $action");
    http_response_code(400);
    echo json_encode([
        'error' => 'Неизвестное действие',
        'available_actions' => [
            'list', 'get_my_vacations', 'get_approved_vacations_for_year',
            'create', 'update', 'delete', 'bulk_create'
        ],
        'received_action' => $action,
        'user_role' => $role,
        'user_id' => $userId
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    error_log("❌ [VACATION] Ошибка: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Ошибка сервера',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
