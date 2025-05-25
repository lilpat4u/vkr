<?php
session_start();
include('connect.php');


if (empty($_SESSION['basket_id'])) {
    die('Корзина не найдена');
}

$basket_id = $_SESSION['basket_id'];
$product_id = intval($_POST['product_id']);
$price_id = intval($_POST['price_id']);

// Логика добавления в basket_item...

// Проверить наличие позиции
$res = $mysqli->query("SELECT id, quantity FROM basket_item WHERE basket_id = $basket_id AND price_id = $price_id");
if (!$res) {
    die('Ошибка в запросе для проверки позиции: ' . $mysqli->error);
}

if ($item = $res->fetch_assoc()) {
    $item_id = $item['id'];
    $new_qty = $item['quantity'] + 1;
    $mysqli->query("UPDATE basket_item SET quantity = $new_qty WHERE id = $item_id");
} else {
    $mysqli->query("INSERT INTO basket_item (basket_id, price_id, quantity) VALUES ($basket_id, $price_id, 1)");
}

echo "Товар успешно добавлен в корзину!";

// Закомментируй редирект для проверки
header("Location: products.php?success=1");
 exit;
?>
