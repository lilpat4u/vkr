<?php
session_start();
include('connect.php');
$app_id = isset($_GET['application_id']) ? intval($_GET['application_id']) : 0;

// Получаем менеджера из заявки
$stmt = $mysqli->prepare("SELECT workersnumber FROM application WHERE id = ?");
$stmt->bind_param("i", $app_id);
$stmt->execute();
$stmt->bind_result($manager_id);
$stmt->fetch();
$stmt->close();

$manager_fio = '';
if ($manager_id) {
    $stmt = $mysqli->prepare("SELECT surname, name, patronymic FROM workers WHERE workersnumber = ?");
    $stmt->bind_param("i", $manager_id);
    $stmt->execute();
    $stmt->bind_result($surname, $name, $patronymic);
    if ($stmt->fetch()) {
        $manager_fio = trim("$surname $name $patronymic");
    }
    $stmt->close();
}

// Получаем сотрудников для выбора исполнителя
$employees = [];
$result = $mysqli->query("SELECT workersnumber, surname, post, division FROM workers");
while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
}
$result->free();

// Обработка POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_task'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $application_id = intval($_POST['application_id']);
    $assignee_id = intval($_POST['assignee_id']);
    $status = 'to_do';
    $comment = ''; // всегда пустая строка


    if ($title !== '' && $description !== '' && $assignee_id > 0 && $application_id > 0) {
        $stmt = $mysqli->prepare("INSERT INTO task (title, description, application_id, assignee_id, status, comment) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiiss", $title, $description, $application_id, $assignee_id, $status, $comment);

        $stmt->execute();
        $stmt->close();

        echo "<p style='color: green;'>Задача успешно создана!</p>";
    } else {
        echo "<p style='color: red;'>Ошибка: заполните все поля корректно.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Создание задачи</title>
    <link rel="stylesheet" href="style/main.css">
    <style>
        .task-form {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-weight: 500;
        }
        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-group input[type="text"]:disabled {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .success-message {
            color: #2ecc71;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .error-message {
            color: #e74c3c;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 4px;
            margin-bottom: 1rem;
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
                <h1>Создание задачи по заявке №<?= htmlspecialchars($app_id) ?></h1>
            </div>

            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_task'])): ?>
                <?php if ($title !== '' && $description !== '' && $assignee_id > 0 && $application_id > 0): ?>
                    <div class="success-message">Задача успешно создана!</div>
                <?php else: ?>
                    <div class="error-message">Ошибка: заполните все поля корректно.</div>
                <?php endif; ?>
            <?php endif; ?>

            <form method="POST" class="task-form">
                <input type="hidden" name="application_id" value="<?= $app_id ?>">

                <div class="form-group">
                    <label>Название задачи:</label>
                    <input type="text" name="title" required>
                </div>

                <div class="form-group">
                    <label>Описание задачи:</label>
                    <textarea name="description" required></textarea>
                </div>

                <div class="form-group">
                    <label>Постановщик:</label>
                    <input type="text" value="<?= htmlspecialchars($manager_fio) ?>" disabled>
                </div>

                <div class="form-group">
                    <label>Назначить на:</label>
                    <select name="assignee_id" required>
                        <option value="">-- выберите сотрудника --</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['workersnumber'] ?>">
                                <?= htmlspecialchars($emp['surname']) ?> (<?= $emp['post'] ?>, <?= $emp['division'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" name="create_task" class="submit-button">Создать задачу</button>
            </form>
        </main>
    </div>

    <footer class="footer">
        <div class="footer__content">
            <p>Телефон для связи: +375-17-272-49-38 | Почтовый адрес: info@tmcontact.by | Юридический адрес: г.Минск, ул.Мележа, д.5, корп.2, оф.1504</p>
        </div>
    </footer>
</body>
</html>
