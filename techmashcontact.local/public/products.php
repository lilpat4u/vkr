<?php
session_start();
include('connect.php');

// Получаем все товары с последней актуальной ценой по дате
$query = "
    SELECT p.id, p.name, p.description, p.unit, pl.price, pl.price_id
    FROM product p
    LEFT JOIN (
        SELECT pr1.product_id, pr1.price, pr1.id AS price_id
        FROM price_list pr1
        INNER JOIN (
            SELECT product_id, MAX(time) AS max_time
            FROM price_list
            WHERE time <= NOW()
            GROUP BY product_id
        ) pr2 ON pr1.product_id = pr2.product_id AND pr1.time = pr2.max_time
    ) pl ON p.id = pl.product_id
";

$result = $mysqli->query($query);
if (!$result) {
    die("Ошибка запроса: " . $mysqli->error);
}

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Продукция</title>
    <link rel="stylesheet" href="style/products.css">
</head>
<body>
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

<h2>Наша продукция</h2>
<div class="products">
    <?php foreach ($products as $index => $product): ?>
        <?php
        $imageName = strtolower(str_replace(' ', '_', $product['name'])) . '.jpg';
        ?>
        <div class="product">
            <img src="images/products/<?= $imageName ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            <h3><?= htmlspecialchars($product['name']) ?></h3>
            <p><?= htmlspecialchars($product['description']) ?></p>
            <p>
                <?php if ($product['price'] !== null): ?>
                    Цена: <?= number_format($product['price'], 2, ',', ' ') ?> руб. за <?= htmlspecialchars($product['unit']) ?>
                <?php else: ?>
                    Цена: не указана
                <?php endif; ?>
            </p>
            <form action="add_to_cart.php" method="post">
                 <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                 <input type="hidden" name="price_id" value="<?= $product['price_id'] ?? 0 ?>">
                 <button type="submit">В корзину</button>
            </form>

        </div>
        <?php if (($index + 1) % 3 === 0): ?>
            <div style="clear: both;"></div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
    <footer class="footer">
    <div class="footer__content">
        <p>Телефон для связи: +375-17-272-49-38 | Почтовый адрес: info@tmcontact.by | Юридический адрес: г.Минск, ул.Мележа, д.5, корп.2, оф.1504</p>
    </div>
</footer>
</body>
</html>
