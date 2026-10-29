<?php
session_start();
require_once 'db.php';

$stmtUser = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmtUser->execute([$_SESSION['user_id'] ?? 0]);
if (!in_array($stmtUser->fetchColumn(), ['admin', 'manager'])) die("Доступ запрещен.");

// Загружаем справочники для списков
$categories = $pdo->query("SELECT * FROM product_categories ORDER BY name")->fetchAll();
$brands = $pdo->query("SELECT * FROM brands ORDER BY name")->fetchAll();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item = ['name' => '', 'description' => '', 'price' => '', 'image_path' => '', 'is_active' => 1, 'category_id' => '', 'brand_id' => ''];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch() ?: $item;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $category_id = empty($_POST['category_id']) ? null : (int)$_POST['category_id'];
    $brand_id = empty($_POST['brand_id']) ? null : (int)$_POST['brand_id'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $image_path = $item['image_path'];
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    if (!empty($_FILES['image']['name'])) {
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
            $image_path = $uploadDir . $fileName;
        }
    } elseif (!empty($_POST['image_url'])) {
        $url = trim($_POST['image_url']);
        $imageContent = @file_get_contents($url);
        if ($imageContent) {
            $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $fileName = time() . '_' . uniqid() . '.' . $ext;
            if (file_put_contents($uploadDir . $fileName, $imageContent)) {
                $image_path = $uploadDir . $fileName;
            }
        }
    }

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, price=?, image_path=?, is_active=?, category_id=?, brand_id=? WHERE id=?");
        $stmt->execute([$name, $description, $price, $image_path, $is_active, $category_id, $brand_id, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image_path, is_active, category_id, brand_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $image_path, $is_active, $category_id, $brand_id]);
    }
    header("Location: admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $id ? 'Редактирование' : 'Добавление' ?> товара</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #05101d; color: #fff; font-family: sans-serif; }
        .form-select, .form-control { background-color: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
        .form-select:focus, .form-control:focus { background-color: rgba(255,255,255,0.1); color: #fff; box-shadow: none; border-color: #f2c94c; }
        .form-select option { background-color: #091c32; color: #fff; }
    </style>
</head>
<body class="p-5">
    <div class="container" style="max-width: 600px;">
        <h2 class="mb-4 text-warning"><?= $id ? 'Редактировать товар' : 'Новый товар' ?></h2>
        <form method="POST" enctype="multipart/form-data" class="bg-dark p-4 rounded border border-secondary">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Категория</label>
                    <select name="category_id" class="form-select">
                        <option value="">-- Без категории --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $item['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mt-3 mt-md-0">
                    <label class="form-label">Бренд</label>
                    <select name="brand_id" class="form-select">
                        <option value="">-- Без бренда --</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?= $brand['id'] ?>" <?= $item['brand_id'] == $brand['id'] ? 'selected' : '' ?>><?= htmlspecialchars($brand['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Название</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($item['name']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Описание</label>
                <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($item['description']) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Цена (₽)</label>
                <input type="number" name="price" class="form-control" value="<?= $item['price'] ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Изображение (файл с ПК)</label>
                <input type="file" name="image" class="form-control" accept="image/*">
                <div class="mt-3 mb-2 text-center text-white-50 small">ИЛИ</div>
                <label class="form-label">Ссылка на картинку в интернете</label>
                <input type="url" name="image_url" class="form-control" placeholder="https://example.com/image.jpg">
                <?php if ($item['image_path']): ?>
                    <div class="mt-3">
                        <small class="text-white-50 d-block mb-1">Текущее фото:</small>
                        <img src="<?= $item['image_path'] ?>" width="120" class="rounded">
                    </div>
                <?php endif; ?>
            </div>
            <div class="form-check mb-4">
                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" <?= $item['is_active'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="isActive">Активен (отображается в каталоге)</label>
            </div>
            <button type="submit" class="btn btn-warning w-100 fw-bold">Сохранить</button>
            <a href="admin.php" class="btn btn-outline-light w-100 mt-2">Отмена</a>
        </form>
    </div>
</body>
</html>