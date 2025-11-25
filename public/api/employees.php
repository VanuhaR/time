<?php
// --- 1. Старт сессии (самое первое!) ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 2. Очистка буфера (если есть) ---
if (ob_get_level()) {
    ob_clean();
}

// --- 3. Заголовок Content-Type ---
header('Content-Type: application/json; charset=utf-8');

// --- 4. Проверка авторизации ---
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    error_log("❌ [EMPLOYEES_API] Не авторизован");
    http_response_code(403);
    echo json_encode(['error' => 'Не авторизован'], JSON_UNESCAPED_UNICODE);
    exit;
}

error_log("🔧 [EMPLOYEES_API] Start");

$user = $_SESSION['user'];
$userId = $user['id'];
$role = $user['role'] ?? '';
error_log("🎯 [EMPLOYEES_API] USER: {$user['full_name']} (ID: $userId, role: $role)");

// --- 5. Подключение к БД ---
try {
    require_once __DIR__ . '/../../src/Config/Database.php';
    $database = new Database();
    $pdo = $database->pdo;
} catch (Exception $e) {
    error_log("❌ [EMPLOYEES_API] Ошибка БД: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка подключения к БД'], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 6. Получение action ---
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? $input['action'] ?? 'list';
error_log("🎯 [EMPLOYEES_API] ACTION: $action");

// === LIST: список сотрудников (только role = 'employee') ===
if ($action === 'list') {
    if (!in_array($role, ['admin', 'director', 'senior_nurse', 'employee'])) {
        error_log("❌ [EMPLOYEES_API] Доступ к list запрещён: $role");
        http_response_code(403);
        echo json_encode(['error' => 'Доступ к списку запрещён'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        // Получаем должности
        $positions = [];
        $posStmt = $pdo->query("SELECT code, title FROM positions");
        while ($row = $posStmt->fetch(PDO::FETCH_ASSOC)) {
            $positions[$row['code']] = $row['title'];
        }

        // Получаем сотрудников с role = 'employee'
        $stmt = $pdo->prepare("
            SELECT 
                e.id,
                e.full_name,
                e.phone,
                e.role,
                e.gender,
                e.department,
                e.position_code,
                e.hire_date
            FROM employees e
            WHERE e.role = 'employee'
            ORDER BY e.full_name
        ");
        $stmt->execute();
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = array_map(function ($emp) use ($positions) {
            return [
                'id' => (int)$emp['id'],
                'full_name' => $emp['full_name'],
                'phone' => $emp['phone'],
                'role' => $emp['role'],
                'gender' => $emp['gender'],
                'department' => $emp['department'] ?? 'Не указан',
                'position_code' => $emp['position_code'] ?? 'unknown',
                'position_title' => $positions[$emp['position_code']] ?? 'Неизвестно',
                'hire_date' => $emp['hire_date'] ? date('Y-m-d', strtotime($emp['hire_date'])) : null
            ];
        }, $employees);

        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        error_log("❌ [EMPLOYEES_API] Ошибка списка: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка загрузки списка'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// === GET: получить сотрудника ===
if ($action === 'get') {
    $id = $_GET['id'] ?? $input['id'] ?? null;
    if (!$id || !is_numeric($id)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID не указан'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($role === 'employee' && $id != $userId) {
        error_log("❌ [EMPLOYEES_API] Доступ к чужому профилю: $userId → $id");
        http_response_code(403);
        echo json_encode(['error' => 'Доступ запрещён'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT id, full_name, phone, role, position_code, department, gender, hire_date
            FROM employees WHERE id = ? LIMIT 1
        ");
        $stmt->execute([(int)$id]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$emp) {
            http_response_code(404);
            echo json_encode(['error' => 'Сотрудник не найден'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Получаем название должности
        $posTitle = 'Неизвестно';
        if ($emp['position_code']) {
            $posStmt = $pdo->prepare("SELECT title FROM positions WHERE code = ?");
            $posStmt->execute([$emp['position_code']]);
            $title = $posStmt->fetch(PDO::FETCH_COLUMN);
            if ($title) {
                $posTitle = $title;
            }
        }

        $result = [
            'id' => (int)$emp['id'],
            'full_name' => $emp['full_name'],
            'phone' => $emp['phone'],
            'role' => $emp['role'],
            'position_code' => $emp['position_code'] ?? 'unknown',
            'position_title' => $posTitle,
            'department' => $emp['department'] ?? 'Не указан',
            'gender' => $emp['gender'],
            'hire_date' => $emp['hire_date'] ? date('Y-m-d', strtotime($emp['hire_date'])) : null
        ];

        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        error_log("❌ [EMPLOYEES_API] Ошибка get: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка сервера'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// === CREATE: добавить сотрудника ===
if ($action === 'create') {
    if ($role !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Только для admin'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $fullName = trim($input['full_name'] ?? '');
    $phone = preg_replace('/\D/', '', $input['phone'] ?? '');
    $positionCode = trim($input['position_code'] ?? '');
    $department = trim($input['department'] ?? '');
    $gender = trim($input['gender'] ?? 'male');
    $hireDate = $input['hire_date'] ?? null;
    $password = $input['password'] ?? '123456';

    if (!$fullName) {
        http_response_code(400);
        echo json_encode(['error' => 'ФИО обязательно'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (strlen($phone) !== 11) {
        http_response_code(400);
        echo json_encode(['error' => 'Телефон должен содержать 11 цифр'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $posStmt = $pdo->prepare("SELECT 1 FROM positions WHERE code = ?");
        $posStmt->execute([$positionCode]);
        if ($posStmt->rowCount() === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Неверная должность'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } catch (Exception $e) {
        error_log("❌ [EMPLOYEES_API] Ошибка при проверке должности: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка БД'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO employees (full_name, phone, position_code, department, role, password_hash, hire_date, gender, created_at)
        VALUES (?, ?, ?, ?, 'employee', ?, ?, ?, NOW())
    ");
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt->execute([$fullName, $phone, $positionCode, $department, $passwordHash, $hireDate, $gender]);

    $newId = $pdo->lastInsertId();
    error_log("✅ [EMPLOYEES_API] Сотрудник добавлен: $fullName (ID: $newId)");
    echo json_encode(['success' => true, 'id' => $newId], JSON_UNESCAPED_UNICODE);
    exit;
}

// === UPDATE: обновить сотрудника ===
if ($action === 'update') {
    if ($role !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Только для admin'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $id = $input['id'] ?? null;
    if (!$id || !is_numeric($id)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID обязателен'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $fullName = trim($input['full_name'] ?? '');
    $phone = preg_replace('/\D/', '', $input['phone'] ?? '');
    $positionCode = trim($input['position_code'] ?? '');
    $department = trim($input['department'] ?? '');
    $gender = trim($input['gender'] ?? 'male');
    $hireDate = $input['hire_date'] ?? null;
    $password = $input['password'] ?? null;

    if (!$fullName) {
        http_response_code(400);
        echo json_encode(['error' => 'ФИО обязательно'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (strlen($phone) !== 11) {
        http_response_code(400);
        echo json_encode(['error' => 'Телефон должен содержать 11 цифр'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $posStmt = $pdo->prepare("SELECT 1 FROM positions WHERE code = ?");
        $posStmt->execute([$positionCode]);
        if ($posStmt->rowCount() === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Неверная должность'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } catch (Exception $e) {
        error_log("❌ [EMPLOYEES_API] Ошибка при проверке должности: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка БД'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $fields = [];
    $params = [];

    foreach (['full_name', 'phone', 'position_code', 'department', 'gender'] as $field) {
        $value = $input[$field] ?? '';
        if ($value !== '') {
            $fields[] = "$field = ?";
            $params[] = $value;
        }
    }

    $fields[] = "hire_date = ?";
    $params[] = $hireDate;

    if ($password) {
        $fields[] = "password_hash = ?";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }

    $fields[] = "updated_at = NOW()";
    $params[] = (int)$id;

    $sql = "UPDATE employees SET " . implode(', ', $fields) . " WHERE id = ?";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        error_log("✅ [EMPLOYEES_API] Сотрудник обновлён: ID=$id");
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        error_log("❌ [EMPLOYEES_API] Ошибка обновления: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка обновления'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// === DELETE: удалить сотрудника ===
if ($action === 'delete') {
    if ($role !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Только для admin'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $id = $input['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID обязателен'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->execute([(int)$id]);
        $deleted = $stmt->rowCount();
        error_log("✅ [EMPLOYEES_API] Сотрудник удалён: ID=$id");
        echo json_encode(['success' => true, 'deleted' => $deleted], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        error_log("❌ [EMPLOYEES_API] Ошибка удаления: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка удаления'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// === Неизвестное действие ===
error_log("❓ [EMPLOYEES_API] Неизвестное действие: $action");
http_response_code(400);
echo json_encode(['error' => 'Неизвестное действие'], JSON_UNESCAPED_UNICODE);
exit;
