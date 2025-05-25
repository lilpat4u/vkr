<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация клиента</title>
</head>
<body>
    <h2>Регистрация</h2>
    <form method="post" action="reg_process.php">
        <label>Фамилия:</label><br>
        <input type="text" name="surname" required><br><br>

        <label>Имя:</label><br>
        <input type="text" name="name" required><br><br>

        <label>Отчество:</label><br>
        <input type="text" name="patronymic" required><br><br>

        <label>Телефон:</label><br>
        <input type="text" name="phone" required><br><br>

        <label>Номер паспорта:</label><br>
        <input type="text" name="passport_number" required><br><br>

        <label>Email:</label><br>
        <input type="text" name="email" required><br><br>

        <label>Логин:</label><br>
        <input type="text" name="login" required><br><br>

        <label>Пароль:</label><br>
        <input type="password" name="password" required><br><br>

        <input type="submit" value="Зарегистрироваться">
    </form>
</body>
</html>

