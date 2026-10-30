<?php
session_start();
require_once 'db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        name VARCHAR(255) NOT NULL,
        phone VARCHAR(50) NOT NULL,
        total_price DECIMAL(10,2) NOT NULL,
        appointment_datetime DATETIME NULL,
        status VARCHAR(50) DEFAULT 'Новый',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        item_type VARCHAR(50) NOT NULL,
        item_id INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        quantity INT DEFAULT 1,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )");
} catch (PDOException $e) {} 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $key = $_POST['key'];
    $action = $_POST['action'];

    if (isset($_SESSION['cart'][$key])) {
        if (is_array($_SESSION['cart'][$key])) {
            if ($action === 'plus') $_SESSION['cart'][$key]['quantity']++;
            elseif ($action === 'minus') $_SESSION['cart'][$key]['quantity']--;
            if ($_SESSION['cart'][$key]['quantity'] <= 0) unset($_SESSION['cart'][$key]);
        } else {
            if ($action === 'plus') $_SESSION['cart'][$key]++;
            elseif ($action === 'minus') $_SESSION['cart'][$key]--;
            if ($_SESSION['cart'][$key] <= 0) unset($_SESSION['cart'][$key]);
        }
    }
    header("Location: cart.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $key = $_POST['key'];
    if (isset($_SESSION['cart'][$key])) unset($_SESSION['cart'][$key]);
    header("Location: cart.php");
    exit;
}

$cart_items = [];
$total_price = 0;
$has_services = false;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $key => $cart_item) {
        if (is_array($cart_item)) {
            $type = $cart_item['type'];
            $id = (int)$cart_item['id'];
            $qty = (int)$cart_item['quantity'];
        } else {
            list($type, $id) = explode('_', $key);
            $qty = (int)$cart_item;
        }

        if ($type === 'service') {
            $has_services = true;
            $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        }
        $stmt->execute([$id]);
        $db_item = $stmt->fetch();

        if ($db_item) {
            $price = $db_item['price'];
            $total_price += $price * $qty;
            $cart_items[$key] = [
                'db_id' => $id,
                'type' => $type,
                'name' => $db_item['name'],
                'image' => $db_item['image_path'] ?? '',
                'price' => $price,
                'quantity' => $qty
            ];
        }
    }
}

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $user_id = $_SESSION['user_id'] ?? null;
    
    $appointment = null;
    if (!empty($_POST['appointment'])) {
        $appointment = str_replace('T', ' ', $_POST['appointment']) . ':00';
    }

    if (empty($cart_items)) {
        $error_msg = 'Ваша корзина пуста.';
    } elseif (empty($name) || empty($phone)) {
        $error_msg = 'Пожалуйста, заполните имя и телефон.';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, name, phone, total_price, appointment_datetime, status) VALUES (?, ?, ?, ?, ?, 'Новый')");
            $stmt->execute([$user_id, $name, $phone, $total_price, $appointment]);
            $order_id = $pdo->lastInsertId();

            $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, item_type, item_id, price, quantity) VALUES (?, ?, ?, ?, ?)");
            foreach ($cart_items as $item) {
                $stmtItem->execute([$order_id, $item['type'], $item['db_id'], $item['price'], $item['quantity']]);
            }

            $pdo->commit();
            
            // Уведомление в Telegram
            $token = "8";
            $chat_id = "5";
            
            $msg = "🛴 *Новый заказ #$order_id*\n";
            $msg .= "👤 Клиент: $name\n";
            $msg .= "📞 Телефон: $phone\n";
            $msg .= "💰 Сумма: " . number_format($total_price, 0, '', ' ') . " ₽\n";
            if (!empty($appointment)) {
                $msg .= "📅 Запись: " . date('d.m.Y H:i', strtotime($appointment));
            }

            $url = "https://api.telegram.org/bot$token/sendMessage?chat_id=$chat_id&parse_mode=Markdown&text=" . urlencode($msg);
            @file_get_contents($url);

            unset($_SESSION['cart']);
            $cart_items = []; 
            $total_price = 0;
            $success_msg = "Ваш заказ #$order_id успешно оформлен!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_msg = "Ошибка при сохранении заказа: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <link rel="icon" type="image/png" href="/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина - Самокатыч</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif !important; background-color: #05101d !important; color: #ffffff !important; min-height: 100vh; display: flex; flex-direction: column; }
        .navbar { background-color: #091c32; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .custom-card { background-color: #091c32; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 15px; }
        .btn-custom { background-color: #f2c94c; color: #091c32; font-weight: 600; border: none; }
        .btn-custom:hover { background-color: #e0b93e; color: #091c32; }
        .form-control-custom { background-color: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
        .form-control-custom:focus { background-color: rgba(255,255,255,0.1); color: #fff; box-shadow: none; border-color: #f2c94c; color-scheme: dark; }
        .qty-btn { background-color: transparent; border: 1px solid rgba(255,255,255,0.2); color: #fff; padding: 2px 10px; cursor: pointer; transition: 0.2s; }
        .qty-btn:hover { background-color: rgba(255,255,255,0.1); }
        .footer { background-color: #091c32; border-top: 1px solid rgba(255, 255, 255, 0.05); padding: 2rem 0; margin-top: auto; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="/">
            <span style="color: #ffffff !important; font-weight: 700;">Самокатыч</span>
        </a>
        <div class="ms-auto d-flex align-items-center">
            <a href="catalog.php" class="btn btn-outline-light me-3">Продолжить покупки</a>
            <a href="profile.php" class="btn btn-custom">Профиль</a>
        </div>
    </div>
</nav>

<div class="container mt-5 mb-5">
    <h2 class="mb-4 fw-bold">Корзина</h2>

    <?php if ($success_msg): ?>
        <div class="alert alert-success border-success bg-dark text-success fw-bold p-4 rounded-3">
            <?= htmlspecialchars($success_msg) ?>
        </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div class="alert alert-danger border-danger bg-dark text-danger fw-bold p-4 rounded-3">
            <?= htmlspecialchars($error_msg) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($cart_items) && !$success_msg): ?>
        <div class="custom-card p-5 text-center">
            <h4 class="text-white-50 mb-4">Ваша корзина пуста</h4>
            <a href="catalog.php" class="btn btn-custom">Перейти в каталог</a>
        </div>
    <?php elseif (!$success_msg): ?>
        <div class="row g-4">
            
            <div class="col-lg-8">
                <div class="custom-card p-4">
                    <?php foreach ($cart_items as $key => $item): ?>
                        <div class="d-flex justify-content-between align-items-center border-bottom border-secondary pb-3 mb-3">
                            <div class="d-flex align-items-center" style="width: 40%;">
                                <img src="<?= htmlspecialchars($item['image'] ?: 'placeholder.png') ?>" alt="" width="70" height="70" class="rounded me-3" style="object-fit: cover;">
                                <div>
                                    <h6 class="mb-1 text-white"><?= htmlspecialchars($item['name']) ?></h6>
                                    <small class="text-white-50"><?= $item['type'] === 'product' ? 'Товар' : 'Услуга' ?></small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <form method="POST" action="cart.php" class="m-0 d-flex">
                                    <input type="hidden" name="update_quantity" value="1">
                                    <input type="hidden" name="key" value="<?= $key ?>">
                                    <button type="submit" name="action" value="minus" class="qty-btn rounded-start">-</button>
                                    <div class="bg-transparent text-white border-top border-bottom border-secondary px-3 d-flex align-items-center" style="font-size: 0.95rem;">
                                        <?= $item['quantity'] ?> шт.
                                    </div>
                                    <button type="submit" name="action" value="plus" class="qty-btn rounded-end">+</button>
                                </form>
                            </div>
                            <div class="d-flex align-items-center text-end" style="width: 25%; justify-content: flex-end;">
                                <span class="fw-bold text-white fs-5 me-3"><?= number_format($item['price'] * $item['quantity'], 0, '', ' ') ?> ₽</span>
                                <form method="POST" action="cart.php" class="m-0">
                                    <input type="hidden" name="remove_item" value="1">
                                    <input type="hidden" name="key" value="<?= $key ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2 fs-5" style="line-height: 1.2;">&times;</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <h4 class="text-white-50 m-0">Итого:</h4>
                        <h2 class="text-warning fw-bold m-0"><?= number_format($total_price, 0, '', ' ') ?> ₽</h2>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="custom-card p-4 sticky-top" style="top: 100px;">
                    <h5 class="text-warning mb-4 fw-bold">Оформление заказа</h5>
                    <form method="POST" action="cart.php">
                        <div class="mb-3">
                            <label class="form-label text-white-50">Ваше имя *</label>
                            <input type="text" name="name" class="form-control form-control-custom" required value="<?= isset($_SESSION['user_id']) ? 'Даня' : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white-50">Контактный телефон *</label>
                            <input type="text" name="phone" class="form-control form-control-custom" required value="<?= isset($_SESSION['user_id']) ? '+79898182613' : '' ?>">
                        </div>
                        
                        <?php if ($has_services): ?>
                        <div class="mb-4">
                            <label class="form-label text-warning fw-bold">Дата и время визита *</label>
                            <input type="datetime-local" name="appointment" class="form-control form-control-custom" required>
                        </div>
                        <?php endif; ?>

                        <button type="submit" name="checkout" class="btn btn-custom w-100 fw-bold fs-5 py-2 mt-2">Подтвердить заказ</button>
                    </form>
                </div>
            </div>

        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
