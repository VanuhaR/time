<?php
// public/shared/auth_check.php

session_start();

// Проверка: залогинен ли пользователь
if (!isset($_SESSION['user'])) {
    header('Location: /public/pages/auth/login.php');
    exit;
}

// Подключаем Database.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/Config/Database.php';

try {
    $database = new Database();
    $pdo = $database->pdo;

    // Получаем полные данные сотрудника
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Пользователь удалён из БД
        session_destroy();
        header('Location: /public/pages/auth/login.php');
        exit;
    }

    // Теперь $user содержит все поля: full_name, position, gender, hire_date и т.д.

} catch (Exception $e) {
    error_log('Auth check error: ' . $e->getMessage());
    http_response_code(500);
    die('Ошибка сервера');
}
