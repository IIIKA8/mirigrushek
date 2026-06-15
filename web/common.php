<?php

require_once __DIR__ . '/db.php';

session_start();

const ROLE_ADMIN = 'Администратор';
const ROLE_MANAGER = 'Менеджер';
const ROLE_CLIENT = 'Авторизированный клиент';

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_guest(): bool
{
    return empty($_SESSION['user']);
}

function is_admin(): bool
{
    $user = current_user();
    return $user && $user['role'] === ROLE_ADMIN;
}

function is_manager(): bool
{
    $user = current_user();
    return $user && $user['role'] === ROLE_MANAGER;
}

function can_manage(): bool
{
    return is_admin() || is_manager();
}

function require_login(): void
{
    if (is_guest()) {
        header('Location: index.php?page=login');
        exit;
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        $_SESSION['flash_error'] = 'Доступ только для администратора.';
        header('Location: index.php');
        exit;
    }
}

function product_photo_url(?string $photo): string
{
    $base = 'images/';
    if ($photo && file_exists(__DIR__ . '/images/' . $photo)) {
        return $base . rawurlencode($photo);
    }
    if ($photo && file_exists(__DIR__ . '/../images/' . $photo)) {
        return $base . rawurlencode($photo);
    }
    return $base . 'picture.png';
}

function format_price(float $price): string
{
    return number_format($price, 2, '.', ' ') . ' ₽';
}

function discounted_price(float $price, int $discount): float
{
    return round($price * (100 - $discount) / 100, 2);
}

function layout_header(string $title): void
{
    $user = current_user();
    $displayName = $user['full_name'] ?? 'Гость';
    $pageTitle = htmlspecialchars($title) . ' — МирИгрушек';
    ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="icon" href="images/icon.ico" type="image/x-icon">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="top">
    <img class="logo" src="images/icon.png" alt="МирИгрушек">
    <h1>ООО «МирИгрушек»</h1>
    <div class="who">Пользователь: <b><?= htmlspecialchars($displayName) ?></b></div>
</header>
<nav>
    <a class="btn" href="index.php">Товары</a>
    <?php if (can_manage()): ?>
        <a class="btn" href="index.php?page=orders">Заказы</a>
    <?php endif; ?>
    <?php if (is_admin()): ?>
        <a class="btn accent" href="index.php?page=product_edit">Добавить товар</a>
    <?php endif; ?>
    <?php if ($user): ?>
        <a class="btn" href="index.php?page=logout" style="margin-left:auto">Выход</a>
    <?php else: ?>
        <a class="btn accent" href="index.php?page=login" style="margin-left:auto">Войти</a>
    <?php endif; ?>
</nav>
<main>
    <h2><?= htmlspecialchars($title) ?></h2>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="msg error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_ok'])): ?>
        <div class="msg ok"><?= htmlspecialchars($_SESSION['flash_ok']) ?></div>
        <?php unset($_SESSION['flash_ok']); ?>
    <?php endif; ?>
<?php
}

function layout_footer(): void
{
    ?>
</main>
<script src="assets/catalog.js"></script>
</body>
</html>
<?php
}
