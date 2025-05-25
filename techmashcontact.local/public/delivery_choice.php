<?php
session_start();
include('connect.php');

if (empty($_SESSION['client_id'])) {
    header("Location: login.php");
    exit;
}

$minDate = date('Y-m-d H:i:s', strtotime('+3 days midnight'));
$pickupDateTime = date('Y-m-d', strtotime('+3 days')); // только дата
$pickupDate = date('d.m.Y', strtotime($pickupDateTime)); // отображение в человекочитаемом формате



$sql = "SELECT id, start_time, end_time FROM delivery_slots WHERE status = 'free' AND start_time >= ?";
$stmt = $mysqli->prepare($sql);

if (!$stmt) {
    die("Ошибка в запросе: " . $mysqli->error);
}

$stmt->bind_param("s", $minDate);
$stmt->execute();
$result = $stmt->get_result();

$slots = [];
while ($row = $result->fetch_assoc()) {
    $slots[] = $row;
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['delivery_type'];

    if ($type === 'selfdriven') {
        $stmt = $mysqli->prepare("INSERT INTO delivery (type, pickup_date) VALUES ('selfdriven', ?)");
        $stmt->bind_param("s", $pickupDateTime);
    } else {
        $town = $_POST['town'];
        $street = $_POST['street'];
        $number = $_POST['number'];
        $apartment = $_POST['apartment'];
        $slot_id = (int)$_POST['delivery_slot'];

        $stmt = $mysqli->prepare("INSERT INTO delivery (type, town, street, number, apartment, delivery_slot) VALUES ('delivery', ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $town, $street, $number, $apartment, $slot_id);
    }

    if ($stmt && $stmt->execute()) {
        $delivery_id = $stmt->insert_id;
        $_SESSION['delivery_id'] = $delivery_id;

        if ($type === 'delivery') {
            $updateSlot = $mysqli->prepare("UPDATE delivery_slots SET status = 'busy' WHERE id = ?");
            $updateSlot->bind_param("i", $slot_id);
            $updateSlot->execute();
            $updateSlot->close();
        }

        $basket_id = $_SESSION['basket_id'] ?? null;

        if (!$basket_id) {
            die("Ошибка: корзина не найдена.");
        }

        $workersnumber = null;
        $insertApplication = $mysqli->prepare("INSERT INTO application (workersnumber, basket_id, deliveri_id, status) VALUES (?, ?, ?, 'to_do')");
        $insertApplication->bind_param("iii", $workersnumber, $basket_id, $delivery_id);

        if (!$insertApplication->execute()) {
            die("Ошибка при создании заявки: " . $mysqli->error);
        }
        $insertApplication->close();

        header("Location: confirm_order.php");
        exit;
    } else {
        echo "Ошибка при сохранении: " . $mysqli->error;
    }
}
?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Выбор доставки</title>
    <style>
        .hidden { display: none; }
        .field-block { margin-bottom: 10px; }
    </style>
    <script>
    function toggleDeliveryFields() {
    const type = document.querySelector('input[name="delivery_type"]:checked').value;
    const fields = document.getElementById('delivery_fields');
    const pickup = document.getElementById('pickup_info');

    // Все input'ы внутри блока доставки
    const deliveryInputs = fields.querySelectorAll('input, select');

    if (type === 'delivery') {
        fields.style.display = 'block';
        pickup.style.display = 'none';
        deliveryInputs.forEach(input => input.required = true);
    } else {
        fields.style.display = 'none';
        pickup.style.display = 'block';
        deliveryInputs.forEach(input => input.required = false);
    }
    }

    </script>
</head>
<body>
    <h2>Выберите способ доставки</h2>

    <form method="post">
        <div class="field-block">
            <label><input type="radio" name="delivery_type" value="selfdriven" checked onchange="toggleDeliveryFields()"> Самовывоз</label>
            <label><input type="radio" name="delivery_type" value="delivery" onchange="toggleDeliveryFields()"> Доставка</label>
        </div>

        <div id="pickup_info">
            <p>Вы можете забрать свой товар <strong>с <?= $pickupDate ?></strong> с 8:00 до 18:00.</p>
        </div>

        <div id="delivery_fields" class="hidden">
            <div class="field-block">
                <label>Город: <input type="text" name="town" required></label>
            </div>
            <div class="field-block">
                <label>Улица: <input type="text" name="street" required></label>
            </div>
            <div class="field-block">
                <label>Дом: <input type="text" name="number" required></label>
            </div>
            <div class="field-block">
                <label>Квартира: <input type="text" name="apartment" required></label>
            </div>
            <div class="field-block">
                <label>Время доставки:
                    <select name="delivery_slot" required>
                        <option value="">-- Выберите слот --</option>
                        <?php foreach ($slots as $slot): ?>
                            <option value="<?= $slot['id'] ?>">
                                <?= date('d.m.Y H:i', strtotime($slot['start_time'])) ?> - <?= date('H:i', strtotime($slot['end_time'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        </div>

        <button type="submit">Оплатить</button>
    </form>

    <script>
        // Установить состояние при загрузке
        toggleDeliveryFields();
    </script>
</body>
</html>