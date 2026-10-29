<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $newName = trim($_POST['name']);
    $newEmail = trim($_POST['email']);
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['password_confirm'];

    if (!empty($newName) && !empty($newEmail)) {
        if (!empty($newPassword)) {
            if ($newPassword === $confirmPassword) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
                $stmt->execute([$newName, $newEmail, $hashedPassword, $user_id]);
                header("Location: profile.php");
                exit;
            } else {
                $error_msg = "Пароли не совпадают!";
            }
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$newName, $newEmail, $user_id]);
            header("Location: profile.php");
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $order_id = (int)$_POST['order_id'];
    $stmt = $pdo->prepare("UPDATE orders SET status = 'Отменен' WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    header("Location: profile.php");
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch();

$stmtOrders = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmtOrders->execute([$user_id]);
$orders = $stmtOrders->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <link rel="icon" type="image/png" href="/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - Самокатыч</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif !important; background-color: #05101d !important; color: #ffffff !important; min-height: 100vh; display: flex; flex-direction: column; }
        .navbar { background-color: #091c32; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .custom-card { background-color: #091c32; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 15px; }
        .btn-custom { background-color: #f2c94c; color: #091c32; font-weight: 600; border: none; }
        .btn-custom:hover { background-color: #e0b93e; color: #091c32; }
        .table-custom { color: #ffffff !important; }
        .table-custom th, .table-custom td { border-color: rgba(255,255,255,0.1); background-color: transparent; vertical-align: middle; color: #ffffff !important; }
        .form-control-custom { background-color: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
        .form-control-custom:focus { background-color: rgba(255,255,255,0.1); color: #fff; box-shadow: none; border-color: #f2c94c; }
        .btn-eye { background-color: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); border-left: none; color: #fff; display: flex; align-items: center; justify-content: center; width: 45px; }
        .btn-eye:hover, .form-control-custom:focus + .btn-eye { border-color: #f2c94c; color: #f2c94c; }
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
            <a href="catalog.php" class="btn btn-outline-light me-3">В каталог</a>
            <?php if (in_array($user['role'], ['admin', 'manager'])): ?>
                <a href="admin.php" class="btn btn-outline-warning me-3">Админ-панель</a>
            <?php endif; ?>
            <a href="profile.php?logout=1" class="btn btn-outline-danger">Выйти</a>
        </div>
    </div>
</nav>

<div class="container mt-5 mb-5">
    
    <?php if ($error_msg): ?>
        <div class="alert alert-danger border-danger bg-dark text-danger fw-bold p-3 rounded-3 mb-4">
            <?= htmlspecialchars($error_msg) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="custom-card p-4 h-100 d-flex flex-column">
                <h4 class="text-warning mb-4">Мой профиль</h4>
                <p class="mb-3"><span class="text-white-50">Имя:</span> <br><span class="fs-5"><?= htmlspecialchars($user['name']) ?></span></p>
                <p class="mb-3"><span class="text-white-50">Email:</span> <br><span class="fs-5"><?= htmlspecialchars($user['email']) ?></span></p>
                <p class="mb-0"><span class="text-white-50">Роль:</span> <br><span class="fs-5"><?= $user['role'] === 'admin' ? 'Администратор' : ($user['role'] === 'manager' ? 'Менеджер' : 'Покупатель') ?></span></p>
                
                <button type="button" class="btn btn-outline-light w-100 mt-auto pt-2 pb-2" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                    Настройки профиля
                </button>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="custom-card p-4 h-100">
                <h4 class="text-warning mb-4">Мои заказы</h4>
                
                <?php if (empty($orders)): ?>
                    <p class="text-white-50">У вас пока нет заказов.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom m-0">
                            <thead>
                                <tr>
                                    <th>№ Заказа</th>
                                    <th>Дата</th>
                                    <th>Сумма</th>
                                    <th>Статус</th>
                                    <th>Действие</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): 
                                    $status = !empty($order['status']) ? $order['status'] : 'Новый';
                                ?>
                                <tr>
                                    <td>#<?= $order['id'] ?></td>
                                    <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                                    <td class="fw-bold"><?= number_format($order['total_price'], 0, '', ' ') ?> ₽</td>
                                    <td>
                                        <?php
                                            $badgeClass = 'bg-warning text-dark';
                                            if ($status === 'Готов') $badgeClass = 'bg-success';
                                            if ($status === 'Отменен') $badgeClass = 'bg-danger';
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <a href="order_details.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-light">Состав</a>
                                            
                                            <?php if ($status !== 'Готов' && $status !== 'Отменен'): ?>
                                                <form method="POST" action="profile.php" class="m-0" onsubmit="return confirm('Отменить этот заказ?');">
                                                    <input type="hidden" name="cancel_order" value="1">
                                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2">&times;</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно настроек профиля -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: #091c32; border: 1px solid rgba(255,255,255,0.1);">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title text-warning fw-bold">Настройки профиля</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form action="profile.php" method="POST" id="profileForm">
                <div class="modal-body">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label text-white-50">Ваше имя</label>
                        <input type="text" name="name" class="form-control form-control-custom" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-white-50">Email</label>
                        <input type="email" name="email" class="form-control form-control-custom" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-white-50">Новый пароль</label>
                        <div class="input-group">
                            <input type="password" name="password" id="new_pwd" class="form-control form-control-custom border-end-0" placeholder="Оставьте пустым, если не меняете">
                            <button class="btn btn-eye toggle-password" type="button" data-target="new_pwd">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/><path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/></svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-white-50">Подтвердите новый пароль</label>
                        <div class="input-group">
                            <input type="password" name="password_confirm" id="confirm_pwd" class="form-control form-control-custom border-end-0" placeholder="Повторите пароль">
                            <button class="btn btn-eye toggle-password" type="button" data-target="confirm_pwd">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/><path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/></svg>
                            </button>
                        </div>
                        <div id="pwd_error" class="text-danger small mt-1 d-none">Пароли не совпадают</div>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-custom">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Проверка совпадения паролей
document.getElementById('profileForm').addEventListener('submit', function(e) {
    const pwd = document.getElementById('new_pwd').value;
    const confirmPwd = document.getElementById('confirm_pwd').value;
    const errorMsg = document.getElementById('pwd_error');
    
    if (pwd !== confirmPwd) {
        e.preventDefault();
        errorMsg.classList.remove('d-none');
        document.getElementById('confirm_pwd').classList.add('border-danger');
    } else {
        errorMsg.classList.add('d-none');
        document.getElementById('confirm_pwd').classList.remove('border-danger');
    }
});

// Переключение видимости пароля (глаз)
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);
        
        // SVG иконки глаза (открытый и закрытый)
        const eyeOpen = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/><path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/></svg>`;
        const eyeClosed = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.944 5.944 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755l.191.195z"/><path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/><path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12-.708.708z"/></svg>`;
        
        if (input.type === 'password') {
            input.type = 'text';
            this.innerHTML = eyeClosed;
        } else {
            input.type = 'password';
            this.innerHTML = eyeOpen;
        }
    });
});
</script>
</body>
</html>