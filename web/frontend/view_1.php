<?php

require_once __DIR__ . '/common.php';
require_role(1);

if (isset($_GET['cancel_edit'])) {
    unset($_SESSION['editing_product']);
}

layout_header('Каталог товаров');
render_catalog(true, true);
layout_footer();
