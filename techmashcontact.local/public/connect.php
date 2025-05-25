<?php
$host = 'localhost';
$user = 'root';
$password = '1111'; // Пароль по умолчанию пустой
$database = 'techmashcontact';

$mysqli = new mysqli($host, $user, $password, $database);

// Проверка подключения
if ($mysqli->connect_error) {
    die("Ошибка подключения к базе данных: " . $mysqli->connect_error);
}

// Установка кодировки
$mysqli->set_charset("utf8");
?>
