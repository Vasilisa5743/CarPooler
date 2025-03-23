<?php
include 'templates/header.php';
include 'includes/db.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$trip_id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM Поездки WHERE ID_поездки = :id");
$stmt->execute(['id' => $trip_id]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trip) {
    header('Location: index.php');
    exit;
}
?>

    <main class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm p-4">
                    <h2 class="text-center mb-4">Детали поездки</h2>
                    <h5 class="card-title text-center"><?= htmlspecialchars($trip['Место_отправки']) ?> → <?= htmlspecialchars($trip['Место_назначения']) ?></h5>
                    <p class="card-text"><strong>Дата:</strong> <?= htmlspecialchars($trip['Дата_поездки']) ?></p>
                    <p class="card-text"><strong>Свободные места:</strong> <?= htmlspecialchars($trip['Колличество_свободных_мест']) ?></p>
                    <p class="card-text"><strong>Цена:</strong> <?= htmlspecialchars($trip['Цена_поездки']) ?> ₽</p>
                    <?php if (isset($_SESSION['user_id'])) : ?>
                        <a href="#" class="btn btn-primary w-100">Забронировать место</a>
                    <?php else : ?>
                        <p class="text-center">Войдите, чтобы забронировать место.</p>
                        <a href="login.php" class="btn btn-outline-primary w-100">Войти</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

<?php include 'templates/footer.php'; ?>