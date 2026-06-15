<?php

require_once __DIR__ . '/../common.php';
require_admin();

$article = trim($_GET['article'] ?? '');
if ($article === '') {
    header('Location: index.php');
    exit;
}

$st = db()->prepare('SELECT name FROM Products WHERE article = ?');
$st->execute([$article]);
$product = $st->fetch();
if (!$product) {
    $_SESSION['flash_error'] = 'Товар не найден.';
    header('Location: index.php');
    exit;
}

$chk = db()->prepare('SELECT COUNT(*) FROM OrderItems WHERE product_article = ?');
$chk->execute([$article]);
if ((int)$chk->fetchColumn() > 0) {
    $_SESSION['flash_error'] = 'Нельзя удалить товар: он присутствует в заказе.';
    header('Location: index.php');
    exit;
}

$photoSt = db()->prepare('SELECT photo FROM Products WHERE article = ?');
$photoSt->execute([$article]);
$photo = $photoSt->fetchColumn();

db()->prepare('DELETE FROM Products WHERE article = ?')->execute([$article]);

if ($photo) {
    $path = realpath(__DIR__ . '/../images') . DIRECTORY_SEPARATOR . $photo;
    if (is_file($path) && !preg_match('/^\d+\.jpg$/i', $photo)) {
        @unlink($path);
    }
}

$_SESSION['flash_ok'] = 'Товар «' . $product['name'] . '» удалён.';
header('Location: index.php');
exit;
