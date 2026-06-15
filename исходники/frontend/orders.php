<?php

require_once __DIR__ . '/common.php';

if (!can_manage()) {
    $_SESSION['flash_error'] = 'Просмотр заказов доступен менеджеру и администратору.';
    header('Location: ' . catalog_home_url());
    exit;
}

$orders = select(
    "SELECT o.id, o.order_date, o.delivery_date, o.receive_code,
            pp.address AS pickup,
            cu.full_name AS client,
            os.name AS status,
            GROUP_CONCAT(CONCAT(oi.product_article, ', ', oi.quantity) SEPARATOR ', ') AS items
     FROM Orders o
     LEFT JOIN PickupPoints pp ON pp.id = o.pickup_point_id
     LEFT JOIN Users cu ON cu.id = o.client_user_id
     JOIN OrderStatuses os ON os.id = o.status_id
     LEFT JOIN OrderItems oi ON oi.order_id = o.id
     GROUP BY o.id
     ORDER BY o.id"
);

if (isset($orders['status']) && $orders['status'] === 'failed') {
    $orders = [];
}

layout_header('Заказы');
?>

<?php if (is_admin()): ?>
    <p><a class="btn accent" href="/frontend/order_edit.php">Добавить заказ</a></p>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>№</th>
            <th>Артикул заказа</th>
            <th>Статус заказа</th>
            <th>Адрес пункта выдачи</th>
            <th>Дата заказа</th>
            <th>Дата доставки</th>
            <th>ФИО клиента</th>
            <th>Код получения</th>
            <?php if (is_admin()): ?><th>Действия</th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
        <tr class="<?= is_admin() ? 'clickable' : '' ?>"
            <?php if (is_admin()): ?>
            onclick="if(!event.target.closest('a,button'))location.href='/frontend/order_edit.php?id=<?= (int)$o['id'] ?>'"
            <?php endif; ?>>
            <td><?= (int)$o['id'] ?></td>
            <td><?= htmlspecialchars($o['items'] ?? '') ?></td>
            <td><?= htmlspecialchars($o['status']) ?></td>
            <td><?= htmlspecialchars($o['pickup'] ?? '') ?></td>
            <td><?= $o['order_date'] ? htmlspecialchars(date('d.m.Y', strtotime($o['order_date']))) : '—' ?></td>
            <td><?= $o['delivery_date'] ? htmlspecialchars(date('d.m.Y', strtotime($o['delivery_date']))) : '—' ?></td>
            <td><?= htmlspecialchars($o['client'] ?? '') ?></td>
            <td><?= htmlspecialchars($o['receive_code'] ?? '') ?></td>
            <?php if (is_admin()): ?>
            <td class="actions" onclick="event.stopPropagation()">
                <a class="btn" href="/frontend/order_edit.php?id=<?= (int)$o['id'] ?>">Изменить</a>
                <a class="btn" href="/frontend/order_delete.php?id=<?= (int)$o['id'] ?>"
                   onclick="return confirm('Удалить заказ №<?= (int)$o['id'] ?>?')">Удалить</a>
            </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php if (!$orders): ?>
    <p class="msg">Заказов нет.</p>
<?php endif; ?>

<?php layout_footer(); ?>
