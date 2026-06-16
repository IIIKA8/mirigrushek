<?php

require_once __DIR__ . '/../api/crud.php';
require_once __DIR__ . '/../api/session.php';

function product_photo_url(?string $photo): string
{
    $base = '/images/';
    $root = dirname(__DIR__);
    if ($photo && file_exists($root . '/images/' . $photo)) {
        return $base . rawurlencode($photo);
    }
    return $base . 'picture.png';
}

function format_price(float $price): string
{
    return number_format($price, 2, '.', ' ') . ' ₽';
}

function discounted_price(float $price, int $discount): float
{
    return round($price * (100 - $discount) / 100, 2);
}

function catalog_home_url(): string
{
    $user = current_user();
    if (!$user) {
        return '/frontend/guest.php';
    }
    return '/frontend/view_' . (int)$user['role_id'] . '.php';
}

function layout_header(string $title): void
{
    $user = current_user();
    $displayName = $user['full_name'] ?? 'Гость';
    $pageTitle = htmlspecialchars($title) . ' — МирИгрушек';
    ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="icon" href="/images/icon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/frontend/style.css">
</head>
<body>
<header class="top">
    <img class="logo" src="/images/icon.png" alt="МирИгрушек">
    <h1>ООО «МирИгрушек»</h1>
    <div class="who">Пользователь: <b><?= htmlspecialchars($displayName) ?></b></div>
</header>
<nav>
    <a class="btn" href="<?= catalog_home_url() ?>">Товары</a>
    <?php if (can_manage()): ?>
        <a class="btn" href="/frontend/orders.php">Заказы</a>
    <?php endif; ?>
    <?php if (is_admin()): ?>
        <a class="btn accent" href="/frontend/product_edit.php">Добавить товар</a>
    <?php endif; ?>
    <?php if ($user): ?>
        <a class="btn" href="/api/logout.php" style="margin-left:auto">Выход</a>
    <?php else: ?>
        <a class="btn accent" href="/index.php" style="margin-left:auto">Войти</a>
    <?php endif; ?>
</nav>
<main>
    <h2><?= htmlspecialchars($title) ?></h2>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="msg error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_ok'])): ?>
        <div class="msg ok"><?= htmlspecialchars($_SESSION['flash_ok']) ?></div>
        <?php unset($_SESSION['flash_ok']); ?>
    <?php endif; ?>
<?php
}

function layout_footer(): void
{
    ?>
</main>
<script src="/frontend/catalog.js"></script>
</body>
</html>
<?php
}

function text_lower(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
}

function resolve_supplier_id(string $name): int
{
    $name = trim($name);
    $row = select_one_prepared('SELECT id FROM Suppliers WHERE name = ?', 's', $name);
    if ($row) {
        return (int)$row['id'];
    }
    execute_prepared('INSERT INTO Suppliers (name) VALUES (?)', 's', $name);
    return (int)$_SERVER['db']->insert_id;
}

function save_resized_product_image(string $tmpPath, string $destPath, int $maxW = 300, int $maxH = 200): bool
{
    $info = @getimagesize($tmpPath);
    if (!$info) {
        return false;
    }

    $srcW = $info[0];
    $srcH = $info[1];
    $scale = min($maxW / $srcW, $maxH / $srcH, 1.0);
    $dstW = max(1, (int)round($srcW * $scale));
    $dstH = max(1, (int)round($srcH * $scale));

    switch ($info[2]) {
        case IMAGETYPE_JPEG:
            $src = imagecreatefromjpeg($tmpPath);
            break;
        case IMAGETYPE_PNG:
            $src = imagecreatefrompng($tmpPath);
            break;
        case IMAGETYPE_GIF:
            $src = imagecreatefromgif($tmpPath);
            break;
        case IMAGETYPE_WEBP:
            if (!function_exists('imagecreatefromwebp')) {
                return false;
            }
            $src = imagecreatefromwebp($tmpPath);
            break;
        default:
            return false;
    }

    if (!$src) {
        return false;
    }

    $dst = imagecreatetruecolor($dstW, $dstH);
    if ($info[2] === IMAGETYPE_PNG || $info[2] === IMAGETYPE_GIF) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

    $ext = strtolower(pathinfo($destPath, PATHINFO_EXTENSION));
    $ok = match ($ext) {
        'png' => imagepng($dst, $destPath),
        'gif' => imagegif($dst, $destPath),
        'webp' => function_exists('imagewebp') ? imagewebp($dst, $destPath) : false,
        default => imagejpeg($dst, $destPath, 90),
    };

    imagedestroy($src);
    imagedestroy($dst);

    return (bool)$ok;
}

function load_products(): array
{
    $rows = select(
        'SELECT p.article, p.name, p.price, p.discount, p.stock_qty, p.description, p.photo,
                c.name AS cat_name, s.name AS sup_name, m.name AS man_name, u.name AS unit_name
         FROM Products p
         JOIN Categories c ON c.id = p.category_id
         JOIN Suppliers s ON s.id = p.supplier_id
         JOIN Manufacturers m ON m.id = p.manufacturer_id
         JOIN Units u ON u.id = p.unit_id
         ORDER BY p.name'
    );
    if (isset($rows['status']) && $rows['status'] === 'failed') {
        $_SESSION['catalog_error'] = 'Ошибка SQL при загрузке каталога: ' . ($rows['message'] ?? 'неизвестно');
        return [];
    }
    if (!$rows) {
        $countRow = select('SELECT COUNT(*) AS cnt FROM Products');
        $productCount = (int)($countRow[0]['cnt'] ?? 0);
        if ($productCount === 0) {
            $_SESSION['catalog_error'] = 'Таблица Products пуста. Импортируйте товары (Tovar.xlsx или init.sql).';
        } else {
            $_SESSION['catalog_error'] = 'В Products ' . $productCount . ' строк, но JOIN со справочниками (Categories, Suppliers, Manufacturers, Units) вернул 0. Проверьте category_id, supplier_id, manufacturer_id, unit_id.';
        }
    }
    return $rows;
}

function render_catalog(bool $with_filters, bool $is_admin_ui): void
{
    $products = load_products();
    $manufacturers = $with_filters ? select('SELECT id, name FROM Manufacturers ORDER BY name') : [];

    if (!empty($_SESSION['catalog_error'])): ?>
        <div class="msg error"><?= htmlspecialchars($_SESSION['catalog_error']) ?></div>
        <?php unset($_SESSION['catalog_error']); ?>
    <?php endif; ?>

    <?php if ($with_filters): ?>
<form class="bar" id="catalog-filters" onsubmit="return false;">
    <label>Поиск
        <input type="search" id="filter-search" placeholder="По всем текстовым полям…" autocomplete="off">
    </label>
    <label>Производитель
        <select id="filter-manufacturer">
            <option value="">Все производители</option>
            <?php foreach ($manufacturers as $man): ?>
                <option value="<?= htmlspecialchars($man['name']) ?>"><?= htmlspecialchars($man['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Сортировка
        <select id="filter-sort">
            <option value="">Без сортировки</option>
            <option value="price_asc">Цена ↑</option>
            <option value="price_desc">Цена ↓</option>
            <option value="stock_asc">Кол-во на складе ↑</option>
            <option value="stock_desc">Кол-во на складе ↓</option>
            <option value="discount_asc">Скидка ↑</option>
            <option value="discount_desc">Скидка ↓</option>
        </select>
    </label>
</form>
    <?php endif; ?>

<div id="products-list" class="products-list">
    <?php foreach ($products as $p):
        $final = discounted_price((float)$p['price'], (int)$p['discount']);
        $rowClass = '';
        if ((int)$p['stock_qty'] === 0) {
            $rowClass = 'row-oos';
        } elseif ((int)$p['discount'] > 17) {
            $rowClass = 'row-sale';
        }
        $searchBlob = implode(' ', [
            $p['article'], $p['name'], $p['cat_name'], $p['description'],
            $p['man_name'], $p['sup_name'], $p['unit_name'],
        ]);
    ?>
    <article class="product-card <?= $rowClass ?><?= $is_admin_ui ? ' clickable' : '' ?>"
        data-search="<?= htmlspecialchars(text_lower($searchBlob)) ?>"
        data-manufacturer="<?= htmlspecialchars($p['man_name']) ?>"
        data-price="<?= (float)$p['price'] ?>"
        data-stock="<?= (int)$p['stock_qty'] ?>"
        data-discount="<?= (int)$p['discount'] ?>"
        <?php if ($is_admin_ui): ?>
        onclick="if(!event.target.closest('a,button,form'))location.href='/frontend/product_edit.php?article=<?= urlencode($p['article']) ?>'"
        <?php endif; ?>>
        <div class="product-card-photo">
            <img class="product-photo" src="<?= htmlspecialchars(product_photo_url($p['photo'])) ?>" alt="">
        </div>
        <div class="product-card-body">
            <h3 class="product-card-title">
                <?= htmlspecialchars($p['cat_name']) ?> | <?= htmlspecialchars($p['name']) ?>
            </h3>
            <dl class="product-card-fields">
                <div><dt>Описание товара:</dt><dd><?= htmlspecialchars($p['description'] ?? '') ?></dd></div>
                <div><dt>Производитель:</dt><dd><?= htmlspecialchars($p['man_name']) ?></dd></div>
                <div><dt>Поставщик:</dt><dd><?= htmlspecialchars($p['sup_name']) ?></dd></div>
                <div><dt>Цена:</dt><dd>
                    <?php if ((int)$p['discount'] > 0): ?>
                        <span class="price-old"><?= format_price((float)$p['price']) ?></span>
                        <span class="price-new"><?= format_price($final) ?></span>
                    <?php else: ?>
                        <?= format_price((float)$p['price']) ?>
                    <?php endif; ?>
                </dd></div>
                <div><dt>Единица измерения:</dt><dd><?= htmlspecialchars($p['unit_name']) ?></dd></div>
                <div><dt>Количество на складе:</dt><dd><?= (int)$p['stock_qty'] ?></dd></div>
            </dl>
            <?php if ($is_admin_ui): ?>
            <div class="actions product-card-actions" onclick="event.stopPropagation()">
                <a class="btn" href="/frontend/product_edit.php?article=<?= urlencode($p['article']) ?>">Изменить</a>
                <a class="btn" href="/frontend/product_delete.php?article=<?= urlencode($p['article']) ?>"
                   onclick="return confirm('Удалить товар?')">Удалить</a>
            </div>
            <?php endif; ?>
        </div>
        <div class="product-card-discount">
            <div class="product-card-discount-label">Действующая скидка</div>
            <div class="product-card-discount-value"><?= (int)$p['discount'] ?>%</div>
        </div>
    </article>
    <?php endforeach; ?>
</div>
<p id="no-products" class="<?= $products ? 'hidden' : '' ?> msg">Товары не найдены.</p>
<?php
}
