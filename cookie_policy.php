<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Политика использования Cookie - Самокатыч</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif !important; background-color: #05101d !important; color: #ffffff !important; min-height: 100vh; display: flex; flex-direction: column; }
        .navbar { background-color: #091c32; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .custom-card { background-color: #091c32; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 15px; }
        .btn-custom { background-color: #f2c94c; color: #091c32; font-weight: 600; border: none; }
        .btn-custom:hover { background-color: #e0b93e; color: #091c32; }
        .footer { background-color: #091c32; border-top: 1px solid rgba(255, 255, 255, 0.05); padding: 2rem 0; margin-top: auto; }
        .policy-text { color: #aeb9c6; line-height: 1.8; }
        .policy-text h5 { color: #f2c94c; margin-top: 2rem; font-weight: 600; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="/">
            <span style="color: #ffffff !important; font-weight: 700;">Самокатыч</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                <li class="nav-item"><a class="nav-link text-white" href="services.php">Услуги ремонта</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="catalog.php">Товары и запчасти</a></li>
            </ul>
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item me-lg-3 mt-3 mt-lg-0">
                    <a href="cart.php" class="btn btn-outline-warning d-flex align-items-center">
                        Корзина
                        <span class="badge bg-danger ms-2"><?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?></span>
                    </a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item ms-lg-2"><a href="profile.php" class="btn btn-custom">Профиль</a></li>
                <?php else: ?>
                    <li class="nav-item ms-lg-2"><a href="login.php" class="btn btn-custom text-nowrap px-4 py-2">Войти</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5 mb-5 flex-grow-1">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="custom-card p-4 p-md-5">
                <h2 class="fw-bold mb-4">Политика использования файлов cookie</h2>
                <div class="policy-text">
                    <p>Настоящая политика объясняет, как интернет-магазин и мастерская «Самокатыч» использует файлы cookie и аналогичные технологии на нашем сайте. Продолжая использовать сайт, вы соглашаетесь с применением данных технологий в соответствии с этим документом.</p>

                    <h5>1. Что такое файлы cookie?</h5>
                    <p>Файлы cookie — это небольшие текстовые файлы, которые сохраняются на вашем устройстве (компьютере, планшете или смартфоне) при посещении веб-сайтов. Они позволяют сайту «запоминать» ваши действия и предпочтения (например, логин, содержимое корзины, язык и другие настройки отображения) на определенный период времени.</p>

                    <h5>2. Как мы используем cookie</h5>
                    <p>Мы используем файлы cookie исключительно для обеспечения корректной работы функционала сайта. В частности, они необходимы для:</p>
                    <ul>
                        <li><strong>Авторизации и безопасности:</strong> чтобы вы могли входить в свой личный кабинет и безопасно управлять своими заказами.</li>
                        <li><strong>Работы корзины:</strong> чтобы сохранять выбранные вами товары и услуги ремонта при переходе между страницами сайта.</li>
                        <li><strong>Оформления заказов:</strong> для корректной связи ваших данных и выбранных позиций в момент подтверждения заказа.</li>
                    </ul>

                    <h5>3. Типы используемых файлов cookie</h5>
                    <p>На нашем сайте используются только <strong>строго необходимые (технические) cookie</strong>. Это сессионные файлы, которые создаются для поддержания вашей сессии (входа в аккаунт и корзины) и удаляются автоматически после закрытия браузера или по истечении короткого промежутка времени.</p>

                    <h5>4. Файлы cookie третьих лиц</h5>
                    <p>Мы не передаем ваши данные сторонним рекламным сетям. Вся собираемая техническая информация используется локально для работы платформы «Самокатыч».</p>

                    <h5>5. Управление файлами cookie</h5>
                    <p>Вы можете в любой момент изменить настройки своего браузера, чтобы блокировать файлы cookie или получать предупреждения об их отправке на ваше устройство. Обратите внимание, что отключение строго необходимых файлов cookie приведет к невозможности авторизации в личном кабинете и оформления заказов через корзину.</p>

                    <p class="mt-5 small text-white-50">Дата последнего обновления: <?= date('d.m.Y') ?></p>
                </div>
                
                <div class="mt-4 pt-3 border-top border-secondary text-center">
                    <a href="javascript:history.back()" class="btn btn-outline-light px-4">Вернуться назад</a>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="footer text-center">
    <div class="container text-white-50 small">
        &copy; 2026 Самокатыч. Все права защищены.
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>