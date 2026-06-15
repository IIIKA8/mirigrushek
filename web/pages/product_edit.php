<?php

require_once __DIR__ . '/../common.php';
require_admin();

if (!empty($_SESSION['editing_product']) && empty($_GET['article']) && empty($_POST)) {
    $_SESSION['flash_error'] = 'Закройте текущее окно редактирования перед открытием нового.';
    header('Location: index.php');
    exit;
}

$article = trim($_GET['article'] ?? $_POST['article'] ?? '');
$isEdit = $article !== '';
$errors = [];

$cats = db()->query('SELECT id, name FROM Categories ORDER BY name')->fetchAll();
$sups = db()->query('SELECT id, name FROM Suppliers ORDER BY name')->fetchAll();
$mans = db()->query('SELECT id, name FROM Manufacturers ORDER BY name')->fetchAll();
$units = db()->query('SELECT id, name FROM Units ORDER BY name')->fetchAll();

if ($isEdit) {
    $st = db()->prepare('SELECT * FROM Products WHERE article = ?');
    $st->execute([$article]);
    $product = $st->fetch();
    if (!$product) {
        exit('Товар не найден.');
    }
    $_SESSION['editing_product'] = $article;
} else {
    $product = [
        'article' => '',
        'name' => '',
        'unit_id' => $units[0]['id'] ?? 1,
        'price' => '',
        'supplier_id' => $sups[0]['id'] ?? 1,
        'manufacturer_id' => $mans[0]['id'] ?? 1,
        'category_id' => $cats[0]['id'] ?? 1,
        'discount' => 0,
        'stock_qty' => 0,
        'description' => '',
        'photo' => null,
    ];
    $_SESSION['editing_product'] = 'new';
}

$imagesDir = realpath(__DIR__ . '/../images');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product['name'] = trim($_POST['name'] ?? '');
    $product['unit_id'] = (int)($_POST['unit_id'] ?? 0);
    $product['price'] = $_POST['price'] ?? '';
    $product['supplier_id'] = (int)($_POST['supplier_id'] ?? 0);
    $product['manufacturer_id'] = (int)($_POST['manufacturer_id'] ?? 0);
    $product['category_id'] = (int)($_POST['category_id'] ?? 0);
    $product['discount'] = (int)($_POST['discount'] ?? 0);
    $product['stock_qty'] = (int)($_POST['stock_qty'] ?? 0);
    $product['description'] = trim($_POST['description'] ?? '');

    if ($product['name'] === '') {
        $errors[] = 'Укажите наименование товара.';
    }
    if (!is_numeric($product['price']) || (float)$product['price'] < 0) {
        $errors[] = 'Цена не может быть отрицательной.';
    }
    if ($product['stock_qty'] < 0) {
        $errors[] = 'Количество на складе не может быть отрицательным.';
    }
    if ($product['discount'] < 0) {
        $errors[] = 'Скидка не может быть отрицательной.';
    }

    $photoName = $product['photo'];
    if (!empty($_FILES['photo']['tmp_name'])) {
        $info = @getimagesize($_FILES['photo']['tmp_name']);
        if (!$info) {
            $errors[] = 'Загруженный файл не является изображением.';
        } elseif ($info[0] < 300 || $info[1] < 200) {
            $errors[] = 'Минимальный размер изображения 300×200 пикселей (загружено '
                . $info[0] . '×' . $info[1] . ').';
        } else {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION)) ?: 'jpg';
            $photoName = 'p_' . bin2hex(random_bytes(5)) . '.' . $ext;
            $target = $imagesDir . DIRECTORY_SEPARATOR . $photoName;
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
                $errors[] = 'Не удалось сохранить изображение на сервере.';
            } elseif ($isEdit && $product['photo'] && $product['photo'] !== $photoName) {
                $oldPath = $imagesDir . DIRECTORY_SEPARATOR . $product['photo'];
                if (is_file($oldPath) && !preg_match('/^picture\.png$/i', $product['photo'])) {
                    @unlink($oldPath);
                }
            }
        }
    }

    if (!$errors) {
        if ($isEdit) {
            $st = db()->prepare(
                'UPDATE Products SET name=?, unit_id=?, price=?, supplier_id=?, manufacturer_id=?,
                 category_id=?, discount=?, stock_qty=?, description=?, photo=? WHERE article=?'
            );
            $st->execute([
                $product['name'], $product['unit_id'], $product['price'], $product['supplier_id'],
                $product['manufacturer_id'], $product['category_id'], $product['discount'],
                $product['stock_qty'], $product['description'], $photoName, $article,
            ]);
        } else {
            do {
                $newArticle = strtoupper(bin2hex(random_bytes(3)));
                $chk = db()->prepare('SELECT 1 FROM Products WHERE article=?');
                $chk->execute([$newArticle]);
            } while ($chk->fetch());

            $st = db()->prepare(
                'INSERT INTO Products (article, name, unit_id, price, supplier_id, manufacturer_id,
                 category_id, discount, stock_qty, description, photo)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)'
            );
            $st->execute([
                $newArticle, $product['name'], $product['unit_id'], $product['price'],
                $product['supplier_id'], $product['manufacturer_id'], $product['category_id'],
                $product['discount'], $product['stock_qty'], $product['description'], $photoName,
            ]);
        }
        unset($_SESSION['editing_product']);
        $_SESSION['flash_ok'] = 'Товар успешно сохранён.';
        header('Location: index.php');
        exit;
    }
}

layout_header($isEdit ? 'Редактирование товара' : 'Добавление товара');
?>
<?php foreach ($errors as $err): ?>
    <div class="msg error"><?= htmlspecialchars($err) ?></div>
<?php endforeach; ?>

<form class="panel" method="post" enctype="multipart/form-data">
    <?php if ($isEdit): ?>
        <label>Артикул
            <input type="text" value="<?= htmlspecialchars($article) ?>" readonly>
        </label>
        <input type="hidden" name="article" value="<?= htmlspecialchars($article) ?>">
    <?php endif; ?>

    <label>Наименование товара
        <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
    </label>
    <label>Описание
        <textarea name="description" rows="4"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
    </label>
    <label>Категория
        <select name="category_id" required>
            <?php foreach ($cats as $c): ?>
                <option value="<?= $c['id'] ?>" <?= (int)$product['category_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Поставщик
        <select name="supplier_id" required>
            <?php foreach ($sups as $s): ?>
                <option value="<?= $s['id'] ?>" <?= (int)$product['supplier_id'] === (int)$s['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Производитель
        <select name="manufacturer_id" required>
            <?php foreach ($mans as $m): ?>
                <option value="<?= $m['id'] ?>" <?= (int)$product['manufacturer_id'] === (int)$m['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Единица измерения
        <select name="unit_id" required>
            <?php foreach ($units as $u): ?>
                <option value="<?= $u['id'] ?>" <?= (int)$product['unit_id'] === (int)$u['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Цена, ₽
        <input type="number" name="price" step="0.01" min="0" value="<?= htmlspecialchars((string)$product['price']) ?>" required>
    </label>
    <label>Количество на складе
        <input type="number" name="stock_qty" min="0" value="<?= (int)$product['stock_qty'] ?>" required>
    </label>
    <label>Действующая скидка, %
        <input type="number" name="discount" min="0" max="100" value="<?= (int)$product['discount'] ?>" required>
    </label>
    <label>Фото товара (минимум 300×200 px)
        <input type="file" name="photo" accept="image/*">
    </label>
    <?php if (!empty($product['photo'])): ?>
        <img class="product-photo" src="<?= htmlspecialchars(product_photo_url($product['photo'])) ?>" alt="">
    <?php endif; ?>
    <div class="actions">
        <button class="btn accent" type="submit">Сохранить</button>
        <a class="btn" href="index.php">Назад</a>
    </div>
</form>
<?php
unset($_SESSION['editing_product']);
layout_footer();
