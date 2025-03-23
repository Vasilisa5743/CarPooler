<?php
session_start();
include 'includes/db.php';

// Проверяем роль пользователя
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] !== 2) {
    header('Location: index.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departure = trim($_POST['departure']);
    $destination = trim($_POST['destination']);
    $date = trim($_POST['date']);
    $seats = (int)$_POST['seats'];
    $price = (float)$_POST['price'];

    // Валидация данных
    if (empty($departure)) {
        $error = 'Место отправки обязательно.';
    } elseif (empty($destination)) {
        $error = 'Место назначения обязательно.';
    } elseif (empty($date)) {
        $error = 'Дата обязательна.';
    } elseif ($seats <= 0) {
        $error = 'Количество мест должно быть больше нуля.';
    } elseif ($price <= 0) {
        $error = 'Цена должна быть больше нуля.';
    } else {
        try {
            // Получаем ID водителя
            $stmt = $pdo->prepare("SELECT ID_водителя FROM Водители WHERE ID_пользователя = :user_id");
            $stmt->execute(['user_id' => $_SESSION['user_id']]);
            $driver_id = $stmt->fetchColumn();

            if (!$driver_id) {
                $error = 'Вы не являетесь зарегистрированным водителем.';
            } else {
                // Создаём новую поездку
                $stmt = $pdo->prepare("INSERT INTO Поездки (
                    ID_водителя,
                    Место_отправки,
                    Место_назначения,
                    Колличество_свободных_мест,
                    Цена_поездки,
                    Дата_поездки
                ) VALUES (:driver_id, :departure, :destination, :seats, :price, :date)");
                $stmt->execute([
                    'driver_id' => $driver_id,
                    'departure' => $departure,
                    'destination' => $destination,
                    'seats' => $seats,
                    'price' => $price,
                    'date' => $date . ' 00:00:00'
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
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<header class="bg-primary text-white py-3">
    <div class="container">
        <h1 class="text-center">CarPooler</h1>
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">Главная</a>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="profile.php">Профиль</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Выход</a></li>
                </ul>
            </div>
        </nav>
    </div>
</header>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm p-4">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <h2 class="text-center mb-4">Создание поездки</h2>
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="departure" class="form-label">Место отправки:</label>
                        <input type="text" name="departure" id="departure" class="form-control" required>
                        <div class="invalid-feedback">Место отправки обязательно.</div>
                    </div>

                    <div class="mb-3">
                        <label for="destination" class="form-label">Место назначения:</label>
                        <input type="text" name="destination" id="destination" class="form-control" required>
                        <div class="invalid-feedback">Место назначения обязательно.</div>
                    </div>

                    <div class="mb-3">
                        <label for="date" class="form-label">Дата поездки:</label>
                        <input type="date" name="date" id="date" class="form-control" required>
                        <div class="invalid-feedback">Дата обязательна.</div>
                    </div>

                    <div class="mb-3">
                        <label for="seats" class="form-label">Количество мест:</label>
                        <input type="number" name="seats" id="seats" class="form-control" min="1" required>
                        <div class="invalid-feedback">Количество мест должно быть больше нуля.</div>
                    </div>

                    <div class="mb-3">
                        <label for="price" class="form-label">Цена поездки:</label>
                        <input type="number" name="price" id="price" class="form-control" step="0.01" min="0.01" required>
                        <div class="invalid-feedback">Цена должна быть больше нуля.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Создать поездку</button>
                </form>
            </div>
        </div>
    </div>
</div>

<footer class="bg-dark text-white text-center py-3">
    <p>&copy; 2025 CarPooler</p>
</footer>

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
</body>
</html>