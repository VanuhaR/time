<?php
// --- 1. Старт сессии (самое первое!) ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 2. Очистка буфера ---
if (ob_get_level()) {
    ob_clean();
}

// --- 3. Заголовок Content-Type ---
header('Content-Type: application/json; charset=utf-8');

// --- 4. Проверка авторизации ---
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Не авторизован'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = $_SESSION['user'];
$userRole = $user['role'] ?? '';

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
    error_log("DB init failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка подключения к БД'], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 6. Получение входных данных ---
$action = null;
$year = null;
$norms = [];
$updates = [];
$rates = [];

$rawBody = file_get_contents('php://input');
if (!empty($rawBody)) {
    $input = json_decode($rawBody, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $action = $input['action'] ?? null;
        $year = $input['year'] ?? null;
        $norms = $input['norms'] ?? [];
        $updates = $input['updates'] ?? [];
        $rates = $input['rates'] ?? [];
    } else {
        error_log("JSON decode error: " . json_last_error_msg());
    }
}

// Если action не передан — смотрим GET
if ($action === null) {
    $action = $_GET['action'] ?? null;
    $year = $_GET['year'] ?? date('Y');
}

// === 1. Получить норму по месяцу и полу (вспомогательный метод) ===
if ($action === 'get_norm_for_month') {
    $year = (int)($_GET['year'] ?? 0);
    $month = (int)($_GET['month'] ?? 0);
    $gender = $_GET['gender'] ?? 'male';
    $genderKey = $gender === 'female' ? 'f' : 'm';

    if (!$year || !$month || $month < 1 || $month > 12) {
        http_response_code(400);
        echo json_encode(['error' => 'Некорректные year/month'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $key = "work_norm_{$year}_{$month}_{$genderKey}";

    try {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE key_name = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();

        $norm = $row ? (float)$row['value'] : 100;

        echo json_encode(['norm' => $norm], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Exception $e) {
        error_log("Ошибка get_norm: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['norm' => 100], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// === 2. Получить нормы за год (только просмотр: admin + director) ===
if ($action === 'get_norms') {
    if ($userRole !== 'admin' && $userRole !== 'director') {
        http_response_code(403);
        echo json_encode(['error' => 'Доступ запрещён. Требуется admin или director.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $year = (int)$year;
    if ($year < 1900 || $year > 2100) {
        http_response_code(400);
        echo json_encode(['error' => 'Некорректный год'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $norms = [];

    try {
        $stmt = $pdo->prepare("SELECT key_name, value FROM settings WHERE key_name LIKE ? AND CHAR_LENGTH(key_name) = 18");
        $stmt->execute(["work_norm_{$year}_%"]);
        $rows = $stmt->fetchAll();

        foreach ($rows as $row) {
            if (preg_match('/work_norm_(\d+)_(\d+)_(m|f)/', $row['key_name'], $m)) {
                $month = (int)$m[2];
                $gender = $m[3] === 'f' ? 'female' : 'male';
                $value = floatval($row['value']);

                if (!isset($norms[$month])) {
                    $norms[$month] = ['male' => 0, 'female' => 0];
                }
                $norms[$month][$gender] = $value;
            }
        }

        // Заполним значения по умолчанию
        for ($m = 1; $m <= 12; $m++) {
            $norms[$m]['male'] = $norms[$m]['male'] ?? 100;
            $norms[$m]['female'] = $norms[$m]['female'] ?? 100;
        }

        echo json_encode(['norms' => $norms], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Exception $e) {
        error_log("Ошибка get_norms: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['norms' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// === 3. Сохранить нормы (только admin) ===
if ($action === 'save_norms' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($userRole !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Сохранение норм доступно только администратору.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!$year || !is_numeric($year) || $year < 1900 || $year > 2100) {
        http_response_code(400);
        echo json_encode(['error' => 'Укажите корректный год'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $year = (int)$year;

    if (!is_array($norms) || count($norms) === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Нормы должны быть массивом по месяцам'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $pdo->beginTransaction();

        for ($m = 1; $m <= 12; $m++) {
            $male = max(0, round(floatval($norms[$m]['male'] ?? 100), 1));
            $female = max(0, round(floatval($norms[$m]['female'] ?? 100), 1));

            $keyM = "work_norm_{$year}_{$m}_m";
            $keyF = "work_norm_{$year}_{$m}_f";

            $stmt = $pdo->prepare("
                INSERT INTO settings (key_name, value, description) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE value = VALUES(value)
            ");

            $stmt->execute([$keyM, $male, "Норма для мужчин, {$m}/{$year}"]);
            $stmt->execute([$keyF, $female, "Норма для женщин, {$m}/{$year}"]);
        }

        $pdo->commit();
        error_log("✅ Нормы на {$year} успешно сохранены");
        echo json_encode(['success' => true, 'message' => "Нормы на {$year} сохранены"], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Ошибка save_norms: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка при сохранении норм в БД'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// === 4. Обновить оклады должностей (только admin) ===
if ($action === 'update_salary_batch' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($userRole !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Изменение окладов доступно только администратору.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!is_array($updates) || empty($updates)) {
        http_response_code(400);
        echo json_encode(['error' => 'Не переданы данные для обновления окладов'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $pdo->beginTransaction();
        $updateStmt = $pdo->prepare("UPDATE positions SET salary = ?, updated_at = CURRENT_TIMESTAMP WHERE code = ?");

        foreach ($updates as $item) {
            $code = trim($item['code'] ?? '');
            $salary = floatval($item['salary'] ?? 0);

            if (empty($code)) {
                $pdo->rollback();
                http_response_code(400);
                echo json_encode(['error' => 'Код должности не указан'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($salary < 0) {
                $pdo->rollback();
                http_response_code(400);
                echo json_encode(['error' => "Оклад не может быть отрицательным: {$code} = {$salary}"], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $updateStmt->execute([$salary, $code]);
        }

        $pdo->commit();
        error_log("✅ Оклады успешно обновлены: " . count($updates));
        echo json_encode(['success' => true, 'message' => 'Оклады успешно обновлены'], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Ошибка update_salary_batch: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка БД при обновлении окладов'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// === 5. Получить проценты доплат (только admin и director) ===
if ($action === 'get_bonus_rates') {
    if ($userRole !== 'admin' && $userRole !== 'director') {
        http_response_code(403);
        echo json_encode(['error' => 'Доступ запрещён. Требуется admin или director.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $keys = $_GET['keys'] ?? '';
    $keys = array_filter(explode(',', $keys));
    if (empty($keys)) {
        $keys = ['bonus_harmful', 'bonus_experience', 'bonus_special_work', 'bonus_rayon', 'bonus_north'];
    }

    $allowedKeys = ['bonus_harmful', 'bonus_experience', 'bonus_special_work', 'bonus_rayon', 'bonus_north'];
    $keys = array_intersect($keys, $allowedKeys);

    if (empty($keys)) {
        http_response_code(400);
        echo json_encode(['error' => 'Нет разрешённых ключей для чтения'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $rates = [];
    try {
        $placeholders = str_repeat('?,', count($keys) - 1) . '?';
        $stmt = $pdo->prepare("SELECT key_name, value FROM settings WHERE key_name IN ($placeholders)");
        $stmt->execute($keys);

        foreach ($stmt->fetchAll() as $row) {
            $rates[$row['key_name']] = (float)$row['value'];
        }

        // Значения по умолчанию
        $defaults = [
            'bonus_harmful'        => 0.05,
            'bonus_experience'     => 0.20,
            'bonus_special_work'   => 0.06,
            'bonus_rayon'          => 1.00,
            'bonus_north'          => 0.50,
        ];

        foreach ($defaults as $key => $default) {
            if (!isset($rates[$key])) {
                $rates[$key] = $default;
                $stmt = $pdo->prepare("INSERT INTO settings (key_name, value, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
                $stmt->execute([$key, $default, "Доплата: $key"]);
            }
        }

        echo json_encode(['success' => true, 'rates' => $rates], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Exception $e) {
        error_log("Ошибка get_bonus_rates: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Ошибка БД'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// === 6. Сохранить проценты доплат (только admin) ===
if ($action === 'save_bonus_rates' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($userRole !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Сохранение доплат доступно только администратору.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!is_array($rates) || empty($rates)) {
        http_response_code(400);
        echo json_encode(['error' => 'Не переданы данные для сохранения доплат'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $allowedKeys = [
        'bonus_harmful'        => 'Доплата за вредность',
        'bonus_experience'     => 'Надбавка за стаж',
        'bonus_special_work'   => 'Доплата за характер работы',
        'bonus_rayon'          => 'Районный коэффициент',
        'bonus_north'          => 'Северная надбавка',
    ];

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO settings (key_name, value, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");

        foreach ($rates as $key => $value) {
            if (!isset($allowedKeys[$key])) {
                $pdo->rollback();
                http_response_code(400);
                echo json_encode(['error' => "Недопустимый ключ доплаты: $key"], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $value = (float)$value;
            if ($value < 0 || $value > 10.0) {
                $pdo->rollback();
                http_response_code(400);
                echo json_encode(['error' => "Недопустимое значение доплаты: $value"], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $desc = $allowedKeys[$key];
            $stmt->execute([$key, $value, $desc]);
        }

        $pdo->commit();
        error_log("✅ Проценты доплат сохранены: " . count($rates));
        echo json_encode([
            'success' => true,
            'message' => 'Проценты доплат успешно сохранены'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Ошибка save_bonus_rates: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка БД при сохранении доплат'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// === Ошибка: неверное действие ===
http_response_code(400);
echo json_encode([
    'error' => 'Неверное действие',
    'available_actions' => [
        'get_norms',
        'save_norms',
        'get_norm_for_month',
        'update_salary_batch',
        'get_bonus_rates',
        'save_bonus_rates'
    ],
    'received_action' => $action,
    'method' => $_SERVER['REQUEST_METHOD'],
    'user_role' => $userRole
], JSON_UNESCAPED_UNICODE);
exit;
