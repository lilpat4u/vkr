<?php
session_start();
require_once 'connect.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отгрузки</title>
    <link rel="stylesheet" href="style/main.css">
    <style>
        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .tab {
            padding: 1rem 2rem;
            background-color: #f8f9fa;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .tab:hover {
            background-color: #e9ecef;
        }
        .tab.active {
            background-color: #2c3e50;
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="main__content">
        <header class="header">
            <div class="header__list">
                <a href="worker_dashboard.php" class="header__link">Заявки</a>
                <a href="clients.php" class="header__link">Клиенты</a>
                <a href="task.php" class="header__link">Задачи</a>
                <a href="shipments.php" class="header__link">Отгрузки</a>
            </div>

            <div class="header__profile">
                <p>Добро пожаловать, <?php echo htmlspecialchars($_SESSION['worker_surname'] . ' ' . $_SESSION['worker_name']); ?>!</p>
                <p>Вы вошли как сотрудник: <?php echo htmlspecialchars($_SESSION['worker_login']); ?></p>
                <a href="logout.php" class="header__link">Выйти</a>
            </div>
        </header>

        <main>
            <div class="main__label">
                <h1>Отгрузки</h1>
            </div>

            <div class="tabs">
                <div class="tab active" data-tab="selfdriven">Самовывоз</div>
                <div class="tab" data-tab="delivery">Доставка</div>
            </div>

            <div id="selfdriven" class="tab-content active">
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID заявки</th>
                                <th>Дата самовывоза</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "
                                SELECT a.id AS application_id, d.pickup_date
                                FROM application a
                                JOIN delivery d ON a.deliveri_id = d.id
                                WHERE a.status = 'in_process' AND d.type = 'selfdriven'
                                ORDER BY d.pickup_date ASC
                            ";
                            $result = $mysqli->query($sql);
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td>{$row['application_id']}</td>
                                        <td>" . date('d.m.Y', strtotime($row['pickup_date'])) . "</td>
                                      </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="delivery" class="tab-content">
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID заявки</th>
                                <th>Город</th>
                                <th>Улица</th>
                                <th>Дом</th>
                                <th>Квартира</th>
                                <th>Окно доставки</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "
                                SELECT a.id AS application_id, d.*, ds.start_time, ds.end_time, ds.status AS slot_status
                                FROM application a
                                JOIN delivery d ON a.deliveri_id = d.id
                                LEFT JOIN delivery_slots ds ON d.delivery_slot = ds.id
                                WHERE a.status = 'in_process' AND d.type = 'delivery'
                                ORDER BY ds.start_time ASC
                            ";
                            $result = $mysqli->query($sql);
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td>{$row['application_id']}</td>
                                        <td>{$row['town']}</td>
                                        <td>{$row['street']}</td>
                                        <td>{$row['number']}</td>
                                        <td>{$row['apartment']}</td>
                                        <td>" . date('d.m.Y H:i', strtotime($row['start_time'])) . " – " . date('H:i', strtotime($row['end_time'])) . "</td>
                                      </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <footer class="footer">
        <div class="footer__content">
            <p>Телефон для связи: +375-17-272-49-38 | Почтовый адрес: info@tmcontact.by | Юридический адрес: г.Минск, ул.Мележа, д.5, корп.2, оф.1504</p>
        </div>
    </footer>

    <script>
        const tabs = document.querySelectorAll('.tab');
        const contents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.dataset.tab;
                tabs.forEach(t => t.classList.remove('active'));
                contents.forEach(c => c.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById(target).classList.add('active');
            });
        });
    </script>
</body>
</html>

