<?php
session_start();
require_once 'db.php';

$active_portfolio = $pdo->query("SELECT * FROM portfolio WHERE is_active = 1 ORDER BY sort_order ASC, id DESC")->fetchAll();

$stmtProducts = $pdo->query("SELECT * FROM products WHERE is_active = 1 AND is_hit = 1 ORDER BY sort_order ASC, id DESC LIMIT 6");
$products = $stmtProducts->fetchAll();

$stmtServices = $pdo->query("SELECT * FROM services WHERE is_active = 1 AND is_hit = 1 ORDER BY sort_order ASC, id DESC LIMIT 6");
$services = $stmtServices->fetchAll();

$stmtReviews = $pdo->query("SELECT * FROM reviews WHERE is_active = 1 ORDER BY id DESC LIMIT 5");
$reviews = $stmtReviews->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <link rel="icon" type="image/png" href="/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Самокатыч — Ремонт электросамокатов и велосипедов</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* ============================================================
           ДИЗАЙН-ТОКЕНЫ (золотое сечение как основа ритма)
           ============================================================ */
        :root {
            /* Цвета */
            --bg-0: #050a12;          /* глубокий фон */
            --bg-1: #0a1524;          /* поверхность */
            --bg-2: #101f33;          /* приподнятая поверхность */
            --line: rgba(255,255,255,.08);
            --gold: #f2c94c;
            --gold-soft: rgba(242,201,76,.12);
            --gold-strong: #ffd76a;
            --text-1: #f5f7fa;        /* основной, контраст 14:1 */
            --text-2: #a9b6c6;        /* вторичный, контраст 7:1 */
            --text-3: #6b7a8c;        /* третичный */
            --danger: #ff5c5c;

            /* Золотое сечение */
            --phi: 1.618;
            --space: 1rem;                                  /* 16 */
            --space-phi2: calc(var(--space) * 1.618 * 1.618); /* ≈ 41.9 */
            --space-phi3: calc(var(--space) * 4.236);         /* ≈ 67.8 */
            --radius: 18px;
            --radius-sm: 12px;
            --ease: cubic-bezier(.2,.8,.2,1);
            --shadow: 0 20px 60px -20px rgba(0,0,0,.6);
            --shadow-gold: 0 20px 50px -20px rgba(242,201,76,.35);

            /* Типографика по модульной шкале 1.618 */
            --fs-xs: .75rem;      /* 12 */
            --fs-sm: .875rem;     /* 14 */
            --fs-md: 1rem;        /* 16 */
            --fs-lg: 1.25rem;     /* 20 */
            --fs-xl: 1.618rem;    /* 25.9 */
            --fs-2xl: 2.058rem;   /* 32.9 */
            --fs-3xl: 3.33rem;    /* 53.3 */
        }

        *, *::before, *::after { box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            margin: 0;
            font-family: 'Manrope', system-ui, -apple-system, sans-serif;
            background: var(--bg-0);
            color: var(--text-1);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            /* Мягкие «золотые» источники света — подсознательные акценты */
            background-image:
                radial-gradient(1200px 600px at 85% -10%, rgba(242,201,76,.10), transparent 60%),
                radial-gradient(900px 500px at -10% 20%, rgba(41,98,255,.10), transparent 60%);
            background-attachment: fixed;
        }

        a { color: inherit; }
        img { display: block; }

        /* Контейнер: предел 1240 ≈ 768 * φ */
        .container-phi {
            width: 100%;
            max-width: 1240px;
            margin-inline: auto;
            padding-inline: clamp(16px, 4vw, 32px);
        }

        /* Вертикальный ритм золотого сечения */
        .section { padding-block: var(--space-phi3); }
        .section-sm { padding-block: var(--space-phi2); }

        /* ============================================================
           ТИПОГРАФИКА
           ============================================================ */
        .eyebrow {
            font-size: var(--fs-xs);
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--gold);
            font-weight: 700;
            margin: 0 0 .5rem;
        }
        .h1 { font-size: clamp(2rem, 5vw, var(--fs-3xl)); font-weight: 800; line-height: 1.08; letter-spacing: -.02em; margin: 0; }
        .h2 { font-size: clamp(1.5rem, 3vw, var(--fs-2xl)); font-weight: 800; line-height: 1.15; letter-spacing: -.01em; margin: 0 0 .5rem; }
        .h3 { font-size: var(--fs-xl); font-weight: 700; margin: 0; }
        .lead { color: var(--text-2); font-size: var(--fs-lg); line-height: 1.55; max-width: 56ch; }
        .muted { color: var(--text-2); }

        /* ============================================================
           КНОПКИ (Fitts: ≥44px, зазор 12px)
           ============================================================ */
        .btn-gold, .btn-ghost, .btn-soft {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            min-height: 44px;
            padding: .75rem 1.5rem;
            border-radius: 999px;
            font-weight: 700;
            font-size: var(--fs-sm);
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition: transform .25s var(--ease), background .25s var(--ease), color .25s var(--ease), box-shadow .25s var(--ease), border-color .25s var(--ease);
            white-space: nowrap;
        }
        .btn-gold {
            background: linear-gradient(135deg, var(--gold-strong), var(--gold));
            color: #1a1200;
            box-shadow: var(--shadow-gold);
        }
        .btn-gold:hover { transform: translateY(-2px); color: #1a1200; }
        .btn-soft {
            background: var(--gold-soft);
            color: var(--gold);
            border-color: rgba(242,201,76,.3);
        }
        .btn-soft:hover { background: rgba(242,201,76,.2); color: var(--gold-strong); }
        .btn-ghost {
            background: transparent;
            color: var(--text-1);
            border-color: var(--line);
        }
        .btn-ghost:hover { border-color: var(--gold); color: var(--gold); }
        .btn-sm { min-height: 36px; padding: .45rem 1rem; font-size: var(--fs-xs); }

        /* ============================================================
           ХЕДЕР
           ============================================================ */
        .site-header {
            position: sticky;
            top: 0;
            z-index: 1030;
            background: rgba(5,10,18,.72);
            backdrop-filter: blur(18px) saturate(140%);
            -webkit-backdrop-filter: blur(18px) saturate(140%);
            border-bottom: 1px solid var(--line);
        }
        .header-inner {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            min-height: 72px;
        }
        .brand { display: flex; align-items: center; gap: .75rem; text-decoration: none; flex-shrink: 0; }
        .brand-logo {
            width: 44px; height: 44px; object-fit: cover;
            border-radius: 12px;
            border: 1px solid var(--line);
            background: var(--bg-2);
        }
        .brand-name { font-weight: 800; font-size: var(--fs-lg); letter-spacing: -.01em; }
        .brand-name span { color: var(--gold); }

        .nav-main { display: flex; gap: .25rem; margin-left: .5rem; }
        .nav-main a {
            padding: .55rem .9rem;
            border-radius: 999px;
            color: var(--text-2);
            text-decoration: none;
            font-weight: 600;
            font-size: var(--fs-sm);
            transition: color .2s var(--ease), background .2s var(--ease);
        }
        .nav-main a:hover { color: var(--text-1); background: rgba(255,255,255,.05); }

        .search {
            flex: 1;
            max-width: 380px;
            margin-left: auto;
            display: flex;
            align-items: center;
            background: var(--bg-1);
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: .25rem .25rem .25rem 1rem;
            transition: border-color .2s var(--ease), box-shadow .2s var(--ease);
        }
        .search:focus-within { border-color: rgba(242,201,76,.5); box-shadow: 0 0 0 4px rgba(242,201,76,.12); }
        .search input {
            flex: 1; min-width: 0;
            background: transparent; border: 0; outline: none;
            color: var(--text-1); font-size: var(--fs-sm); font-family: inherit;
            padding: .5rem 0;
        }
        .search input::placeholder { color: var(--text-3); }
        .search button {
            min-height: 36px; padding: .4rem 1rem;
            border: 0; border-radius: 999px;
            background: var(--gold); color: #1a1200;
            font-weight: 700; font-size: var(--fs-xs); cursor: pointer;
            transition: background .2s var(--ease);
        }
        .search button:hover { background: var(--gold-strong); }

        .header-actions { display: flex; align-items: center; gap: .5rem; margin-left: auto; }
        .icon-btn {
            position: relative;
            display: inline-flex; align-items: center; justify-content: center;
            width: 44px; height: 44px;
            border-radius: 12px;
            color: var(--text-1);
            border: 1px solid var(--line);
            background: transparent;
            text-decoration: none;
            transition: border-color .2s var(--ease), color .2s var(--ease), background .2s var(--ease);
        }
        .icon-btn:hover { border-color: var(--gold); color: var(--gold); background: var(--gold-soft); }
        .cart-badge {
            position: absolute; top: -6px; right: -6px;
            min-width: 20px; height: 20px; padding: 0 5px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 999px;
            background: var(--gold); color: #1a1200;
            font-size: 11px; font-weight: 800;
            box-shadow: 0 0 0 3px var(--bg-0);
        }

        /* Мобильное меню */
        .menu-toggle { display: none; }
        @media (max-width: 991px) {
            .nav-main, .search { display: none; }
            .menu-toggle { display: inline-flex; }
        }

        /* ============================================================
           HERO (правило 61.8 / 38.2)
           ============================================================ */
        .hero {
            display: grid;
            grid-template-columns: 1.618fr 1fr; /* золотое сечение */
            gap: var(--space-phi2);
            align-items: center;
            padding-block: var(--space-phi3);
        }
        @media (max-width: 991px) {
            .hero { grid-template-columns: 1fr; padding-block: var(--space-phi2); }
        }
        .hero-copy .lead { margin-top: 1rem; }
        .hero-cta { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.75rem; }
        .hero-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: var(--space-phi2);
            padding-top: var(--space-phi2);
            border-top: 1px solid var(--line);
            max-width: 520px;
        }
        .stat-num { font-size: var(--fs-xl); font-weight: 800; color: var(--gold); line-height: 1; }
        .stat-label { font-size: var(--fs-xs); color: var(--text-2); margin-top: .25rem; }

        .hero-visual {
            position: relative;
            aspect-ratio: 1 / 1.618; /* портретная φ-пропорция */
            border-radius: var(--radius);
            overflow: hidden;
            border: 1px solid var(--line);
            background: var(--bg-1);
            box-shadow: var(--shadow);
        }
        .hero-visual img { width: 100%; height: 100%; object-fit: cover; opacity: .92; }
        .hero-visual::after {
            content: "";
            position: absolute; inset: 0;
            background: linear-gradient(200deg, transparent 40%, rgba(5,10,18,.85));
        }
        .hero-badge {
            position: absolute; left: 1.25rem; bottom: 1.25rem; z-index: 2;
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .55rem .9rem;
            border-radius: 999px;
            background: rgba(5,10,18,.8);
            border: 1px solid rgba(242,201,76,.35);
            backdrop-filter: blur(10px);
            font-size: var(--fs-xs);
            font-weight: 700;
            color: var(--gold);
        }
        .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--gold); box-shadow: 0 0 12px var(--gold); animation: pulse 2s infinite; }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.35} }

        /* ============================================================
           КАРТОЧКИ-ВХОДЫ (Услуги / Товары)
           ============================================================ */
        .entry-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
        @media (max-width: 767px) { .entry-grid { grid-template-columns: 1fr; } }
        .entry-card {
            position: relative;
            overflow: hidden;
            padding: var(--space-phi2);
            border-radius: var(--radius);
            background: linear-gradient(160deg, var(--bg-1), var(--bg-2));
            border: 1px solid var(--line);
            display: flex; flex-direction: column; gap: .75rem;
            transition: transform .35s var(--ease), border-color .35s var(--ease), box-shadow .35s var(--ease);
        }
        .entry-card:hover { transform: translateY(-4px); border-color: rgba(242,201,76,.4); box-shadow: var(--shadow-gold); }
        .entry-card h3 { color: var(--text-1); }
        .entry-card p { color: var(--text-2); margin: 0; flex: 1; font-size: var(--fs-sm); }
        .entry-card .num {
            position: absolute; right: 1rem; top: .5rem;
            font-size: 6rem; font-weight: 800; line-height: 1;
            color: rgba(242,201,76,.06); pointer-events: none; user-select: none;
        }

        /* ============================================================
           ТАБЫ (pill-переключатель)
           ============================================================ */
        .pill-tabs {
            display: inline-flex; gap: .25rem;
            padding: .3rem;
            border-radius: 999px;
            background: var(--bg-1);
            border: 1px solid var(--line);
        }
        .pill-tabs .nav-link {
            border: 0; border-radius: 999px;
            padding: .55rem 1.4rem;
            color: var(--text-2);
            font-weight: 700; font-size: var(--fs-sm);
            background: transparent;
            transition: color .2s var(--ease), background .2s var(--ease);
        }
        .pill-tabs .nav-link:hover { color: var(--text-1); }
        .pill-tabs .nav-link.active { background: var(--gold); color: #1a1200; }

        /* ============================================================
           СКРОЛЛ-КАРУСЕЛЬ КАРТОЧЕК
           ============================================================ */
        .scroll-x {
            display: flex; gap: 1.25rem;
            overflow-x: auto;
            padding-block: .5rem 1.25rem;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: rgba(242,201,76,.5) transparent;
        }
        .scroll-x::-webkit-scrollbar { height: 6px; }
        .scroll-x::-webkit-scrollbar-track { background: transparent; }
        .scroll-x::-webkit-scrollbar-thumb { background: rgba(242,201,76,.5); border-radius: 999px; }

        .product-card {
            flex: 0 0 auto;
            width: 280px;
            scroll-snap-align: start;
            border-radius: var(--radius);
            overflow: hidden;
            background: var(--bg-1);
            border: 1px solid var(--line);
            display: flex; flex-direction: column;
            transition: transform .35s var(--ease), border-color .35s var(--ease), box-shadow .35s var(--ease);
        }
        .product-card:hover { transform: translateY(-6px); border-color: rgba(242,201,76,.4); box-shadow: var(--shadow-gold); }
        .product-card .thumb { aspect-ratio: 1 / .75; overflow: hidden; background: var(--bg-2); }
        .product-card .thumb img { width: 100%; height: 100%; object-fit: cover; transition: transform .6s var(--ease); }
        .product-card:hover .thumb img { transform: scale(1.05); }
        .product-card .body { padding: 1rem 1.1rem 1.1rem; display: flex; flex-direction: column; gap: .5rem; flex: 1; }
        .product-card .title { font-weight: 700; font-size: var(--fs-md); color: var(--text-1); text-decoration: none; line-height: 1.35; }
        .product-card .title:hover { color: var(--gold); }
        .product-card .desc { font-size: var(--fs-xs); color: var(--text-2); margin: 0; line-height: 1.5; }
        .product-card .time { font-size: var(--fs-xs); color: var(--gold); font-weight: 600; }
        .product-card .foot { margin-top: auto; display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding-top: .5rem; }
        .price { font-size: var(--fs-lg); font-weight: 800; color: var(--text-1); letter-spacing: -.01em; }
        .price small { font-size: var(--fs-xs); font-weight: 600; color: var(--text-2); }

        /* ============================================================
           КАРУСЕЛЬ «НАШИ РАБОТЫ»
           ============================================================ */
        .works-carousel {
            border-radius: var(--radius);
            overflow: hidden;
            border: 1px solid var(--line);
            box-shadow: var(--shadow);
        }
        .works-carousel .carousel-item img {
            width: 100%;
            aspect-ratio: 16 / 9;
            object-fit: cover;
        }
        .works-carousel .carousel-caption {
            position: absolute; inset: auto 0 0 0;
            padding: 2.5rem 1.5rem 1.25rem;
            background: linear-gradient(to top, rgba(5,10,18,.95), transparent);
            text-align: left;
        }
        .works-carousel .carousel-caption h5 {
            color: var(--gold); font-weight: 700; font-size: var(--fs-lg); margin: 0;
        }
        .works-carousel .carousel-indicators [data-bs-target] {
            width: 8px; height: 8px; border-radius: 50%;
            background: rgba(255,255,255,.4); border: 0; opacity: 1;
        }
        .works-carousel .carousel-indicators .active { background: var(--gold); }
        .works-carousel .carousel-control-prev,
        .works-carousel .carousel-control-next { width: 56px; }

        /* ============================================================
           ОТЗЫВЫ / КАРТА (φ-сетка 38.2 / 61.8)
           ============================================================ */
        .split-phi { display: grid; grid-template-columns: 1fr 1.618fr; gap: 1.25rem; align-items: stretch; }
        @media (max-width: 991px) { .split-phi { grid-template-columns: 1fr; } }

        .panel {
            background: var(--bg-1);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 1.5rem;
            display: flex; flex-direction: column;
        }
        .panel-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
        .panel-head .h3 { font-size: var(--fs-lg); }

        .review-slide { text-align: center; padding: .5rem 0; }
        .stars { color: var(--gold); font-size: var(--fs-lg); letter-spacing: .1em; margin-bottom: .75rem; }
        .review-text { color: var(--text-1); font-style: italic; font-size: var(--fs-md); line-height: 1.6; margin: 0 0 .75rem; }
        .review-author { color: var(--text-2); font-size: var(--fs-sm); font-weight: 700; }

        .rating-badge {
            margin-top: auto;
            padding-top: 1rem;
            display: flex; justify-content: center;
        }
        .rating-badge iframe { border-radius: 10px; overflow: hidden; }

        .map-frame {
            flex: 1;
            min-height: 420px;
            border-radius: var(--radius-sm);
            overflow: hidden;
            border: 1px solid var(--line);
            background: var(--bg-2);
        }
        .map-frame iframe { width: 100%; height: 100%; min-height: 420px; border: 0; display: block; filter: grayscale(.2) contrast(1.05); }

        /* ============================================================
           ФУТЕР
           ============================================================ */
        .site-footer {
            margin-top: var(--space-phi3);
            border-top: 1px solid var(--line);
            background: linear-gradient(180deg, transparent, rgba(16,31,51,.6));
            padding-block: var(--space-phi2) 1.5rem;
        }
        .footer-grid { display: grid; grid-template-columns: 1.618fr 1fr 1.618fr; gap: 2rem; }
        @media (max-width: 767px) { .footer-grid { grid-template-columns: 1fr; } }
        .footer-title { font-size: var(--fs-sm); font-weight: 800; text-transform: uppercase; letter-spacing: .12em; color: var(--text-1); margin: 0 0 .9rem; }
        .footer-links { list-style: none; padding: 0; margin: 0; display: grid; gap: .5rem; }
        .footer-links a { color: var(--text-2); text-decoration: none; font-size: var(--fs-sm); transition: color .2s var(--ease); }
        .footer-links a:hover { color: var(--gold); }
        .footer-info { font-size: var(--fs-xs); color: var(--text-2); margin: 0 0 .4rem; }
        .footer-bottom { border-top: 1px solid var(--line); margin-top: 2rem; padding-top: 1.25rem; text-align: center; font-size: var(--fs-xs); color: var(--text-3); }

        /* ============================================================
           COOKIE-БАННЕР
           ============================================================ */
        .cookie-bar {
            position: fixed; inset: auto 0 0 0; z-index: 1050;
            padding: 1rem;
            background: rgba(5,10,18,.94);
            backdrop-filter: blur(16px);
            border-top: 1px solid rgba(242,201,76,.3);
        }
        .cookie-inner {
            max-width: 1240px; margin-inline: auto;
            display: flex; flex-wrap: wrap; align-items: center; gap: 1rem;
            justify-content: space-between;
        }
        .cookie-inner p { margin: 0; font-size: var(--fs-xs); color: var(--text-2); }
        .cookie-inner a { color: var(--gold); text-decoration: underline; }

        /* Утилиты */
        .row-between { display: flex; align-items: end; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
        .text-center { text-align: center; }
        .mb-0 { margin-bottom: 0 !important; }

        /* Уважение к prefers-reduced-motion */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; scroll-behavior: auto !important; }
        }
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
            <a href="services.php">Услуги</a>
            <a href="catalog.php">Товары</a>
            <a href="#works">Работы</a>
            <a href="#contacts">Контакты</a>
        </nav>

        <form action="catalog.php" method="GET" class="search" role="search">
            <input type="text" name="search" placeholder="Поиск по каталогу…" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" aria-label="Поиск" required>
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
                <a href="logout.php" class="btn-ghost btn-sm">Выйти</a>
            <?php else: ?>
                <a href="login.php" class="btn-gold btn-sm">Войти</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main>
<!-- ============================================================
     HERO (61.8 / 38.2)
     ============================================================ -->
<section class="container-phi">
    <div class="hero">
        <div class="hero-copy">
            <p class="eyebrow">Сервисный центр · Краснодар</p>
            <h1 class="h1">Ремонт электросамокатов<br>и велосипедов <span style="color:var(--gold)">за 1 день</span></h1>
            <p class="lead">Бесплатная диагностика, оригинальные запчасти и гарантия на все работы. Более 4 000 довольных клиентов.</p>

            <div class="hero-cta">
                <a href="services.php" class="btn-gold">Записаться на ремонт</a>
                <a href="catalog.php" class="btn-ghost">Смотреть каталог</a>
            </div>

            <div class="hero-stats">
                <div>
                    <div class="stat-num">4 000+</div>
                    <div class="stat-label">клиентов</div>
                </div>
                <div>
                    <div class="stat-num">1 день</div>
                    <div class="stat-label">средний ремонт</div>
                </div>
                <div>
                    <div class="stat-num">1 год</div>
                    <div class="stat-label">гарантия</div>
                </div>
            </div>
        </div>

        <div class="hero-visual">
            <img src="hero.jpg" alt="Мастерская Самокатыч">
            <div class="hero-badge"><span class="dot"></span> Работаем ПН–СБ · 10:00–19:00</div>
        </div>
    </div>
</section>

<!-- ============================================================
     ДВА ВХОДА: УСЛУГИ / ТОВАРЫ
     ============================================================ -->
<section class="container-phi section-sm">
    <div class="entry-grid">
        <article class="entry-card">
            <span class="num">01</span>
            <p class="eyebrow">Ремонт</p>
            <h3 class="h3">Услуги мастерской</h3>
            <p>Диагностика, замена контроллера, тормозов, покрышек. Работаем с популярными брендами.</p>
            <a href="services.php" class="btn-soft btn-sm">Перейти к услугам</a>
        </article>
        <article class="entry-card">
            <span class="num">02</span>
            <p class="eyebrow">Магазин</p>
            <h3 class="h3">Товары и запчасти</h3>
            <p>Оригинальные запчасти, аксессуары и новые электросамокаты в наличии.</p>
            <a href="catalog.php" class="btn-soft btn-sm">Перейти в магазин</a>
        </article>
    </div>
</section>

<!-- ============================================================
     ПОПУЛЯРНОЕ: ТАБЫ + ГОРИЗОНТАЛЬНЫЙ СКРОЛЛ
     ============================================================ -->
<section class="container-phi section-sm" id="popular">
    <div class="row-between">
        <div>
            <p class="eyebrow">Хиты</p>
            <h2 class="h2">Популярные товары и услуги</h2>
        </div>
        <ul class="nav pill-tabs" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pills-products-tab" data-bs-toggle="pill" data-bs-target="#pills-products" type="button" role="tab" aria-selected="true">Товары</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pills-services-tab" data-bs-toggle="pill" data-bs-target="#pills-services" type="button" role="tab" aria-selected="false">Услуги</button>
            </li>
        </ul>
    </div>

    <div class="tab-content" id="pills-tabContent">
        <!-- ТОВАРЫ -->
        <div class="tab-pane fade show active" id="pills-products" role="tabpanel">
            <div class="scroll-x">
                <?php if (empty($products)): ?>
                    <p class="muted mb-0">Каталог товаров временно пуст.</p>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <article class="product-card">
                            <a href="product.php?id=<?= $product['id'] ?>" class="thumb" aria-label="<?= htmlspecialchars($product['name']) ?>">
                                <img src="<?= htmlspecialchars($product['image_path'] ?: 'placeholder.png') ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy">
                            </a>
                            <div class="body">
                                <a href="product.php?id=<?= $product['id'] ?>" class="title"><?= htmlspecialchars($product['name']) ?></a>
                                <p class="desc"><?= htmlspecialchars(mb_strimwidth($product['description'], 0, 70, '…')) ?></p>
                                <div class="foot">
                                    <span class="price"><?= number_format($product['price'], 0, '', ' ') ?> <small>₽</small></span>
                                    <form action="add_to_cart.php" method="POST" class="m-0">
                                        <input type="hidden" name="type" value="product">
                                        <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                        <button type="submit" class="btn-gold btn-sm">В корзину</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- УСЛУГИ -->
        <div class="tab-pane fade" id="pills-services" role="tabpanel">
            <div class="scroll-x">
                <?php if (empty($services)): ?>
                    <p class="muted mb-0">Список услуг временно пуст.</p>
                <?php else: ?>
                    <?php foreach ($services as $service): ?>
                        <article class="product-card">
                            <a href="service.php?id=<?= $service['id'] ?>" class="thumb" aria-label="<?= htmlspecialchars($service['name']) ?>">
                                <img src="<?= htmlspecialchars($service['image_path'] ?: 'placeholder.png') ?>" alt="<?= htmlspecialchars($service['name']) ?>" loading="lazy">
                            </a>
                            <div class="body">
                                <a href="service.php?id=<?= $service['id'] ?>" class="title"><?= htmlspecialchars($service['name']) ?></a>
                                <p class="desc"><?= htmlspecialchars(mb_strimwidth($service['description'], 0, 70, '…')) ?></p>
                                <?php if (!empty($service['estimated_time'])): ?>
                                    <span class="time">⏱ <?= htmlspecialchars($service['estimated_time']) ?></span>
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
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     НАШИ РАБОТЫ
     ============================================================ -->
<section class="container-phi section-sm" id="works">
    <div class="row-between">
        <div>
            <p class="eyebrow">Портфолио</p>
            <h2 class="h2">Наши работы</h2>
        </div>
    </div>

    <div id="worksCarousel" class="carousel slide carousel-fade works-carousel" data-bs-ride="carousel">
        <?php if (!empty($active_portfolio)): ?>
            <div class="carousel-indicators">
                <?php foreach ($active_portfolio as $index => $item): ?>
                    <button type="button" data-bs-target="#worksCarousel" data-bs-slide-to="<?= $index ?>" class="<?= $index === 0 ? 'active' : '' ?>" <?= $index === 0 ? 'aria-current="true"' : '' ?> aria-label="Слайд <?= $index + 1 ?>"></button>
                <?php endforeach; ?>
            </div>

            <div class="carousel-inner">
                <?php foreach ($active_portfolio as $index => $item): ?>
                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                        <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="Работа мастерской">
                        <?php if (!empty($item['description'])): ?>
                            <div class="carousel-caption">
                                <h5><?= htmlspecialchars($item['description']) ?></h5>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#worksCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Предыдущий</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#worksCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Следующий</span>
            </button>
        <?php else: ?>
            <div class="text-center p-5 muted">Фотографии работ пока не добавлены.</div>
        <?php endif; ?>
    </div>
</section>

<!-- ============================================================
     ОТЗЫВЫ (38.2%) + КАРТА (61.8%)
     ============================================================ -->
<section class="container-phi section-sm" id="contacts">
    <div class="split-phi">
        <div class="panel">
            <div class="panel-head">
                <p class="eyebrow mb-0">Мнения</p>
                <h3 class="h3">Отзывы</h3>
            </div>

            <div id="reviewsCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="6000">
                <div class="carousel-inner">
                    <?php if (empty($reviews)): ?>
                        <div class="carousel-item active">
                            <p class="muted text-center">Здесь скоро появятся отзывы наших клиентов.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($reviews as $index => $review): ?>
                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                <div class="review-slide">
                                    <div class="stars"><?= str_repeat('★', (int)$review['rating']) ?><?= str_repeat('☆', 5 - (int)$review['rating']) ?></div>
                                    <p class="review-text">«<?= htmlspecialchars($review['review_text']) ?>»</p>
                                    <span class="review-author">— <?= htmlspecialchars($review['author_name']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="rating-badge">
                <iframe src="https://yandex.ru/sprav/widget/rating-badge/145659692359?type=rating&theme=dark" width="150" height="50" frameborder="0" title="Рейтинг Яндекса" loading="lazy"></iframe>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <p class="eyebrow mb-0">Как добраться</p>
                <h3 class="h3">Мы на карте</h3>
            </div>
            <div class="map-frame">
                <iframe src="https://yandex.ru/map-widget/v1/?um=constructor%3A6f3c022b2ddfbfce86e63245f5d356f0699b007fc7a20479a9ec050c9e22429f&amp;source=constructor" title="Карта" loading="lazy"></iframe>
            </div>
        </div>
    </div>
</section>
</main>

<!-- ============================================================
     FOOTER
     ============================================================ -->
<footer class="site-footer">
    <div class="container-phi">
        <div class="footer-grid">
            <div>
                <a class="brand" href="/" style="margin-bottom:.75rem">
                    <img src="logo.png" alt="Самокатыч" class="brand-logo">
                    <span class="brand-name">Самока<span>тыч</span></span>
                </a>
                <p class="footer-info" style="max-width:32ch">Профессиональный ремонт электросамокатов и велосипедов. Качество, гарантия, скорость.</p>
            </div>

            <div>
                <h5 class="footer-title">Навигация</h5>
                <ul class="footer-links">
                    <li><a href="services.php">Услуги</a></li>
                    <li><a href="catalog.php">Товары и запчасти</a></li>
                    <li><a href="login.php">Профиль</a></li>
                    <li><a href="cookie_policy.php">Cookie</a></li>
                </ul>
            </div>

            <div>
                <h5 class="footer-title">Контакты</h5>
                <p class="footer-info">ИП Тищенко Виталий Андреевич</p>
                <p class="footer-info">ОГРНИП 326237500083270 · ИНН 234706478915</p>
                <p class="footer-info">г. Краснодар, ул. Сергея Есенина, 110/1</p>
                <p class="footer-info"><a href="tel:+79915374801" style="color:var(--gold);text-decoration:none;font-weight:700">+7 (991) 537-48-01</a></p>
                <p class="footer-info mb-0">ПН–СБ · 10:00–19:00</p>
            </div>
        </div>
        <div class="footer-bottom">© 2026 Самокатыч. Все права защищены.</div>
    </div>
</footer>

<!-- ============================================================
     COOKIE-БАННЕР
     ============================================================ -->
<div id="cookieConsent" class="cookie-bar d-none">
    <div class="cookie-inner">
        <p>Мы используем cookie для сохранения корзины и корректной работы сайта. <a href="cookie_policy.php" target="_blank" rel="noopener">Подробнее</a></p>
        <button id="acceptCookies" class="btn-gold btn-sm" type="button">Понятно</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Cookie
    if (!document.cookie.split('; ').find(r => r.startsWith('cookies_accepted='))) {
        document.getElementById('cookieConsent').classList.remove('d-none');
    }
    document.getElementById('acceptCookies').addEventListener('click', function () {
        document.cookie = 'cookies_accepted=true; max-age=' + 60*60*24*365 + '; path=/; SameSite=Lax';
        document.getElementById('cookieConsent').classList.add('d-none');
    });
});
</script>
<script>
document.querySelectorAll('form[action="add_to_cart.php"]').forEach(form => {
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = this.querySelector('button');
        const original = btn.innerHTML;
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
                document.querySelectorAll('.cart-badge').forEach(b => b.textContent = data.cart_count);
                btn.innerHTML = '✓ В корзине';
                btn.style.background = 'linear-gradient(135deg,#63d68f,#3fb56f)';
                btn.style.color = '#04240f';
                setTimeout(() => {
                    btn.innerHTML = original;
                    btn.style.background = '';
                    btn.style.color = '';
                    btn.disabled = false;
                }, 1400);
            } else {
                btn.innerHTML = original;
                btn.disabled = false;
            }
        })
        .catch(() => {
            btn.innerHTML = original;
            btn.disabled = false;
        });
    });
});
</script>
</body>
</html>