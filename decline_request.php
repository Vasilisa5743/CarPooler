<?php
session_start();
include 'includes/db.php';

// Проверяем, что пользователь авторизован и является водителем
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] !== 2) {
    $_SESSION['error'] = 'Только водители могут управлять заявками.';
    header('Location: profile.php');
    exit;
}

// Получаем ID заявки из параметров GET
$request_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$request_id) {
    $_SESSION['error'] = 'ID заявки не указан.';
    header('Location: profile.php');
    exit;
}

try {
    // Находим заявку
    $stmt = $pdo->prepare("SELECT * FROM Бронирование WHERE ID_бронирования = :request_id");
    $stmt->execute(['request_id' => $request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        throw new Exception('Заявка не найдена.');
    }

    // Проверяем, что заявка ещё не принята/отклонена
    if ($request['статус'] != 0) {
        throw new Exception('Заявка уже обработана.');
    }

    // Отклоняем заявку (обновляем статус на -1)
    $stmt = $pdo->prepare("UPDATE Бронирование SET статус = -1 WHERE ID_бронирования = :request_id");
    $stmt->execute(['request_id' => $request_id]);

    $_SESSION['success'] = 'Заявка успешно отклонена!';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Ошибка отклонения заявки: ' . $e->getMessage();
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header('Location: profile.php');
exit;