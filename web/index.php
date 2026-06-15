<?php

require_once __DIR__ . '/common.php';

unset($_SESSION['editing_product']);

$page = $_GET['page'] ?? 'products';

$routes = [
    'login' => 'pages/login.php',
    'logout' => 'pages/logout.php',
    'orders' => 'pages/orders.php',
    'product_edit' => 'pages/product_edit.php',
    'product_delete' => 'pages/product_delete.php',
    'order_edit' => 'pages/order_edit.php',
    'order_delete' => 'pages/order_delete.php',
];

if (isset($routes[$page])) {
    require __DIR__ . '/' . $routes[$page];
    exit;
}

$sql = "SELECT p.article, p.name, p.price, p.discount, p.stock_qty, p.description, p.photo,
               c.name AS cat_name, s.name AS sup_name, m.name AS man_name, u.name AS unit_name
        FROM Products p
        JOIN Categories c ON c.id = p.category_id
        JOIN Suppliers s ON s.id = p.supplier_id
        JOIN Manufacturers m ON m.id = p.manufacturer_id
        JOIN Units u ON u.id = p.unit_id
        ORDER BY p.name";
$products = db()->query($sql)->fetchAll();

$suppliers = [];
if (can_manage()) {
    $suppliers = db()->query('SELECT id, name FROM Suppliers ORDER BY name')->fetchAll();
}

layout_header('Каталог товаров');
?>

<?php if (can_manage()): ?>
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

<div class="table-wrap">
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
            <?php if (is_admin()): ?><th>Действия</th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($products as $p):
        $final = discounted_price((float)$p['price'], (int)$p['discount']);
        $rowClass = '';
        if ((int)$p['stock_qty'] === 0) {
            $rowClass = 'row-oos';
        } elseif ((int)$p['discount'] > 15) {
            $rowClass = 'row-sale';
        }
        $searchBlob = implode(' ', [
            $p['article'], $p['name'], $p['cat_name'], $p['description'],
            $p['man_name'], $p['sup_name'], $p['unit_name'],
        ]);
        $clickable = is_admin() ? ' clickable' : '';
    ?>
        <tr class="<?= $rowClass ?><?= $clickable ?>"
            data-search="<?= htmlspecialchars(mb_strtolower($searchBlob)) ?>"
            data-supplier="<?= htmlspecialchars($p['sup_name']) ?>"
            data-price="<?= (float)$p['price'] ?>"
            data-stock="<?= (int)$p['stock_qty'] ?>"
            <?php if (is_admin()): ?>
            onclick="if(!event.target.closest('a,button,form'))location.href='index.php?page=product_edit&article=<?= urlencode($p['article']) ?>'"
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
            <?php if (is_admin()): ?>
            <td class="actions" onclick="event.stopPropagation()">
                <a class="btn" href="index.php?page=product_edit&article=<?= urlencode($p['article']) ?>">Изменить</a>
                <a class="btn" href="index.php?page=product_delete&article=<?= urlencode($p['article']) ?>"
                   onclick="return confirm('Удалить товар «<?= htmlspecialchars(addslashes($p['name'])) ?>»?')">Удалить</a>
            </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<p id="no-products" class="hidden msg">Товары не найдены.</p>

<?php layout_footer(); ?>
