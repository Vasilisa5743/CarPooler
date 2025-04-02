<?php
session_start();
include 'includes/db.php';
include 'templates/header.php';

// Проверяем, что указан ID поездки
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$trip_id = (int)$_GET['id'];

try {
    // Получаем информацию о поездке
    $stmt = $pdo->prepare("SELECT * FROM Поездки WHERE ID_поездки = :trip_id");
    $stmt->execute(['trip_id' => $trip_id]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trip) {
        throw new Exception('Поездка не найдена.');
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'Ошибка загрузки деталей поездки: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}
?>

    <main class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <!-- Сообщения об успехе/ошибках -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success text-center mt-3"><?= htmlspecialchars($_SESSION['success']) ?></div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger text-center mt-3"><?= htmlspecialchars($_SESSION['error']) ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Карточка с деталями поездки -->
                <div class="card shadow-sm p-4">
                    <h2 class="text-center mb-4">Детали поездки</h2>
                    <p><strong>Маршрут:</strong> <?= htmlspecialchars($trip['Место_отправки']) ?> → <?= htmlspecialchars($trip['Место_назначения']) ?></p>
                    <p><strong>Дата:</strong> <?= htmlspecialchars($trip['Дата_поездки']) ?></p>
                    <p><strong>Свободные места:</strong> <?= htmlspecialchars($trip['Количество_свободных_мест']) ?></p>
                    <p><strong>Цена:</strong> <?= htmlspecialchars($trip['Цена_поездки']) ?> ₽</p>

                    <!-- Кнопка бронирования (только для пассажиров) -->
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['role_id'] == 1 && $trip['Количество_свободных_мест'] > 0): ?>
                        <button type="button" class="btn btn-success book-trip-btn w-100 mt-3" data-id="<?= htmlspecialchars($trip['ID_поездки']) ?>">Забронировать место</button>
                    <?php elseif ($trip['Количество_свободных_мест'] <= 0): ?>
                        <p class="text-center text-muted mt-3">Нет свободных мест.</p>
                    <?php else: ?>
                        <p class="text-center text-muted mt-3">Войдите как пассажир, чтобы забронировать место.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Модальное окно для подтверждения бронирования -->
        <div class="modal fade" id="bookModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Подтверждение бронирования</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Вы уверены, что хотите забронировать место в этой поездке?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <a href="#" id="confirmBookingLink" class="btn btn-success">Подтвердить</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Подключение Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Инициализация модального окна для бронирования
        document.querySelectorAll('.book-trip-btn').forEach(button => {
            button.addEventListener('click', function () {
                const tripId = this.dataset.id;

                // Настройка ссылки для подтверждения бронирования
                const confirmBookingLink = document.getElementById('confirmBookingLink');
                confirmBookingLink.href = 'book_trip.php?id=' + tripId;

                // Открываем модальное окно
                const modal = new bootstrap.Modal(document.getElementById('bookModal'));
                modal.show();
            });
        });
    </script>

<?php include 'templates/footer.php'; ?>