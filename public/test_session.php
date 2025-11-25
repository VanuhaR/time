<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'session_status' => session_status(),
    'session_user' => $_SESSION['user'] ?? null,
    'has_id' => isset($_SESSION['user']['id']),
    'user_data' => $_SESSION['user'] ?? 'not set'
], JSON_UNESCAPED_UNICODE);
