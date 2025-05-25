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
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        input[type="text"] { padding: 5px; width: 300px; }
        .btn {
            padding: 4px 8px;
            background-color: #007BFF;
            color: white;
            border: none;
            text-decoration: none;
            border-radius: 4px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
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
             </header>
     </div> 
    <h1>Список клиентов</h1>

    <form method="get">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Поиск по ФИО, телефону, паспорту, email...">
        <button type="submit">Найти</button>
    </form>

    <table>
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
                <tr><td colspan="7">Клиенты не найдены</td></tr>
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
                            <a class="btn" href="client_orders.php?id=<?= urlencode($client['id']) ?>">Заявки</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
