<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarPooler</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<header>
    <h1>CarPooler</h1>
    <nav>
        <a href="index.php">Главная</a>
        <?php if (!isset($_SESSION['user_id'])) : ?>
            <a href="login.php">Вход</a>
            <a href="register.php">Регистрация</a>
        <?php else : ?>
            <a href="profile.php">Профиль</a>
            <?php if ($_SESSION['role_id'] == 3) : ?>
                <a href="create_trip.php">Создать поездку</a>
            <?php endif; ?>
            <a href="logout.php">Выход</a>
        <?php endif; ?>
    </nav>
</header>