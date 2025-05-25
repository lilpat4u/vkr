<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['worker_id'])) {
    header('Location: login.php');
    exit();
}

// Получаем список сотрудников отдела продаж
$workers = [];
$result = $mysqli->query("SELECT workersnumber, surname, name, patronymic FROM workers WHERE division = 'Отдел продаж'");
while ($row = $result->fetch_assoc()) {
    $row['full_name'] = trim($row['surname'] . ' ' . $row['name'] . ' ' . $row['patronymic']);
    $workers[] = $row;
}

// Список статусов
$statuses = ['Новая', 'В работе', 'Завершена', 'Отменена']; // подставь реальные статусы из БД, если нужно

// Фильтры
$selected_worker = $_GET['worker'] ?? '';
$client_query     = $_GET['client'] ?? '';
$product_query    = $_GET['product'] ?? '';
$date_from        = $_GET['date_from'] ?? '';
$date_to          = $_GET['date_to'] ?? '';
$status_query     = $_GET['status'] ?? '';

// SQL-запрос
$sql = "
    SELECT 
        a.id AS application_id,
        CONCAT_WS(' ', cl.surname, cl.name, cl.patronymic) AS client_name,
        cl.phone AS client_phone,
        a.status,
        c.create_date AS date,
        p.name AS product,
        CONCAT(w.surname, ' ', w.name, ' ', w.patronymic) AS manager_name
    FROM application a
    LEFT JOIN basket b ON a.basket_id = b.id
    LEFT JOIN clients cl ON b.client_id = cl.id
    LEFT JOIN contract c ON c.application_id = a.id
    LEFT JOIN basket_item bi ON bi.basket_id = b.id
    LEFT JOIN price_list pl ON pl.id = bi.price_id
    LEFT JOIN product p ON p.id = pl.product_id
    LEFT JOIN workers w ON a.workersnumber = w.workersnumber
    WHERE 1
";
// Получаем список товаров
$products = [];
$product_result = $mysqli->query("SELECT id, name FROM product ORDER BY name");
while ($row = $product_result->fetch_assoc()) {
    $products[] = $row;
}

$params = [];
$types = '';

if ($selected_worker !== '') {
    $sql .= " AND a.workersnumber = ?";
    $types .= 'i';
    $params[] = $selected_worker;
}
if ($client_query !== '') {
    $sql .= " AND CONCAT_WS(' ', cl.surname, cl.name, cl.patronymic) LIKE ?";
    $types .= 's';
    $params[] = '%' . $client_query . '%';
}
if ($product_query !== '') {
$sql .= " AND p.name = ?";
$types .= 's';
$params[] = $product_query;
}
if ($date_from !== '') {
    $sql .= " AND c.create_date >= ?";
    $types .= 's';
    $params[] = $date_from;
}
if ($date_to !== '') {
    $sql .= " AND c.create_date <= ?";
    $types .= 's';
    $params[] = $date_to;
}
if ($status_query !== '') {
    $sql .= " AND a.status = ?";
    $types .= 's';
    $params[] = $status_query;
}

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    die("Ошибка подготовки запроса: " . $mysqli->error . "\nSQL: " . $sql);
}

if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Создать отчет</title>
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
                <h1>Создать отчет</h1>
            </div>

            <div class="search-form">
                <form method="GET" action="report.php">
                    <div class="form-group">
                        <label>Сотрудник:</label>
                        <select name="worker">
                            <option value="">-- Все --</option>
                            <?php foreach ($workers as $w): ?>
                                <option value="<?= $w['workersnumber'] ?>" <?= $w['workersnumber'] == $selected_worker ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($w['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Клиент:</label>
                        <input type="text" name="client" value="<?= htmlspecialchars($client_query) ?>" placeholder="Иванов">
                    </div>

                    <div class="form-group">
                        <label>Товар:</label>
                        <select name="product">
                            <option value="">-- Все --</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= htmlspecialchars($product['name']) ?>" <?= $product['name'] == $product_query ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($product['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Статус:</label>
                        <select name="status">
                            <option value="">-- Все --</option>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= htmlspecialchars($status) ?>" <?= $status == $status_query ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($status) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Дата с:</label>
                        <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                    </div>

                    <div class="form-group">
                        <label>Дата по:</label>
                        <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                    </div>

                    <button type="submit" class="submit-button">Применить фильтры</button>
                </form>

                <form method="GET" action="download_report.php">
                    <input type="hidden" name="worker" value="<?= htmlspecialchars($selected_worker) ?>">
                    <input type="hidden" name="client" value="<?= htmlspecialchars($client_query) ?>">
                    <input type="hidden" name="product" value="<?= htmlspecialchars($product_query) ?>">
                    <button type="submit" class="submit-button">Скачать Excel</button>
                </form>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Клиент</th>
                            <th>Статус</th>
                            <th>Товар</th>
                            <th>Дата договора</th>
                            <th>Менеджер</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['application_id'] ?></td>
                                <td><?= htmlspecialchars($row['client_name']) ?></td>
                                <td><?= htmlspecialchars($row['status']) ?></td>
                                <td><?= htmlspecialchars($row['product']) ?></td>
                                <td><?= htmlspecialchars($row['date']) ?></td>
                                <td><?= htmlspecialchars($row['manager_name']) ?></td>
                            </tr>
                        <?php endwhile; ?>
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
