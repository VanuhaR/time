<?php
// public/pages/admin/employees.php
$title = "Сотрудники";
$allowed_roles = ['admin', 'director'];
require_once __DIR__ . '/../shared/header.php';
?>

<main class="main-content">
  <header class="main-header">
    <h1>Управление сотрудниками</h1>
  </header>

  <section class="content-section">
    <!-- Панель инструментов -->
    <div class="toolbar">
      <h2>Сотрудники</h2>
      <div class="actions">
        <button id="addEmployeeBtn" class="btn btn-primary">
          <i class="icon">➕</i> Добавить
        </button>
        <button id="exportExcelBtn" class="btn btn-success">
          <i class="icon">📤</i> Экспорт XLSX
        </button>
        <button id="downloadTemplateBtn" class="btn btn-info">
          <i class="icon">📥</i> Скачать шаблон
        </button>
        <label class="btn btn-secondary">
          <i class="icon">📁</i> Импорт XLSX
          <input type="file" id="importExcel" accept=".xlsx" hidden />
        </label>
      </div>
    </div>

    <!-- Фильтры и поиск -->
    <div class="filters">
      <input type="text" id="searchInput" class="search-input" placeholder="🔍 Поиск по ФИО..." />
      <select id="positionFilter" class="filter-select">
        <option value="">Все должности</option>
        <?php
        $positions = [
          'sanitar' => 'Санитар',
          'sanitarka' => 'Санитарка',
          'sidelka' => 'Сиделка',
          'vanshiza' => 'Ванщица',
          'assistant' => 'Ассистент',
          'nurse' => 'Медсестра',
          'senior_nurse' => 'Старшая медсестра',
          'director' => 'Директор'
        ];
        foreach ($positions as $code => $title):
        ?>
          <option value="<?= $code ?>"><?= htmlspecialchars($title) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Сообщение -->
    <div id="message" class="message" style="display: none;"></div>

    <!-- Таблица -->
    <div class="table-responsive">
      <table class="modern-table" id="employeesTable">
        <thead>
          <tr>
            <th>№</th>
            <th>ФИО</th>
            <th>Телефон</th>
            <th>Должность</th>
            <th>Отдел</th>
            <th>Дата найма</th>
            <th>Стаж</th>
            <th>Роль</th>
            <th>Пол</th>
            <th>Действия</th>
          </tr>
        </thead>
        <tbody id="employeeList"></tbody>
      </table>
    </div>
  </section>
</main>

<!-- Модальное окно -->
<div id="employeeModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h3 id="modalTitle">Добавить сотрудника</h3>
    <form id="employeeForm" class="form-container">
      <input type="hidden" id="employeeId" />

      <label for="fullName">ФИО</label>
      <input type="text" id="fullName" placeholder="Иванов Иван Иванович" required />

      <label for="phone">Телефон</label>
      <input type="tel" id="phone" placeholder="+7 (999) 123-45-67" required />

      <label for="role">Роль</label>
      <select id="role" required>
        <option value="employee">Сотрудник</option>
        <option value="senior_nurse">Старшая медсестра</option>
        <option value="director">Директор</option>
      </select>

      <label for="position">Должность</label>
      <select id="position" required>
        <option value="">Выберите должность</option>
        <?php foreach ($positions as $code => $title): ?>
          <option value="<?= $code ?>"><?= htmlspecialchars($title) ?></option>
        <?php endforeach; ?>
      </select>

      <label for="gender">Пол</label>
      <select id="gender" required>
        <option value="">Выберите пол</option>
        <option value="male">Мужской</option>
        <option value="female">Женский</option>
      </select>

      <label for="department">Отдел</label>
      <select id="department">
        <option value="">Не указан</option>
        <option value="floor_1">1 этаж</option>
        <option value="floor_2">2 этаж</option>
      </select>

      <label for="hire_date">Дата найма</label>
      <input type="date" id="hire_date" />

      <label for="password">Пароль</label>
      <input type="password" id="password" placeholder="Оставьте пустым, если не меняете" />
      <div class="password-hint">Оставьте пустым, если не хотите менять пароль</div>

      <div class="form-actions">
        <button type="button" class="btn btn-secondary" id="cancelBtn">Отмена</button>
        <button type="submit" class="btn btn-primary">Сохранить</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../shared/footer.php'; ?>
