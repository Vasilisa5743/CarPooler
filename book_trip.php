<?php
session_start();
include 'includes/db.php';

// Проверяем, что пользователь авторизован и является пассажиром
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] !== 1) {
    $_SESSION['error'] = 'Только пассажиры могут бронировать места.';
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
    // Начинаем транзакцию
    $pdo->beginTransaction();

    // Проверяем, есть ли свободные места в поездке
    $stmt = $pdo->prepare("SELECT * FROM Поездки WHERE ID_поездки = :trip_id AND Количество_свободных_мест > 0");
    $stmt->execute(['trip_id' => $trip_id]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trip) {
        throw new Exception('Нет свободных мест или поездка не найдена.');
    }

    // Получаем ID пассажира
    $stmt = $pdo->prepare("SELECT ID_пассажира FROM Пассажиры WHERE ID_пользователя = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $passenger_id = $stmt->fetchColumn();

    if (!$passenger_id) {
        throw new Exception('Пассажир не найден.');
    }

    // Проверяем, не отправлена ли уже заявка на эту поездку
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Бронирование WHERE ID_поездки = :trip_id AND ID_пассажира = :passenger_id");
    $stmt->execute(['trip_id' => $trip_id, 'passenger_id' => $passenger_id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        throw new Exception('Вы уже отправили заявку на эту поездку.');
    }

    // Получаем данные из формы
    $waiting_place = trim($_POST['waiting_place']);

    if (empty($waiting_place)) {
        throw new Exception('Укажите место ожидания.');
    }

    // Создаём запись в таблице "бронирование" со статусом "ожидание" (0)
    $stmt = $pdo->prepare("
        INSERT INTO Бронирование (
            ID_поездки,
            ID_водителя,
            ID_пассажира,
            Место_отправки,
            Дата_поездки,
            Дата_бронирования,
            Статус
        ) VALUES (
            :trip_id,
            :driver_id,
            :passenger_id,
            :waiting_place,
            :trip_date,
            NOW(),
            0
        )
    ");

    $stmt->execute([
        'trip_id' => $trip_id,
        'driver_id' => $trip['ID_водителя'],
        'passenger_id' => $passenger_id,
        'waiting_place' => $waiting_place,
        'trip_date' => $trip['Дата_поездки']
    ]);

    // Фиксируем транзакцию
    $pdo->commit();

    $_SESSION['success'] = 'Заявка на бронирование успешно отправлена!';
} catch (PDOException $e) {
    // Откатываем транзакцию при ошибке базы данных
    $pdo->rollBack();
    $_SESSION['error'] = 'Ошибка бронирования: ' . $e->getMessage();
} catch (Exception $e) {
    // Откатываем транзакцию при логической ошибке
    $pdo->rollBack();
    $_SESSION['error'] = $e->getMessage();
}

header('Location: trip_details.php?id=' . $trip_id);
exit;