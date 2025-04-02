<?php
session_start();
include 'includes/db.php';
include 'templates/header.php';

// Проверяем, что пользователь авторизован и является водителем
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] !== 2) {
    header('Location: index.php');
    exit;
}

// Получаем ID поездки из URL
$trip_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Получаем данные поездки
$stmt = $pdo->prepare("SELECT * FROM Поездки WHERE ID_поездки = :trip_id");
$stmt->execute(['trip_id' => $trip_id]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trip) {
    header('Location: profile.php');
    exit;
}

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departure = trim($_POST['departure']);
    $destination = trim($_POST['destination']);
    $date = trim($_POST['date']);
    $seats = (int)$_POST['seats'];
    $price = (float)$_POST['price'];

    try {
        // Обновляем данные поездки
        $stmt = $pdo->prepare("UPDATE Поездки SET 
            Место_отправки = :departure, 
            Место_назначения = :destination, 
            Дата_поездки = :date, 
            Количество_свободных_мест = :seats, 
            Цена_поездки = :price 
            WHERE ID_поездки = :trip_id");

        $stmt->execute([
            'departure' => $departure,
            'destination' => $destination,
            'date' => $date . ' 00:00:00',
            'seats' => $seats,
            'price' => $price,
            'trip_id' => $trip_id
        ]);

        $_SESSION['success'] = 'Данные поездки успешно обновлены!';
        header('Location: profile.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Ошибка обновления данных поездки: ' . $e->getMessage();
        header('Location: profile.php');
        exit;
    }
}
?>

<main class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white text-center">
                    <h2>Редактирование поездки</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="edit_trip.php?id=<?= htmlspecialchars($trip_id) ?>" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="departure" class="form-label">Место отправки:</label>
                            <input type="text" name="departure" id="departure" class="form-control" value="<?= htmlspecialchars($trip['Место_отправки']) ?>" required>
                            <div class="invalid-feedback">Место отправки обязательно.</div>
                        </div>

                        <div class="mb-3">
                            <label for="destination" class="form-label">Место назначения:</label>
                            <input type="text" name="destination" id="destination" class="form-control" value="<?= htmlspecialchars($trip['Место_назначения']) ?>" required>
                            <div class="invalid-feedback">Место назначения обязательно.</div>
                        </div>

                        <div class="mb-3">
                            <label for="date" class="form-label">Дата поездки:</label>
                            <input type="date" name="date" id="date" class="form-control" value="<?= htmlspecialchars(date('Y-m-d', strtotime($trip['Дата_поездки']))) ?>" required>
                            <div class="invalid-feedback">Дата обязательна.</div>
                        </div>

                        <div class="mb-3">
                            <label for="seats" class="form-label">Количество мест:</label>
                            <input type="number" name="seats" id="seats" class="form-control" value="<?= htmlspecialchars($trip['Количество_свободных_мест']) ?>" min="1" required>
                            <div class="invalid-feedback">Количество мест должно быть больше нуля.</div>
                        </div>

                        <div class="mb-3">
                            <label for="price" class="form-label">Цена поездки:</label>
                            <input type="number" name="price" id="price" class="form-control" value="<?= htmlspecialchars($trip['Цена_поездки']) ?>" step="0.01" min="0.01" required>
                            <div class="invalid-feedback">Цена должна быть больше нуля.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Сохранить изменения</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'templates/footer.php'; ?>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Валидация формы Bootstrap
    (function () {
        'use strict';
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
</script>