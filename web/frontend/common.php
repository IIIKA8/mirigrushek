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
        return [];
    }
    return $rows;
}

function render_catalog(bool $with_filters, bool $is_admin_ui): void
{
    $products = load_products();
    $suppliers = $with_filters ? select('SELECT id, name FROM Suppliers ORDER BY name') : [];

    if ($with_filters): ?>
<form class="bar" id="catalog-filters" onsubmit="return false;">
    <label>Поиск
        <input type="search" id="filter-search" placeholder="По всем текстовым полям…" autocomplete="off">
    </label>
    <label>Поставщик
        <select id="filter-supplier">
            <option value="">Все поставщики</option>
            <?php foreach ($suppliers as $sup): ?>
                <option value="<?= htmlspecialchars($sup['name']) ?>"><?= htmlspecialchars($sup['name']) ?></option>
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
        </select>
    </label>
</form>
    <?php endif; ?>

<table id="products-table">
    <thead>
        <tr>
            <th>Фото</th>
            <th>Наименование</th>
            <th>Категория</th>
            <th>Описание</th>
            <th>Производитель</th>
            <th>Поставщик</th>
            <th>Цена</th>
            <th>Ед. изм.</th>
            <th>На складе</th>
            <th>Скидка</th>
            <?php if ($is_admin_ui): ?><th>Действия</th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
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
        <tr class="<?= $rowClass ?><?= $is_admin_ui ? ' clickable' : '' ?>"
            data-search="<?= htmlspecialchars(mb_strtolower($searchBlob)) ?>"
            data-supplier="<?= htmlspecialchars($p['sup_name']) ?>"
            data-price="<?= (float)$p['price'] ?>"
            data-stock="<?= (int)$p['stock_qty'] ?>"
            <?php if ($is_admin_ui): ?>
            onclick="if(!event.target.closest('a,button,form'))location.href='/frontend/product_edit.php?article=<?= urlencode($p['article']) ?>'"
            <?php endif; ?>>
            <td><img class="product-photo" src="<?= htmlspecialchars(product_photo_url($p['photo'])) ?>" alt=""></td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><?= htmlspecialchars($p['cat_name']) ?></td>
            <td><?= htmlspecialchars(mb_strimwidth($p['description'] ?? '', 0, 80, '…')) ?></td>
            <td><?= htmlspecialchars($p['man_name']) ?></td>
            <td><?= htmlspecialchars($p['sup_name']) ?></td>
            <td>
                <?php if ((int)$p['discount'] > 0): ?>
                    <span class="price-old"><?= format_price((float)$p['price']) ?></span>
                    <span class="price-new"><?= format_price($final) ?></span>
                <?php else: ?>
                    <?= format_price((float)$p['price']) ?>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($p['unit_name']) ?></td>
            <td><?= (int)$p['stock_qty'] ?></td>
            <td><?= (int)$p['discount'] ?>%</td>
            <?php if ($is_admin_ui): ?>
            <td class="actions" onclick="event.stopPropagation()">
                <a class="btn" href="/frontend/product_edit.php?article=<?= urlencode($p['article']) ?>">Изменить</a>
                <a class="btn" href="/frontend/product_delete.php?article=<?= urlencode($p['article']) ?>"
                   onclick="return confirm('Удалить товар?')">Удалить</a>
            </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<p id="no-products" class="hidden msg">Товары не найдены.</p>
<?php
}
