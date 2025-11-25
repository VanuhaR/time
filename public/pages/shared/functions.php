<?php
// /public/pages/shared/functions.php

/**
 * Проверяет, активен ли текущий URL для указанного файла
 * Пример: isActive('dashboard.php') → ' class="active"'
 */
function isActive($filename) {
    $current = basename($_SERVER['SCRIPT_NAME']);
    return $current === $filename ? ' class="active"' : '';
}
