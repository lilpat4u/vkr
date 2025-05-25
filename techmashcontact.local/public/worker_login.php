<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход сотрудника</title>
</head>
<body>
    <h2>Вход для сотрудника</h2>
    <form method="post" action="worker_login_process.php">
        <label>Логин:</label><br>
        <input type="text" name="login" required><br><br>
        
        <label>Пароль:</label><br>
        <input type="password" name="password" required><br><br>
        
        <input type="submit" value="Войти">
    </form>
</body>
</html>

