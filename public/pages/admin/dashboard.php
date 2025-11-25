<?php
// Устанавливаем переменные
$title = "Личный кабинет";
$allowed_roles = ['admin', 'director', 'senior_nurse', 'employee'];

// Подключаем header
require_once __DIR__ . '/../shared/header.php';

$user = $user ?? [];
$role = $user['role'] ?? '';
$userId = $user['id'] ?? 0;
$fullName = $user['full_name'] ?? 'Не указано';

// Проверяем роль
if (!in_array($role, $allowed_roles)) {
    http_response_code(403);
    die("Доступ запрещён");
}
?>

<main class="main-content">
    <header class="main-header">
        <h1>Личный кабинет</h1>
        <p class="subtitle">Добро пожаловать, <?= htmlspecialchars($fullName) ?>!</p>
    </header>

    <div class="dashboard-grid">

        <!-- Личная информация -->
        <section class="card personal-card">
            <div class="card-header">
                <h3>Личная информация</h3>
            </div>
            <div class="personal-grid">
                <div class="avatar">
                    <div class="initials">—</div>
                </div>
                <div class="info-section">
                    <div class="name" id="name">Загрузка...</div>
                    <div class="role" id="role">—</div>
                    <div class="department" id="department">—</div>
                </div>
            </div>
            <div class="details">
                <div class="detail-item"><strong>Пол:</strong> <span id="gender">—</span></div>
                <div class="detail-item"><strong>Стаж:</strong> <span id="experience">—</span></div>
                <div class="detail-item"><strong>Дата найма:</strong> <span id="hire-date">—</span></div>
            </div>
        </section>

        <!-- Оклад -->
        <section class="card salary-card">
            <h3>Оклад (<?= date('m.Y') ?>)</h3>
            <div class="salary-total" id="salary-total">— ₽</div>
            <details class="salary-details">
                <summary>Разбивка</summary>
                <ul id="salary-breakdown"></ul>
            </details>
        </section>

        <!-- Статистика -->
        <section class="card stat-card">
            <h4>Статистика за месяц</h4>
            <div class="stats-grid">
                <!-- Отработанные часы -->
                <div class="stat-item">
                    <div class="label">Отработано / Норма</div>
                    <div class="progress-text">
                        <span id="hours-worked">0</span> / <span id="norm-hours">0</span> ч
                    </div>
                    <div class="progress-container">
                        <div class="progress-bar">
                            <div class="progress-fill" id="hours-progress"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Ближайшие смены -->
        <section class="card stat-card">
            <h4>Ближайшие смены</h4>
            <div id="next-shifts">
                <em>Загрузка...</em>
            </div>
        </section>

        <!-- Отпуска -->
        <section class="card vacation-card">
            <h3>Отпуска за <?= date('Y') ?> год</h3>
            <div id="vacations-list">
                <em>Загрузка...</em>
            </div>
        </section>

    </div>

    <!-- Переменные для JS -->
    <script>
        const CURRENT_USER_ID = <?= json_encode($userId) ?>;
        const CURRENT_YEAR = <?= json_encode(date('Y')) ?>;
        const CURRENT_MONTH = <?= json_encode((int)date('m')) ?>;
    </script>
</main>

<?php
require_once __DIR__ . '/../shared/footer.php';
?>
