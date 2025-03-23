<?php
if (!isset($_SESSION)) {
    session_start();
}
include 'includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login']);
    $password = trim($_POST['password']);

    try {
        // Ищем пользователя по логину
        $stmt = $pdo->prepare("SELECT * FROM Пользователи WHERE Логин = :login");
        $stmt->execute(['login' => $login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['Пароль'])) {
            // Сохраняем данные пользователя в сессию
            $_SESSION['user_id'] = $user['ID_пользователя'];
            $_SESSION['role_id'] = $user['ID_роли'];

            // Перенаправляем на главную страницу
            header('Location: index.php');
            exit;
        } else {
            $error = 'Неверный логин или пароль.';
        }
    } catch (PDOException $e) {
        $error = 'Ошибка входа: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<header class="bg-primary text-white py-3">
    <div class="container text-center">
        <h1>CarPooler</h1>
    </div>
</header>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm p-4">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <h2 class="text-center mb-4">Вход</h2>
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="login" class="form-label">Логин:</label>
                        <input type="text" name="login" id="login" class="form-control" required>
                        <div class="invalid-feedback">Логин обязателен.</div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Пароль:</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                        <div class="invalid-feedback">Пароль обязателен.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Войти</button>
                    <p class="text-center mt-3">Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
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