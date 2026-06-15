<?php

require_once __DIR__ . '/common.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: /frontend/orders.php');
    exit;
}

execute_prepared('DELETE FROM Orders WHERE id = ?', 'i', $id);
$_SESSION['flash_ok'] = "Заказ №$id удалён.";
header('Location: /frontend/orders.php');
exit;
