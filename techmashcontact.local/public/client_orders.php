<?php
session_start();
include('connect.php');

// Получение client_id из GET-параметра
if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Неверный или отсутствующий идентификатор клиента.");
}

$client_id = (int)$_GET['id'];

// Получаем все заявки клиента
$stmt = $mysqli->prepare("
    SELECT a.id AS app_id, a.status, d.type, d.pickup_date, d.delivery_slot, d.town, d.street, d.number, d.apartment, b.id AS basket_id
    FROM application a
    JOIN delivery d ON a.deliveri_id = d.id
    JOIN basket b ON a.basket_id = b.id
    WHERE b.client_id = ?
    ORDER BY a.id DESC
");

if (!$stmt) {
    die('Ошибка подготовки запроса: ' . $mysqli->error);
}

$stmt->bind_param("i", $client_id);
$stmt->execute();
$apps = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заявки клиента</title>
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
                <h1>Заявки клиента #<?= $client_id ?></h1>
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
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="continue-shopping">
                <a href="clients.php" class="back-link">Вернуться к списку клиентов</a>
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
