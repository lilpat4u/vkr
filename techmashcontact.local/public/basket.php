<?php
session_start();
include('connect.php');

if (empty($_SESSION['client_id'])) {
    header("Location: login.php");
    exit;
}

$client_id = $_SESSION['client_id'];
$basket_id = $_SESSION['basket_id'] ?? null;

if (!$basket_id) {
    echo "<p>Корзина пуста.</p>";
    exit;
}

// Проверка, есть ли заявка по текущей корзине
$stmt = $mysqli->prepare("SELECT id FROM application WHERE basket_id = ?");
$stmt->bind_param("i", $basket_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo "<p>Корзина уже была использована для оформления заявки и не может быть изменена.</p>";
    exit;
}


$stmt->close();


// Обработка обновления количества
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['quantity_change'])) {
        $item_id = (int)$_POST['item_id'];
        $new_quantity = max(1, (int)$_POST['quantity']);

        $stmt = $mysqli->prepare("UPDATE basket_item SET quantity = ? WHERE id = ? AND basket_id = ?");
        $stmt->bind_param("iii", $new_quantity, $item_id, $basket_id);
        $stmt->execute();
        $stmt->close();
        exit;
    }

    if (isset($_POST['delete_item'])) {
        $item_id = (int)$_POST['item_id'];

        $stmt = $mysqli->prepare("DELETE FROM basket_item WHERE id = ? AND basket_id = ?");
        $stmt->bind_param("ii", $item_id, $basket_id);
        $stmt->execute();
        $stmt->close();
        header("Location: basket.php");
        exit;
    }
}

// Получаем содержимое корзины
$query = "
    SELECT 
        bi.id AS item_id,
        p.name,
        p.unit,
        pl.price,
        bi.quantity,
        (pl.price * bi.quantity) AS total
    FROM basket_item bi
    JOIN (
        SELECT pr1.id, pr1.product_id, pr1.price
        FROM price_list pr1
        INNER JOIN (
            SELECT product_id, MAX(time) AS max_time
            FROM price_list
            WHERE time <= NOW()
            GROUP BY product_id
        ) pr2 ON pr1.product_id = pr2.product_id AND pr1.time = pr2.max_time
    ) pl ON bi.price_id = pl.id
    JOIN product p ON pl.product_id = p.id
    WHERE bi.basket_id = ?
";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $basket_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
$totalSum = 0;

while ($row = $result->fetch_assoc()) {
    $items[] = $row;
    $totalSum += $row['total'];
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Корзина</title>
    <link rel="stylesheet" href="style/main.css">
    <script>
    function updateQuantity(input, itemId) {
        const quantity = input.value;

        fetch('basket.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                quantity: quantity,
                item_id: itemId,
                quantity_change: 1
            })
        }).then(() => {
            location.reload();
        });
    }
    </script>
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
                <h2>Ваша корзина</h2>
            </div>

            <?php if (empty($items)): ?>
                <p>Корзина пуста.</p>
            <?php else: ?>
                <div class="basket-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Наименование</th>
                                <th>Цена</th>
                                <th>Количество</th>
                                <th>Ед. изм.</th>
                                <th>Итого</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['name']) ?></td>
                                    <td><?= number_format($item['price'], 2, ',', ' ') ?> руб.</td>
                                    <td>
                                        <input type="number"
                                               value="<?= $item['quantity'] ?>"
                                               min="1"
                                               class="quantity-input"
                                               onchange="updateQuantity(this, <?= $item['item_id'] ?>)">
                                    </td>
                                    <td><?= htmlspecialchars($item['unit']) ?></td>
                                    <td><?= number_format($item['total'], 2, ',', ' ') ?> руб.</td>
                                    <td>
                                        <form method="post" class="delete-form">
                                            <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                                            <button type="submit" name="delete_item" class="delete-button" onclick="return confirm('Удалить этот товар из корзины?')">Удалить</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="basket-summary">
                    <h3>Общая сумма: <?= number_format($totalSum, 2, ',', ' ') ?> руб.</h3>
                    <?php if (!empty($items)): ?>
                        <form action="delivery_choice.php" method="get">
                            <button type="submit" class="continue-button">Продолжить оформление</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="continue-shopping">
                <a href="products.php" class="back-link">← Продолжить покупки</a>
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
