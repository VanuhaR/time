<?php
// --- 1. Старт сессии ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 2. Очистка буфера ---
if (ob_get_level()) {
    ob_clean();
}

// --- 3. Заголовок ---
header('Content-Type: application/json; charset=utf-8');

// --- 4. Подключение БД и Auth ---
require_once __DIR__ . '/../../src/Config/Database.php';
require_once __DIR__ . '/../../src/Core/Auth.php';

try {
    $database = new Database();
    $auth = new Auth($database->pdo);
} catch (Exception $e) {
    error_log('schedule.php: Ошибка инициализации БД — ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка инициализации'], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 5. Проверка авторизации ---
if (!$auth->isLoggedIn()) {
    error_log('schedule.php: Попытка доступа без авторизации — IP: ' . $_SERVER['REMOTE_ADDR']);
    http_response_code(403);
    echo json_encode(['error' => 'Не авторизован'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = $auth->user();
$role = $user['role'] ?? null;
$userId = $user['id'] ?? null;

if (!$role) {
    http_response_code(403);
    echo json_encode(['error' => 'Роль не определена'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$userId) {
    http_response_code(403);
    echo json_encode(['error' => 'ID пользователя не определён'], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 6. Получение входных данных ---
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $_GET['action'] ?? $_POST['action'] ?? $input['action'] ?? '';

// --- 7. Проверка прав (по действиям) ---
$pdo = $database->pdo;

if ($action === 'get') {
    // Любой авторизованный может запрашивать СВОЙ график
    $employee_id = $_GET['employee_id'] ?? $input['employee_id'] ?? $userId;
    $employee_id = (int)$employee_id;

    if ($userId != $employee_id) {
        error_log("schedule.php: Попытка доступа к чужому графику — user_id=$userId, requested=$employee_id");
        http_response_code(403);
        echo json_encode(['error' => 'Вы можете смотреть только свой график'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    // ✅ OK: продолжаем — это свой график

} elseif ($action === 'get_all') {
    // Все авторизованные могут ПРОСМАТРИВАТЬ весь график
    // Редактирование — только для управляющих
    $canViewAll = in_array($role, ['admin', 'senior_nurse', 'director', 'employee']);
    if (!$canViewAll) {
        error_log("schedule.php: Нет прав на просмотр графика — user_id=$userId, role=$role");
        http_response_code(403);
        echo json_encode(['error' => 'Доступ к просмотру графика запрещён'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    // ✅ OK: пользователь может просматривать график

} elseif (in_array($action, ['update', 'clear_month'])) {
    // Редактирование — только для управляющих
    $allowed_roles = ['admin', 'senior_nurse', 'director'];
    if (!in_array($role, $allowed_roles)) {
        error_log("schedule.php: Попытка редактирования без прав — action=$action, user_id=$userId, role=$role");
        http_response_code(403);
        echo json_encode(['error' => 'Изменение графика доступно только администраторам и старшей медсестре'], JSON_UNESCAPED_UNICODE);
        exit;
    }

} elseif ($action !== '') {
    // Неизвестное действие, но не дефолт — логируем
    error_log("schedule.php: Неизвестное действие — action=$action, user_id=$userId");
    http_response_code(400);
    echo json_encode(['error' => 'Неизвестное действие'], JSON_UNESCAPED_UNICODE);
    exit;
}

// === Обработка действий ===
switch ($action) {
    // === Получить график всех сотрудников ===
    case 'get_all':
        $year = $_GET['year'] ?? null;
        $month = $_GET['month'] ?? null;

        if (!$year || !$month || !is_numeric($year) || !is_numeric($month)) {
            http_response_code(400);
            echo json_encode(['error' => 'Укажите year и month как числа'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $year = (int)$year;
        $month = (int)$month;
        if ($month < 1 || $month > 12) {
            http_response_code(400);
            echo json_encode(['error' => 'Месяц должен быть от 1 до 12'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = sprintf('%04d-%02d-%02d', $year, $month, (new DateTime("$year-$month-01"))->format('t'));

        try {
            $stmt = $pdo->prepare("
                SELECT employee_id, date, shift_type 
                FROM schedule 
                WHERE date BETWEEN ? AND ? 
                ORDER BY employee_id, date
            ");
            $stmt->execute([$start, $end]);
            $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $result = [];
            foreach ($shifts as $row) {
                $id = $row['employee_id'];
                if (!isset($result[$id])) {
                    $result[$id] = ['id' => $id, 'shifts' => []];
                }
                $result[$id]['shifts'][$row['date']] = $row['shift_type'];
            }

            $result = array_values($result);

            echo json_encode([
                'success' => true,
                'year' => $year,
                'month' => $month,
                'schedule' => $result
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Exception $e) {
            error_log("schedule.php: Ошибка get_all — " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка БД при загрузке графика'], JSON_UNESCAPED_UNICODE);
            exit;
        }

    // === Получить график одного сотрудника ===
    case 'get':
        $year = $_GET['year'] ?? null;
        $month = $_GET['month'] ?? null;
        $employee_id = $_GET['employee_id'] ?? $input['employee_id'] ?? $userId;
        $employee_id = (int)$employee_id;

        if (!$year || !$month || !is_numeric($year) || !is_numeric($month)) {
            http_response_code(400);
            echo json_encode(['error' => 'Укажите year и month как числа'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $year = (int)$year;
        $month = (int)$month;
        if ($month < 1 || $month > 12) {
            http_response_code(400);
            echo json_encode(['error' => 'Месяц должен быть от 1 до 12'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = sprintf('%04d-%02d-%02d', $year, $month, (new DateTime("$year-$month-01"))->format('t'));

        try {
            $stmt = $pdo->prepare("
                SELECT date, shift_type 
                FROM schedule 
                WHERE employee_id = ? AND date BETWEEN ? AND ?
            ");
            $stmt->execute([$employee_id, $start, $end]);
            $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT full_name FROM employees WHERE id = ?");
            $stmt->execute([$employee_id]);
            $emp = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'employee_id' => $employee_id,
                'employee_name' => $emp['full_name'] ?? 'Неизвестно',
                'year' => $year,
                'month' => $month,
                'schedule' => $schedule
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Exception $e) {
            error_log("schedule.php: Ошибка get — " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка БД при загрузке графика'], JSON_UNESCAPED_UNICODE);
            exit;
        }

    // === Сохранить смену (update) ===
    case 'update':
        $employee_id = $input['employee_id'] ?? $input['empId'] ?? null;
        $date = $input['date'] ?? null;
        $shift_type = $input['shift_type'] ?? $input['shift'] ?? null;

        if (!$employee_id || !$date || $shift_type === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Не хватает данных: employee_id, date, shift_type'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $employee_id = (int)$employee_id;
        $shift_type = trim($shift_type);
        $validShifts = ['', '10ч', '14ч', 'Б', 'ОТ', 'off'];
        if (!in_array($shift_type, $validShifts)) {
            http_response_code(400);
            echo json_encode(['error' => 'Недопустимый тип смены'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $shift_type = $shift_type === 'off' ? '' : $shift_type;

        try {
            $stmt = $pdo->prepare("
                INSERT INTO schedule (employee_id, date, shift_type)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE shift_type = VALUES(shift_type)
            ");
            $result = $stmt->execute([$employee_id, $date, $shift_type]);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Смена сохранена']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Не удалось сохранить']);
            }
            exit;
        } catch (Exception $e) {
            error_log("schedule.php: Ошибка update — " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка БД: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }

    // === Очистить график за месяц ===
    case 'clear_month':
        $year = $input['year'] ?? $_POST['year'] ?? null;
        $month = $input['month'] ?? $_POST['month'] ?? null;

        if (!$year || !$month || !is_numeric($year) || !is_numeric($month)) {
            http_response_code(400);
            echo json_encode(['error' => 'Укажите year и month'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $year = (int)$year;
        $month = (int)$month;
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = sprintf('%04d-%02d-%02d', $year, $month, (new DateTime("$year-$month-01"))->format('t'));

        try {
            $stmt = $pdo->prepare("DELETE FROM schedule WHERE date BETWEEN ? AND ?");
            $stmt->execute([$start, $end]);

            echo json_encode(['success' => true, 'message' => 'Месяц очищен']);
            exit;
        } catch (Exception $e) {
            error_log("schedule.php: Ошибка clear_month — " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка БД'], JSON_UNESCAPED_UNICODE);
            exit;
        }

    // === Неизвестное действие ===
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Неизвестное действие'], JSON_UNESCAPED_UNICODE);
        exit;
}
// НЕТ ?> В КОНЦЕ ФАЙЛА
