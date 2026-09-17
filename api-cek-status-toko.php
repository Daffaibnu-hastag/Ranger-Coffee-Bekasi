<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

echo json_encode(['toko_open' => isTokoOpen()]);
exit;