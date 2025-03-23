<?php
session_start();
include 'includes/db.php';
include 'templates/header.php';

// Проверяем, что пользователь авторизован
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Получаем данные пользователя
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

    // Если роль "Водитель", получаем информацию из таблицы "Водители"
    if ($role_id == 2) {
        $stmt = $pdo->prepare("SELECT * FROM Водители WHERE ID_пользователя = :user_id");
        $stmt->execute(['user_id' => $user_id]);
        $driver = $stmt->fetch(PDO::FETCH_ASSOC);

        // Созданные поездки водителя
        $stmt = $pdo->prepare("SELECT * FROM Поездки WHERE ID_водителя = :driver_id ORDER BY Дата_поездки DESC");
        $stmt->execute(['driver_id' => $driver['ID_водителя']]);
        $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Если роль "Пассажир", получаем информацию из таблицы "Пассажиры"
    if ($role_id == 1) {
        $stmt = $pdo->prepare("SELECT * FROM Пассажиры WHERE ID_пользователя = :user_id");
        $stmt->execute(['user_id' => $user_id]);
        $passenger = $stmt->fetch(PDO::FETCH_ASSOC);

        // Забронированные поездки пассажира
        $stmt = $pdo->prepare("
            SELECT Поездки.* 
            FROM Бронирование 
            JOIN Поездки ON Бронирование.ID_поездки = Поездки.ID_поездки 
            WHERE Бронирование.ID_пассажира = :user_id
        ");
        $stmt->execute(['user_id' => $user_id]);
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

                    <!-- Форма редактирования профиля (скрыта по умолчанию) -->
                    <div id="edit-form" style="display: none;">
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

                    <!-- Кнопка редактирования -->
                    <button id="edit-button" class="btn btn-outline-primary w-100 mt-3">Редактировать профиль</button>

                    <!-- Модальное окно -->
                    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Подтверждение</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    Вы уверены, что хотите сохранить изменения?
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                    <button type="submit" class="btn btn-primary" id="confirmSave">Сохранить</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Информация о поездках -->
                    <?php if ($role_id == 2 && !empty($trips)): ?>
                        <h3 class="mt-5">Созданные поездки</h3>
                        <div class="row row-cols-1 row-cols-md-2 g-4">
                            <?php foreach ($trips as $trip): ?>
                                <div class="col">
                                    <div class="card shadow-sm h-100">
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($trip['Место_отправки']) ?> → <?= htmlspecialchars($trip['Место_назначения']) ?></h5>
                                            <p class="card-text"><strong>Дата:</strong> <?= htmlspecialchars($trip['Дата_поездки']) ?></p>
                                            <p class="card-text"><strong>Места:</strong> <?= htmlspecialchars($trip['Колличество_свободных_мест']) ?></p>
                                            <p class="card-text"><strong>Цена:</strong> <?= htmlspecialchars($trip['Цена_поездки']) ?> ₽</p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($role_id == 1 && !empty($bookings)): ?>
                        <h3 class="mt-5">Забронированные поездки</h3>
                        <div class="row row-cols-1 row-cols-md-2 g-4">
                            <?php foreach ($bookings as $booking): ?>
                                <div class="col">
                                    <div class="card shadow-sm h-100">
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($booking['Место_отправки']) ?> → <?= htmlspecialchars($booking['Место_назначения']) ?></h5>
                                            <p class="card-text"><strong>Дата:</strong> <?= htmlspecialchars($booking['Дата_поездки']) ?></p>
                                            <p class="card-text"><strong>Цена:</strong> <?= htmlspecialchars($booking['Цена_поездки']) ?> ₽</p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
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
    // Показываем/скрываем форму редактирования
    document.getElementById('edit-button').addEventListener('click', function () {
        const editForm = document.getElementById('edit-form');
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

    // Инициализация модального окна
    document.querySelector('.needs-validation').addEventListener('submit', function (event) {
        event.preventDefault(); // Предотвращаем отправку формы

        // Показываем модальное окно
        var modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        modal.show();

        // Обработка нажатия на кнопку "Сохранить" в модальном окне
        document.getElementById('confirmSave').addEventListener('click', function () {
            // Отправляем форму
            document.querySelector('.needs-validation').submit();
        });
    });
</script>