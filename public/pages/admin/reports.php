<?php
$title = "Управление сотрудниками";
$allowed_roles = ['admin'];

// Определяем текущую страницу
$current_page = $_SERVER['REQUEST_URI'];

function isActive($path) {
    global $current_page;
    return strpos($current_page, $path) !== false ? ' class="active"' : '';
}

// Формируем меню
$sidebar_menu = '
  <li><a href="/public/pages/admin/dashboard.php"' . isActive('dashboard.php') . '><span>📊 Админ-панель</span></a></li>
  <li><a href="/public/pages/admin/employees.php"' . isActive('employees.php') . '><span>👥 Сотрудники</span></a></li>
  <li><a href="/public/pages/admin/schedule.php"' . isActive('schedule.php') . '><span>📅 Общий график</span></a></li>
  <li><a href="/public/pages/admin/vacation.php"' . isActive('vacation.php') . '><span>🏖️ Отпуска</span></a></li>
  <li><a href="/public/pages/admin/reports.php"' . isActive('reports.php') . '><span>📈 Отчёты</span></a></li>
  <li><a href="/public/pages/admin/settings.php"' . isActive('settings.php') . '><span>⚙️ Настройки</span></a></li>
  <li><a href="/public/pages/admin/payslips.php"' . isActive('payslips.php') . '><span>💰 Расчётные листы</span></a></li>
';

require_once __DIR__ . '/../shared/header.php';
?>



<main class="main-content">
  <header class="main-header">
    <h1>Аналитика по сотрудникам</h1>
  </header>

  <section class="content-section">
    <div class="chart-grid">
      <div class="chart-container">
        <h3>Сотрудники по отделам</h3>
        <canvas id="departmentChart"></canvas>
      </div>

      <div class="chart-container">
        <h3>Распределение по стажу</h3>
        <canvas id="experienceChart"></canvas>
      </div>

      <div class="chart-container">
        <h3>Найм по месяцам (2025)</h3>
        <canvas id="hiringChart"></canvas>
      </div>
    </div>
  </section>
</main>

<!-- Подключаем Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const API_URL = '/public/api/employees.php';

async function loadEmployees() {
  const response = await fetch(`${API_URL}?action=list`);
  return await response.json();
}

// --- Форматирование стажа ---
function getExperienceGroup(date) {
  if (!date) return 'Без даты';
  const start = new Date(date);
  const diffYears = (new Date() - start) / (1000 * 60 * 60 * 24 * 365.25);
  if (diffYears < 1) return 'До 1 года';
  if (diffYears < 3) return '1–3 года';
  if (diffYears < 5) return '3–5 лет';
  return '5+ лет';
}

// --- Группировка по отделам ---
function getDepartmentLabel(dep) {
  const map = {
    'floor_1': '1 этаж',
    'floor_2': '2 этаж',
    'nurses': 'Медсёстры',
    'cleaners': 'Уборщики',
    'caregivers': 'Сиделки'
  };
  return map[dep] || 'Прочие';
}

// --- Загрузка и построение графиков ---
async function initCharts() {
  const employees = await loadEmployees();

  // 1. График: по отделам
  const departments = employees.reduce((acc, emp) => {
    const dep = emp.department || 'general';
    acc[getDepartmentLabel(dep)] = (acc[getDepartmentLabel(dep)] || 0) + 1;
    return acc;
  }, {});

  new Chart(document.getElementById('departmentChart'), {
    type: 'pie',
    data: {
      labels: Object.keys(departments),
      datasets: [{
        data: Object.values(departments),
        backgroundColor: ['#4CAF50', '#2196F3', '#FF9800', '#9C27B0', '#FF5722']
      }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
  });

  // 2. График: по стажу
  const experience = employees.reduce((acc, emp) => {
    const group = getExperienceGroup(emp.created_at?.split(' ')[0]);
    acc[group] = (acc[group] || 0) + 1;
    return acc;
  }, {});

  new Chart(document.getElementById('experienceChart'), {
    type: 'bar',
    data: {
      labels: Object.keys(experience),
      datasets: [{
        label: 'Количество',
        data: Object.values(experience),
        backgroundColor: '#4CAF50'
      }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
  });

  // 3. График: найм по месяцам
  const hires = employees
    .filter(emp => emp.created_at)
    .map(emp => emp.created_at.split(' ')[0]) // дата
    .map(date => new Date(date).toISOString().slice(0, 7)); // '2025-04'

  const monthly = hires.reduce((acc, month) => {
    acc[month] = (acc[month] || 0) + 1;
    return acc;
  }, {});

  const months = Array.from({ length: 12 }, (_, i) => {
    const d = new Date(2025, i);
    return d.toISOString().slice(0, 7);
  });

  const hiresData = months.map(m => monthly[m] || 0);

  new Chart(document.getElementById('hiringChart'), {
    type: 'line',
    data: {
      labels: months.map(m => m.slice(-2) + ' мес'),
      datasets: [{
        label: 'Нанято в месяц',
        data: hiresData,
        borderColor: '#2196F3',
        tension: 0.3
      }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
  });
}

// Запуск
initCharts();
</script>
