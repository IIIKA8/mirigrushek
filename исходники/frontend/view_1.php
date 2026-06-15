<?php

require_once __DIR__ . '/common.php';
require_role(1);

layout_header('Каталог товаров');
render_catalog(true, true);
layout_footer();
