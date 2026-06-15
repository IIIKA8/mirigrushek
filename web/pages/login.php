<?php

require_once __DIR__ . '/../common.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $pass = $_POST['password'] ?? '';

    $st = db()->prepare(
        'SELECT u.id, u.full_name, u.login, u.password, r.name AS role
         FROM Users u JOIN Roles r ON r.id = u.role_id
         WHERE u.login = ?'
    );
    $st->execute([$login]);
    $user = $st->fetch();

    if ($user && hash_equals($user['password'], $pass)) {
        unset($user['password']);
        $_SESSION['user'] = $user;
        header('Location: index.php');
        exit;
    }
    $error = 'Неверный логин или пароль. Проверьте данные и повторите попытку.';
}

layout_header('Вход в систему');
?>
<div class="login-box">
    <?php if (!empty($error)): ?>
        <div class="msg error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <label>Логин (e-mail)
            <input type="text" name="login" required autocomplete="username">
        </label>
        <label>Пароль
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button class="btn accent" type="submit">Войти</button>
        <a class="btn" href="index.php" style="text-align:center">Просмотр товаров (гость)</a>
    </form>
</div>
<?php layout_footer(); ?>
