<?php
session_start();

// Если уже залогинен
if (isset($_SESSION['user'])) {
    header('Location: /public/index.php');
    exit;
}

// Генерация CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Вход в систему</title>
    
    <!-- Подключаем шрифты -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    
    <!-- ЕДИНСТВЕННЫЙ файл стилей -->
    <link rel="stylesheet" href="/public/css/pages/login.css" />
    
    <!-- Активация тёмной темы (если включена) -->
    <script>
        // Проверяем предпочтения пользователя
        if (localStorage.getItem('theme') === 'dark' || 
            (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark-mode');
        }
    </script>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="heading">Вход</div>

        <form id="loginForm" action="/public/auth.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>" />

            <input required class="input" type="tel" name="phone" id="phone" placeholder="Телефон" autocomplete="tel" />
            <input required class="input" type="password" name="password" id="password" placeholder="Пароль" autocomplete="current-password" />
            <input class="login-button" type="submit" value="Войти" />
        </form>

        <span class="agreement">
            <a href="#">Политика конфиденциальности и пользовательское соглашение</a>
        </span>
    </div>

    <!-- Подключаем JS -->
    <script src="/public/js/theme-toggle.js" defer></script>
    <script src="/public/js/login-form.js" defer></script>
</body>
</html>
