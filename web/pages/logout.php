<?php

require_once __DIR__ . '/../common.php';

$_SESSION = [];
if (session_id() !== '') {
    session_destroy();
}
header('Location: index.php?page=login');
exit;
