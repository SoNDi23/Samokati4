<?php
session_start();
require_once 'db.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    die("Некорректный ID товара.");
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    die("Товар не найден или снят с продажи.");
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <link rel="icon" type="image/png" href="/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - Самокатыч</title>
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
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                <li class="nav-item"><a class="nav-link text-white" href="services.php">Услуги ремонта</a></li>
                <li class="nav-item"><a class="nav-link" style="color: #f2c94c; font-weight: 600;" href="catalog.php">Товары и запчасти</a></li>
            </ul>
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item me-lg-3 mt-3 mt-lg-0">
                    <a href="cart.php" class="btn btn-outline-warning position-relative d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                            <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l1.313 7h8.17l1.313-7H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                        </svg>
                        Корзина
                        <span class="badge bg-danger ms-2"><?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?></span>
                    </a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item ms-lg-2"><a href="profile.php" class="btn btn-custom">Профиль</a></li>
                <?php else: ?>
                    <li class="nav-item ms-lg-2"><a href="login.php" class="btn btn-custom">Войти</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5 mb-5">
    <div class="card custom-card p-4 p-md-5 shadow-lg">
        <div class="row g-5">
            <div class="col-md-6">
                <img src="<?= htmlspecialchars($product['image_path'] ?: 'placeholder.png') ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img shadow">
            </div>
            <div class="col-md-6 d-flex flex-column">
                <h1 class="mb-3" style="font-weight: 700; color: #f2c94c;"><?= htmlspecialchars($product['name']) ?></h1>
                <p class="fs-4 fw-bold text-white mb-4"><?= number_format($product['price'], 0, '', ' ') ?> ₽</p>
                
                <h5 class="text-white-50 mb-3">Описание</h5>
                <p class="text-white mb-4" style="line-height: 1.6; white-space: pre-wrap;"><?= htmlspecialchars($product['description']) ?></p>
                
                <div class="mt-auto">
                    <form action="add_to_cart.php" method="POST" class="d-flex align-items-center gap-3">
                        <input type="hidden" name="type" value="product">
                        <input type="hidden" name="id" value="<?= $product['id'] ?>">
                        <button type="submit" class="btn btn-custom btn-lg w-100">Добавить в корзину</button>
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