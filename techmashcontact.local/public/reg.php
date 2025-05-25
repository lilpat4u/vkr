<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация клиента</title>
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
                <h2>Регистрация</h2>
            </div>

            <div class="auth-form">
                <form method="post" action="reg_process.php">
                    <div class="form-group">
                        <label>Фамилия:</label>
                        <input type="text" name="surname" required>
                    </div>

                    <div class="form-group">
                        <label>Имя:</label>
                        <input type="text" name="name" required>
                    </div>

                    <div class="form-group">
                        <label>Отчество:</label>
                        <input type="text" name="patronymic" required>
                    </div>

                    <div class="form-group">
                        <label>Телефон:</label>
                        <input type="text" name="phone" required>
                    </div>

                    <div class="form-group">
                        <label>Номер паспорта:</label>
                        <input type="text" name="passport_number" required>
                    </div>

                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label>Логин:</label>
                        <input type="text" name="login" required>
                    </div>

                    <div class="form-group">
                        <label>Пароль:</label>
                        <input type="password" name="password" required>
                    </div>

                    <button type="submit" class="submit-button">Зарегистрироваться</button>
                </form>

                <div class="auth-links">
                    <a href="login.php" class="register-link">Уже есть аккаунт? Войти</a>
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

