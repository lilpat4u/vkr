<?php
session_start();
include('connect.php');

if (empty($_SESSION['client_id']) || empty($_SESSION['delivery_id']) || empty($_SESSION['basket_id'])) {
    header("Location: login.php");
    exit;
}

$delivery_id = $_SESSION['delivery_id'];
$client_id = $_SESSION['client_id'];
$basket_id = $_SESSION['basket_id'];

// Получаем данные о доставке
$stmt = $mysqli->prepare("SELECT * FROM delivery WHERE id = ?");
$stmt->bind_param("i", $delivery_id);
$stmt->execute();
$delivery = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Получаем данные о слоте (если доставка)
$slot = null;
if ($delivery['type'] === 'delivery' && !empty($delivery['delivery_slot'])) {
    $stmt = $mysqli->prepare("SELECT * FROM delivery_slots WHERE id = ?");
    $stmt->bind_param("i", $delivery['delivery_slot']);
    $stmt->execute();
    $slot = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Получаем товары из корзины через basket_item → price_list → product
$stmt = $mysqli->prepare("
    SELECT p.name AS product_name, pl.price, bi.quantity
    FROM basket_item bi
    JOIN price_list pl ON bi.price_id = pl.id
    JOIN product p ON pl.product_id = p.id
    WHERE bi.basket_id = ?
");
$stmt->bind_param("i", $basket_id);
$stmt->execute();
$products = $stmt->get_result();
$stmt->close();

// Получаем статус заявки
$stmt = $mysqli->prepare("SELECT status FROM application WHERE basket_id = ? AND deliveri_id = ?");
$stmt->bind_param("ii", $basket_id, $delivery_id);
$stmt->execute();
$app = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Подтверждение заказа</title>
    <link rel="stylesheet" href="style/main.css">
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

        <main>
            <div class="main__label">
                <h1>Ваш заказ подтверждён!</h1>
            </div>

            <div class="order-card">
                <div class="order-header">
                    <h2>Детали доставки</h2>
                    <span class="order-status"><?= htmlspecialchars($app['status']) ?></span>
                </div>

                <div class="order-details">
                    <p><strong>Тип:</strong> <?= $delivery['type'] === 'selfdriven' ? 'Самовывоз' : 'Доставка' ?></p>

                    <?php if ($delivery['type'] === 'delivery'): ?>
                        <p><strong>Адрес:</strong> <?= $delivery['town'] ?>, ул. <?= $delivery['street'] ?>, д. <?= $delivery['number'] ?>, кв. <?= $delivery['apartment'] ?></p>
                        <?php if ($slot): ?>
                            <p><strong>Время доставки:</strong>
                                <?= date('d.m.Y H:i', strtotime($slot['start_time'])) ?> – <?= date('H:i', strtotime($slot['end_time'])) ?>
                            </p>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (!empty($delivery['pickup_date'])): ?>
                            <p><strong>Дата самовывоза:</strong> <?= date('d.m.Y', strtotime($delivery['pickup_date'])) ?> (с 8:00 до 18:00)</p>
                        <?php else: ?>
                            <p><strong>Дата самовывоза:</strong> не указана</p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="order-items">
                        <h3>Состав заказа:</h3>
                        <ul>
                            <?php
                            $total = 0;
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

            <div class="continue-shopping">
                <a href="products.php" class="back-link">Вернуться к покупкам</a>
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
