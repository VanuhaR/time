<?php
$title = "Расчётный лист";
$allowed_roles = ['admin', 'director', 'senior_nurse', 'employee'];
require_once __DIR__ . '/../shared/header.php';

$user = $_SESSION['user'];
$role = $user['role'];
$my_id = $user['id'];

// Параметры
$viewed_id = isset($_GET['id']) ? (int)$_GET['id'] : $my_id;
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');

// Ограничение доступа
if (!in_array($role, ['admin', 'director']) && $viewed_id != $my_id) {
    $viewed_id = $my_id;
}

// Загружаем сотрудников
$employees = [];
try {
    $stmt = $pdo->query("SELECT id, full_name FROM employees WHERE role NOT IN ('admin', 'director') ORDER BY full_name");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Ошибка загрузки сотрудников: " . $e->getMessage());
}
?>
<main class="main-content">
  <header class="main-header">
    <h1>Расчётный лист</h1>
  </header>

  <section class="content-section">
    <!-- Фильтры -->
    <div class="filters">
      <!-- Выбор сотрудника (только для admin/director) -->
      <?php if (in_array($role, ['admin', 'director'])): ?>
      <div class="form-group">
        <label for="employeeSelect">Сотрудник:</label>
        <select id="employeeSelect" class="select-custom">
          <option value="">— Выберите —</option>
          <?php foreach ($employees as $emp): ?>
            <option value="<?= $emp['id'] ?>" <?= $emp['id'] == $viewed_id ? 'selected' : '' ?>>
              <?= htmlspecialchars($emp['full_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <!-- Выбор периода -->
      <div class="form-group">
        <label for="period">Период:</label>
        <input type="month" id="period" class="input-custom"
          value="<?= sprintf('%04d-%02d', $year, $month) ?>">
      </div>

      <button id="loadPayroll" class="btn btn-primary">🔍 Показать</button>
    </div>

    <!-- Результат -->
    <div id="payrollResult">
      <div class="alert alert-info">Загрузка данных...</div>
    </div>
  </section>
</main>

<?php require_once __DIR__ . '/../shared/footer.php'; ?>
