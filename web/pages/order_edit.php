<?php

require_once __DIR__ . '/../common.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$isEdit = $id > 0;
$errors = [];

$statuses = db()->query('SELECT id, name FROM OrderStatuses ORDER BY id')->fetchAll();
$points = db()->query('SELECT id, address FROM PickupPoints ORDER BY id')->fetchAll();
$clientStmt = db()->prepare(
    "SELECT u.id, u.full_name FROM Users u
     JOIN Roles r ON r.id = u.role_id
     WHERE r.name = ?
     ORDER BY u.full_name"
);
$clientStmt->execute([ROLE_CLIENT]);
$clients = $clientStmt->fetchAll();

$articles = db()->query('SELECT article FROM Products ORDER BY article')->fetchAll(PDO::FETCH_COLUMN);

$order = [
    'order_date' => '',
    'delivery_date' => '',
    'pickup_point_id' => $points[0]['id'] ?? null,
    'client_user_id' => $clients[0]['id'] ?? null,
    'receive_code' => '',
    'status_id' => $statuses[0]['id'] ?? 1,
];
$itemsText = '';

if ($isEdit) {
    $st = db()->prepare('SELECT * FROM Orders WHERE id = ?');
    $st->execute([$id]);
    $order = $st->fetch();
    if (!$order) {
        exit('Заказ не найден.');
    }
    $it = db()->prepare('SELECT product_article, quantity FROM OrderItems WHERE order_id = ?');
    $it->execute([$id]);
    $lines = [];
    foreach ($it->fetchAll() as $row) {
        $lines[] = $row['product_article'] . ', ' . $row['quantity'];
    }
    $itemsText = implode(', ', $lines);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order['order_date'] = $_POST['order_date'] ?: null;
    $order['delivery_date'] = $_POST['delivery_date'] ?: null;
    $order['pickup_point_id'] = (int)$_POST['pickup_point_id'];
    $order['client_user_id'] = (int)$_POST['client_user_id'];
    $order['receive_code'] = trim($_POST['receive_code'] ?? '');
    $order['status_id'] = (int)$_POST['status_id'];
    $itemsText = trim($_POST['items'] ?? '');

    $items = [];
    $parts = preg_split('/\s*,\s*/', $itemsText);
    for ($i = 0; $i < count($parts); $i += 2) {
        $art = strtoupper(trim($parts[$i] ?? ''));
        $qty = (int)trim($parts[$i + 1] ?? '0');
        if ($art === '') {
            continue;
        }
        if (!in_array($art, $articles, true)) {
            $errors[] = "Неизвестный артикул: $art";
            continue;
        }
        if ($qty <= 0) {
            $errors[] = "Укажите количество > 0 для артикула $art";
            continue;
        }
        $items[] = [$art, $qty];
    }

    if (!$items) {
        $errors[] = 'Укажите состав заказа в формате: АРТИКУЛ, количество, АРТИКУЛ, количество…';
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            if ($isEdit) {
                $st = $pdo->prepare(
                    'UPDATE Orders SET order_date=?, delivery_date=?, pickup_point_id=?,
                     client_user_id=?, receive_code=?, status_id=? WHERE id=?'
                );
                $st->execute([
                    $order['order_date'], $order['delivery_date'], $order['pickup_point_id'],
                    $order['client_user_id'], $order['receive_code'], $order['status_id'], $id,
                ]);
                $pdo->prepare('DELETE FROM OrderItems WHERE order_id = ?')->execute([$id]);
                $orderId = $id;
            } else {
                $st = $pdo->prepare(
                    'INSERT INTO Orders (order_date, delivery_date, pickup_point_id, client_user_id, receive_code, status_id)
                     VALUES (?,?,?,?,?,?)'
                );
                $st->execute([
                    $order['order_date'], $order['delivery_date'], $order['pickup_point_id'],
                    $order['client_user_id'], $order['receive_code'], $order['status_id'],
                ]);
                $orderId = (int)$pdo->lastInsertId();
            }
            $ins = $pdo->prepare('INSERT INTO OrderItems (order_id, product_article, quantity) VALUES (?,?,?)');
            foreach ($items as [$art, $qty]) {
                $ins->execute([$orderId, $art, $qty]);
            }
            $pdo->commit();
            $_SESSION['flash_ok'] = 'Заказ сохранён.';
            header('Location: index.php?page=orders');
            exit;
        } catch (Throwable $ex) {
            $pdo->rollBack();
            $errors[] = 'Ошибка сохранения: ' . $ex->getMessage();
        }
    }
}

layout_header($isEdit ? "Редактирование заказа №$id" : 'Добавление заказа');
?>
<?php foreach ($errors as $err): ?>
    <div class="msg error"><?= htmlspecialchars($err) ?></div>
<?php endforeach; ?>

<form class="panel" method="post">
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <label>Номер заказа
            <input type="text" value="<?= $id ?>" readonly>
        </label>
    <?php endif; ?>
    <label>Артикул заказа (через запятую: артикул, кол-во, артикул, кол-во…)
        <textarea name="items" rows="3" required><?= htmlspecialchars($itemsText) ?></textarea>
    </label>
    <label>Статус заказа
        <select name="status_id" required>
            <?php foreach ($statuses as $s): ?>
                <option value="<?= $s['id'] ?>" <?= (int)$order['status_id'] === (int)$s['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Адрес пункта выдачи
        <select name="pickup_point_id" required>
            <?php foreach ($points as $p): ?>
                <option value="<?= $p['id'] ?>" <?= (int)$order['pickup_point_id'] === (int)$p['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['address']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>ФИО авторизированного клиента
        <select name="client_user_id" required>
            <?php foreach ($clients as $c): ?>
                <option value="<?= $c['id'] ?>" <?= (int)$order['client_user_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['full_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Дата заказа
        <input type="date" name="order_date" value="<?= htmlspecialchars($order['order_date'] ?? '') ?>">
    </label>
    <label>Дата доставки
        <input type="date" name="delivery_date" value="<?= htmlspecialchars($order['delivery_date'] ?? '') ?>">
    </label>
    <label>Код для получения
        <input type="text" name="receive_code" value="<?= htmlspecialchars($order['receive_code'] ?? '') ?>">
    </label>
    <div class="actions">
        <button class="btn accent" type="submit">Сохранить</button>
        <a class="btn" href="index.php?page=orders">Назад</a>
    </div>
</form>
<?php layout_footer(); ?>
