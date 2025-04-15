<?php
session_start();
include 'includes/db.php';
include 'templates/header.php';

// Проверяем, что указан ID поездки
if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'ID поездки не указан.';
    header('Location: index.php');
    exit;
}

$trip_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'] ?? null;

try {
    // Получаем информацию о поездке
    $stmt = $pdo->prepare("SELECT * FROM Поездки WHERE ID_поездки = :trip_id");
    $stmt->execute(['trip_id' => $trip_id]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trip) {
        throw new Exception('Поездка не найдена.');
    }

    // Проверяем, является ли пользователь участником поездки
    $is_participant = false;

    if ($user_id) {
        // Для водителя показываем чат без проверки статуса
        if ($_SESSION['role_id'] == 2 && $trip['ID_водителя'] == $user_id) {
            $is_participant = true;
        }

        // Для пассажира проверяем статус бронирования
        if ($_SESSION['role_id'] == 1) {
            $stmt = $pdo->prepare("
                SELECT * 
                FROM Бронирование 
                WHERE ID_поездки = :trip_id AND ID_пассажира = (
                    SELECT ID_пассажира 
                    FROM Пассажиры 
                    WHERE ID_пользователя = :user_id
                ) AND Статус = 1
            ");
            $stmt->execute(['trip_id' => $trip_id, 'user_id' => $user_id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($booking) {
                $is_participant = true;
            }
        }
    }

    // Если пользователь участник или водитель, загружаем комментарии
    if ($is_participant || ($_SESSION['role_id'] ?? 0) == 2) {
        $stmt = $pdo->prepare("
            SELECT Комментарий_к_поездке.*, Пользователи.Логин AS автор 
            FROM Комментарий_к_поездке 
            JOIN Пользователи ON Комментарий_к_поездке.ID_пользователя = Пользователи.ID_пользователя 
            WHERE Комментарий_к_поездке.ID_поездки = :trip_id 
            ORDER BY Дата_комментария ASC
        ");
        $stmt->execute(['trip_id' => $trip_id]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $comments = [];
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'Ошибка загрузки данных: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}
?>

    <main class="container my-5">
        <div class="row justify-content-center">
            <!-- Чат (слева, только для участников поездки или водителя) -->
            <?php if ($is_participant || ($_SESSION['role_id'] ?? 0) == 2): ?>
                <div class="col-md-6 col-lg-5 order-lg-1">
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-info text-white text-center">
                            <h3>Чат поездки</h3>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                            <?php if (!empty($comments)): ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="mb-3">
                                        <strong><?= htmlspecialchars($comment['автор']) ?>:</strong>
                                        <?= nl2br(htmlspecialchars($comment['Сообщение'])) ?><br>
                                        <small class="text-muted">Отправлено: <?= htmlspecialchars($comment['Дата_комментария']) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-center text-muted">Пока нет сообщений.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Форма отправки сообщения -->
                    <form method="POST" action="add_comment.php?id=<?= htmlspecialchars($trip_id) ?>" class="needs-validation" novalidate>
                        <input type="hidden" name="trip_id" value="<?= htmlspecialchars($trip_id) ?>">
                        <div class="mb-3">
                            <label for="comment-text" class="form-label">Напишите сообщение:</label>
                            <textarea name="comment_text" id="comment-text" class="form-control" rows="3" placeholder="Введите сообщение..." required></textarea>
                            <div class="invalid-feedback">Текст сообщения обязателен.</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Отправить</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="col-md-6 col-lg-5 order-lg-1">
                    <p class="text-center text-muted">Чат доступен только после одобрения бронирования.</p>
                </div>
            <?php endif; ?>

            <!-- Детали поездки (справа) -->
            <div class="col-md-6 col-lg-5 order-lg-2">
                <div class="card shadow-sm p-4">
                    <h2 class="text-center mb-4">Детали поездки</h2>
                    <p><strong>Маршрут:</strong> <?= htmlspecialchars($trip['Место_отправки'] ?? 'Не указано') ?> → <?= htmlspecialchars($trip['Место_назначения'] ?? 'Не указано') ?></p>
                    <p><strong>Дата:</strong> <?= htmlspecialchars($trip['Дата_поездки'] ?? 'Не указана') ?></p>
                    <p><strong>Свободные места:</strong> <?= htmlspecialchars($trip['Количество_свободных_мест'] ?? 'Не указано') ?></p>
                    <p><strong>Цена:</strong> <?= htmlspecialchars($trip['Цена_поездки'] ?? 'Не указана') ?> ₽</p>

                    <!-- Логика для кнопки бронирования -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role_id'] == 1): ?>
                            <?php
                            // Проверяем, была ли уже заявка на бронирование
                            $stmt = $pdo->prepare("
                            SELECT COUNT(*) 
                            FROM Бронирование 
                            WHERE ID_поездки = :trip_id AND ID_пассажира = (
                                SELECT ID_пассажира 
                                FROM Пассажиры 
                                WHERE ID_пользователя = :user_id
                            )
                        ");
                            $stmt->execute(['trip_id' => $trip_id, 'user_id' => $_SESSION['user_id']]);
                            $has_booking = $stmt->fetchColumn() > 0;
                            ?>

                            <?php if (!$has_booking && $trip['Количество_свободных_мест'] > 0): ?>
                                <button type="button" class="btn btn-success book-trip-btn w-100 mt-3" data-id="<?= htmlspecialchars($trip['ID_поездки'] ?? '') ?>">Забронировать место</button>
                            <?php elseif ($has_booking): ?>
                                <p class="text-center text-muted mt-3">Вы уже отправили заявку на эту поездку.</p>
                            <?php elseif ($trip['Количество_свободных_мест'] <= 0): ?>
                                <p class="text-center text-muted mt-3">Нет свободных мест.</p>
                            <?php else: ?>
                                <p class="text-center text-muted mt-3">Войдите как пассажир, чтобы забронировать место.</p>
                            <?php endif; ?>
                        <?php elseif ($_SESSION['role_id'] == 3 && $trip['ID_водителя'] == $_SESSION['user_id']): ?>
                            <p class="text-center text-muted mt-3">Это ваша поездка.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Модальное окно для бронирования -->
    <div class="modal fade" id="bookModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Бронирование места</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="book_trip.php?id=<?= htmlspecialchars($trip_id) ?>" class="needs-validation" novalidate>
                        <input type="hidden" name="trip_id" value="<?= htmlspecialchars($trip_id) ?>">
                        <div class="mb-3">
                            <label for="waiting-place" class="form-label">Где вы будете ждать машину:</label>
                            <input type="text" name="waiting_place" id="waiting-place" class="form-control" placeholder="Например, у подъезда или на вокзале" required>
                            <div class="invalid-feedback">Укажите место ожидания.</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                            <button type="submit" class="btn btn-success">Отправить заявку</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Подключение Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Инициализация модального окна для бронирования
        document.querySelectorAll('.book-trip-btn').forEach(button => {
            button.addEventListener('click', function () {
                const modal = new bootstrap.Modal(document.getElementById('bookModal'));
                modal.show();
            });
        });

        // Валидация форм Bootstrap
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

<?php include 'templates/footer.php'; ?>