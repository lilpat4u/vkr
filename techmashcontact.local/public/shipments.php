<?php
session_start();
require_once 'connect.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отгрузки</title>
    <style>
        .tab {
            display: inline-block;
            padding: 10px;
            background: #ddd;
            cursor: pointer;
            margin-right: 5px;
        }
        .tab.active {
            background: #bbb;
            font-weight: bold;
        }
        .tab-content {
            display: none;
            margin-top: 10px;
        }
        .tab-content.active {
            display: block;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 10px;
        }
        th, td {
            padding: 8px;
            border: 1px solid #ccc;
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
             </header>
     </div> 
<h2>Отгрузки</h2>

<div id="tabs">
    <div class="tab active" data-tab="selfdriven">Самовывоз</div>
    <div class="tab" data-tab="delivery">Доставка</div>
</div>

<div id="selfdriven" class="tab-content active">
    <h3>Самовывоз</h3>
    <table>
        <tr>
            <th>ID заявки</th>
            <th>Дата самовывоза</th>
        </tr>
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
                    <td>{$row['pickup_date']}</td>
                  </tr>";
        }
        ?>
    </table>
</div>

<div id="delivery" class="tab-content">
    <h3>Доставка</h3>
    <table>
        <tr>
            <th>ID заявки</th>
            <th>Город</th>
            <th>Улица</th>
            <th>Дом</th>
            <th>Квартира</th>
            <th>Окно доставки</th>
        </tr>
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
                    <td>{$row['start_time']} – {$row['end_time']}</td>
                  </tr>";
        }
        ?>
    </table>
</div>

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

