<?php
// public/pages/shared/menu.php

function getSidebarMenu($currentPage = '') {
    // Исправлено: role берётся из $_SESSION['user']['role']
    $role = $_SESSION['user']['role'] ?? 'guest'; // ✅ Теперь правильно

    $fullMenu = [
        ['url' => '/public/pages/admin/dashboard.php',   'label' => '📊 Админ-панель',        'roles' => ['admin', 'senior_nurse', 'employee']],
        ['url' => '/public/pages/admin/employees.php',   'label' => '👥 Сотрудники',          'roles' => ['admin', 'director']],
        ['url' => '/public/pages/admin/schedule.php',    'label' => '📅 Общий график',        'roles' => ['admin', 'director', 'senior_nurse', 'employee']],
        ['url' => '/public/pages/admin/vacation.php',    'label' => '🏖️ Отпуска',             'roles' => ['admin', 'director', 'senior_nurse']],
        ['url' => '/public/pages/admin/payroll.php',     'label' => '💰 Расчётные листы',     'roles' => ['admin', 'director', 'senior_nurse', 'employee']],
        ['url' => '/public/pages/admin/settings.php',    'label' => '⚙️ Настройки',           'roles' => ['admin', 'director']],
    ];

    $menu = array_filter($fullMenu, function ($item) use ($role) {
        return in_array($role, $item['roles']);
    });

    $html = '';
    foreach ($menu as $item) {
        $active = strpos($_SERVER['REQUEST_URI'], $item['url']) !== false ? ' class="active"' : '';
        $html .= '<li><a href="' . $item['url'] . '"' . $active . '><span>' . $item['label'] . '</span></a></li>';
    }

    return $html;
}
?>
