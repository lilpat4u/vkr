<?php
session_start();
include('connect.php');

if (empty($_SESSION['client_id'])) {
    header("Location: login.php");
    exit;
}

$client_id = $_SESSION['client_id'];

// Получаем все заявки клиента
$stmt = $mysqli->prepare("
    SELECT a.id AS app_id, a.status, d.type, d.pickup_date, d.delivery_slot, d.town, d.street, d.number, d.apartment, b.id AS basket_id
    FROM application a
    JOIN delivery d ON a.deliveri_id = d.id
    JOIN basket b ON a.basket_id = b.id
    WHERE b.client_id = ?
    ORDER BY a.id DESC
");

if ($stmt === false) {
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
    <title>Мои заказы</title>
</head>
<body>
        <div class="main__content">
        <header class="header">
           <div class="header__list">
             <a href="index.php" class="header__link">Главная</a>
             <a href="products.php" class="header__link">Продукция</a>
             <a href="my_orders.php" class="header__link">Мои заказы</a>
             <?php if (!empty($_SESSION['client_login'])): ?>
             <a href="basket.php" class="header__link">Корзина</a>
              <?php endif; ?>
            </div>

            <div class="header__profile">
                <?php 
                if (empty($_SESSION['client_login'])) {
                    echo "<p>Вы вошли на сайт, как гость</p>
                          <form action='login.php'><button>Войти</button></form>
                          <form action='reg.php'><button>Зарегистрироваться</button></form>";
                } else {
                    echo "<p>Добро пожаловать, " . htmlspecialchars($_SESSION['client_name']) . "!</p>";
                    echo "<p>Вы успешно вошли в систему как " . htmlspecialchars($_SESSION['client_login']) . ".</p>";
                    echo "<a href='logout.php'>Выйти</a>";
                }
                ?>
            </div>
        </header>
        </div>
    <h1>Мои заказы</h1>

    <?php while ($app = $apps->fetch_assoc()): ?>
        <hr>
        <h2>Заказ #<?= $app['app_id'] ?> (Статус: <?= htmlspecialchars($app['status']) ?>)</h2>

        <?php if ($app['type'] === 'selfdriven'): ?>
            <p><strong>Самовывоз с:</strong> <?= date('d.m.Y', strtotime($app['pickup_date'])) ?> (с 8:00 до 18:00)</p>
        <?php else: ?>
            <p><strong>Доставка:</strong> <?= $app['town'] ?>, ул. <?= $app['street'] ?>, д. <?= $app['number'] ?>, кв. <?= $app['apartment'] ?></p>
            <?php
            // Получаем слот
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
        <p><strong>Итого по заявке:</strong> <?= number_format($total, 0, ',', ' ') ?> руб.</p>
    <?php endwhile; ?>
    <footer class="footer">
    <div class="footer__content">
        <p>Телефон для связи: +375-17-272-49-38 | Почтовый адрес: info@tmcontact.by | Юридический адрес: г.Минск, ул.Мележа, д.5, корп.2, оф.1504</p>
    </div>
</footer>
</body>
</html>
