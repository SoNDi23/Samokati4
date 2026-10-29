<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$stmtUser = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$userRole = $stmtUser->fetchColumn();

if (!in_array($userRole, ['admin', 'manager'])) {
    die("<h2 style='color: white; text-align: center; margin-top: 50px;'>У вас нет прав для доступа к этой странице.</h2>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- ДОБАВЛЕНИЕ РАБОТЫ В ПОРТФОЛИО ---
    if (isset($_POST['action']) && $_POST['action'] === 'add_portfolio') {
        $description = trim($_POST['description']);
        $image_path = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            $upload_dir = 'uploads/portfolio/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                $image_path = $upload_dir . $filename;
            }
        }
        $stmt = $pdo->prepare("INSERT INTO portfolio (image_path, description) VALUES (?, ?)");
        $stmt->execute([$image_path, $description]);
        header("Location: admin.php?tab=portfolio");
        exit;
    }

    // --- ИЗМЕНЕНИЕ ДАТЫ ЗАПИСИ (DRAG-AND-DROP КАЛЕНДАРЯ) ---
    if (isset($_POST['update_event_date'])) {
        $id = (int)$_POST['event_id'];
        $new_date = date('Y-m-d H:i:s', strtotime($_POST['new_start']));
        
        $pdo->prepare("UPDATE orders SET appointment_datetime = ? WHERE id = ?")->execute([$new_date, $id]);
        
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
        exit;
    }

    // --- УДАЛЕНИЕ РАБОТЫ ИЗ ПОРТФОЛИО ---
    if (isset($_POST['delete_portfolio'])) {
        $id = (int)$_POST['portfolio_id'];
        $stmt = $pdo->prepare("SELECT image_path FROM portfolio WHERE id = ?");
        $stmt->execute([$id]);
        $image = $stmt->fetchColumn();
        if ($image && file_exists($image)) {
            unlink($image);
        }
        $pdo->prepare("DELETE FROM portfolio WHERE id = ?")->execute([$id]);
        header("Location: admin.php?tab=portfolio");
        exit;
    }

    // --- СОРТИРОВКА ПОРТФОЛИО ---
    if (isset($_POST['update_portfolio_sort'])) {
        $order = json_decode($_POST['order'], true);
        foreach ($order as $index => $id) {
            $pdo->prepare("UPDATE portfolio SET sort_order = ? WHERE id = ?")->execute([$index, (int)$id]);
        }
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
        exit;
    }

    // --- СОРТИРОВКА ТОВАРОВ И УСЛУГ ---
    if (isset($_POST['update_sort'])) {
        $table = $_POST['table'] === 'products' ? 'products' : 'services';
        $order = json_decode($_POST['order'], true);
        foreach ($order as $index => $id) {
            $pdo->prepare("UPDATE $table SET sort_order = ? WHERE id = ?")->execute([$index, (int)$id]);
        }
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
        exit;
    }

    // --- ДОБАВЛЕНИЕ ОТЗЫВА ---
    if (isset($_POST['action']) && $_POST['action'] === 'add_review') {
        $author_name = trim($_POST['author_name']);
        $review_text = trim($_POST['review_text']);
        $rating = (int)$_POST['rating'];
        $stmt = $pdo->prepare("INSERT INTO reviews (author_name, review_text, rating) VALUES (?, ?)");
        $stmt->execute([$author_name, $review_text, $rating]);
        header("Location: admin.php?tab=reviews");
        exit;
    }

    // --- УДАЛЕНИЕ ОТЗЫВА ---
    if (isset($_POST['delete_review'])) {
        $id = (int)$_POST['review_id'];
        $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([$id]);
        header("Location: admin.php?tab=reviews");
        exit;
    }

    // --- Обработка AJAX-запросов (Статус и Хит продаж) ---
    if (isset($_POST['toggle_product_status']) || isset($_POST['toggle_service_status']) || isset($_POST['toggle_portfolio_status']) || isset($_POST['toggle_review_status']) || isset($_POST['toggle_product_hit']) || isset($_POST['toggle_service_hit'])) {
        
        $table = ''; $tab = ''; $field = 'is_active'; $actionType = 'active';
        
        if (isset($_POST['toggle_product_status'])) { $table = 'products'; $tab = 'products'; }
        elseif (isset($_POST['toggle_service_status'])) { $table = 'services'; $tab = 'services'; }
        elseif (isset($_POST['toggle_portfolio_status'])) { $table = 'portfolio'; $tab = 'portfolio'; }
        elseif (isset($_POST['toggle_review_status'])) { $table = 'reviews'; $tab = 'reviews'; }
        elseif (isset($_POST['toggle_product_hit'])) { $table = 'products'; $tab = 'products'; $field = 'is_hit'; $actionType = 'hit'; }
        elseif (isset($_POST['toggle_service_hit'])) { $table = 'services'; $tab = 'services'; $field = 'is_hit'; $actionType = 'hit'; }
        
        $id = (int)$_POST['item_id'];
        $pdo->prepare("UPDATE $table SET $field = NOT $field WHERE id = ?")->execute([$id]);
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $stmt = $pdo->prepare("SELECT $field FROM $table WHERE id = ?");
            $stmt->execute([$id]);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'action' => $actionType, 'state' => (bool)$stmt->fetchColumn()]);
            exit;
        }
        header("Location: admin.php?tab=" . $tab); exit;
    }

    // --- ЗАКАЗЫ ---
    if (isset($_POST['update_order_status'])) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ? AND status != 'Отменен'")->execute([$_POST['status'], (int)$_POST['order_id']]);
        header("Location: admin.php?tab=orders"); exit;
    }
    
    if (isset($_POST['delete_order'])) {
        $order_id = (int)$_POST['order_id'];
        $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$order_id]);
        $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$order_id]);
        header("Location: admin.php?tab=orders"); exit;
    }

    // --- СПРАВОЧНИКИ ---
    if (isset($_POST['add_prod_cat']) && !empty(trim($_POST['name']))) {
        $pdo->prepare("INSERT INTO product_categories (name) VALUES (?)")->execute([trim($_POST['name'])]);
        header("Location: admin.php?tab=directories"); exit;
    }
    if (isset($_POST['add_brand']) && !empty(trim($_POST['name']))) {
        $pdo->prepare("INSERT INTO brands (name) VALUES (?)")->execute([trim($_POST['name'])]);
        header("Location: admin.php?tab=directories"); exit;
    }
    if (isset($_POST['add_serv_cat']) && !empty(trim($_POST['name']))) {
        $pdo->prepare("INSERT INTO service_categories (name) VALUES (?)")->execute([trim($_POST['name'])]);
        header("Location: admin.php?tab=directories"); exit;
    }
    if (isset($_POST['del_prod_cat'])) {
        $id = (int)$_POST['id'];
        $pdo->prepare("UPDATE products SET category_id = NULL WHERE category_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM product_categories WHERE id = ?")->execute([$id]);
        header("Location: admin.php?tab=directories"); exit;
    }
    if (isset($_POST['del_brand'])) {
        $id = (int)$_POST['id'];
        $pdo->prepare("UPDATE products SET brand_id = NULL WHERE brand_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM brands WHERE id = ?")->execute([$id]);
        header("Location: admin.php?tab=directories"); exit;
    }
    if (isset($_POST['del_serv_cat'])) {
        $id = (int)$_POST['id'];
        $pdo->prepare("UPDATE services SET category_id = NULL WHERE category_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM service_categories WHERE id = ?")->execute([$id]);
        header("Location: admin.php?tab=directories"); exit;
    }
} // КОНЕЦ БЛОКА POST

// --- ПОЛУЧЕНИЕ ДАННЫХ ---
$portfolios = $pdo->query("SELECT * FROM portfolio ORDER BY sort_order ASC, id DESC")->fetchAll();
$reviews = $pdo->query("SELECT * FROM reviews ORDER BY id DESC")->fetchAll();
$orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();
$products = $pdo->query("SELECT p.*, c.name as cat_name, b.name as brand_name FROM products p LEFT JOIN product_categories c ON p.category_id = c.id LEFT JOIN brands b ON p.brand_id = b.id ORDER BY p.sort_order ASC, p.id DESC")->fetchAll();
$services = $pdo->query("SELECT s.*, c.name as cat_name FROM services s LEFT JOIN service_categories c ON s.category_id = c.id ORDER BY s.sort_order ASC, s.id DESC")->fetchAll();
$prod_cats = $pdo->query("SELECT * FROM product_categories ORDER BY name")->fetchAll();
$brands = $pdo->query("SELECT * FROM brands ORDER BY name")->fetchAll();
$serv_cats = $pdo->query("SELECT * FROM service_categories ORDER BY name")->fetchAll();

$stmtCalendar = $pdo->query("
    SELECT o.id, o.name AS client_name, o.created_at, o.appointment_datetime, s.name AS service_name 
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN services s ON oi.item_id = s.id
    WHERE oi.item_type = 'service' AND o.status != 'Отменен'
");
$calendarEvents = [];
while ($row = $stmtCalendar->fetch()) {
    $start = !empty($row['appointment_datetime']) ? date('Y-m-d\TH:i:s', strtotime($row['appointment_datetime'])) : date('Y-m-d', strtotime($row['created_at']));
    
    $calendarEvents[] = [
        'id' => $row['id'],
        'title' => '#' . $row['id'] . ' ' . $row['client_name'] . ' (' . ($row['service_name'] ?: 'Удаленная услуга') . ')',
        'start' => $start,
        'url' => 'order_details.php?id=' . $row['id'],
        'backgroundColor' => '#f2c94c',
        'borderColor' => '#f2c94c',
        'textColor' => '#091c32'
    ];
}
$calendarEventsJson = json_encode($calendarEvents, JSON_UNESCAPED_UNICODE);
$statuses = ['Новый', 'В обработке', 'Готов', 'Отменен'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <link rel="icon" type="image/png" href="/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления - Самокатыч</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <style>
        body { font-family: 'Montserrat', sans-serif !important; background-color: #05101d !important; color: #ffffff !important; min-height: 100vh; }
        .navbar { background-color: #091c32; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .custom-card { background-color: #091c32; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 15px; }
        .nav-pills .nav-link { color: #fff; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; margin-right: 10px; font-weight: 600; }
        .nav-pills .nav-link.active { background-color: #f2c94c; color: #091c32; border-color: #f2c94c; }
        .table-custom { color: #ffffff !important; }
        .table-custom th, .table-custom td { border-color: rgba(255,255,255,0.1); background-color: transparent; vertical-align: middle; color: #ffffff !important; }
        .form-select-custom, .form-control-custom { background-color: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
        .form-select-custom:focus, .form-control-custom:focus { background-color: rgba(255,255,255,0.1); color: #fff; box-shadow: none; border-color: #f2c94c; }
        .form-select-custom option { background-color: #091c32; color: #fff; }
        .btn-custom { background-color: #f2c94c; color: #091c32; font-weight: 600; border: none; }
        .btn-custom:hover { background-color: #e0b93e; color: #091c32; }
        .list-group-item-custom { background-color: rgba(255,255,255,0.02); border-color: rgba(255,255,255,0.1); color: #fff; display: flex; justify-content: space-between; align-items: center; }
        .fc-theme-standard td, .fc-theme-standard th { border-color: rgba(255,255,255,0.1) !important; }
        .fc-col-header-cell-cushion, .fc-daygrid-day-number { color: #fff !important; text-decoration: none; }
        .fc-col-header-cell-cushion:hover, .fc-daygrid-day-number:hover { color: #f2c94c !important; }
        .fc .fc-toolbar-title { color: #f2c94c; font-weight: 700; }
        .fc .fc-button-primary { background-color: #f2c94c !important; color: #091c32 !important; border: none !important; font-weight: 600; }
        .fc .fc-button-primary:hover { background-color: #e0b93e !important; }
        .fc .fc-button-primary:disabled { background-color: rgba(242, 201, 76, 0.5) !important; }
        .fc-day-today { background-color: rgba(242, 201, 76, 0.1) !important; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="/">
            <span style="color: #ffffff !important; font-weight: 700;">Самокатыч | Админ-панель</span>
        </a>
        <div class="ms-auto">
            <a href="profile.php" class="btn btn-outline-light me-2">В профиль</a>
            <a href="/" class="btn btn-outline-light">На сайт</a>
        </div>
    </div>
</nav>

<div class="container mt-5 mb-5">
    
    <ul class="nav nav-pills mb-4" id="adminTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#orders" type="button">Заказы</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#calendar_tab" type="button">Календарь услуг</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#products" type="button">Товары</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#services" type="button">Услуги</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#directories" type="button">Справочники</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#portfolio_tab" type="button">Наши работы</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#reviews_tab" type="button">Отзывы</button>
        </li>
    </ul>

    <div class="tab-content custom-card p-4">
        
        <div class="tab-pane fade show active" id="orders">
            <h4 class="mb-4">Управление заказами</h4>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Дата</th>
                            <th>Клиент</th>
                            <th>Телефон</th>
                            <th>Сумма</th>
                            <th>Статус / Действие</th>
                            <th>Состав</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?= $order['id'] ?></td>
                            <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                            <td><?= htmlspecialchars($order['name']) ?></td>
                            <td><?= htmlspecialchars($order['phone']) ?></td>
                            <td><?= number_format($order['total_price'], 0, '', ' ') ?> ₽</td>
                            <td>
                                <?php if ($order['status'] === 'Отменен'): ?>
                                    <span class="badge bg-danger mb-2 d-block w-100 py-2">Отменен</span>
                                    <form action="" method="POST" class="m-0" onsubmit="return confirm('Удалить заказ полностью из базы данных?');">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <button type="submit" name="delete_order" class="btn btn-sm btn-outline-danger w-100">Удалить из БД</button>
                                    </form>
                                <?php else: ?>
                                    <form action="" method="POST" class="d-flex align-items-center m-0">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <select name="status" class="form-select form-select-sm form-select-custom me-2" style="width: 140px;">
                                            <?php foreach ($statuses as $st): ?>
                                                <option value="<?= $st ?>" <?= $order['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="update_order_status" class="btn btn-sm btn-outline-warning">OK</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td><a href="order_details.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-light">Посмотреть</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="portfolio_tab">
            <h3 class="mt-4 text-warning">Наши работы</h3>
            
            <div class="card custom-card p-4 mb-4 text-white">
                <h5>Добавить работу в карусель</h5>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_portfolio">
                    <div class="mb-3">
                        <label>Фотография (обязательно)</label>
                        <div id="drop-zone" class="text-center p-4" style="border: 2px dashed rgba(255, 255, 255, 0.2); border-radius: 10px; cursor: pointer; transition: all 0.3s ease;">
                            <span class="drop-zone-text text-white-50 d-block mb-2">Перетащите картинку сюда или нажмите для выбора</span>
                            <input type="file" name="image" id="file-input" class="d-none" accept="image/*" required>
                            <img id="image-preview" src="" alt="Предпросмотр" class="d-none" style="max-height: 150px; border-radius: 8px; margin: 0 auto; display: block;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Краткое описание (например: "Ремонт контроллера Kugoo M4")</label>
                        <input type="text" name="description" class="form-control form-control-custom">
                    </div>
                    <button type="submit" class="btn btn-custom">Добавить фото</button>
                </form>
            </div>

            <table class="table table-dark table-hover align-middle">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>Фото</th>
                        <th>Описание</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody id="portfolio-sortable">
                    <?php foreach ($portfolios as $p): ?>
                    <tr data-id="<?= $p['id'] ?>">
                        <td><span style="cursor: grab; font-size: 1.2rem; color: #f2c94c;">☰</span></td>
                        <td><img src="<?= htmlspecialchars($p['image_path']) ?>" height="50" style="border-radius:5px;"></td>
                        <td><?= htmlspecialchars($p['description']) ?></td>
                        <td>
                            <form action="" method="POST" class="m-0 toggle-status-form">
                                <input type="hidden" name="toggle_portfolio_status" value="1">
                                <input type="hidden" name="item_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn btn-sm <?= $p['is_active'] ? 'btn-success' : 'btn-danger' ?>" style="min-width: 90px;">
                                    <?= $p['is_active'] ? 'Активен' : 'Отключен' ?>
                                </button>
                            </form>
                        </td>
                        <td>
                            <form action="" method="POST" class="m-0" onsubmit="return confirm('Удалить эту работу навсегда?');">
                                <input type="hidden" name="portfolio_id" value="<?= $p['id'] ?>">
                                <button type="submit" name="delete_portfolio" class="btn btn-sm btn-outline-danger">Удалить</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="tab-pane fade" id="reviews_tab">
            <h3 class="mt-4 text-warning">Отзывы клиентов</h3>
            <div class="card custom-card p-4 mb-4 text-white">
                <h5>Добавить отзыв</h5>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="add_review">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label>Имя клиента</label>
                            <input type="text" name="author_name" class="form-control form-control-custom" required>
                        </div>
                        <div class="col-md-4">
                            <label>Оценка (звезды)</label>
                            <select name="rating" class="form-control form-control-custom">
                                <option value="5">5 звезд</option>
                                <option value="4">4 звезды</option>
                                <option value="3">3 звезды</option>
                                <option value="2">2 звезды</option>
                                <option value="1">1 звезда</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Текст отзыва</label>
                        <textarea name="review_text" class="form-control form-control-custom" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-custom">Добавить отзыв</button>
                </form>
            </div>
            <table class="table table-dark table-hover align-middle">
                <thead><tr><th>Имя</th><th>Отзыв</th><th>Оценка</th><th>Статус</th><th>Действия</th></tr></thead>
                <tbody>
                    <?php foreach ($reviews as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['author_name']) ?></td>
                        <td><?= htmlspecialchars(mb_strimwidth($r['review_text'], 0, 50, '...')) ?></td>
                        <td><?= $r['rating'] ?>/5</td>
                        <td>
                            <form action="" method="POST" class="m-0 toggle-status-form">
                                <input type="hidden" name="toggle_review_status" value="1">
                                <input type="hidden" name="item_id" value="<?= $r['id'] ?>">
                                <button type="submit" class="btn btn-sm <?= $r['is_active'] ? 'btn-success' : 'btn-danger' ?>" style="min-width: 90px;">
                                    <?= $r['is_active'] ? 'Активен' : 'Отключен' ?>
                                </button>
                            </form>
                        </td>
                        <td>
                            <form action="" method="POST" class="m-0" onsubmit="return confirm('Удалить этот отзыв?');">
                                <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                <button type="submit" name="delete_review" class="btn btn-sm btn-outline-danger">Удалить</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="tab-pane fade" id="calendar_tab">
            <h4 class="mb-4">График оказания услуг</h4>
            <div id="calendar" style="background-color: #091c32; padding: 20px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.1);"></div>
        </div>

        <div class="tab-pane fade" id="products">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="m-0">Каталог товаров</h4>
                <a href="edit_product.php" class="btn btn-custom btn-sm">Добавить товар</a>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Категория / Бренд</th>
                            <th>Цена</th>
                            <th>Статус</th>
                            <th>Метка</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="products-sortable">
                        <?php foreach ($products as $item): ?>
                        <tr data-id="<?= $item['id'] ?>">
                            <td><span style="cursor: grab; font-size: 1.2rem; color: #f2c94c;">☰</span></td>
                            <td><?= $item['id'] ?></td>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td>
                                <small class="text-white-50 d-block"><?= $item['cat_name'] ?: 'Нет' ?></small>
                                <small class="text-warning"><?= $item['brand_name'] ?: 'Нет' ?></small>
                            </td>
                            <td><?= number_format($item['price'], 0, '', ' ') ?> ₽</td>
                            <td>
                                <form action="" method="POST" class="m-0 toggle-status-form">
                                    <input type="hidden" name="toggle_product_status" value="1">
                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $item['is_active'] ? 'btn-success' : 'btn-danger' ?>" style="min-width: 90px;">
                                        <?= $item['is_active'] ? 'Активен' : 'Отключен' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form action="" method="POST" class="m-0 toggle-status-form">
                                    <input type="hidden" name="toggle_product_hit" value="1">
                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= !empty($item['is_hit']) ? 'btn-warning' : 'btn-outline-secondary' ?>" style="min-width: 100px;">
                                        <?= !empty($item['is_hit']) ? '★ Хит' : '☆ Обычный' ?>
                                    </button>
                                </form>
                            </td>
                            <td><a href="edit_product.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-light">Редакт.</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="services">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="m-0">Услуги ремонта</h4>
                <a href="edit_service.php" class="btn btn-custom btn-sm">Добавить услугу</a>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Категория</th>
                            <th>Цена</th>
                            <th>Статус</th>
                            <th>Метка</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody id="services-sortable">
                        <?php foreach ($services as $item): ?>
                        <tr data-id="<?= $item['id'] ?>">
                            <td><span style="cursor: grab; font-size: 1.2rem; color: #f2c94c;">☰</span></td>
                            <td><?= $item['id'] ?></td>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td><small class="text-white-50"><?= $item['cat_name'] ?: 'Нет' ?></small></td>
                            <td><?= number_format($item['price'], 0, '', ' ') ?> ₽</td>
                            <td>
                                <form action="" method="POST" class="m-0 toggle-status-form">
                                    <input type="hidden" name="toggle_service_status" value="1">
                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $item['is_active'] ? 'btn-success' : 'btn-danger' ?>" style="min-width: 90px;">
                                        <?= $item['is_active'] ? 'Активен' : 'Отключен' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form action="" method="POST" class="m-0 toggle-status-form">
                                    <input type="hidden" name="toggle_service_hit" value="1">
                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= !empty($item['is_hit']) ? 'btn-warning' : 'btn-outline-secondary' ?>" style="min-width: 100px;">
                                        <?= !empty($item['is_hit']) ? '★ Хит' : '☆ Обычный' ?>
                                    </button>
                                </form>
                            </td>
                            <td><a href="edit_service.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-light">Редакт.</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="directories">
            <h4 class="mb-4">Управление категориями и брендами</h4>
            <div class="row g-4">
                <div class="col-md-4">
                    <h6 class="text-warning mb-3">Категории товаров</h6>
                    <form action="" method="POST" class="d-flex mb-3">
                        <input type="text" name="name" class="form-control form-control-sm form-control-custom me-2" placeholder="Новая категория" required>
                        <button type="submit" name="add_prod_cat" class="btn btn-sm btn-custom">+</button>
                    </form>
                    <ul class="list-group">
                        <?php foreach ($prod_cats as $cat): ?>
                            <li class="list-group-item list-group-item-custom">
                                <?= htmlspecialchars($cat['name']) ?>
                                <form action="" method="POST" class="m-0" onsubmit="return confirm('Удалить категорию?');">
                                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                    <button type="submit" name="del_prod_cat" class="btn btn-sm btn-outline-danger py-0 px-2">&times;</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="col-md-4">
                    <h6 class="text-warning mb-3">Бренды</h6>
                    <form action="" method="POST" class="d-flex mb-3">
                        <input type="text" name="name" class="form-control form-control-sm form-control-custom me-2" placeholder="Новый бренд" required>
                        <button type="submit" name="add_brand" class="btn btn-sm btn-custom">+</button>
                    </form>
                    <ul class="list-group">
                        <?php foreach ($brands as $brand): ?>
                            <li class="list-group-item list-group-item-custom">
                                <?= htmlspecialchars($brand['name']) ?>
                                <form action="" method="POST" class="m-0" onsubmit="return confirm('Удалить бренд?');">
                                    <input type="hidden" name="id" value="<?= $brand['id'] ?>">
                                    <button type="submit" name="del_brand" class="btn btn-sm btn-outline-danger py-0 px-2">&times;</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="col-md-4">
                    <h6 class="text-warning mb-3">Категории услуг</h6>
                    <form action="" method="POST" class="d-flex mb-3">
                        <input type="text" name="name" class="form-control form-control-sm form-control-custom me-2" placeholder="Новая категория" required>
                        <button type="submit" name="add_serv_cat" class="btn btn-sm btn-custom">+</button>
                    </form>
                    <ul class="list-group">
                        <?php foreach ($serv_cats as $cat): ?>
                            <li class="list-group-item list-group-item-custom">
                                <?= htmlspecialchars($cat['name']) ?>
                                <form action="" method="POST" class="m-0" onsubmit="return confirm('Удалить категорию?');">
                                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                    <button type="submit" name="del_serv_cat" class="btn btn-sm btn-outline-danger py-0 px-2">&times;</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.querySelectorAll('.toggle-status-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault(); 
        
        const btn = this.querySelector('button');
        const originalText = btn.innerHTML;
        
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        btn.disabled = true;

        fetch('admin.php', {
            method: 'POST',
            body: new FormData(this),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                if (data.action === 'hit') {
                    if (data.state) {
                        btn.classList.remove('btn-outline-secondary');
                        btn.classList.add('btn-warning');
                        btn.innerHTML = '★ Хит';
                    } else {
                        btn.classList.remove('btn-warning');
                        btn.classList.add('btn-outline-secondary');
                        btn.innerHTML = '☆ Обычный';
                    }
                } else {
                    if (data.state) {
                        btn.classList.remove('btn-danger');
                        btn.classList.add('btn-success');
                        btn.innerHTML = 'Активен';
                    } else {
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-danger');
                        btn.innerHTML = 'Отключен';
                    }
                }
            } else {
                btn.innerHTML = originalText;
            }
            btn.disabled = false;
        })
        .catch(error => {
            console.error('Ошибка:', error);
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    if (calendarEl) {
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'ru',
            buttonText: { today: 'Сегодня', month: 'Месяц', week: 'Неделя', day: 'День' },
            events: <?= $calendarEventsJson ?>,
            displayEventEnd: false,
            nextDayThreshold: '24:00:00', 
            editable: true,
            eventDrop: function(info) {
                const formData = new FormData();
                formData.append('update_event_date', '1');
                formData.append('event_id', info.event.id);
                formData.append('new_start', info.event.startStr);

                fetch('admin.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status !== 'success') {
                        info.revert();
                        alert('Ошибка сохранения новой даты');
                    }
                })
                .catch(error => {
                    info.revert();
                    console.error('Ошибка:', error);
                });
            },
            eventClick: function(info) {
                if (info.event.url) {
                    window.location.href = info.event.url;
                    info.jsEvent.preventDefault();
                }
            }
        });
        
        var calendarTab = document.querySelector('button[data-bs-target="#calendar_tab"]');
        if (calendarTab) {
            calendarTab.addEventListener('shown.bs.tab', function () {
                calendar.render();
            });
        }
    }
});

document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    
    if (tab) {
        let activeBtn = document.querySelector(`button[data-bs-target="#${tab}"]`) || document.querySelector(`button[data-bs-target="#${tab}_tab"]`);
        if (activeBtn) new bootstrap.Tab(activeBtn).show();
    }
});

// Логика Drag-and-Drop для загрузки фото
const dropZone = document.getElementById('drop-zone');
const fileInput = document.getElementById('file-input');
const dropZoneText = dropZone.querySelector('.drop-zone-text');
const imagePreview = document.getElementById('image-preview');

if (dropZone && fileInput) {
    dropZone.addEventListener('click', () => fileInput.click());

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, e => {
            e.preventDefault();
            e.stopPropagation();
        }, false);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.style.borderColor = '#f2c94c';
            dropZone.style.backgroundColor = 'rgba(242, 201, 76, 0.05)';
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.style.borderColor = 'rgba(255, 255, 255, 0.2)';
            dropZone.style.backgroundColor = 'transparent';
        }, false);
    });

    dropZone.addEventListener('drop', e => {
        const files = e.dataTransfer.files;
        if (files.length) {
            fileInput.files = files;
            updateThumbnail(files[0]);
        }
    });

    fileInput.addEventListener('change', function() {
        if (this.files.length) updateThumbnail(this.files[0]);
    });

    function updateThumbnail(file) {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = e => {
                imagePreview.src = e.target.result;
                imagePreview.classList.remove('d-none');
                dropZoneText.textContent = file.name;
                dropZoneText.classList.add('text-warning');
            };
            reader.readAsDataURL(file);
        }
    }
}

// Универсальная функция сортировки таблиц
function initSortable(elementId, tableName) {
    const el = document.getElementById(elementId);
    if (el) {
        new Sortable(el, {
            animation: 150,
            handle: 'span',
            onEnd: function () {
                const order = Array.from(el.children).map(tr => tr.dataset.id);
                const formData = new FormData();
                formData.append('update_sort', '1');
                formData.append('table', tableName);
                formData.append('order', JSON.stringify(order));

                fetch('admin.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(res => res.json()).then(data => {
                    if (data.status !== 'success') alert('Ошибка сохранения порядка');
                });
            }
        });
    }
}

// Инициализация сортировки для таблиц
initSortable('portfolio-sortable', 'portfolio');
initSortable('products-sortable', 'products');
initSortable('services-sortable', 'services');

</script>
</body>
</html>