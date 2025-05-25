<?php
session_start();
include('connect.php');

// Обработка поискового запроса
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$clients = [];

if ($search !== '') {
    $like = "%" . $mysqli->real_escape_string($search) . "%";
    $stmt = $mysqli->prepare("
        SELECT id, surname, name, patronymic, phone, passport_number, email
        FROM clients
        WHERE surname LIKE ? OR name LIKE ? OR patronymic LIKE ?
           OR phone LIKE ? OR passport_number LIKE ? OR email LIKE ?
    ");
    $stmt->bind_param('ssssss', $like, $like, $like, $like, $like, $like);
} else {
    $stmt = $mysqli->prepare("
        SELECT id, surname, name, patronymic, phone, passport_number, email
        FROM clients
    ");
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $clients[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Список клиентов</title>
    <link rel="stylesheet" href="style/main.css">
</head>
<body>
    <div class="main__content">
        <header class="header">
            <div class="header__list">
                <a href="worker_dashboard.php" class="header__link">Заявки</a>
                <a href="clients.php" class="header__link">Клиенты</a>
                <a href="task.php" class="header__link">Задачи</a>
                <a href="shipments.php" class="header__link">Отгрузки</a>
            </div>

            <div class="header__profile">
                <p>Добро пожаловать, <?php echo htmlspecialchars($_SESSION['worker_surname'] . ' ' . $_SESSION['worker_name']); ?>!</p>
                <p>Вы вошли как сотрудник: <?php echo htmlspecialchars($_SESSION['worker_login']); ?></p>
                <a href="logout.php" class="header__link">Выйти</a>
            </div>
        </header>

        <main>
            <div class="main__label">
                <h1>Список клиентов</h1>
            </div>

            <div class="search-form">
                <form method="get">
                    <div class="form-group">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Поиск по ФИО, телефону, паспорту, email...">
                        <button type="submit" class="submit-button">Найти</button>
                    </div>
                </form>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Фамилия</th>
                            <th>Имя</th>
                            <th>Отчество</th>
                            <th>Телефон</th>
                            <th>Паспорт</th>
                            <th>Email</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clients)): ?>
                            <tr><td colspan="7" class="no-results">Клиенты не найдены</td></tr>
                        <?php else: ?>
                            <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td><?= htmlspecialchars($client['surname']) ?></td>
                                    <td><?= htmlspecialchars($client['name']) ?></td>
                                    <td><?= htmlspecialchars($client['patronymic']) ?></td>
                                    <td><?= htmlspecialchars($client['phone']) ?></td>
                                    <td><?= htmlspecialchars($client['passport_number']) ?></td>
                                    <td><?= htmlspecialchars($client['email']) ?></td>
                                    <td>
                                        <a href="client_orders.php?id=<?= urlencode($client['id']) ?>" class="action-button">Заявки</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <footer class="footer">
        <div class="footer__content">
            <p>Телефон для связи: +375-17-272-49-38 | Почтовый адрес: info@tmcontact.by | Юридический адрес: г.Минск, ул.Мележа, д.5, корп.2, оф.1504</p>
        </div>
    </footer>
</body>
</html>
