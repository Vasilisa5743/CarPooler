<?php
session_start();
include 'includes/db.php';

// Проверяем, что пользователь авторизован и является водителем
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] !== 3) {
    header('Location: index.php');
    exit;
}

// Переменные для ошибок и успеха
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departure = trim($_POST['departure']);
    $destination = trim($_POST['destination']);
    $date = trim($_POST['date']);
    $seats = (int)$_POST['seats'];
    $price = (float)$_POST['price'];

    // Валидация данных
    if (empty($departure)) {
        $error = 'Место отправки не может быть пустым.';
    } elseif (empty($destination)) {
        $error = 'Место назначения не может быть пустым.';
    } elseif (empty($date)) {
        $error = 'Дата поездки не может быть пустой.';
    } elseif ($seats <= 0) {
        $error = 'Количество мест должно быть больше нуля.';
    } elseif ($price <= 0) {
        $error = 'Цена должна быть больше нуля.';
    } else {
        try {
            // Получаем ID водителя из таблицы "Водители"
            $stmt = $pdo->prepare("SELECT ID_водителя FROM Водители WHERE ID_пользователя = :user_id");
            $stmt->execute(['user_id' => $_SESSION['user_id']]);
            $driver_id = $stmt->fetchColumn();

            if (!$driver_id) {
                $error = 'Вы не являетесь зарегистрированным водителем.';
            } else {
                // Добавляем поездку в таблицу "поездки"
                $stmt = $pdo->prepare("INSERT INTO поездки (ID_водителя, Место_отправки, Место_назначения, Колличество_свободных_мест, Цена_поездки, Дата_поездки) 
                                     VALUES (:driver_id, :departure, :destination, :seats, :price, :date)");
                $stmt->execute([
                    'driver_id' => $driver_id,
                    'departure' => $departure,
                    'destination' => $destination,
                    'seats' => $seats,
                    'price' => $price,
                    'date' => $date
                ]);

                $success = 'Поездка успешно создана!';
            }
        } catch (PDOException $e) {
            $error = 'Ошибка создания поездки: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание поездки</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<header>
    <h1>Поиск попутчиков</h1>
    <nav>
        <a href="index.php">Главная</a>
        <a href="profile.php">Профиль</a>
        <a href="create_trip.php">Создать поездку</a>
        <a href="logout.php">Выход</a>
    </nav>
</header>

<main>
    <div class="create-trip-form">
        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p class="success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <h2>Создание поездки</h2>
        <form method="POST">
            <label for="departure">Место отправки:</label>
            <input type="text" name="departure" id="departure" required value="<?= htmlspecialchars($_POST['departure'] ?? '') ?>">

            <label for="destination">Место назначения:</label>
            <input type="text" name="destination" id="destination" required value="<?= htmlspecialchars($_POST['destination'] ?? '') ?>">

            <label for="date">Дата поездки:</label>
            <input type="date" name="date" id="date" required value="<?= htmlspecialchars($_POST['date'] ?? '') ?>">

            <label for="seats">Количество мест:</label>
            <input type="number" name="seats" id="seats" min="1" required value="<?= htmlspecialchars($_POST['seats'] ?? '') ?>">

            <label for="price">Цена поездки:</label>
            <input type="number" name="price" id="price" step="0.01" min="0.01" required value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">

            <button type="submit">Создать поездку</button>
        </form>
    </div>
</main>

<footer>
    <p>&copy; 2025 CarPooler</p>
</footer>
</body>
</html>