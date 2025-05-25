<?php
session_start();
include('connect.php');

$worker_id = $_SESSION['worker_id'] ?? 0;
$worker_name = $_SESSION['worker_name'] ?? '';
$worker_surname = $_SESSION['worker_surname'] ?? '';
$worker_login = $_SESSION['worker_login'] ?? '';

$filter = $_GET['filter'] ?? 'my'; // по умолчанию "мои задачи"

// Обработка изменения статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'], $_POST['new_status'])) {
    $task_id = intval($_POST['task_id']);
    $new_status = strtolower(trim($_POST['new_status']));

    $stmt = $mysqli->prepare("
        SELECT t.assignee_id, a.workersnumber AS manager_id
        FROM task t
        LEFT JOIN application a ON t.application_id = a.id
        WHERE t.id = ?
    ");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $stmt->bind_result($assignee_id, $manager_id);
    $stmt->fetch();
    $stmt->close();

    $allowed_statuses = ['to_do', 'in_process', 'done', 'cancelled'];
    $allowed = false;

    if ((int)$worker_id === (int)$assignee_id) {
        $allowed = true;
    } elseif ((int)$worker_id === (int)$manager_id && $new_status === 'cancelled') {
        $allowed = true;
    }

    if (in_array($new_status, $allowed_statuses, true) && $allowed) {
        $stmt = $mysqli->prepare("UPDATE task SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $task_id);
        if ($stmt->execute()) {
            header("Location: task.php?filter=$filter");
            exit;
        } else {
            echo "<p style='color:red;'>Ошибка обновления: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        echo "<p style='color:orange;'>Неверный статус или недостаточно прав</p>";
    }
}

// Обработка обновления комментария
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'], $_POST['update_comment'], $_POST['new_comment'])) {
    $task_id = intval($_POST['task_id']);
    $new_comment = trim($_POST['new_comment']);

    $stmt = $mysqli->prepare("SELECT assignee_id FROM task WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $stmt->bind_result($assignee_id_check);
    $stmt->fetch();
    $stmt->close();

    if ((int)$assignee_id_check === (int)$worker_id) {
        $stmt = $mysqli->prepare("UPDATE task SET comment = ? WHERE id = ?");
        $stmt->bind_param("si", $new_comment, $task_id);
        if ($stmt->execute()) {
            header("Location: task.php?filter=$filter");
            exit;
        } else {
            echo "<p style='color:red;'>Ошибка сохранения комментария: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        echo "<p style='color:orange;'>Недостаточно прав для изменения комментария</p>";
    }
}
// Получение задач по фильтру
$tasks = [];

if ($filter === 'all') {
    $query = "
        SELECT t.id, t.title, t.description, t.status, t.application_id, t.assignee_id, w.surname AS assignee_surname, w.name AS assignee_name,
               a.workersnumber AS manager_id, t.comment
        FROM task t
        LEFT JOIN workers w ON t.assignee_id = w.workersnumber
        LEFT JOIN application a ON t.application_id = a.id
    ";
    $result = $mysqli->query($query);
} else { // my
    $stmt = $mysqli->prepare("
        SELECT t.id, t.title, t.description, t.status, t.application_id, t.assignee_id, w.surname AS assignee_surname, w.name AS assignee_name,
               a.workersnumber AS manager_id, t.comment
        FROM task t
        LEFT JOIN workers w ON t.assignee_id = w.workersnumber
        LEFT JOIN application a ON t.application_id = a.id
        WHERE t.assignee_id = ? OR a.workersnumber = ?
    ");
    $stmt->bind_param("ii", $worker_id, $worker_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}
$result->free();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Задачи</title>
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
            <h2>Добро пожаловать, <?= htmlspecialchars($worker_surname) ?> <?= htmlspecialchars($worker_name) ?>!</h2>
            <p>Вы вошли как сотрудник: <?= htmlspecialchars($worker_login) ?>.</p>
            <a href="logout.php">Выйти</a>
        </header>

        <h2>Задачи</h2>

        <div>
            <form method="get" style="display:inline;">
                <input type="hidden" name="filter" value="my">
                <button type="submit" <?= $filter === 'my' ? 'disabled' : '' ?>>Мои задачи</button>
            </form>
            <form method="get" style="display:inline;">
                <input type="hidden" name="filter" value="all">
                <button type="submit" <?= $filter === 'all' ? 'disabled' : '' ?>>Все задачи</button>
            </form>
        </div>

        <?php if (empty($tasks)): ?>
            <p>Задач нет.</p>
        <?php else: ?>
            <ul>
<?php foreach ($tasks as $task): ?>
    <div style="margin-bottom: 30px; padding: 10px; border-bottom: 1px solid #ccc;">
        <strong><?= htmlspecialchars($task['title']) ?></strong> (Заявка №<?= $task['application_id'] ?>)<br>
        Назначено: <?= htmlspecialchars($task['assignee_name']) ?><br>
        Статус: <strong><?= htmlspecialchars($task['status']) ?></strong><br><br>

        <form method="POST" style="margin-bottom: 10px;">
            <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
            <select name="new_status">
                <option value="to_do" <?= $task['status'] === 'to_do' ? 'selected' : '' ?>>To_do</option>
                <option value="in_process" <?= $task['status'] === 'in_process' ? 'selected' : '' ?>>In_process</option>
                <option value="done" <?= $task['status'] === 'done' ? 'selected' : '' ?>>Done</option>
                <option value="cancelled" <?= $task['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
            <button type="submit">Обновить</button>
        </form>

        <div style="margin-bottom: 10px;">
            <strong>Описание:</strong><br>
            <?= nl2br(htmlspecialchars($task['description'])) ?>
        </div>

        <div style="margin-bottom: 10px;">
            <strong>Комментарий исполнителя:</strong><br>
            <?= nl2br(htmlspecialchars($task['comment'])) ?>
        </div>

        <form method="POST">
            <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
            <input type="hidden" name="update_comment" value="1">
            <textarea name="new_comment" rows="3" style="width: 100%; padding: 8px; box-sizing: border-box; background-color: #fff; border: 1px solid #ccc;"></textarea><br>
            <button type="submit" style="margin-top: 5px;">Сохранить комментарий</button>
        </form>
    </div>
<?php endforeach; ?>

            </ul>
        <?php endif; ?>
    </div>
</body>
</html>

