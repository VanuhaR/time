<?php
// public/pages/shared/footer.php
// Определяем текущую страницу (без .php)
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php');
$user = $_SESSION['user'] ?? null;
?>

<!-- JS — в конце body -->
<!-- Глобальные скрипты -->
<script src="/public/js/menu-toggle.js"></script>

<!-- Страница: dashboard -->
<?php if ($current_page === 'dashboard'): ?>
  <script src="/public/js/dashboard.js"></script>
<?php endif; ?>

<!-- Страница: employees -->
<?php if ($current_page === 'employees'): ?>
  <script src="/public/js/employees.js"></script>
<?php endif; ?>

<!-- Страница: schedule -->
<?php if ($current_page === 'schedule'): ?>
  <script src="/public/js/schedule-v2.js?v=<?= time() ?>"></script>
<?php endif; ?>

<!-- Страница: reports -->
<?php if ($current_page === 'reports'): ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="/public/js/reports.js"></script>
<?php endif; ?>

<!-- Страница: settings -->
<?php if ($current_page === 'settings'): ?>
  <script src="/public/js/settings.js"></script>
<?php endif; ?>

<!-- Страница: vacation -->
<?php if ($current_page === 'vacation'): ?>
  <script src="/public/js/libs/xlsx.full.min.js"></script>
  <script src="/public/js/vacation.js"></script>
<?php endif; ?>

<!-- Страница: payroll -->
<?php if ($current_page === 'payroll' && $user): ?>
  <script>
    document.body.dataset.role = '<?= htmlspecialchars($user['role']) ?>';
    document.body.dataset.userId = '<?= (int)$user['id'] ?>';
  </script>
  <script src="/public/js/payroll.js"></script>
<?php endif; ?>

<!-- Подвал -->
<footer class="main-footer">
  <p>&copy; <?= date('Y') ?> Учёт времени. Все права защищены.</p>
</footer>

</main> <!-- Конец .main-wrapper -->
</body>
</html>
