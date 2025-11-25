<?php
// --- public/logout.php ---
session_start(); // ✅ Добавлено!

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Config/Database.php';

try {
    $database = new Database();
    $auth = new Auth($database->pdo);
    $auth->logout();
} catch (Exception $e) {
    error_log("Ошибка при выходе: " . $e->getMessage());
}

// Перенаправляем на страницу входа
header('Location: /public/login.php');
exit;
