<?php
session_start();
include 'includes/db.php';

// Проверяем, что пользователь авторизован
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Вы не авторизованы.';
    header('Location: trip_details.php?id=' . htmlspecialchars($_GET['id']));
    exit;
}

// Получаем ID поездки из параметров GET
$trip_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$trip_id) {
    $_SESSION['error'] = 'ID поездки не указан.';
    header('Location: index.php');
    exit;
}

try {
    // Проверяем, существует ли поездка
    $stmt = $pdo->prepare("SELECT * FROM Поездки WHERE ID_поездки = :trip_id");
    $stmt->execute(['trip_id' => $trip_id]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trip) {
        throw new Exception('Поездка не найдена.');
    }

    // Добавляем комментарий
    $comment_text = trim($_POST['comment_text']);

    if (empty($comment_text)) {
        throw new Exception('Текст сообщения не может быть пустым.');
    }

    $stmt = $pdo->prepare("
        INSERT INTO Комментарий_к_поездке (ID_пользователя, ID_поездки, Сообщение) 
        VALUES (:user_id, :trip_id, :comment_text)
    ");

    $stmt->execute([
        'user_id' => $_SESSION['user_id'],
        'trip_id' => $trip_id,
        'comment_text' => $comment_text
    ]);

    $_SESSION['success'] = 'Ваше сообщение успешно отправлено!';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Ошибка отправки сообщения: ' . $e->getMessage();
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header('Location: trip_details.php?id=' . $trip_id);
exit;