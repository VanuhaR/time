<?php
// ВАЖНО: Сохранить в UTF-8 БЕЗ BOM!

if (ob_get_level()) ob_clean();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="шаблон_импорта_сотрудников.csv"');

$handle = fopen('php://output', 'w');

// Добавляем BOM — чтобы Excel читал UTF-8
fwrite($handle, "\xEF\xBB\xBF");

// Заголовки
fputcsv($handle, [
    'ФИО',
    'Телефон',
    'Должность',      // → sanitar
    'Отдел',          // → floor_1
    'Роль',           // → employee
    'Дата найма',     // → 16.11.2025
    'Пол'             // → male/female
], ';');

// Пример
fputcsv($handle, [
    'Бычков Илья Демидович',
    '+7 (902) 362-64-76',
    'sanitar',
    'floor_1',
    'employee',
    '16.11.2025',
    'male'
], ';');

fclose($handle);
exit;
