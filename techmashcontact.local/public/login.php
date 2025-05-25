<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход клиента</title>
    <link rel="stylesheet" href="style/main.css">
</head>
<body>
    <div class="main__content">
        <header class="header">
            <div class="header__list">
                <a href="index.php" class="header__link">Главная</a>
                <a href="products.php" class="header__link">Продукция</a>
            </div>
        </header>

        <main>
            <div class="main__label">
                <h2>Вход в систему</h2>
            </div>

            <div class="auth-form">
                <form method="post" action="login_process.php">
                    <div class="form-group">
                        <label>Логин:</label>
                        <input type="text" name="login" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Пароль:</label>
                        <input type="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="submit-button">Войти</button>
                </form>

                <div class="auth-links">
                    <form action="worker_login.php" method="get">
                        <button type="submit" class="worker-button">Войти как сотрудник</button>
                    </form>
                    <a href="reg.php" class="register-link">Зарегистрироваться</a>
                </div>
            </div>
        </main>
    </div>

    <footer class="footer">
        <div class="footer__content">
            <p>Телефон для связи: +375-17-272-49-38 | Почтовый адрес: info@tmcontact.by | Юридический адрес: г.Минск, ул.Мележа, д.5, корп.2, оф.1504</p>
        </div>
    </footer>
</body>
</html>
