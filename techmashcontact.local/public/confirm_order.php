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
</head>
<body>
    <h1>Ваш заказ подтверждён!</h1>

    <h2>Детали доставки</h2>
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


    <h2>Состав заказа</h2>
    <ul>
        <?php
        $total = 0;
        while ($product = $products->fetch_assoc()):
            $line = $product['price'] * $product['quantity'];
            $total += $line;
        ?>
            <li><?= htmlspecialchars($product['product_name']) ?> — <?= $product['quantity'] ?> × <?= $product['price'] ?> = <?= $line ?> руб.</li>
        <?php endwhile; ?>
    </ul>
    <p><strong>Итого:</strong> <?= $total ?> руб.</p>

    <h2>Статус заявки: <?= htmlspecialchars($app['status']) ?></h2>
</body>
</html>
