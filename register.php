<?php
session_start();
include 'includes/db.php';

// Переменные для ошибок и успеха
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login']);
    $password = trim($_POST['password']);
    $role = (int)$_POST['role'];
    $fio = trim($_POST['fio']);
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;

    // Валидация данных
    if (empty($login)) {
        $error = 'Логин не может быть пустым.';
    } elseif (empty($password)) {
        $error = 'Пароль не может быть пустым.';
    } elseif ($role !== 2 && $role !== 3) {
        $error = 'Неверная роль.';
    } elseif (empty($fio)) {
        $error = 'ФИО не может быть пустым.';
    } else {
        try {
            // Добавляем пользователя в таблицу "Пользователи"
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO Пользователи (ID_роли, Логин, Пароль) VALUES (:role, :login, :password)");
            $stmt->execute(['role' => $role, 'login' => $login, 'password' => $hashed_password]);
            $user_id = $pdo->lastInsertId();

            // Если роль "Водитель", добавляем данные в таблицу "Водители"
            if ($role == 3) {
                $stmt = $pdo->prepare("INSERT INTO Водители (ID_пользователя, ФИО, Номер_телефона) VALUES (:user_id, :fio, :phone)");
                $stmt->execute(['user_id' => $user_id, 'fio' => $fio, 'phone' => $phone]);
            }
            // Если роль "Пассажир", добавляем данные в таблицу "Пассажиры"
            elseif ($role == 2) {
                $stmt = $pdo->prepare("INSERT INTO Пассажиры (ID_пользователя, ФИО) VALUES (:user_id, :fio)");
                $stmt->execute(['user_id' => $user_id, 'fio' => $fio]);
            }

            $success = 'Вы успешно зарегистрировались!';
        } catch (PDOException $e) {
            $error = 'Ошибка регистрации: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<header>
    <h1>Поиск попутчиков</h1>
</header>

<main>
    <div class="register-form">
        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p class="success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <h2>Регистрация</h2>
        <form method="POST">
            <label for="login">Логин:</label>
            <input type="text" name="login" id="login" required value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">

            <label for="password">Пароль:</label>
            <input type="password" name="password" id="password" required>

            <label for="role">Роль:</label>
            <select name="role" id="role" required>
                <option value="2">Пассажир</option>
                <option value="3">Водитель</option>
            </select>

            <label for="fio">ФИО:</label>
            <input type="text" name="fio" id="fio" required value="<?= htmlspecialchars($_POST['fio'] ?? '') ?>">

            <label for="phone">Телефон (только для водителей):</label>
            <input type="text" name="phone" id="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">

            <button type="submit">Зарегистрироваться</button>
        </form>

        <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
    </div>
</main>

<footer>
    <p>&copy; 2025 CarPooler</p>
</footer>
</body>
</html>