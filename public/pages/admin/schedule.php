<?php
// public/pages/admin/schedule.php
$title = "График рабочих смен";
$allowed_roles = ['admin', 'senior_nurse', 'employee', 'director'];
require_once __DIR__ . '/../shared/header.php';

// Определяем, может ли редактировать
$canEdit = in_array($user['role'] ?? '', ['admin', 'senior_nurse']);
?>

<main class="main-content">
  <header class="main-header">
    <h1>График смен</h1>
    <div class="schedule-controls">
      <button id="prevMonth" class="btn btn-outline">&lt; Назад</button>
      <h2 id="monthLabel">Загрузка...</h2>
      <button id="nextMonth" class="btn btn-outline">Вперёд &gt;</button>
    </div>
  </header>

  <section class="content-section">
    <!-- Панель: Фильтр групп — ВСЕГДА виден -->
    <div class="schedule-toolbar">
      <!-- Фильтр групп — доступен всем -->
      <div class="toolbar-group">
        <label for="groupFilter">Группы</label>
        <select id="groupFilter" class="select-sm">
          <option value="all">Все сотрудники</option>
          <option value="cleaners">1. Санитары, ассистенты</option>
          <option value="floor1_staff">2. Персонал 1 этажа</option>
          <option value="floor2_staff">3. Персонал 2 этажа</option>
          <option value="nurses">4. Медперсонал</option>
        </select>
      </div>

      <!-- Шаблоны и смены — теперь ВИДНЫ ВСЕМ (для дебага) -->
      <div class="toolbar-quick-controls">
        <div class="shift-templates">
          <span class="label">Шаблоны:</span>
          <div class="template-buttons">
            <button class="btn btn-template" data-template="pattern1" title="Через 2 дня: 10ч, 14ч, отдых">
              2/3
            </button>
            <button class="btn btn-template" data-template="pattern2" title="2 дня работы, 2 дня отдыха">
              2/2
            </button>
          </div>
        </div>

        <div class="shift-selector">
          <span class="label">Смена:</span>
          <div class="shift-buttons">
            <button class="shift-btn" data-shift="10ч">10ч</button>
            <button class="shift-btn" data-shift="14ч">14ч</button>
            <button class="shift-btn" data-shift="Б">Б</button>
            <button class="shift-btn" data-shift="ОТ">ОТ</button>
            <button class="shift-btn" data-shift="off">—</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Норма месяца -->
    <div class="monthly-norm">
      <strong>Норма:</strong> <span id="monthlyNorm">???</span> ч
    </div>

    <!-- Таблица графика -->
    <div class="table-container" id="print-area">
      <table class="schedule-table" id="scheduleTable">
        <thead>
          <tr>
            <th></th>
            <th>Сотрудник</th>
            <!-- заполняется JS -->
          </tr>
        </thead>
        <tbody id="scheduleBody">
          <tr><td colspan="33" class="loading">Загрузка...</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Кнопки действий — теперь ВИДНЫ ВСЕМ (для дебага) -->
    <div class="schedule-actions">
      <button id="clearSchedule" class="btn btn-danger btn-lg">
        <i class="icon">🗑️</i> Очистить график
      </button>
      <button id="printSchedule" class="btn btn-primary btn-lg">
        <i class="icon">🖨️</i> Печать
      </button>
    </div>
  </section>
</main>

<!-- Сначала передаём флаг редактирования -->
<script>
  const CAN_EDIT = <?= json_encode($canEdit) ?>;
  console.log("✅ PHP: CAN_EDIT =", CAN_EDIT, "| role =", <?= json_encode($user['role'] ?? 'guest') ?>);
</script>

<!-- Потом подключаем скрипт -->
<script src="/public/js/schedule-v2.js?v=<?= time() ?>"></script>

<?php require_once __DIR__ . '/../shared/footer.php'; ?>
