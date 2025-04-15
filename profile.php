<?php
session_start();
include 'includes/db.php';
include 'templates/header.php';

// Проверяем, что пользователь авторизован
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];

try {
    // Информация о пользователе
    $stmt = $pdo->prepare("SELECT * FROM Пользователи WHERE ID_пользователя = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Пользователь не найден.');
    }

    // Если роль "Водитель", получаем информацию из таблицы "Водители" и список созданных поездок
    if ($role_id == 2) {
        $stmt = $pdo->prepare("SELECT * FROM Водители WHERE ID_пользователя = :user_id");
        $stmt->execute(['user_id' => $user_id]);
        $driver = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$driver) {
            throw new Exception('Водитель не найден.');
        }

        // Созданные поездки водителя
        $stmt = $pdo->prepare("SELECT * FROM Поездки WHERE ID_водителя = :driver_id ORDER BY Дата_поездки DESC");
        $stmt->execute(['driver_id' => $driver['ID_водителя']]);
        $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Список заявок на бронирование для каждой поездки
        foreach ($trips as &$trip) {
            $stmt = $pdo->prepare("
                SELECT Бронирование.*, Пассажиры.ФИО AS Пассажир_фамилия 
                FROM Бронирование 
                JOIN Пассажиры ON Бронирование.ID_пассажира = Пассажиры.ID_пассажира 
                WHERE Бронирование.ID_поездки = :trip_id
            ");
            $stmt->execute(['trip_id' => $trip['ID_поездки']]);
            $trip['Заявки'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    // Если роль "Пассажир", получаем информацию из таблицы "Пассажиры" и список забронированных поездок
    if ($role_id == 1) {
        $stmt = $pdo->prepare("SELECT * FROM Пассажиры WHERE ID_пользователя = :user_id");
        $stmt->execute(['user_id' => $user_id]);
        $passenger = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$passenger) {
            throw new Exception('Пассажир не найден.');
        }

        // Забронированные поездки пассажира
        $stmt = $pdo->prepare("
            SELECT Бронирование.*, Поездки.*  
            FROM Бронирование 
            JOIN Поездки ON Бронирование.ID_поездки = Поездки.ID_поездки 
            WHERE Бронирование.ID_пассажира = :passenger_id
        ");
        $stmt->execute(['passenger_id' => $passenger['ID_пассажира']]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = 'Ошибка загрузки профиля: ' . $e->getMessage();
}
?>

<main class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <!-- Карточка профиля -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white text-center">
                    <h2>Профиль</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <!-- Информация о пользователе -->
                    <div id="profile-info" class="mb-4">
                        <p><strong>Логин:</strong> <?= htmlspecialchars($user['Логин']) ?></p>
                        <p><strong>Роль:</strong>
                            <?= ($role_id == 1) ? 'Пассажир' : (($role_id == 2) ? 'Водитель' : 'Неизвестная роль') ?>
                        </p>

                        <?php if ($role_id == 2): ?>
                            <p><strong>ФИО:</strong> <?= htmlspecialchars($driver['ФИО'] ?? 'Не указано') ?></p>
                            <p><strong>Телефон:</strong> <?= htmlspecialchars($driver['Номер_телефона'] ?? 'Не указано') ?></p>
                            <p><strong>Стаж вождения:</strong> <?= htmlspecialchars($driver['Стаж_вождения'] ?? 'Не указано') ?> лет</p>
                        <?php elseif ($role_id == 1): ?>
                            <p><strong>ФИО:</strong> <?= htmlspecialchars($passenger['ФИО'] ?? 'Не указано') ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Форма редактирования профиля -->
                    <button id="edit-profile-button" class="btn btn-outline-primary w-100 mt-3">Редактировать профиль</button>
                    <div id="edit-profile-form" style="display: none;">
                        <form method="POST" action="update_profile.php" class="needs-validation" novalidate>
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($user_id) ?>">
                            <div class="mb-3">
                                <label for="login" class="form-label">Логин:</label>
                                <input type="text" name="login" id="login" class="form-control" value="<?= htmlspecialchars($user['Логин']) ?>" required>
                                <div class="invalid-feedback">Логин обязателен.</div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Новый пароль (оставьте пустым, если не хотите менять):</label>
                                <input type="password" name="password" id="password" class="form-control">
                            </div>

                            <?php if ($role_id == 2): ?>
                                <div class="mb-3">
                                    <label for="fio" class="form-label">ФИО:</label>
                                    <input type="text" name="fio" id="fio" class="form-control" value="<?= htmlspecialchars($driver['ФИО'] ?? '') ?>" required>
                                    <div class="invalid-feedback">ФИО обязательно.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label">Телефон:</label>
                                    <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($driver['Номер_телефона'] ?? '') ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="experience" class="form-label">Стаж вождения (лет):</label>
                                    <input type="number" name="experience" id="experience" class="form-control" value="<?= htmlspecialchars($driver['Стаж_вождения'] ?? '') ?>" min="0">
                                </div>
                            <?php elseif ($role_id == 1): ?>
                                <div class="mb-3">
                                    <label for="fio" class="form-label">ФИО:</label>
                                    <input type="text" name="fio" id="fio" class="form-control" value="<?= htmlspecialchars($passenger['ФИО'] ?? '') ?>" required>
                                    <div class="invalid-feedback">ФИО обязательно.</div>
                                </div>
                            <?php endif; ?>

                            <button type="submit" class="btn btn-primary w-100">Сохранить изменения</button>
                        </form>
                    </div>

                    <!-- Список созданных поездок и заявок (для водителей) -->
                    <?php if ($role_id == 2 && !empty($trips)): ?>
                        <h3 class="mt-5">Созданные поездки</h3>
                        <?php foreach ($trips as $trip): ?>
                            <div class="card shadow-sm mb-3">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($trip['Место_отправки']) ?> → <?= htmlspecialchars($trip['Место_назначения']) ?></h5>
                                    <p class="card-text"><strong>Дата:</strong> <?= htmlspecialchars($trip['Дата_поездки']) ?></p>
                                    <p class="card-text"><strong>Места:</strong> <?= htmlspecialchars($trip['Количество_свободных_мест']) ?></p>
                                    <p class="card-text"><strong>Цена:</strong> <?= htmlspecialchars($trip['Цена_поездки']) ?> ₽</p>

                                    <!-- Список заявок на бронирование -->
                                    <?php if (!empty($trip['Заявки'])): ?>
                                        <h4 class="mt-3">Заявки на бронирование</h4>
                                        <ul class="list-group">
                                            <?php foreach ($trip['Заявки'] as $request): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <span>
                                                        <strong>Пассажир:</strong> <?= htmlspecialchars($request['Пассажир_фамилия']) ?><br>
                                                        <strong>Место ожидания:</strong> <?= htmlspecialchars($request['Место_отправки']) ?><br>
                                                        <strong>Статус:</strong>
                                                        <?= $request['Статус'] == 0 ? 'Ожидает подтверждения' :
                                                            ($request['Статус'] == 1 ? 'Принята' : 'Отклонена') ?>
                                                    </span>
                                                    <div>
                                                        <!-- Кнопки "Принять" и "Отклонить" -->
                                                        <?php if ($request['Статус'] == 0): ?>
                                                            <a href="accept_request.php?id=<?= htmlspecialchars($request['ID_бронирования']) ?>" class="btn btn-success me-2">Принять</a>
                                                            <a href="decline_request.php?id=<?= htmlspecialchars($request['ID_бронирования']) ?>" class="btn btn-danger">Отклонить</a>
                                                        <?php endif; ?>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-muted mt-3">У этой поездки пока нет заявок.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php elseif ($role_id == 2): ?>
                        <p class="text-center text-muted mt-3">У вас пока нет созданных поездок.</p>
                    <?php endif; ?>

                    <!-- Список забронированных поездок (для пассажиров) -->
                    <?php if ($role_id == 1 && !empty($bookings)): ?>
                        <h3 class="mt-5">Забронированные поездки</h3>
                        <div class="row row-cols-1 row-cols-md-2 g-4">
                            <?php foreach ($bookings as $booking): ?>
                                <div class="col">
                                    <div class="card shadow-sm h-100">
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($booking['Место_отправки']) ?> → <?= htmlspecialchars($booking['Место_назначения']) ?></h5>
                                            <p class="card-text"><strong>Дата:</strong> <?= htmlspecialchars($booking['Дата_поездки']) ?></p>
                                            <p class="card-text"><strong>Цена:</strong> <?= htmlspecialchars($booking['Цена_поездки']) ?> ₽</p>
                                            <p class="card-text"><strong>Статус:</strong>
                                                <?= ($booking['Статус'] == 0) ? 'Ожидает подтверждения' :
                                                    ($booking['Статус'] == 1 ? 'Принята' : 'Отклонена') ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($role_id == 1): ?>
                        <p class="text-center text-muted mt-3">У вас пока нет забронированных поездок.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'templates/footer.php'; ?>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Показываем/скрываем форму редактирования профиля
    document.getElementById('edit-profile-button').addEventListener('click', function () {
        const editForm = document.getElementById('edit-profile-form');
        const profileInfo = document.getElementById('profile-info');

        if (editForm.style.display === 'none') {
            editForm.style.display = 'block';
            profileInfo.style.display = 'none';
            this.textContent = 'Отменить редактирование';
        } else {
            editForm.style.display = 'none';
            profileInfo.style.display = 'block';
            this.textContent = 'Редактировать профиль';
        }
    });

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