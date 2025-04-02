<?php
session_start();
include 'includes/db.php';
include 'templates/header.php';

$query = isset($_GET['query']) ? $_GET['query'] : '';

// SQL-запрос для поиска поездок
$sql = "SELECT * FROM поездки 
        WHERE (Место_отправки LIKE :query OR Место_назначения LIKE :query) 
        AND Количество_свободных_мест > 0 
        ORDER BY Дата_поездки ASC";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':query', "%$query%", PDO::PARAM_STR);
$stmt->execute();
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

    <main>
        <h2>Результаты поиска</h2>

        <!-- Список найденных поездок -->
        <?php if (empty($trips)) : ?>
            <p>По вашему запросу ничего не найдено.</p>
        <?php else : ?>
            <table border="1">
                <thead>
                <tr>
                    <th>ID_водителя</th>
                    <th>Место отправки</th>
                    <th>Место назначения</th>
                    <th>Дата поездки</th>
                    <th>Свободные места</th>
                    <th>Цена</th>
                    <th>Действия</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($trips as $trip) : ?>
                    <tr>
                        <td><?= htmlspecialchars($trip['ID_водителя']) ?></td>
                        <td><?= htmlspecialchars($trip['Место_отправки']) ?></td>
                        <td><?= htmlspecialchars($trip['Место_назначения']) ?></td>
                        <td><?= htmlspecialchars($trip['Дата_поездки']) ?></td>
                        <td><?= htmlspecialchars($trip['Количество_свободных_мест']) ?></td>
                        <td><?= htmlspecialchars($trip['Цена_поездки']) ?> ₽</td>
                        <td>
                            <a href="trip_details.php?id=<?= $trip['ID_поездки'] ?>">Посмотреть</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>

<?php include 'templates/footer.php'; ?>