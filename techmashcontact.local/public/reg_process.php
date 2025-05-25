<?php
session_start();
include('connect.php');

$surname = $_POST['surname'];
$name = $_POST['name'];
$patronymic = $_POST['patronymic'];
$phone = $_POST['phone'];
$passport = $_POST['passport_number'];
$email = $_POST['email'];
$login = $_POST['login'];
$password = $_POST['password'];

// Проверка на существующий логин
$checkQuery = "SELECT id FROM clients WHERE login = '$login'";
$result = $mysqli->query($checkQuery);

if ($result->num_rows > 0) {
    echo "Пользователь с таким логином уже существует.";
    exit;
}

// Вставка нового клиента
$query = "INSERT INTO clients (login, password, surname, name, patronymic, phone, passport_number, email)
          VALUES ('$login', '$password', '$surname','$name','$patronymic', '$phone', '$passport', '$email')";

if ($mysqli->query($query)) {
    $_SESSION['client_login'] = $login;
    $_SESSION['client_name'] = $name;
    header("Location: index.php");
    exit;
} else {
    echo "Ошибка при регистрации: " . $mysqli->error;
}
?>
