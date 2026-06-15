<?php

require_once __DIR__ . '/api/session.php';

$user = current_user();
if ($user) {
    header('Location: /frontend/view_' . (int)$user['role_id'] . '.php');
    exit;
}

$error = isset($_GET['error']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход — МирИгрушек</title>
    <link rel="icon" href="/images/icon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/frontend/style.css">
</head>
<body>
<div class="login-page">
    <div class="login-box">
        <div class="logo-wrap">
            <img src="/images/icon.png" alt="МирИгрушек">
        </div>
        <h1 class="form-title">Авторизация</h1>
        <p class="form-subtitle">ООО «МирИгрушек»</p>
        <?php if ($error): ?>
            <div class="msg error">Неверный логин или пароль.</div>
        <?php endif; ?>
        <form method="POST" action="/api/auth.php">
            <label>Логин
                <input type="text" name="login" required autocomplete="username">
            </label>
            <label>Пароль
                <input type="password" name="password" required autocomplete="current-password">
            </label>
            <button class="btn accent" type="submit">Войти</button>
        </form>
        <p style="text-align:center;margin:16px 0 0">
            <a class="btn" href="/frontend/guest.php">Просмотр каталога без авторизации</a>
        </p>
    </div>
</div>
</body>
</html>
