<?php
session_start();
include('connect.php');

// Обновление исполнителя и статуса, если POST-запрос
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id'], $_POST['worker_id'])) {
    $application_id = intval($_POST['application_id']);
    $worker_id = intval($_POST['worker_id']);

    // Проверим текущий статус
    $stmt = $mysqli->prepare("SELECT status FROM application WHERE id = ?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current = $result->fetch_assoc();
    $stmt->close();

    if ($current && $current['status'] === 'to_do') {
    $stmt = $mysqli->prepare("UPDATE application SET workersnumber = ?, status = 'in_process' WHERE id = ?");
    $stmt->bind_param("ii", $worker_id, $application_id);
    $stmt->execute();
    $stmt->close();
}

}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status_id'], $_POST['new_status'])) {
    $application_id = intval($_POST['change_status_id']);
    $new_status = $_POST['new_status'];
    $allowed_statuses = ['done', 'cancelled'];

    if (in_array($new_status, $allowed_statuses, true)) {
        // Проверим, что заявка принадлежит текущему менеджеру и сейчас в статусе in_process
        $stmt = $mysqli->prepare("SELECT workersnumber, status FROM application WHERE id = ?");
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $app_check = $result->fetch_assoc();
        $stmt->close();

        if ($app_check && $app_check['status'] === 'in_process' && $app_check['workersnumber'] == $_SESSION['worker_id']) {
            // Обновляем статус
            $stmt = $mysqli->prepare("UPDATE application SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $application_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}


// ВСТАВИТЬ в самый верх после session_start и connect.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_contract'])) {
    $application_id = intval($_POST['create_contract']);

    // Проверим, принадлежит ли заявка текущему менеджеру
    $stmt = $mysqli->prepare("SELECT workersnumber FROM application WHERE id = ?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row && $row['workersnumber'] == $_SESSION['worker_id']) {
        // Проверим, нет ли уже договора
        $stmt = $mysqli->prepare("SELECT COUNT(*) as cnt FROM contract WHERE application_id = ?");
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($res['cnt'] == 0) {
            // Вставка договора
            $stmt = $mysqli->prepare("INSERT INTO contract (application_id, create_date) VALUES (?, NOW())");
            $stmt->bind_param("i", $application_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}



// Получаем всех менеджеров отдела продаж
$managers = [];
$result = $mysqli->query("SELECT workersnumber, surname, name, patronymic FROM workers WHERE post = 'Менеджер' AND division = 'Отдел продаж'");
while ($row = $result->fetch_assoc()) {
    $managers[] = $row;
}

$where = [];
$params = [];
$types = '';

// Поиск по номеру заявки
if (!empty($_GET['app_id'])) {
    $where[] = 'a.id = ?';
    $params[] = intval($_GET['app_id']);
    $types .= 'i';
}

// Поиск по ФИО клиента
if (!empty($_GET['client_name'])) {
    $where[] = '(c.surname LIKE ? OR c.name LIKE ? OR c.patronymic LIKE ?)';
    $search = '%' . $mysqli->real_escape_string($_GET['client_name']) . '%';
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= 'sss';
}

// Поиск по менеджеру
if (!empty($_GET['manager_name'])) {
    $where[] = 'a.workersnumber IN (
        SELECT workersnumber FROM workers 
        WHERE surname LIKE ? OR name LIKE ? OR patronymic LIKE ?
    )';
    $search = '%' . $mysqli->real_escape_string($_GET['manager_name']) . '%';
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= 'sss';
}

$sql = "
    SELECT a.id AS app_id, a.status, a.workersnumber, d.type, d.pickup_date, d.delivery_slot, d.town, d.street, d.number, d.apartment,
           b.id AS basket_id, b.client_id,
           c.surname, c.name, c.patronymic
    FROM application a
    JOIN delivery d ON a.deliveri_id = d.id
    JOIN basket b ON a.basket_id = b.id
    JOIN clients c ON b.client_id = c.id
";

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY a.id DESC';

$stmt = $mysqli->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$apps = $stmt->get_result();
$stmt->close();

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Все заявки</title>
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
                <h1>Все заявки</h1>
            </div>

            <div class="search-form">
                <form method="GET">
                    <div class="form-group">
                        <label>Номер заявки:</label>
                        <input type="text" name="app_id" value="<?= isset($_GET['app_id']) ? htmlspecialchars($_GET['app_id']) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Клиент:</label>
                        <input type="text" name="client_name" value="<?= isset($_GET['client_name']) ? htmlspecialchars($_GET['client_name']) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Менеджер:</label>
                        <input type="text" name="manager_name" value="<?= isset($_GET['manager_name']) ? htmlspecialchars($_GET['manager_name']) : '' ?>">
                    </div>
                    <button type="submit" class="submit-button">Поиск</button>
                    <a href="?" class="back-link">Сбросить</a>
                </form>

                <form method="GET" action="report.php">
                    <button type="submit" class="submit-button">Создать отчет</button>
                </form>
            </div>

            <?php if ($apps->num_rows === 0): ?>
                <p class="no-results">Заявки не найдены.</p>
            <?php endif; ?>

            <div class="orders-list">
                <?php while ($app = $apps->fetch_assoc()): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <h2>Заказ #<?= $app['app_id'] ?></h2>
                            <span class="order-status"><?= htmlspecialchars($app['status']) ?></span>
                        </div>

                        <div class="order-details">
                            <p><strong>Клиент:</strong> <?= htmlspecialchars("{$app['surname']} {$app['name']} {$app['patronymic']}") ?></p>

                            <?php if ($app['type'] === 'selfdriven'): ?>
                                <p><strong>Самовывоз с:</strong> <?= date('d.m.Y', strtotime($app['pickup_date'])) ?> (с 8:00 до 18:00)</p>
                            <?php else: ?>
                                <p><strong>Доставка:</strong> <?= $app['town'] ?>, ул. <?= $app['street'] ?>, д. <?= $app['number'] ?>, кв. <?= $app['apartment'] ?></p>
                                <?php
                                $slot = null;
                                if (!empty($app['delivery_slot'])) {
                                    $stmt = $mysqli->prepare("SELECT start_time, end_time FROM delivery_slots WHERE id = ?");
                                    $stmt->bind_param("i", $app['delivery_slot']);
                                    $stmt->execute();
                                    $slot = $stmt->get_result()->fetch_assoc();
                                    $stmt->close();
                                }
                                ?>
                                <?php if ($slot): ?>
                                    <p><strong>Время доставки:</strong> <?= date('d.m.Y H:i', strtotime($slot['start_time'])) ?> – <?= date('H:i', strtotime($slot['end_time'])) ?></p>
                                <?php endif; ?>
                            <?php endif; ?>

                            <div class="order-items">
                                <h3>Состав заказа:</h3>
                                <ul>
                                    <?php
                                    $total = 0;
                                    $stmt = $mysqli->prepare("
                                        SELECT p.name AS product_name, pl.price, bi.quantity
                                        FROM basket_item bi
                                        JOIN price_list pl ON bi.price_id = pl.id
                                        JOIN product p ON pl.product_id = p.id
                                        WHERE bi.basket_id = ?
                                    ");
                                    $stmt->bind_param("i", $app['basket_id']);
                                    $stmt->execute();
                                    $products = $stmt->get_result();
                                    $stmt->close();

                                    while ($product = $products->fetch_assoc()):
                                        $line = $product['price'] * $product['quantity'];
                                        $total += $line;
                                    ?>
                                        <li><?= htmlspecialchars($product['product_name']) ?> — <?= $product['quantity'] ?> × <?= $product['price'] ?> = <?= number_format($line, 0, ',', ' ') ?> руб.</li>
                                    <?php endwhile; ?>
                                </ul>
                                <p class="order-total"><strong>Итого:</strong> <?= number_format($total, 0, ',', ' ') ?> руб.</p>
                            </div>

                            <?php
                            $is_manager_for_app = ($app['workersnumber'] == $_SESSION['worker_id']);
                            $stmt = $mysqli->prepare("SELECT id FROM contract WHERE application_id = ?");
                            $stmt->bind_param("i", $app['app_id']);
                            $stmt->execute();
                            $contract_exists = $stmt->get_result()->num_rows > 0;
                            $stmt->close();

                            if ($is_manager_for_app && !$contract_exists): ?>
                                <form method="POST" class="contract-form">
                                    <input type="hidden" name="create_contract" value="<?= $app['app_id'] ?>">
                                    <button type="submit" class="submit-button">Создать договор</button>
                                </form>
                            <?php elseif ($contract_exists): ?>
                                <p class="contract-info">Договор создан</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
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