<?php
// public/pages/shared/header.php
session_start();

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../../../src/Config/Database.php';
require_once __DIR__ . '/../../../src/Core/Auth.php';
require_once __DIR__ . '/menu.php';

try {
    $database = new Database();
    $pdo = $database->pdo;
    $auth = new Auth($pdo);
} catch (Exception $e) {
    error_log("Ошибка инициализации БД или Auth: " . $e->getMessage());
    die('Серверная ошибка. Попробуйте позже.');
}

if (!$auth->isLoggedIn()) {
    header('Location: /public/login.php');
    exit;
}

global $user;
$user = $auth->user();

$role = $user['role'] ?? 'guest';
$allowed_roles = $allowed_roles ?? ['admin'];
if (!in_array($role, $allowed_roles)) {
    http_response_code(403);
    die('Доступ запрещён');
}

// Определяем текущую страницу
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php');

// Определяем, включена ли тёмная тема
$is_dark_mode = isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark';

// Список CSS-файлов для каждой страницы
$available_css = [
    'dashboard'  => 'dashboard.css',
    'schedule'   => 'schedule.css',
    'vacation'   => 'vacation.css',
    'employees'  => 'employees.css',
    'settings'   => 'settings.css',
    'payroll'    => 'payroll.css',
    'login'      => 'login.css',
];

// Определяем, какой CSS нужен
$current_css = $available_css[$current_page] ?? null;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?= htmlspecialchars($title ?? 'Панель') ?> | Учёт времени</title>

    <!-- Подключаем шрифты -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- 🔥 БАЗОВЫЕ СТИЛИ (всегда) -->
    <link rel="stylesheet" href="/public/css/basic-styles/base.css">

    <!-- 🔧 МАКЕТ (всегда) -->
    <link rel="stylesheet" href="/public/css/layout/sidebar.css">

    <!-- 📄 СТРАНИЦА: только нужный CSS -->
    <?php if ($current_css): ?>
        <link rel="stylesheet" href="/public/css/pages/<?= htmlspecialchars($current_css) ?>">
    <?php endif; ?>

    <!-- 📱 АДАПТИВНОСТЬ (всегда) -->
    <link rel="stylesheet" href="/public/css/adaptability/responsive.css">

    <!-- 🌙 ТЁМНАЯ ТЕМА (всегда последней!) -->
    <link rel="stylesheet" href="/public/css/basic-styles/dark-theme.css">
</head>
<body class="<?= $is_dark_mode ? 'dark-mode' : '' ?>">

    <!-- Кнопка мобильного меню -->
    <button id="menuToggle" class="menu-toggle" aria-label="Открыть меню">☰</button>

    <!-- Шапка с ФИО -->
    <header class="main-header">
        <div class="logo">
            <a href="/public/pages/admin/dashboard.php">🕒 Учёт времени</a>
        </div>

        <nav class="main-nav">
            <!-- Можно добавить пункты при необходимости -->
        </nav>

        <!-- Приветствие с градиентным именем -->
        <div class="header-user">
            Добро пожаловать, 
            <span class="header-user-name">
                <?= htmlspecialchars($user['full_name'] ?? $user['name'] ?? 'Пользователь') ?>
            </span>
        </div>
    </header>

    <!-- Левое меню -->
    <nav class="sidebar" id="sidebar">
        <button id="toggleSidebar" class="sidebar-toggle" aria-label="Свернуть меню">
            <svg class="toggle-icon" width="16" height="16" viewBox="0 0 24 24" fill="none">
                <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>

        <div class="sidebar-header">
            <h2 class="sidebar-title">Меню</h2>
        </div>

        <ul class="sidebar-menu">
            <?= getSidebarMenu() ?>
        </ul>

        <div class="logout-container">
            <a href="/public/logout.php" class="logout-btn">🚪 Выйти</a>
        </div>
    </nav>

    <!-- Основной контент -->
    <main class="main-wrapper" id="mainWrapper">
