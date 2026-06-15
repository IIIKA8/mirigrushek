<?php

require_once __DIR__ . '/common.php';
require_role(3);

layout_header('Каталог товаров');
render_catalog(false, false);
layout_footer();
