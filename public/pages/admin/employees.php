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
      <select id="blockFilter" class="filter-select">
        <option value="">Все блоки</option>
        <option value="1">1 блок</option>
        <option value="1-2">1-2 блок</option>
        <option value="2">2 блок</option>
        <option value="2-3">2-3 блок</option>
        <option value="3">3 блок</option>
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
            <th>Блок</th>
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

      <label for="block" id="blockLabel">Блок</label>
      <select id="block">
        <option value="">Не указан</option>
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

<!-- Скрипт: динамическое обновление блоков и скрытие для сиделки/ванщицы -->
<script>
  // Доступные блоки по этажам
  const blocksByFloor = {
    'floor_1': [
      { value: '1', label: '1 блок' },
      { value: '1-2', label: '1-2 блок' },
      { value: '2', label: '2 блок' },
      { value: '2-3', label: '2-3 блок' },
      { value: '3', label: '3 блок' }
    ],
    'floor_2': [
      { value: '1', label: '1 блок' },
      { value: '2', label: '2 блок' },
      { value: '3', label: '3 блок' }
    ]
  };

  // Должности, у которых нет блока
  const noBlockPositions = ['sidelka', 'vanshiza'];

  // Функция обновления списка блоков
  function updateBlockOptions() {
    const blockSelect = document.getElementById('block');
    const floor = document.getElementById('department').value;

    blockSelect.innerHTML = '<option value="">Не указан</option>';

    if (blocksByFloor[floor]) {
      blocksByFloor[floor].forEach(block => {
        const option = document.createElement('option');
        option.value = block.value;
        option.textContent = block.label;
        blockSelect.appendChild(option);
      });
    }
  }

  // Функция показа/скрытия блока в зависимости от должности
  function updateBlockVisibility() {
    const position = document.getElementById('position').value;
    const blockLabel = document.getElementById('blockLabel');
    const blockSelect = document.getElementById('block');

    if (noBlockPositions.includes(position)) {
      blockSelect.value = '';
      blockLabel.style.opacity = '0.5';
      blockSelect.disabled = true;
    } else {
      blockLabel.style.opacity = '1';
      blockSelect.disabled = false;
    }
  }

  // Обработчики событий

  // При смене этажа — обновить блоки
  document.getElementById('department').addEventListener('change', updateBlockOptions);

  // При смене должности — скрыть/показать блок
  document.getElementById('position').addEventListener('change', updateBlockVisibility);

  // При открытии модального окна — обновить отображение
  function refreshForm() {
    updateBlockOptions();
    updateBlockVisibility();
  }

  // Кнопка "Добавить"
  document.getElementById('addEmployeeBtn').addEventListener('click', () => {
    document.getElementById('employeeForm').reset();
    document.getElementById('employeeId').value = '';
    document.getElementById('password').required = true;
    refreshForm();
  });

  // При редактировании — после загрузки данных
  // (вызывается в employees.js после установки position)
  // Добавим глобальную функцию
  window.refreshForm = refreshForm;

  // Инициализация при загрузке
  document.addEventListener('DOMContentLoaded', () => {
    const department = document.getElementById('department');
    if (department.value) {
      department.dispatchEvent(new Event('change'));
    }
    updateBlockVisibility();
  });

  // Закрытие модального окна
  document.querySelector('.close')?.addEventListener('click', () => {
    document.getElementById('employeeModal').style.display = 'none';
  });

  window.addEventListener('click', (e) => {
    const modal = document.getElementById('employeeModal');
    if (e.target === modal) {
      modal.style.display = 'none';
    }
  });

  // Кнопка "Отмена"
  document.getElementById('cancelBtn')?.addEventListener('click', () => {
    document.getElementById('employeeModal').style.display = 'none';
  });
</script>
