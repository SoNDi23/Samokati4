<?php
session_start();
require_once 'db.php';

$serviceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($serviceId <= 0) {
    die("Некорректный ID услуги.");
}

$stmt = $pdo->prepare("SELECT * FROM services WHERE id = ? AND is_active = 1");
$stmt->execute([$serviceId]);
$service = $stmt->fetch();

if (!$service) {
    die("Услуга не найдена или временно недоступна.");
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <link rel="icon" type="image/png" href="/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($service['name']) ?> - Самокатыч</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif !important; background-color: #05101d !important; color: #ffffff !important;
            min-height: 100vh; display: flex; flex-direction: column;
            background-image: url("data:image/svg+xml,%3Csvg width='250' height='250' viewBox='0 0 250 250' xmlns='http://www.w3.org/2000/svg' fill='%23f2c94c' stroke='%23f2c94c' stroke-opacity='0.08' fill-opacity='0.08'%3E%3Cg transform='translate(40, 30) scale(2.5)' stroke='none'%3E%3Cpath d='M12 2L5 14h5v10l9-14h-6z'/%3E%3C/g%3E%3Cg transform='translate(180, 80) scale(1.2)' stroke='none'%3E%3Cpath d='M 0 -22 L 4 -22 L 6 -15 L 10 -11 L 16 -13 L 19 -9 L 14 -4 L 14 4 L 19 9 L 16 13 L 10 11 L 6 15 L 4 22 L -4 22 L -6 15 L -10 11 L -16 13 L -19 9 L -14 4 L -14 -4 L -19 -9 L -16 -13 L -10 -11 L -6 -15 Z M 0 -7 A 7 7 0 1 0 0 7 A 7 7 0 1 0 0 -7 Z' fill-rule='evenodd'/%3E%3C/g%3E%3Cg transform='translate(70, 140) scale(2.5)' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' fill='none'%3E%3Ccircle cx='8' cy='20' r='4'/%3E%3Ccircle cx='28' cy='20' r='4'/%3E%3Cpath d='M8 20h18M26 20l-4-16l2 0M18 4h8'/%3E%3C/g%3E%3C/svg%3E") !important;
            background-attachment: fixed;
        }
        .navbar { background-color: #091c32; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .custom-card { background-color: #091c32; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 15px; backdrop-filter: blur(5px); }
        .btn-custom { background-color: #f2c94c; color: #091c32; font-weight: 600; border: none; }
        .btn-custom:hover { background-color: #e0b93e; color: #091c32; }
        .footer { background-color: #091c32; border-top: 1px solid rgba(255, 255, 255, 0.05); padding: 2rem 0; margin-top: auto; }
        .product-img { width: 100%; border-radius: 15px; object-fit: cover; max-height: 500px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="/">
            <img src="logo.png" alt="Логотип" style="height: 55px; border-radius: 8px; margin-right: 12px;">
            <span style="color: #ffffff !important; font-weight: 700;">Самокатыч</span>
        </a>
        <div class="ms-auto d-flex align-items-center">
            <a href="cart.php" class="btn btn-outline-warning d-flex align-items-center me-3">
                Корзина <span class="badge bg-danger ms-2">0</span>
            </a>
            <a href="services.php" class="btn btn-outline-light">Назад к услугам</a>
        </div>
    </div>
</nav>

<div class="container mt-5 mb-5">
    <div class="card custom-card p-4 p-md-5 shadow-lg">
        <div class="row g-5">
            <div class="col-md-6">
                <img src="<?= htmlspecialchars($service['image_path'] ?: 'placeholder.png') ?>" alt="<?= htmlspecialchars($service['name']) ?>" class="product-img shadow">
            </div>
            <div class="col-md-6 d-flex flex-column">
                <h1 class="mb-3" style="font-weight: 700; color: #f2c94c;"><?= htmlspecialchars($service['name']) ?></h1>
                
                <div class="d-flex align-items-center mb-4">
                    <p class="fs-4 fw-bold text-white mb-0 me-4"><?= number_format($service['price'], 0, '', ' ') ?> ₽</p>
                    <?php if (!empty($service['estimated_time'])): ?>
                        <span class="badge bg-secondary fs-6 py-2 px-3">Время: <?= htmlspecialchars($service['estimated_time']) ?></span>
                    <?php endif; ?>
                </div>
                
                <h5 class="text-white-50 mb-3">Описание работ</h5>
                <p class="text-white mb-4" style="line-height: 1.6; white-space: pre-wrap;"><?= htmlspecialchars($service['description']) ?></p>
                
                <div class="mt-auto">
                    <form action="add_to_cart.php" method="POST" class="d-flex align-items-center gap-3">
                        <input type="hidden" name="type" value="service">
                        <input type="hidden" name="id" value="<?= $service['id'] ?>">
                        <button type="submit" class="btn btn-custom btn-lg w-100">Заказать услугу</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="footer text-center">
    <div class="container text-white-50 small">&copy; 2026 Самокатыч. Все права защищены.</div>
</footer>

</body>
</html>