<?php
session_start();
require_once 'db.php';

// Проверка авторизации и прав
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$stmtUser = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$userRole = $stmtUser->fetchColumn();

// Получаем основную информацию о заказе СНАЧАЛА
$stmtOrder = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmtOrder->execute([$orderId]);
$order = $stmtOrder->fetch();

// Проверяем права (админ, менеджер ИЛИ владелец заказа)
if (!in_array($userRole, ['admin', 'manager']) && $order['user_id'] != $_SESSION['user_id']) {
    die("<h2 style='color: white; text-align: center; margin-top: 50px;'>Доступ запрещен.</h2>");
}

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId <= 0) {
    die("<h2 style='color: white; text-align: center; margin-top: 50px;'>Некорректный ID заказа. <br><a href='admin.php' style='color: #f2c94c;'>Вернуться назад</a></h2>");
}

// Получаем основную информацию о заказе
$stmtOrder = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmtOrder->execute([$orderId]);
$order = $stmtOrder->fetch();

if (!$order) {
    die("<h2 style='color: white; text-align: center; margin-top: 50px;'>Заказ не найден. <br><a href='admin.php' style='color: #f2c94c;'>Вернуться назад</a></h2>");
}

// Получаем состав заказа (связываем с таблицами products и services для получения актуальных названий)
$stmtItems = $pdo->prepare("
    SELECT oi.*, 
           p.name AS product_name, 
           s.name AS service_name 
    FROM order_items oi
    LEFT JOIN products p ON oi.item_type = 'product' AND oi.item_id = p.id
    LEFT JOIN services s ON oi.item_type = 'service' AND oi.item_id = s.id
    WHERE oi.order_id = ?
");
$stmtItems->execute([$orderId]);
$items = $stmtItems->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Детали заказа #<?= $orderId ?> - Самокатыч</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif !important; background-color: #05101d !important; color: #ffffff !important; min-height: 100vh; }
        .navbar { background-color: #091c32; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .custom-card { background-color: #091c32; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 15px; }
        .table-custom { color: #ffffff !important; }
        .table-custom th, .table-custom td { border-color: rgba(255,255,255,0.1); background-color: transparent; vertical-align: middle; color: #ffffff !important; }
        .btn-custom { background-color: #f2c94c; color: #091c32; font-weight: 600; border: none; }
        .btn-custom:hover { background-color: #e0b93e; color: #091c32; }
        .info-label { color: #8a98a8; font-size: 0.9rem; margin-bottom: 2px; }
        .info-value { font-size: 1.1rem; font-weight: 600; margin-bottom: 15px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="admin.php">
            <span style="color: #ffffff !important; font-weight: 700;">Самокатыч | Админ-панель</span>
        </a>
       <div class="ms-auto">
    <a href="javascript:history.back()" class="btn btn-outline-light">Назад</a>
</div>
    </div>
</nav>

<div class="container mt-5 mb-5" style="max-width: 900px;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0" style="font-weight: 700;">Заказ #<?= $order['id'] ?></h2>
        <span class="badge fs-5 <?= $order['status'] === 'Готов' ? 'bg-success' : ($order['status'] === 'Отменен' ? 'bg-danger' : 'bg-warning text-dark') ?>">
            <?= htmlspecialchars($order['status']) ?>
        </span>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="custom-card p-4 h-100">
                <h5 class="text-warning mb-4">Информация о клиенте</h5>
                <div class="info-label">Имя клиента</div>
                <div class="info-value"><?= htmlspecialchars($order['name']) ?></div>
                
                <div class="info-label">Телефон</div>
                <div class="info-value"><?= htmlspecialchars($order['phone']) ?></div>
                
                <div class="info-label">Дата оформления</div>
                <div class="info-value"><?= date('d.m.Y в H:i', strtotime($order['created_at'])) ?></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="custom-card p-4 h-100 d-flex flex-column justify-content-center align-items-center text-center">
                <h5 class="text-white-50 mb-3">Итоговая сумма заказа</h5>
                <h1 class="text-warning m-0 fw-bold"><?= number_format($order['total_price'], 0, '', ' ') ?> ₽</h1>
            </div>
        </div>
    </div>

    <div class="custom-card p-4">
        <h5 class="text-warning mb-4">Состав заказа</h5>
        <div class="table-responsive">
            <table class="table table-custom m-0">
                <thead>
                    <tr>
                        <th>Тип</th>
                        <th>Название</th>
                        <th>Цена за ед.</th>
                        <th class="text-center">Кол-во</th>
                        <th class="text-end">Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): 
                        // Определяем название: если товар/услуга были удалены из БД, берем дефолтный текст
                        $itemName = $item['product_name'] ?: $item['service_name'] ?: '<i class="text-muted">Удаленный товар/услуга (ID: '.$item['item_id'].')</i>';
                    ?>
                    <tr>
                        <td>
                            <span class="badge <?= $item['item_type'] === 'product' ? 'bg-primary' : 'bg-info text-dark' ?>">
                                <?= $item['item_type'] === 'product' ? 'Товар' : 'Услуга' ?>
                            </span>
                        </td>
                        <td><?= $itemName ?></td>
                        <td><?= number_format($item['price'], 0, '', ' ') ?> ₽</td>
                        <td class="text-center"><?= $item['quantity'] ?> шт.</td>
                        <td class="text-end fw-bold"><?= number_format($item['price'] * $item['quantity'], 0, '', ' ') ?> ₽</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>