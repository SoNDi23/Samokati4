<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id > 0 && in_array($type, ['product', 'service'])) {
        $key = $type . '_' . $id;
        
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        if (isset($_SESSION['cart'][$key])) {
            if (is_array($_SESSION['cart'][$key])) {
                $_SESSION['cart'][$key]['quantity']++;
            } else {
                $_SESSION['cart'][$key]++;
            }
        } else {
            $_SESSION['cart'][$key] = [
                'type' => $type,
                'id' => $id,
                'quantity' => 1
            ];
        }
    }
}

// Если запрос пришел фоном через JavaScript (AJAX)
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'cart_count' => count($_SESSION['cart'])]);
    exit;
}

// Запасной вариант (обычный редирект), если JS не сработал
header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'catalog.php'));
exit;