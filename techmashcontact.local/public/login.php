<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход клиента</title>
</head>
<body>
    <h2>Вход</h2>
    <form method="post" action="login_process.php">
        <label>Логин:</label><br>
        <input type="text" name="login" required><br><br>
        
        <label>Пароль:</label><br>
        <input type="password" name="password" required><br><br>
        
        <input type="submit" value="Войти">
    </form>
    <br><br>

    <form action="worker_login.php" method="get">
        <input type="submit" value="Войти как сотрудник">
    </form>
</body>
</html>
