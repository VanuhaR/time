<?php
// --- 1. Старт сессии (ДО ВСЁГО!) ---
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
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'director', 'senior_nurse', 'employee'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещён'], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 5. Путь к базе данных ---
$root = $_SERVER['DOCUMENT_ROOT'];
$databasePath = $root . '/src/Config/Database.php';

if (!file_exists($databasePath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Файл Database.php не найден'], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 6. Подключение к БД ---
require_once $databasePath;

try {
    $database = new Database();
    $pdo = $database->pdo;
} catch (Exception $e) {
    error_log("DB init failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка подключения к БД'], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 7. Получение параметров ---
$employee_id = $_GET['employee_id'] ?? null;
$year = $_GET['year'] ?? null;
$month = $_GET['month'] ?? null;

if (!$employee_id || !$year || !$month) {
    http_response_code(400);
    echo json_encode(['error' => 'Не хватает параметров: employee_id, year, month'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Получаем сотрудника
    $stmt = $pdo->prepare("
        SELECT e.*, p.title as position_title, p.salary as base_salary 
        FROM employees e 
        LEFT JOIN positions p ON e.position_code = p.code 
        WHERE e.id = ?
    ");
    $stmt->execute([(int)$employee_id]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$emp) {
        echo json_encode(['success' => false, 'error' => 'Сотрудник не найден'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Запрещаем расчёт для admin и director
    if (in_array($emp['role'], ['admin', 'director'])) {
        echo json_encode(['success' => false, 'error' => 'Расчёт не для администраторов и директоров'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $base_salary = (float)$emp['base_salary'];
    $hire_date = new DateTime($emp['hire_date']);
    $today = new DateTime();
    $experience_years = $hire_date->diff($today)->y;

    // Норма часов (по полу)
    $gender = $emp['gender'] === 'female' ? 'f' : 'm';
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE key_name = ? LIMIT 1");
    $stmt->execute(["work_norm_{$year}_{$month}_{$gender}"]);
    $norm_row = $stmt->fetch();
    $norm_hours = $norm_row ? (float)$norm_row['value'] : 151; // fallback

    // Отработанные часы и ночные
    $stmt = $pdo->prepare("SELECT shift_type FROM schedule WHERE employee_id = ? AND YEAR(date) = ? AND MONTH(date) = ?");
    $stmt->execute([(int)$employee_id, (int)$year, (int)$month]);
    $shifts = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $hours_worked = 0;
    $night_hours = 0;
    foreach ($shifts as $shift) {
        if (preg_match('/10/', $shift)) {
            $hours_worked += 10;
        } elseif (preg_match('/14/', $shift)) {
            $hours_worked += 14;
            $night_hours += 8;
        }
    }

    // Загрузка процентов из БД
    $bonus_keys = ['bonus_harmful', 'bonus_experience', 'bonus_special_work', 'bonus_rayon', 'bonus_north'];
    $placeholders = str_repeat('?,', count($bonus_keys) - 1) . '?';
    $stmt = $pdo->prepare("SELECT key_name, value FROM settings WHERE key_name IN ($placeholders)");
    $stmt->execute($bonus_keys);
    $bonus_rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $defaults = [
        'bonus_harmful'        => 0.05,
        'bonus_experience'     => 0.20,
        'bonus_special_work'   => 0.06,
        'bonus_rayon'          => 1.00,
        'bonus_north'          => 0.50,
    ];

    $bonus_harmful        = isset($bonus_rows['bonus_harmful'])        ? (float)$bonus_rows['bonus_harmful']        : $defaults['bonus_harmful'];
    $bonus_experience     = isset($bonus_rows['bonus_experience'])     ? (float)$bonus_rows['bonus_experience']     : $defaults['bonus_experience'];
    $bonus_special_work   = isset($bonus_rows['bonus_special_work'])   ? (float)$bonus_rows['bonus_special_work']   : $defaults['bonus_special_work'];
    $bonus_rayon          = isset($bonus_rows['bonus_rayon'])          ? (float)$bonus_rows['bonus_rayon']          : $defaults['bonus_rayon'];
    $bonus_north          = isset($bonus_rows['bonus_north'])          ? (float)$bonus_rows['bonus_north']          : $defaults['bonus_north'];

    // === Формула расчёта ===

    $hourly_rate = $norm_hours > 0 ? $base_salary / $norm_hours : 0;
    $salary_for_hours = $hourly_rate * $hours_worked;

    $harmful_bonus = $base_salary * $bonus_harmful;
    $experience_bonus = $base_salary * $bonus_experience;
    $special_work_bonus = $base_salary * $bonus_special_work;
    $night_bonus = $night_hours > 0 ? $night_hours * $hourly_rate * 0.4 : 0;

    $subtotal = $salary_for_hours + $harmful_bonus + $experience_bonus + $special_work_bonus + $night_bonus;
    $rayon_coeff_sum = $subtotal * $bonus_rayon;
    $north_bonus_sum = ($subtotal + $rayon_coeff_sum) * $bonus_north;
    $total_pay = $subtotal + $rayon_coeff_sum + $north_bonus_sum;

    $total_pay = round($total_pay, 2);

    // Ответ
    echo json_encode([
        'success' => true,
        'employee' => [
            'full_name' => $emp['full_name'],
            'position_title' => $emp['position_title'],
            'department' => $emp['department']
        ],
        'base_salary' => round($base_salary, 2),
        'norm_hours' => round($norm_hours, 1),
        'hours_worked' => round($hours_worked, 1),
        'night_hours' => round($night_hours, 1),
        'salary_for_hours' => round($salary_for_hours, 2),
        'harmful_bonus' => round($harmful_bonus, 2),
        'experience_bonus' => round($experience_bonus, 2),
        'special_work_bonus' => round($special_work_bonus, 2),
        'night_bonus' => round($night_bonus, 2),
        'subtotal' => round($subtotal, 2),
        'rayon_coeff_sum' => round($rayon_coeff_sum, 2),
        'north_bonus_sum' => round($north_bonus_sum, 2),
        'total_pay' => $total_pay,
        'experience_years' => $experience_years,
        'rates_used' => [
            'harmful' => $bonus_harmful,
            'experience' => $bonus_experience,
            'special_work' => $bonus_special_work,
            'rayon' => $bonus_rayon,
            'north' => $bonus_north,
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Ошибка расчёта: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка при расчёте'], JSON_UNESCAPED_UNICODE);
}
