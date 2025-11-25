<?php
$title = "Управление отпусками";
$allowed_roles = ['admin', 'senior_nurse', 'director'];
require_once __DIR__ . '/../shared/header.php';
?>

<main class="main-content">
  <!-- Вкладки -->
  <div class="tabs" role="tablist">
    <button 
      class="tab-button active" 
      data-tab="list" 
      type="button" 
      role="tab" 
      aria-selected="true" 
      aria-controls="tab-list"
    >
      Список отпусков
    </button>
    <button 
      class="tab-button" 
      data-tab="calendar" 
      type="button" 
      role="tab" 
      aria-selected="false" 
      aria-controls="tab-calendar"
    >
      Годовой календарь
    </button>
  </div>

  <div class="tab-content">
    <!-- ВКЛАДКА: Список отпусков -->
    <div id="tab-list" class="tab-pane active" role="tabpanel" aria-labelledby="tab-button-list">
      <h3>Список отпусков сотрудников</h3>
      <p>Добавляйте и управляйте отпусками сотрудников. Доступно для администратора и старшей медсестры.</p>

      <!-- Уведомления -->
      <div id="notification" class="notification" style="display: none;"></div>

      <!-- Панель инструментов -->
      <div class="toolbar">
        <div class="toolbar-left">
          <button id="addVacationBtn" class="btn btn-primary">➕ Добавить</button>
          <button id="importVacationBtn" class="btn btn-secondary">📥 Импорт XLSX</button>
          <button id="exportCSV" class="btn btn-outline">📤 Экспорт шаблона</button>
        </div>
        <div class="toolbar-right">
          <select id="yearFilter" class="year-select" aria-label="Фильтр по году">
            <?php
            $currentYear = date('Y');
            for ($y = $currentYear - 3; $y <= $currentYear + 3; $y++):
            ?>
              <option value="<?= $y ?>" <?= $y == $currentYear ? 'selected' : '' ?>>
                <?= $y ?> год
              </option>
            <?php endfor; ?>
          </select>
        </div>
      </div>

      <!-- Таблица отпусков -->
      <div class="table-responsive">
        <table id="vacationList" class="modern-table">
          <thead>
            <tr>
              <th>ФИО</th>
              <th>Должность</th>
              <th>Отдел</th>
              <th>Периоды отпусков</th>
              <th>Действия</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>

    <!-- ВКЛАДКА: Календарь -->
    <div id="tab-calendar" class="tab-pane" role="tabpanel" aria-labelledby="tab-button-calendar">
      <h3>Календарь отпусков — <span id="calendarYearDisplay"><?= $currentYear ?></span> год</h3>
      <p>Визуальное отображение отпусков по месяцам. Сегодняшний день и отпуска выделены цветом.</p>

      <!-- Фильтр по должности -->
      <div class="filter-controls">
        <label for="positionFilter">Фильтр по должности</label>
        <select id="positionFilter" class="year-filter">
          <option value="">Все должности</option>
        </select>
      </div>

      <!-- Легенда -->
      <div class="legend-container">
        <button id="toggleLegend" class="btn-toggle">▼ Легенда</button>
        <div id="legend" class="legend"></div>
      </div>

      <!-- Годовой календарь -->
      <div id="yearCalendar"></div>
    </div>
  </div>
</main>

<!-- МОДАЛЬНОЕ ОКНО: Добавить/Редактировать отпуск -->
<div id="vacationModal" class="modal">
  <div class="modal-content">
    <h3 id="modalTitle">Добавить отпуск</h3>
    <form id="vacationForm">
      <input type="hidden" id="requestId" />
      
      <div class="form-group">
        <label for="employeeSelect">Сотрудник</label>
        <select id="employeeSelect" required></select>
      </div>
      
      <div class="form-group">
        <label for="startDate">Дата начала</label>
        <input type="date" id="startDate" required />
      </div>
      
      <div class="form-group">
        <label for="endDate">Дата окончания</label>
        <input type="date" id="endDate" required />
      </div>
      
      <div class="form-group">
        <label>Дней отпуска</label>
        <input type="number" id="dayCount" readonly />
      </div>
      
      <div class="modal-actions">
        <button type="button" id="cancelBtn" class="btn btn-secondary">Отмена</button>
        <button type="submit" class="btn btn-primary">Сохранить</button>
      </div>
    </form>
  </div>
</div>

<!-- МОДАЛЬНОЕ ОКНО: Импорт XLSX -->
<div id="importModal" class="modal">
  <div class="modal-content">
    <h3>Импорт отпусков из XLSX</h3>
    <form id="importForm">
      <div class="form-group">
        <input 
          type="file" 
          id="importFile" 
          accept=".xlsx" 
          required 
          aria-label="Выберите файл XLSX для импорта" 
        />
      </div>
      <div class="modal-actions">
        <button type="button" id="cancelImportBtn" class="btn btn-secondary">Отмена</button>
        <button type="submit" class="btn btn-primary">Импортировать</button>
      </div>
    </form>
  </div>
</div>

<!-- Подключение подвала -->
<?php require_once __DIR__ . '/../shared/footer.php'; ?>
