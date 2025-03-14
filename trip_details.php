<?php
session_start();
include 'includes/db.php';
include 'templates/header.php';

if (!isset($_GET['id'])) {
    die("ID поездки не указан.");
}

$tripId = $_GET['id'];

// Получение деталей поездки
$sql = "SELECT * FROM поездки WHERE ID_поездки = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $tripId, PDO::PARAM_INT);
$stmt->execute();
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trip) {
    die("Поездка не найдена.");
}

?>

    <main class="trip-details">
        <h2>Детали поездки</h2>
        <p><strong>ID водителя:</strong> <?= htmlspecialchars($trip['ID_водителя']) ?></p>
        <p><strong>Место отправки:</strong> <?= htmlspecialchars($trip['Место_отправки']) ?></p>
        <p><strong>Место назначения:</strong> <?= htmlspecialchars($trip['Место_назначения']) ?></p>
        <p><strong>Дата поездки:</strong> <?= htmlspecialchars($trip['Дата_поездки']) ?></p>
        <p><strong>Свободные места:</strong> <?= htmlspecialchars($trip['Колличество_свободных_мест']) ?></p>
        <p><strong>Цена:</strong> <?= htmlspecialchars($trip['Цена_поездки']) ?> ₽</p>

        <?php if (isset($_SESSION['user_id'])) : ?>
            <a href="book_trip.php?id=<?= $trip['ID_поездки'] ?>">Забронировать место</a>
        <?php else : ?>
            <p>Войдите в систему, чтобы забронировать место.</p>
        <?php endif; ?>
    </main>

<?php include 'templates/footer.php'; ?>