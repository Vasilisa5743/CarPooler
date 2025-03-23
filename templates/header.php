<?php
if (!isset($_SESSION)) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поиск попутчиков</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Локальный CSS (минимум) -->
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<header class="bg-primary text-white py-3">
    <div class="container">
        <h1 class="text-center">CarPooler</h1>
        <nav class="navbar navbar-expand-lg navbar-light bg-light rounded">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">Главная</a>
                <?php if (!isset($_SESSION['user_id'])) : ?>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="login.php">Вход</a></li>
                        <li class="nav-item"><a class="nav-link" href="register.php">Регистрация</a></li>
                    </ul>
                <?php else : ?>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="profile.php">Профиль</a></li>
                        <?php if ($_SESSION['role_id'] == 2) : ?>
                            <li class="nav-item"><a class="nav-link" href="create_trip.php">Создать поездку</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Выход</a></li>
                    </ul>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>