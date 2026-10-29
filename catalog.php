<?php
session_start();
require_once 'db.php';

$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$brandId = isset($_GET['brand']) ? (int)$_GET['brand'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = ['is_active = 1'];
$params = [];

if ($categoryId > 0) {
    $where[] = 'category_id = ?';
    $params[] = $categoryId;
}
if ($brandId > 0) {
    $where[] = 'brand_id = ?';
    $params[] = $brandId;
}
if ($search !== '') {
    $where[] = '(name LIKE ? OR description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT * FROM products WHERE $whereClause ORDER BY id DESC");
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM product_categories ORDER BY name")->fetchAll();
$brands = $pdo->query("SELECT * FROM brands ORDER BY name")->fetchAll();

$searched_services = [];
if ($search !== '') {
    $stmtServ = $pdo->prepare("SELECT * FROM services WHERE is_active = 1 AND (name LIKE ? OR description LIKE ?) ORDER BY id DESC");
    $stmtServ->execute(["%$search%", "%$search%"]);
    $searched_services = $stmtServ->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <link rel="icon" type="image/png" href="/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог - Самокатыч</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif !important; background-color: #05101d !important; color: #ffffff !important; min-height: 100vh; display: flex; flex-direction: column; }
        .navbar { background-color: #091c32; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .custom-card { background-color: #091c32; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 15px; transition: transform 0.3s ease; }
        .custom-card:hover { transform: translateY(-5px); border-color: #f2c94c; }
        .sidebar-card { background-color: #091c32; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 15px; }
        .btn-custom { background-color: #f2c94c; color: #091c32; font-weight: 600; border: none; }
        .btn-custom:hover { background-color: #e0b93e; color: #091c32; }
        .form-control-custom { background-color: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
        .form-control-custom:focus { background-color: rgba(255,255,255,0.1); color: #fff; box-shadow: none; border-color: #f2c94c; }
        .hover-yellow:hover { color: #f2c94c !important; transition: color 0.2s ease; }
        .filter-link { color: #fff; text-decoration: none; display: block; padding: 5px 0; transition: color 0.2s; }
        .filter-link:hover, .filter-link.active { color: #f2c94c; font-weight: 600; }
        .footer { background-color: #091c32; border-top: 1px solid rgba(255, 255, 255, 0.05); padding: 2rem 0; margin-top: auto; }
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
            
            <!-- Ссылки -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                <li class="nav-item"><a class="nav-link text-white" href="services.php">Услуги</a></li>
                <li class="nav-item"><a class="nav-link" style="color: #f2c94c; font-weight: 600;" href="catalog.php">Товары и запчасти</a></li>
            </ul>
            
            <!-- Строка поиска -->
            <form action="catalog.php" method="GET" class="d-flex mx-auto w-50 mb-2 mb-lg-0">
    <input class="form-control me-2" type="text" name="search" placeholder="Поиск по сайту..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" required>
    <button class="btn btn-outline-warning" type="submit">Найти</button>
</form>

            <!-- Корзина и профиль -->
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item me-lg-3 mt-3 mt-lg-0">
                    <a href="cart.php" class="btn btn-outline-warning position-relative d-flex align-items-center">
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
    <div class="row g-4">
        
        <!-- Боковое меню -->
        <div class="col-lg-3">
            <div class="sidebar-card p-4 sticky-top" style="top: 100px;">
                <h5 class="text-warning mb-3">Категории</h5>
                <div class="mb-4">
                    <a href="catalog.php<?= $brandId ? '?brand='.$brandId : '' ?>" class="filter-link <?= $categoryId === 0 ? 'active' : '' ?>">Все товары</a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="catalog.php?category=<?= $cat['id'] ?><?= $brandId ? '&brand='.$brandId : '' ?>" class="filter-link <?= $categoryId === $cat['id'] ? 'active' : '' ?>">
                            <?= htmlspecialchars($cat['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <h5 class="text-warning mb-3">Бренды</h5>
                <div class="mb-4">
                    <a href="catalog.php<?= $categoryId ? '?category='.$categoryId : '' ?>" class="filter-link <?= $brandId === 0 ? 'active' : '' ?>">Все бренды</a>
                    <?php foreach ($brands as $brand): ?>
                        <a href="catalog.php?brand=<?= $brand['id'] ?><?= $categoryId ? '&category='.$categoryId : '' ?>" class="filter-link <?= $brandId === $brand['id'] ? 'active' : '' ?>">
                            <?= htmlspecialchars($brand['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if ($categoryId > 0 || $brandId > 0 || $search !== ''): ?>
                    <a href="catalog.php" class="btn btn-outline-danger btn-sm w-100 mt-2">Сбросить фильтры</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-9">
            <?php if ($search !== ''): ?>
                <h3 class="mb-4 fw-bold">Результаты поиска: "<?= htmlspecialchars($search) ?>"</h3>
                
                <?php if (empty($products) && empty($searched_services)): ?>
                    <div class="alert alert-dark border-secondary text-center">К сожалению, по вашему запросу ничего не найдено.</div>
                <?php endif; ?>

                <?php if (!empty($searched_services)): ?>
                    <h4 class="text-warning mb-3 mt-4">Найденные услуги</h4>
                    <div class="row g-4 mb-5">
                        <?php foreach ($searched_services as $service): ?>
                            <div class="col-sm-6 col-md-6 col-lg-4">
                                <div class="card custom-card h-100">
                                    <a href="service.php?id=<?= $service['id'] ?>" class="text-decoration-none">
                                        <img src="<?= htmlspecialchars($service['image_path'] ?: 'placeholder.png') ?>" class="card-img-top" style="height: 200px; object-fit: cover; border-radius: 15px 15px 0 0;">
                                    </a>
                                    <div class="card-body d-flex flex-column">
                                        <a href="service.php?id=<?= $service['id'] ?>" class="text-decoration-none">
                                            <h5 class="card-title text-white hover-yellow mb-2"><?= htmlspecialchars($service['name']) ?></h5>
                                        </a>
                                        <p class="card-text small mb-2" style="color: #aeb9c6 !important;">
                                            <?= htmlspecialchars(mb_strimwidth($service['description'], 0, 80, '...')) ?>
                                        </p>
                                        <div class="mt-auto d-flex justify-content-between align-items-center">
                                            <span class="fw-bold fs-5 text-white"><?= number_format($service['price'], 0, '', ' ') ?> ₽</span>
                                            <form action="add_to_cart.php" method="POST" class="m-0">
                                                <input type="hidden" name="type" value="service">
                                                <input type="hidden" name="id" value="<?= $service['id'] ?>">
                                                <button type="submit" class="btn btn-custom btn-sm">В корзину</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($products)): ?>
                    <h4 class="text-warning mb-3">Найденные товары и запчасти</h4>
                <?php endif; ?>

            <?php else: ?>
                <h2 class="mb-4" style="font-weight: 700;">Каталог товаров</h2>
            <?php endif; ?>

            <div class="row g-4">
                <?php if (empty($products) && $search === ''): ?>
                    <div class="col-12">
                        <div class="alert alert-dark border-secondary text-center">В данной категории пока нет товаров.</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col-sm-6 col-md-6 col-lg-4">
                            <div class="card custom-card h-100">
                                <a href="product.php?id=<?= $product['id'] ?>" class="text-decoration-none">
                                    <img src="<?= htmlspecialchars($product['image_path'] ?: 'placeholder.png') ?>" class="card-img-top" style="height: 200px; object-fit: cover; border-radius: 15px 15px 0 0;">
                                </a>
                                <div class="card-body d-flex flex-column">
                                    <a href="product.php?id=<?= $product['id'] ?>" class="text-decoration-none">
                                        <h5 class="card-title text-white hover-yellow mb-2"><?= htmlspecialchars($product['name']) ?></h5>
                                    </a>
                                    <p class="card-text small mb-3" style="color: #aeb9c6 !important;">
                                        <?= htmlspecialchars(mb_strimwidth($product['description'], 0, 80, '...')) ?>
                                    </p>
                                    <div class="mt-auto d-flex justify-content-between align-items-center">
                                        <span class="fw-bold fs-5 text-white"><?= number_format($product['price'], 0, '', ' ') ?> ₽</span>
                                        <form action="add_to_cart.php" method="POST" class="m-0">
                                            <input type="hidden" name="type" value="product">
                                            <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                            <button type="submit" class="btn btn-custom btn-sm">В корзину</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
<script>
document.querySelectorAll('form[action="add_to_cart.php"]').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault(); 
        const btn = this.querySelector('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        btn.disabled = true;

        fetch('add_to_cart.php', {
            method: 'POST',
            body: new FormData(this),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.querySelectorAll('.badge.bg-danger').forEach(badge => {
                    badge.innerText = data.cart_count;
                });
                btn.innerHTML = 'В корзине!';
                btn.classList.remove('btn-custom');
                btn.classList.add('btn-success');
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-custom');
                    btn.disabled = false;
                }, 1500);
            }
        })
        .catch(error => {
            console.error('Ошибка:', error);
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    });
});
</script>
</body>
</html>