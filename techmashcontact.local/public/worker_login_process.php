<?php
session_start();
include('connect.php');

$login = $_POST['login'];
$password = $_POST['password'];

$query = "SELECT * FROM workers WHERE login = '$login' AND password = '$password'";
$result = $mysqli->query($query);

if ($result->num_rows > 0) {
    $worker = $result->fetch_assoc();
    $_SESSION['worker_login'] = $worker['login'];
    $_SESSION['worker_id'] = $worker['workersnumber'];
    $_SESSION['worker_name'] = $worker['name']; // если есть поле FIO у работников
    $_SESSION['worker_surname'] = $worker['surname']; // если есть поле FIO у работников
    header("Location: worker_dashboard.php"); // сделаем отдельную страницу для сотрудников
    exit;
} else {
    echo "Неверный логин или пароль.";
}
?>
