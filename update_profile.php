<?php
session_start();
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)$_POST['user_id'];
    $login = trim($_POST['login']);
    $password = trim($_POST['password']);
    $fio = isset($_POST['fio']) ? trim($_POST['fio']) : null;
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
    $experience = isset($_POST['experience']) ? (int)$_POST['experience'] : null;

    try {
        // Обновляем логин пользователя
        $stmt = $pdo->prepare("UPDATE Пользователи SET Логин = :login WHERE ID_пользователя = :user_id");
        $stmt->execute(['login' => $login, 'user_id' => $user_id]);

        // Если указан новый пароль, обновляем его
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE Пользователи SET Пароль = :password WHERE ID_пользователя = :user_id");
            $stmt->execute(['password' => $hashed_password, 'user_id' => $user_id]);
        }

        // Обновляем информацию о водителе или пассажире
        if ($_SESSION['role_id'] == 2) {
            $stmt = $pdo->prepare("UPDATE Водители SET ФИО = :fio, Номер_телефона = :phone, Стаж_вождения = :experience WHERE ID_пользователя = :user_id");
            $stmt->execute(['fio' => $fio, 'phone' => $phone, 'experience' => $experience, 'user_id' => $user_id]);
        } elseif ($_SESSION['role_id'] == 1) {
            $stmt = $pdo->prepare("UPDATE Пассажиры SET ФИО = :fio WHERE ID_пользователя = :user_id");
            $stmt->execute(['fio' => $fio, 'user_id' => $user_id]);
        }

        $_SESSION['success'] = 'Данные успешно обновлены!';
        header('Location: profile.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Ошибка обновления данных: ' . $e->getMessage();
        header('Location: profile.php');
        exit;
    }
}
?>