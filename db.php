<?php
$host = 'localhost';
$db   = 'sonvdajo_samokat';
// На Beget логин пользователя базы по умолчанию совпадает с именем самой базы
$user = 'sonvdajo_samokat'; 
$pass = 'Sosi123!123'; // Укажите пароль, который вы сгенерировали
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // Отключаем эмуляцию для реальной защиты от инъекций
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // В рабочей версии сайта ошибки подключения лучше логировать в файл, а не выводить на экран
    die("Ошибка подключения к базе данных.");
}
?>