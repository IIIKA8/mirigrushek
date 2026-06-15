<?php

require_once __DIR__ . '/../common.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php?page=orders');
    exit;
}

db()->prepare('DELETE FROM Orders WHERE id = ?')->execute([$id]);
$_SESSION['flash_ok'] = "Заказ №$id удалён.";
header('Location: index.php?page=orders');
exit;
