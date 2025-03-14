<?php
// Файл для подключения к базе данных
$host = 'localhost'; // Хост (обычно localhost)
$dbname = 'carpooling'; // Имя вашей базы данных
$username = 'root'; // Имя пользователя БД
$password = ''; // Пароль пользователя БД

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>