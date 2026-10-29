<?php
session_start();
require_once 'db.php';

$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = ['is_active = 1'];
$params = [];

if ($categoryId > 0) {
    $where[] = 'category_id = ?';
    $params[] = $categoryId;
}
if ($search !== '') {
    $where[] = '(name LIKE ? OR description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT * FROM services WHERE $whereClause ORDER BY id DESC");
$stmt->execute($params);
$services = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM service_categories ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <link rel="icon" type="image/png" href="/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Услуги - Самокатыч</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* ============================================================
           ДИЗАЙН-ТОКЕНЫ (золотое сечение как основа ритма)
           ============================================================ */
        :root {
            --bg-0: #050a12;
            --bg-1: #0a1524;
            --bg-2: #101f33;
            --line: rgba(255,255,255,.08);
            --gold: #f2c94c;
            --gold-soft: rgba(242,201,76,.12);
            --gold-strong: #ffd76a;
            --text-1: #f5f7fa;
            --text-2: #a9b6c6;
            --text-3: #6b7a8c;
            
            --phi: 1.618;
            --space: 1rem;
            --space-phi2: calc(var(--space) * 1.618 * 1.618);
            --space-phi3: calc(var(--space) * 4.236);
            --radius: 18px;
            --ease: cubic-bezier(.2,.8,.2,1);
            --shadow-gold: 0 20px 50px -20px rgba(242,201,76,.35);

            --fs-xs: .75rem;
            --fs-sm: .875rem;
            --fs-md: 1rem;
            --fs-lg: 1.25rem;
            --fs-xl: 1.618rem;
            --fs-2xl: 2.058rem;
        }

        *, *::before, *::after { box-sizing: border-box; }
        
        body {
            margin: 0;
            font-family: 'Manrope', system-ui, -apple-system, sans-serif;
            background: var(--bg-0);
            color: var(--text-1);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            background-image:
                radial-gradient(1200px 600px at 85% -10%, rgba(242,201,76,.10), transparent 60%),
                radial-gradient(900px 500px at -10% 20%, rgba(41,98,255,.10), transparent 60%);
            background-attachment: fixed;
            display: flex; flex-direction: column; min-height: 100vh;
        }
        
        a { color: inherit; }
        
        .container-phi {
            width: 100%; max-width: 1240px; margin-inline: auto; padding-inline: clamp(16px, 4vw, 32px);
        }

        /* ============================================================
           ТИПОГРАФИКА
           ============================================================ */
        .h2 { font-size: clamp(1.5rem, 3vw, var(--fs-2xl)); font-weight: 800; line-height: 1.15; letter-spacing: -.01em; margin: 0 0 .5rem; }
        .muted { color: var(--text-2); }
        .eyebrow { font-size: var(--fs-xs); letter-spacing: .18em; text-transform: uppercase; color: var(--gold); font-weight: 700; margin: 0 0 .5rem; }

        /* ============================================================
           КНОПКИ
           ============================================================ */
        .btn-gold, .btn-ghost {
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            min-height: 44px; padding: .75rem 1.5rem; border-radius: 999px;
            font-weight: 700; font-size: var(--fs-sm); text-decoration: none; border: 1px solid transparent;
            cursor: pointer; transition: transform .25s var(--ease), background .25s var(--ease), color .25s var(--ease), box-shadow .25s var(--ease), border-color .25s var(--ease);
            white-space: nowrap;
        }
        .btn-gold { background: linear-gradient(135deg, var(--gold-strong), var(--gold)); color: #1a1200; box-shadow: var(--shadow-gold); }
        .btn-gold:hover { transform: translateY(-2px); color: #1a1200; }
        .btn-ghost { background: transparent; color: var(--text-1); border-color: var(--line); }
        .btn-ghost:hover { border-color: var(--gold); color: var(--gold); }
        .btn-sm { min-height: 36px; padding: .45rem 1rem; font-size: var(--fs-xs); }
        .btn-outline-danger { border-radius: 999px; font-weight: 600; font-size: var(--fs-sm); transition: all 0.2s var(--ease); }

        /* ============================================================
           ХЕДЕР
           ============================================================ */
        .site-header {
            position: sticky; top: 0; z-index: 1030; background: rgba(5,10,18,.72);
            backdrop-filter: blur(18px) saturate(140%); -webkit-backdrop-filter: blur(18px) saturate(140%);
            border-bottom: 1px solid var(--line);
        }
        .header-inner { display: flex; align-items: center; gap: 1.5rem; min-height: 72px; }
        .brand { display: flex; align-items: center; gap: .75rem; text-decoration: none; flex-shrink: 0; }
        .brand-logo { width: 44px; height: 44px; object-fit: cover; border-radius: 12px; border: 1px solid var(--line); background: var(--bg-2); }
        .brand-name { font-weight: 800; font-size: var(--fs-lg); letter-spacing: -.01em; }
        .brand-name span { color: var(--gold); }
        
        .nav-main { display: flex; gap: .25rem; margin-left: .5rem; }
        .nav-main a { padding: .55rem .9rem; border-radius: 999px; color: var(--text-2); text-decoration: none; font-weight: 600; font-size: var(--fs-sm); transition: color .2s var(--ease), background .2s var(--ease); }
        .nav-main a:hover { color: var(--text-1); background: rgba(255,255,255,.05); }
        .nav-main a.active { color: var(--gold); }

        .search {
            flex: 1; max-width: 380px; margin-left: auto; display: flex; align-items: center;
            background: var(--bg-1); border: 1px solid var(--line); border-radius: 999px;
            padding: .25rem .25rem .25rem 1rem; transition: border-color .2s var(--ease), box-shadow .2s var(--ease);
        }
        .search:focus-within { border-color: rgba(242,201,76,.5); box-shadow: 0 0 0 4px rgba(242,201,76,.12); }
        .search input { flex: 1; min-width: 0; background: transparent; border: 0; outline: none; color: var(--text-1); font-size: var(--fs-sm); padding: .5rem 0; }
        .search button { min-height: 36px; padding: .4rem 1rem; border: 0; border-radius: 999px; background: var(--gold); color: #1a1200; font-weight: 700; font-size: var(--fs-xs); cursor: pointer; transition: background .2s var(--ease); }
        .search button:hover { background: var(--gold-strong); }

        .header-actions { display: flex; align-items: center; gap: .5rem; margin-left: auto; }
        .icon-btn {
            position: relative; display: inline-flex; align-items: center; justify-content: center;
            width: 44px; height: 44px; border-radius: 12px; color: var(--text-1); border: 1px solid var(--line);
            background: transparent; text-decoration: none; transition: border-color .2s var(--ease), color .2s var(--ease), background .2s var(--ease);
        }
        .icon-btn:hover { border-color: var(--gold); color: var(--gold); background: var(--gold-soft); }
        .cart-badge {
            position: absolute; top: -6px; right: -6px; min-width: 20px; height: 20px; padding: 0 5px;
            display: inline-flex; align-items: center; justify-content: center; border-radius: 999px;
            background: var(--gold); color: #1a1200; font-size: 11px; font-weight: 800; box-shadow: 0 0 0 3px var(--bg-0);
        }

        @media (max-width: 991px) { .nav-main, .search { display: none; } }

        /* ============================================================
           КОНТЕНТНАЯ ЧАСТЬ (Сайдбар 1fr : Контент 2.618fr)
           ============================================================ */
        .layout-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
            margin-block: var(--space-phi2);
            flex: 1;
        }
        @media (max-width: 991px) { .layout-grid { grid-template-columns: 1fr; } }

        /* Сайдбар */
        .sidebar {
            background: var(--bg-1);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 1.5rem;
            height: fit-content;
        }
        .filter-link {
            color: var(--text-2); text-decoration: none; display: block; padding: .65rem 1rem;
            border-radius: 8px; font-size: var(--fs-sm); font-weight: 500; transition: all .2s var(--ease);
            margin-bottom: .25rem;
        }
        .filter-link:hover { color: var(--text-1); background: rgba(255,255,255,.03); }
        .filter-link.active { color: var(--gold); background: var(--gold-soft); font-weight: 700; }

        /* Сетка услуг */
        .product-card {
            border-radius: var(--radius); overflow: hidden; background: var(--bg-1); border: 1px solid var(--line);
            display: flex; flex-direction: column; transition: transform .35s var(--ease), border-color .35s var(--ease), box-shadow .35s var(--ease);
            height: 100%;
        }
        .product-card:hover { transform: translateY(-6px); border-color: rgba(242,201,76,.4); box-shadow: var(--shadow-gold); }
        .product-card .thumb { aspect-ratio: 16 / 10; overflow: hidden; background: var(--bg-2); display: block; }
        .product-card .thumb img { width: 100%; height: 100%; object-fit: cover; transition: transform .6s var(--ease); }
        .product-card:hover .thumb img { transform: scale(1.05); }
        
        .product-card .body { padding: 1.25rem; display: flex; flex-direction: column; gap: .75rem; flex: 1; }
        .product-card .title { font-weight: 800; font-size: var(--fs-md); color: var(--text-1); text-decoration: none; line-height: 1.3; }
        .product-card .title:hover { color: var(--gold); }
        .product-card .desc { font-size: var(--fs-sm); color: var(--text-2); margin: 0; line-height: 1.5; }
        .product-card .time { font-size: var(--fs-xs); color: var(--gold); font-weight: 700; display: inline-flex; align-items: center; gap: .25rem; background: var(--gold-soft); padding: .25rem .75rem; border-radius: 999px; width: fit-content; }
        
        .product-card .foot { margin-top: auto; display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding-top: 1rem; border-top: 1px solid var(--line); }
        .price { font-size: var(--fs-xl); font-weight: 800; color: var(--text-1); letter-spacing: -.02em; }
        .price small { font-size: var(--fs-sm); color: var(--text-2); font-weight: 600; }

        /* ============================================================
           ФУТЕР
           ============================================================ */
        .site-footer {
            margin-top: auto; border-top: 1px solid var(--line); background: linear-gradient(180deg, transparent, rgba(16,31,51,.6));
            padding-block: 2rem 1.5rem; text-align: center;
        }
        .footer-bottom { font-size: var(--fs-xs); color: var(--text-3); }

        /* Уважение к prefers-reduced-motion */
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation: none !important; transition: none !important; } }
    </style>
</head>
<body>

<!-- ============================================================
     HEADER
     ============================================================ -->
<header class="site-header">
    <div class="container-phi header-inner">
        <a class="brand" href="/">
            <img src="logo.png" alt="Самокатыч" class="brand-logo">
            <span class="brand-name">Самока<span>тыч</span></span>
        </a>

        <nav class="nav-main" aria-label="Основная навигация">
            <a href="services.php" class="active">Услуги</a>
            <a href="catalog.php">Товары</a>
        </nav>

        <form action="catalog.php" method="GET" class="search" role="search">
            <input type="text" name="search" placeholder="Поиск по сайту…" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" aria-label="Поиск" required>
            <button type="submit">Найти</button>
        </form>

        <div class="header-actions">
            <a href="cart.php" class="icon-btn" aria-label="Корзина">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l1.313 7h8.17l1.313-7H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                </svg>
                <span class="cart-badge"><?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?></span>
            </a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="profile.php" class="btn-gold btn-sm">Профиль</a>
            <?php else: ?>
                <a href="login.php" class="btn-gold btn-sm">Войти</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- ============================================================
     ОСНОВНОЙ КОНТЕНТ (Сетка Фибоначчи)
     ============================================================ -->
<main class="container-phi layout-grid">
    
    <!-- САЙДБАР (Категории) -->
    <aside class="sidebar position-sticky" style="top: 100px;">
        <p class="eyebrow">Вид работ</p>
        <div class="mb-4">
            <a href="services.php" class="filter-link <?= $categoryId === 0 ? 'active' : '' ?>">Все услуги</a>
            <?php foreach ($categories as $cat): ?>
                <a href="services.php?category=<?= $cat['id'] ?>" class="filter-link <?= $categoryId === $cat['id'] ? 'active' : '' ?>">
                    <?= htmlspecialchars($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <?php if ($categoryId > 0 || $search !== ''): ?>
            <a href="services.php" class="btn btn-outline-danger btn-sm w-100 mt-2">Сбросить фильтры</a>
        <?php endif; ?>
    </aside>

    <!-- СПИСОК УСЛУГ -->
    <section>
        <?php if ($search !== ''): ?>
            <h2 class="h2">Поиск услуг: «<?= htmlspecialchars($search) ?>»</h2>
        <?php else: ?>
            <h2 class="h2">Услуги мастерской</h2>
            <p class="muted mb-4">Выберите необходимую услугу. Точная стоимость может зависеть от модели устройства.</p>
        <?php endif; ?>
        
        <div class="row g-4">
            <?php if (empty($services)): ?>
                <div class="col-12">
                    <div class="panel p-5 text-center muted" style="background:var(--bg-1); border:1px solid var(--line); border-radius:var(--radius);">
                        Услуги по данному запросу не найдены.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($services as $service): ?>
                    <div class="col-md-6 col-xl-6">
                        <article class="product-card">
                            <a href="service.php?id=<?= $service['id'] ?>" class="thumb" aria-label="<?= htmlspecialchars($service['name']) ?>">
                                <img src="<?= htmlspecialchars($service['image_path'] ?: 'placeholder.png') ?>" alt="<?= htmlspecialchars($service['name']) ?>" loading="lazy">
                            </a>
                            <div class="body">
                                <a href="service.php?id=<?= $service['id'] ?>" class="title"><?= htmlspecialchars($service['name']) ?></a>
                                
                                <p class="desc"><?= htmlspecialchars(mb_strimwidth($service['description'], 0, 90, '…')) ?></p>
                                
                                <?php if (!empty($service['estimated_time'])): ?>
                                    <div class="time">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/></svg>
                                        Время: <?= htmlspecialchars($service['estimated_time']) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="foot">
                                    <span class="price"><?= number_format($service['price'], 0, '', ' ') ?> <small>₽</small></span>
                                    <form action="add_to_cart.php" method="POST" class="m-0">
                                        <input type="hidden" name="type" value="service">
                                        <input type="hidden" name="id" value="<?= $service['id'] ?>">
                                        <button type="submit" class="btn-gold btn-sm">В корзину</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

</main>

<footer class="site-footer">
    <div class="container-phi footer-bottom">
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
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                document.querySelectorAll('.cart-badge').forEach(badge => {
                    badge.innerText = data.cart_count;
                });
                btn.innerHTML = '✓ В корзине';
                btn.style.background = 'linear-gradient(135deg,#63d68f,#3fb56f)';
                btn.style.color = '#04240f';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.style.background = '';
                    btn.style.color = '';
                    btn.disabled = false;
                }, 1400);
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