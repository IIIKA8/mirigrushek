<?php // session.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const ROLE_ADMIN = 'Администратор';
const ROLE_MANAGER = 'Менеджер';
const ROLE_CLIENT = 'Авторизированный клиент';

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool
{
    $user = current_user();
    return $user && (int)$user['role_id'] === 1;
}

function is_manager(): bool
{
    $user = current_user();
    return $user && (int)$user['role_id'] === 2;
}

function is_client(): bool
{
    $user = current_user();
    return $user && (int)$user['role_id'] === 3;
}

function can_manage(): bool
{
    return is_admin() || is_manager();
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: /index.php');
        exit;
    }
}

function require_role(int $role_id): void
{
    require_login();
    $user = current_user();
    if ((int)$user['role_id'] !== $role_id) {
        $_SESSION['flash_error'] = 'Доступ запрещён.';
        header('Location: ' . ($user ? '/frontend/view_' . (int)$user['role_id'] . '.php' : '/index.php'));
        exit;
    }
}

function require_admin(): void
{
    if (!is_admin()) {
        $_SESSION['flash_error'] = 'Доступ только для администратора.';
        header('Location: /frontend/view_1.php');
        exit;
    }
}
