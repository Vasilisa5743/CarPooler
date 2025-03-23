<?php
if (!isset($_SESSION)) {
    session_start();
}
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login']);
    $password = trim($_POST['password']);
    $role = (int)$_POST['role'];
    $fio = trim($_POST['fio']);
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;

    try {
        // Проверяем, что логин уникален
        $stmt = $pdo->prepare("SELECT * FROM Пользователи WHERE Логин = :login");
        $stmt->execute(['login' => $login]);
        if ($stmt->rowCount() > 0) {
            $error = 'Логин уже занят.';
        } else {
            // Добавляем пользователя в таблицу "Пользователи"
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO Пользователи (ID_роли, Логин, Пароль) VALUES (:role, :login, :password)");
            $stmt->execute(['role' => $role, 'login' => $login, 'password' => $hashed_password]);
            $user_id = $pdo->lastInsertId();

            // Если роль "Водитель", добавляем данные в таблицу "Водители"
            if ($role == 2) {
                $stmt = $pdo->prepare("INSERT INTO Водители (ID_пользователя, ФИО, Номер_телефона) VALUES (:user_id, :fio, :phone)");
                $stmt->execute(['user_id' => $user_id, 'fio' => $fio, 'phone' => $phone]);
            }
            // Если роль "Пассажир", добавляем данные в таблицу "Пассажиры"
            elseif ($role == 1) {
                $stmt = $pdo->prepare("INSERT INTO Пассажиры (ID_пользователя, ФИО) VALUES (:user_id, :fio)");
                $stmt->execute(['user_id' => $user_id, 'fio' => $fio]);
            }

            // Перенаправляем на страницу входа
            header('Location: login.php?success=registered');
            exit;
        }
    } catch (PDOException $e) {
        $error = 'Ошибка регистрации: ' . $e->getMessage();
    }
}

// Если пользователь пришёл со страницы входа с параметром success
$success = isset($_GET['success']) && $_GET['success'] === 'registered' ? 'Вы успешно зарегистрировались!' : '';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
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
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <h2 class="text-center mb-4">Регистрация</h2>
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

                    <div class="mb-3">
                        <label for="role" class="form-label">Роль:</label>
                        <select name="role" id="role" class="form-select" required>
                            <option value="1">Пассажир</option>
                            <option value="2">Водитель</option>
                        </select>
                        <div class="invalid-feedback">Выберите роль.</div>
                    </div>

                    <div class="mb-3">
                        <label for="fio" class="form-label">ФИО:</label>
                        <input type="text" name="fio" id="fio" class="form-control" required>
                        <div class="invalid-feedback">ФИО обязательно.</div>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Телефон (только для водителей):</label>
                        <input type="text" name="phone" id="phone" class="form-control">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Зарегистрироваться</button>
                    <p class="text-center mt-3">Уже есть аккаунт? <a href="login.php">Войти</a></p>
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