<?php

require_once __DIR__ . '/common.php';
require_role(2);

layout_header('Каталог товаров');
render_catalog(true, false);
layout_footer();
