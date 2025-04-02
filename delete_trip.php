<?php
session_start();
include 'includes/db.php';

// Проверяем, что пользователь авторизован и является водителем
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] !== 2) {
    header('Location: index.php');
    exit;
}

// Получаем ID поездки из URL
$trip_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($trip_id) {
    try {
        // Удаляем поездку
        $stmt = $pdo->prepare("DELETE FROM Поездки WHERE ID_поездки = :trip_id");
        $stmt->execute(['trip_id' => $trip_id]);

        $_SESSION['success'] = 'Поездка успешно удалена!';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Ошибка удаления поездки: ' . $e->getMessage();
    }
}

header('Location: profile.php');
exit;