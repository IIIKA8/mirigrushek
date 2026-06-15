<?php

require_once __DIR__ . '/common.php';
require_admin();

$article = trim($_GET['article'] ?? '');
if ($article === '') {
    header('Location: /frontend/view_1.php');
    exit;
}

$product = select_one_prepared('SELECT name FROM Products WHERE article = ?', 's', $article);
if (!$product) {
    $_SESSION['flash_error'] = 'Товар не найден.';
    header('Location: /frontend/view_1.php');
    exit;
}

$chk = select_one_prepared(
    'SELECT COUNT(*) AS cnt FROM OrderItems WHERE product_article = ?',
    's',
    $article
);
if ((int)($chk['cnt'] ?? 0) > 0) {
    $_SESSION['flash_error'] = 'Нельзя удалить товар: он присутствует в заказе.';
    header('Location: /frontend/view_1.php');
    exit;
}

$row = select_one_prepared('SELECT photo FROM Products WHERE article = ?', 's', $article);
$photo = $row['photo'] ?? null;

execute_prepared('DELETE FROM Products WHERE article = ?', 's', $article);

if ($photo) {
    $path = dirname(__DIR__) . '/images/' . $photo;
    if (is_file($path) && !preg_match('/^\d+\.jpg$/i', $photo)) {
        @unlink($path);
    }
}

$_SESSION['flash_ok'] = 'Товар «' . $product['name'] . '» удалён.';
header('Location: /frontend/view_1.php');
exit;
