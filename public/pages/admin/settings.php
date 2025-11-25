<?php
$title = "Настройки";
$allowed_roles = ['admin', 'director'];
require_once __DIR__ . '/../shared/header.php';
?>

<main class="main-content">
  <header class="main-header">
    <h1>Настройки</h1>
  </header>

  <section class="content-section">
    <!-- Вкладки -->
    <div class="tabs" role="tablist">
      <button 
        class="tab-button active" 
        data-tab="norms" 
        type="button"
        role="tab"
        aria-selected="true"
        aria-controls="tab-norms"
      >
        Норма часов
      </button>
      <button 
        class="tab-button" 
        data-tab="salary" 
        type="button"
        role="tab"
        aria-selected="false"
        aria-controls="tab-salary"
      >
        Оклады должностей
      </button>
      <button 
        class="tab-button" 
        data-tab="other" 
        type="button"
        role="tab"
        aria-selected="false"
        aria-controls="tab-other"
      >
        Настройка доплат
      </button>
    </div>

    <!-- Панели вкладок -->
    <div class="tab-content">
      <!-- Вкладка: Норма часов -->
      <div id="tab-norms" class="tab-pane active" role="tabpanel">
        <h3>Норма часов по месяцам</h3>
        <p>Установите норму отработанных часов для каждого месяца отдельно по полу.</p>

        <div class="form-group">
          <label for="yearSelect">Год:</label>
          <select id="yearSelect" class="year-select">
            <?php
            $currentYear = date('Y');
            for ($y = $currentYear - 5; $y <= $currentYear + 5; $y++):
            ?>
              <option value="<?= $y ?>" <?= $y == $currentYear ? 'selected' : '' ?>>
                <?= $y ?>
              </option>
            <?php endfor; ?>
          </select>
        </div>

        <form id="normsForm" novalidate>
          <table class="settings-table">
            <thead>
              <tr>
                <th>Месяц</th>
                <th>Мужчины (ч)</th>
                <th>Женщины (ч)</th>
              </tr>
            </thead>
            <tbody id="normsTableBody"></tbody>
          </table>
          <div class="action-buttons">
            <button type="submit" class="btn-action" data-color="blue">💾 Сохранить нормы</button>
          </div>
        </form>
      </div>

      <!-- Вкладка: Оклады должностей -->
      <div id="tab-salary" class="tab-pane" role="tabpanel">
        <h3>Оклады сотрудников по должностям</h3>
        <p>Установите базовый оклад для каждой должности. Эти значения используются при расчёте заработной платы.</p>

        <form id="salaryForm" novalidate>
          <table class="settings-table">
            <thead>
              <tr>
                <th>Должность</th>
                <th>Текущий оклад (₽)</th>
                <th>Новый оклад</th>
              </tr>
            </thead>
            <tbody id="salaryTableBody"></tbody>
          </table>
          <div class="action-buttons">
            <button type="submit" class="btn-action" data-color="green">💰 Сохранить оклады</button>
          </div>
        </form>
      </div>

      <!-- Вкладка: Настройка доплат -->
      <div id="tab-other" class="tab-pane" role="tabpanel">
        <h3>Настройка доплат</h3>
        <p>Настройка процентных доплат для расчёта заработной платы.</p>

        <form id="bonusRatesForm" novalidate>
          <table class="settings-table">
            <thead>
              <tr>
                <th>Параметр</th>
                <th>Текущее значение (%)</th>
                <th>Новое значение (%)</th>
              </tr>
            </thead>
            <tbody id="bonusRatesBody">
              <tr>
                <td><strong>Доплата за вредность</strong></td>
                <td id="current_harmful">...</td>
                <td><input type="number" name="harmful" min="0" max="100" step="0.5" placeholder="5.0"></td>
              </tr>
              <tr>
                <td><strong>Надбавка за стаж</strong></td>
                <td id="current_experience">...</td>
                <td><input type="number" name="experience" min="0" max="100" step="0.5" placeholder="20.0"></td>
              </tr>
              <tr>
                <td><strong>Доплата за характер работы</strong></td>
                <td id="current_special_work">...</td>
                <td><input type="number" name="special_work" min="0" max="100" step="0.5" placeholder="6.0"></td>
              </tr>
              <tr>
                <td><strong>Районный коэффициент</strong></td>
                <td id="current_rayon">...</td>
                <td><input type="number" name="rayon" min="0" max="200" step="0.5" placeholder="100.0"></td>
              </tr>
              <tr>
                <td><strong>Северная надбавка</strong></td>
                <td id="current_north">...</td>
                <td><input type="number" name="north" min="0" max="100" step="0.5" placeholder="50.0"></td>
              </tr>
            </tbody>
          </table>
          <div class="action-buttons">
            <button type="submit" class="btn-action" data-color="purple">🔧 Сохранить доплаты</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</main>

<!-- JavaScript будет загружен ниже -->
<?php require_once __DIR__ . '/../shared/footer.php'; ?>
