<?php
session_start();
require_once 'connect.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=report.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Фильтры
$selected_worker = $_GET['worker'] ?? '';
$client_query = $_GET['client'] ?? '';
$product_query = $_GET['product'] ?? '';
$status_query = $_GET['status'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// SQL
$sql = "
    SELECT 
        a.id AS application_id,
        CONCAT_WS(' ', cl.surname, cl.name, cl.patronymic) AS client_name,
        cl.phone AS client_phone,
        a.status,
        c.create_date AS date,
        p.name AS product,
        CONCAT_WS(' ', w.surname, w.name, w.patronymic) AS manager_name
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
    $sql .= " AND p.name LIKE ?";
    $types .= 's';
    $params[] = '%' . $product_query . '%';
}

if ($status_query !== '') {
    $sql .= " AND a.status = ?";
    $types .= 's';
    $params[] = $status_query;
}

if ($start_date !== '') {
    $sql .= " AND (c.create_date >= ? OR c.create_date IS NULL)";
    $types .= 's';
    $params[] = $start_date;
}
if ($end_date !== '') {
    $sql .= " AND (c.create_date <= ? OR c.create_date IS NULL)";
    $types .= 's';
    $params[] = $end_date;
}

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    die("Ошибка подготовки запроса: " . $mysqli->error . "<br>SQL: " . $sql);
}

if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Выводим как таблицу Excel
echo "<table border='1'>";
echo "<tr>
        <th>ID</th>
        <th>Клиент</th>
        <th>Телефон</th>
        <th>Статус</th>
        <th>Товар</th>
        <th>Дата</th>
        <th>Менеджер</th>
      </tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>
        <td>{$row['application_id']}</td>
        <td>" . htmlspecialchars($row['client_name']) . "</td>
        <td>" . htmlspecialchars($row['client_phone']) . "</td>
        <td>" . htmlspecialchars($row['status']) . "</td>
        <td>" . htmlspecialchars($row['product']) . "</td>
        <td>" . htmlspecialchars($row['date']) . "</td>
        <td>" . htmlspecialchars($row['manager_name']) . "</td>
    </tr>";
}
echo "</table>";
?>
