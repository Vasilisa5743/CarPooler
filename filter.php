<?php
session_start();
include 'includes/db.php';
include 'templates/header.php';

// Получаем параметры фильтрации из GET-запроса
$price = isset($_GET['price']) ? (float)$_GET['price'] : null;
$date = isset($_GET['date']) ? $_GET['date'] : null;
$seats = isset($_GET['seats']) ? (int)$_GET['seats'] : null;

// Формируем SQL-запрос с учетом параметров фильтрации
$sql = "SELECT * FROM поездки WHERE Количество_свободных_мест > 0";

$params = [];
$conditions = [];

if (!empty($price)) {
    $conditions[] = "Цена_поездки <= :price";
    $params[':price'] = $price;
}

if (!empty($date)) {
    $conditions[] = "Дата_поездки >= :date";
    $params[':date'] = $date . ' 00:00:00';
}

if (!empty($seats)) {
    $conditions[] = "Количество_свободных_мест >= :seats";
    $params[':seats'] = $seats;
}

if (!empty($conditions)) {
    $sql .= ' AND ' . implode(' AND ', $conditions);
}

$sql .= ' ORDER BY Дата_поездки ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

    <main>
        <h2>Фильтрация поездок</h2>

        <!-- Форма фильтрации -->
        <form class="filter-form" action="filter.php" method="GET">
            <label for="price">Цена до:</label>
            <input type="number" name="price" id="price" value="<?= htmlspecialchars($_GET['price'] ?? '') ?>" placeholder="Введите максимальную цену">

            <label for="date">Дата от:</label>
            <input type="date" name="date" id="date" value="<?= htmlspecialchars($_GET['date'] ?? '') ?>" placeholder="Выберите дату">

            <label for="seats">Минимальное количество мест:</label>
            <input type="number" name="seats" id="seats" value="<?= htmlspecialchars($_GET['seats'] ?? '') ?>" placeholder="Введите количество мест">

            <button type="submit">Применить фильтр</button>

            <a href="filter.php" class="reset-button">Сбросить фильтры</a>
        </form>

        <!-- Результаты фильтрации -->
        <?php if (empty($trips)) : ?>
            <p>По вашему запросу ничего не найдено.</p>
        <?php else : ?>
            <div class="trip-grid">
                <?php foreach ($trips as $trip) : ?>
                    <div class="trip-card">

                        <!-- Заголовок маршрута -->
                        <h3><?= htmlspecialchars($trip['Место_отправки']) ?> → <?= htmlspecialchars($trip['Место_назначения']) ?></h3>

                        <!-- Дата поездки -->
                        <p><strong>Дата:</strong> <?= htmlspecialchars($trip['Дата_поездки']) ?></p>

                        <!-- Количество свободных мест -->
                        <p><strong>Свободные места:</strong> <?= htmlspecialchars($trip['Количество_свободных_мест']) ?></p>

                        <!-- Цена -->
                        <p><strong>Цена:</strong> <?= htmlspecialchars($trip['Цена_поездки']) ?> ₽</p>

                        <!-- Действия -->
                        <div class="details">
                            <?php if (isset($_SESSION['user_id'])) : ?>
                                <a href="trip_details.php?id=<?= htmlspecialchars($trip['ID_поездки']) ?>">
                                    <button>Посмотреть</button>
                                </a>
                            <?php else : ?>
                                <span>Войдите, чтобы забронировать место</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

<?php include 'templates/footer.php'; ?>