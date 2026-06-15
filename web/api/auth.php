<?php // auth.php
require_once __DIR__ . '/crud.php';
require_once __DIR__ . '/session.php';

$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

if ($login === '' || $password === '') {
    header('Location: /index.php?error=1');
    exit;
}

$user = select_one_prepared(
    'SELECT u.id, p.full_name, u.login, u.password, u.role_id, r.name AS role
     FROM Users u
     JOIN Persons p ON p.id = u.person_id
     JOIN Roles r ON r.id = u.role_id
     WHERE u.login = ?',
    's',
    $login
);

if ($user && hash_equals($user['password'], $password)) {
    unset($user['password']);
    $_SESSION['user'] = $user;
    $role_id = (int)$user['role_id'];
    header("Location: /frontend/view_$role_id.php");
    exit;
}

header('Location: /index.php?error=1');
exit;
