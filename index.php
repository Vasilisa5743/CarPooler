<?php
include 'templates/header.php';
include 'includes/db.php';

// Получаем параметры поиска и фильтрации
$departure = isset($_GET['departure']) ? trim($_GET['departure']) : '';
$destination = isset($_GET['destination']) ? trim($_GET['destination']) : '';
$price = isset($_GET['price']) ? (float)$_GET['price'] : null;
$date = isset($_GET['date']) ? $_GET['date'] : null;
$seats = isset($_GET['seats']) ? (int)$_GET['seats'] : null;

// Формируем SQL-запрос
$sql = "SELECT * FROM Поездки WHERE Колличество_свободных_мест > 0";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

$params = [];
$conditions = [];

if (!empty($departure)) {
    $conditions[] = "Место_отправки LIKE :departure";
    $params[':departure'] = '%' . $departure . '%';
}

if (!empty($destination)) {
    $conditions[] = "Место_назначения LIKE :destination";
    $params[':destination'] = '%' . $destination . '%';
}

if (!empty($price)) {
    $conditions[] = "Цена_поездки <= :price";
    $params[':price'] = $price;
}

if (!empty($date)) {
    $conditions[] = "Дата_поездки >= :date";
    $params[':date'] = $date . ' 00:00:00';
}

if (!empty($seats)) {
    $conditions[] = "Колличество_свободных_мест >= :seats";
    $params[':seats'] = $seats;
}

if (!empty($conditions)) {
    $sql .= ' AND (' . implode(' OR ', $conditions) . ')';
}

if (empty($trips)) {
    $trips = [];
}

$sql .= ' ORDER BY Дата_поездки ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container my-5">
    <h2 class="text-center mb-4">Доступные поездки</h2>

    <!-- Форма поиска и фильтрации -->
    <form action="index.php" method="GET" class="row g-3 align-items-center mb-4">
        <div class="col-auto">
            <label for="departure" class="form-label visually-hidden">Место отправки:</label>
            <input type="text" name="departure" id="departure" class="form-control" placeholder="Место отправки" value="<?= htmlspecialchars($departure) ?>">
        </div>

        <div class="col-auto">
            <label for="destination" class="form-label visually-hidden">Место назначения:</label>
            <input type="text" name="destination" id="destination" class="form-control" placeholder="Место назначения" value="<?= htmlspecialchars($destination) ?>">
        </div>

        <div class="col-auto">
            <label for="price" class="form-label visually-hidden">Цена до:</label>
            <input type="number" name="price" id="price" class="form-control" placeholder="Цена до" value="<?= htmlspecialchars($price ?? '') ?>">
        </div>

        <div class="col-auto">
            <label for="date" class="form-label visually-hidden">Дата от:</label>
            <input type="date" name="date" id="date" class="form-control" value="<?= htmlspecialchars($date ?? '') ?>">
        </div>

        <div class="col-auto">
            <label for="seats" class="form-label visually-hidden">Мин. места:</label>
            <input type="number" name="seats" id="seats" class="form-control" placeholder="Мин. места" value="<?= htmlspecialchars($seats ?? '') ?>">
        </div>

        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Применить</button>
        </div>

        <div class="col-auto">
            <a href="index.php" class="btn btn-outline-secondary">Сбросить</a>
        </div>
    </form>

    <!-- Список поездок в виде карточек -->
    <?php if (empty($trips)) : ?>
        <p class="text-center text-muted">Нет доступных поездок.</p>
    <?php else : ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($trips as $trip) : ?>
                <div class="col">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($trip['Место_отправки']) ?> → <?= htmlspecialchars($trip['Место_назначения']) ?></h5>
                            <p class="card-text"><strong>Дата:</strong> <?= htmlspecialchars($trip['Дата_поездки']) ?></p>
                            <p class="card-text"><strong>Свободные места:</strong> <?= htmlspecialchars($trip['Колличество_свободных_мест']) ?></p>
                            <p class="card-text"><strong>Цена:</strong> <?= htmlspecialchars($trip['Цена_поездки']) ?> ₽</p>
                            <div class="d-grid gap-2">
                                <?php if (isset($_SESSION['user_id'])) : ?>
                                    <a href="trip_details.php?id=<?= htmlspecialchars($trip['ID_поездки']) ?>" class="btn btn-primary">Посмотреть</a>
                                <?php else : ?>
                                    <a href="login.php" class="btn btn-outline-primary">Войдите, чтобы забронировать место</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include 'templates/footer.php'; ?>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>