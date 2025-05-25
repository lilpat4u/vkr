<?php
session_start();
include('connect.php');

$login = $_POST['login'];
$password = $_POST['password'];

$query = "SELECT * FROM clients WHERE login = '$login' AND password = '$password'";
$result = $mysqli->query($query);

if ($result->num_rows > 0) {
    $client = $result->fetch_assoc();
    $_SESSION['client_login'] = $client['login'];
    $_SESSION['client_name'] = $client['name'];
    $_SESSION['client_id'] = $client['id']; 
    
    // ✅ Создаём новую корзину при входе
    $stmt = $mysqli->prepare("INSERT INTO basket (client_id) VALUES (?)");
    $stmt->bind_param("i", $_SESSION['client_id']);
    $stmt->execute();
    $_SESSION['basket_id'] = $stmt->insert_id;
    $stmt->close();

    header("Location: index.php");
    exit;
} else {
    echo "Неверный логин или пароль.";
}
?>
