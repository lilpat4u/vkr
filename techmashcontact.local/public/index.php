<?php
session_start();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Главная страница</title>
    <link rel="stylesheet" href="style/index.css">
</head>
<body>
    <div class="main__content">
        <header class="header">
            <div class="header__list">
                <a href="index.php" class="header__link">Главная</a>
                <a href="products.php" class="header__link">Продукция</a>
                <a href="my_orders.php" class="header__link">Мои заказы</a>
                <?php if (!empty($_SESSION['client_login'])): ?>
                <a href="basket.php" class="header__link">Корзина</a>
                <?php endif; ?>
            </div>

            <div class="header__profile">
                <?php 
                if (empty($_SESSION['client_login'])) {
                    echo "<p>Вы вошли на сайт, как гость</p>
                          <form action='login.php'><button>Войти</button></form>
                          <form action='reg.php'><button>Зарегистрироваться</button></form>";
                } else {
                    echo "<p>Добро пожаловать, " . htmlspecialchars($_SESSION['client_name']) . "!</p>";
                    echo "<p>Вы успешно вошли в систему как " . htmlspecialchars($_SESSION['client_login']) . ".</p>";
                    echo "<a href='logout.php'>Выйти</a>";
                }
                ?>
            </div>
        </header>

        <main>
            <div class="main__label">
                <h1>ООО «Техмашконтакт»</h1>
            </div>
            <div class="info">
                <p><strong>Общество с ограниченной ответственностью «Техмашконтакт»</strong> — белорусский производитель кормовой рыбной муки и кормовых концентратов, предназначенных для сельскохозяйственных животных, птицы, а также для использования в растениеводстве.</p>

                <p>Мы производим высокоэффективную кормовую продукцию, обогащённую белками и микроэлементами, которая применяется:</p>
                <ul>
                    <li>в животноводстве и птицеводстве — как ценная добавка к основному рациону;</li>
                    <li>в растениеводстве — как натуральное органическое удобрение или прикормка, способствующая повышению урожайности и улучшению состава почвы.</li>
                </ul>

                <p>ООО «Техмашконтакт» успешно поставляет продукцию по всей России, а также экспортирует в страны СНГ и Дальнего зарубежья, обеспечивая стабильное качество, конкурентные цены и гибкую логистику.</p>

                <p>Наши приоритеты — качество, экологичность и эффективность. Производство организовано в соответствии с современными технологическими стандартами, а каждый этап — от сырья до отгрузки — проходит строгий контроль.</p>

                <p>Мы открыты к сотрудничеству с аграрными холдингами, фермерскими хозяйствами, дистрибьюторами и экспортными компаниями.</p>
            </div>
            <div>
                <img src="images/main_photo.jpg" alt="Фото продукции" class="main__photo">
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



