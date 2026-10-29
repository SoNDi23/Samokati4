<?php
session_start();
require_once 'db.php';

// Если пользователь уже авторизован, отправляем его на главную
if (isset($_SESSION['user_id'])) {
    header("Location: https://samokati4.ru/");
    exit;
}

$errors = [];
$email = '';

// Конфигурация для Яндекс OAuth
$yandexClientId = '7'; 
$yandexRedirectUri = 'https://samokati4.ru/yandex_callback.php';
$yandexAuthUrl = "https://oauth.yandex.ru/authorize?response_type=code&client_id={$yandexClientId}&redirect_uri=" . urlencode($yandexRedirectUri);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = "Введите Email и пароль.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // Проверяем существование пользователя и совпадение пароля
        if ($user && password_verify($password, $user['password_hash'])) {
            // Успешная авторизация
            $_SESSION['user_id'] = $user['id'];
           header("Location: https://samokati4.ru/");
            exit;
        } else {
            $errors[] = "Неверный Email или пароль.";
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
    <title>Вход - Самокатыч</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --brand-blue: #091c32; 
            --brand-dark: #05101d; 
            --brand-yellow: #f2c94c;
            --brand-white: #ffffff;
        }
        body {
            font-family: 'Montserrat', sans-serif !important;
            background-color: var(--brand-dark) !important;
            color: var(--brand-white) !important;
            min-height: 100vh;
            background-image: url("data:image/svg+xml,%3Csvg width='250' height='250' viewBox='0 0 250 250' xmlns='http://www.w3.org/2000/svg' fill='%23f2c94c' stroke='%23f2c94c' stroke-opacity='0.08' fill-opacity='0.08'%3E%3Cg transform='translate(40, 30) scale(2.5)' stroke='none'%3E%3Cpath d='M12 2L5 14h5v10l9-14h-6z'/%3E%3C/g%3E%3Cg transform='translate(180, 80) scale(1.2)' stroke='none'%3E%3Cpath d='M 0 -22 L 4 -22 L 6 -15 L 10 -11 L 16 -13 L 19 -9 L 14 -4 L 14 4 L 19 9 L 16 13 L 10 11 L 6 15 L 4 22 L -4 22 L -6 15 L -10 11 L -16 13 L -19 9 L -14 4 L -14 -4 L -19 -9 L -16 -13 L -10 -11 L -6 -15 Z M 0 -7 A 7 7 0 1 0 0 7 A 7 7 0 1 0 0 -7 Z' fill-rule='evenodd'/%3E%3C/g%3E%3Cg transform='translate(70, 140) scale(2.5)' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' fill='none'%3E%3Ccircle cx='8' cy='20' r='4'/%3E%3Ccircle cx='28' cy='20' r='4'/%3E%3Cpath d='M8 20h18M26 20l-4-16l2 0M18 4h8'/%3E%3C/g%3E%3C/svg%3E") !important;
            background-attachment: fixed;
        }
        .navbar { background-color: var(--brand-blue); border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .custom-card {
            background-color: var(--brand-blue);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            backdrop-filter: blur(5px);
        }
        .btn-custom { background-color: var(--brand-yellow); color: var(--brand-blue); font-weight: 600; border: none; }
        .btn-custom:hover { background-color: #e0b93e; color: var(--brand-blue); }
        
        .btn-yandex { background-color: #fc3f1d; color: #ffffff; font-weight: 600; border: none; transition: background-color 0.2s; }
        .btn-yandex:hover { background-color: #e03215; color: #ffffff; }

        .form-control { background-color: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; }
        .form-control:focus { background-color: rgba(255,255,255,0.1); border-color: var(--brand-yellow); color: #fff; box-shadow: none; }
        .form-label { font-weight: 600; font-size: 0.9rem; color: rgba(255,255,255,0.8); }
        .btn-eye { background-color: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); border-left: none; color: #fff; display: flex; align-items: center; justify-content: center; width: 45px; }
.btn-eye:hover, .form-control-custom:focus + .btn-eye { border-color: #f2c94c; color: #f2c94c; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="/">
            <img src="logo.png" alt="Логотип" style="height: 55px; width: auto; border-radius: 8px; margin-right: 12px;">
            <span style="color: #ffffff !important; font-weight: 700;">Самокатыч</span>
        </a>
    </div>
</nav>

<div class="container mt-5 pt-3 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card custom-card p-4 p-md-5 shadow-lg">
                <h2 class="text-center mb-4" style="font-weight: 700; color: var(--brand-yellow);">Вход</h2>
                
                <?php
                // Сообщение об успешной регистрации
                if (isset($_SESSION['success_msg'])) {
                    echo '<div class="alert alert-success" style="background-color: rgba(25, 135, 84, 0.1); border-color: #198754; color: #75b798;">' . htmlspecialchars($_SESSION['success_msg']) . '</div>';
                    unset($_SESSION['success_msg']);
                }
                // Сообщение об ошибке входа через Яндекс
                if (isset($_SESSION['error_msg'])) {
                    echo '<div class="alert alert-danger" style="background-color: rgba(220, 53, 69, 0.1); border-color: #dc3545; color: #ff6b72;">' . htmlspecialchars($_SESSION['error_msg']) . '</div>';
                    unset($_SESSION['error_msg']);
                }
                ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" style="background-color: rgba(220, 53, 69, 0.1); border-color: #dc3545; color: #ff6b72;">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <a href="<?= $yandexAuthUrl ?>" class="btn btn-yandex w-100 py-2 mb-4 d-flex justify-content-center align-items-center">
                    <span style="font-family: Arial, sans-serif; font-size: 1.2rem; font-weight: bold; margin-right: 10px;">Я</span>
                    Войти через Яндекс
                </a>

                <div class="d-flex align-items-center mb-4">
                    <hr class="flex-grow-1 border-secondary m-0">
                    <span class="mx-3 text-white-50 small">или по email</span>
                    <hr class="flex-grow-1 border-secondary m-0">
                </div>

                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Пароль</label>
                        <div class="input-group">
    <input type="password" name="password" id="pwd" class="form-control form-control-custom border-end-0" required>
    <button class="btn btn-eye toggle-password" type="button" data-target="pwd">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/><path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/></svg>
    </button>
</div>
                    </div>

                    <button type="submit" class="btn btn-custom w-100 py-2 mb-3">Войти</button>
                    
                    <div class="text-center">
                        <span class="text-white-50 small">Нет аккаунта?</span> 
                        <a href="register.php" style="color: var(--brand-yellow); text-decoration: none; font-weight: 600;" class="small">Зарегистрироваться</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);
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
