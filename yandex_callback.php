<?php
session_start();
require_once 'db.php';

$clientId = '7fa838844b804c11b036e4ac34057fc8';
$clientSecret = 'a031a38542c641f681083136f3108b11';
$redirectUri = 'https://samokati4.ru/yandex_callback.php';

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // 1. Обмениваем код на токен доступа
    $ch = curl_init('https://oauth.yandex.ru/token');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'authorization_code',
        'code' => $code,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $tokenInfo = json_decode($response, true);

    if (isset($tokenInfo['access_token'])) {
        $token = $tokenInfo['access_token'];

        // 2. Запрашиваем данные профиля пользователя
        $ch = curl_init('https://login.yandex.ru/info?format=json');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: OAuth ' . $token]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $userInfo = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (isset($userInfo['id'])) {
            $yandexId = $userInfo['id'];
            $email = $userInfo['default_email'] ?? '';
            $name = $userInfo['real_name'] ?? $userInfo['first_name'] ?? 'Пользователь';
            $phone = $userInfo['default_phone']['number'] ?? '';

            // 3. Ищем пользователя по yandex_id
            $stmt = $pdo->prepare("SELECT id FROM users WHERE yandex_id = ?");
            $stmt->execute([$yandexId]);
            $user = $stmt->fetch();

            if ($user) {
                // Пользователь найден — авторизуем
                $_SESSION['user_id'] = $user['id'];
            } else {
                // Ищем по email, чтобы связать аккаунты, если он уже регистрировался по почте
                $stmtEmail = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmtEmail->execute([$email]);
                $userByEmail = $stmtEmail->fetch();

                if ($userByEmail) {
                    // Привязываем Яндекс к существующему аккаунту
                    $updateStmt = $pdo->prepare("UPDATE users SET yandex_id = ? WHERE id = ?");
                    $updateStmt->execute([$yandexId, $userByEmail['id']]);
                    $_SESSION['user_id'] = $userByEmail['id'];
                } else {
                    // Создаем новый аккаунт с заглушкой вместо пароля
                    $randomPassword = bin2hex(random_bytes(8));
                    $hash = password_hash($randomPassword, PASSWORD_DEFAULT);

                    $insertStmt = $pdo->prepare("INSERT INTO users (yandex_id, name, email, phone, password_hash) VALUES (?, ?, ?, ?, ?)");
                    $insertStmt->execute([$yandexId, $name, $email, $phone, $hash]);
                    
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                }
            }
            
            // Успешный вход, направляем на главную
            header("Location: index.php");
            exit;
        }
    }
}

// В случае отмены или ошибки
$_SESSION['error_msg'] = "Не удалось войти через Яндекс.";
header("Location: login.php");
exit;