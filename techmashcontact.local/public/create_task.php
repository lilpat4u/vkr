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
</head>
<body>
    <div class="main__content">
        <header class="header">
            <div class="header__list">
                <a href="worker_dashboard.php" class="header__link">Заявки</a>
                <a href="clients.php" class="header__link">Клиенты</a>
                <a href="task.php" class="header__link">Задачи</a>
            </div>
            <h2>Добро пожаловать, <?php echo $_SESSION['worker_surname']; ?> <?php echo $_SESSION['worker_name']; ?>!</h2>
            <p>Вы вошли как сотрудник: <?php echo $_SESSION['worker_login']; ?>.</p>
            <a href="logout.php">Выйти</a>   
        </header>
    </div> 

    <h2>Создание задачи по заявке №<?= htmlspecialchars($app_id) ?></h2>

    <form method="POST">
        <input type="hidden" name="application_id" value="<?= $app_id ?>">

        <label>Название задачи:<br>
            <input type="text" name="title" required style="width: 100%;">
        </label><br><br>

        <label>Описание задачи:<br>
            <textarea name="description" rows="5" required style="width: 100%;"></textarea>
        </label><br><br>

        <label>Постановщик:<br>
            <input type="text" value="<?= htmlspecialchars($manager_fio) ?>" disabled>
        </label><br><br>

        <label>Назначить на:<br>
            <select name="assignee_id" required>
                <option value="">-- выберите сотрудника --</option>
                <?php foreach ($employees as $emp): ?>
                    <option value="<?= $emp['workersnumber'] ?>">
                        <?= htmlspecialchars($emp['surname']) ?> (<?= $emp['post'] ?>, <?= $emp['division'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label><br><br>

        <button type="submit" name="create_task">Создать задачу</button>
    </form>
</body>
</html>
