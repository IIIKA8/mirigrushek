<?php // logout.php
require_once __DIR__ . '/session.php';
$_SESSION = [];
if (session_id() !== '') {
    session_destroy();
}
header('Location: /index.php');
exit;
