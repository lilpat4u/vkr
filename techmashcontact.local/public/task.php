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
    <link rel="stylesheet" href="style/main.css">
    <style>
        .task-filters {
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
        }
        .task-filters button {
            padding: 0.5rem 1rem;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .task-filters button:hover {
            background-color: #e9ecef;
        }
        .task-filters button:disabled {
            background-color: #2c3e50;
            color: white;
            cursor: default;
        }
        .task-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .task-header {
            margin-bottom: 1rem;
        }
        .task-title {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        .task-info {
            color: #666;
            margin-bottom: 1rem;
        }
        .task-status {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background-color: #e9ecef;
            border-radius: 4px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .task-description {
            margin-bottom: 1rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        .task-comment {
            margin-bottom: 1rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        .task-form {
            margin-top: 1rem;
        }
        .task-form select {
            padding: 0.5rem;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-right: 0.5rem;
        }
        .task-form textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-bottom: 0.5rem;
            resize: vertical;
        }
        .task-form button {
            padding: 0.5rem 1rem;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .task-form button:hover {
            background-color: #2980b9;
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
                <p>Добро пожаловать, <?= htmlspecialchars($worker_surname) ?> <?= htmlspecialchars($worker_name) ?>!</p>
                <p>Вы вошли как сотрудник: <?= htmlspecialchars($worker_login) ?></p>
                <a href="logout.php" class="header__link">Выйти</a>
            </div>
        </header>

        <main>
            <div class="main__label">
                <h1>Задачи</h1>
            </div>

            <div class="task-filters">
                <form method="get">
                    <input type="hidden" name="filter" value="my">
                    <button type="submit" <?= $filter === 'my' ? 'disabled' : '' ?>>Мои задачи</button>
                </form>
                <form method="get">
                    <input type="hidden" name="filter" value="all">
                    <button type="submit" <?= $filter === 'all' ? 'disabled' : '' ?>>Все задачи</button>
                </form>
            </div>

            <?php if (empty($tasks)): ?>
                <p class="no-results">Задач нет.</p>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <div class="task-card">
                        <div class="task-header">
                            <div class="task-title">
                                <?= htmlspecialchars($task['title']) ?> (Заявка №<?= $task['application_id'] ?>)
                            </div>
                            <div class="task-info">
                                Назначено: <?= htmlspecialchars($task['assignee_name']) ?>
                            </div>
                            <div class="task-status">
                                Статус: <?= htmlspecialchars($task['status']) ?>
                            </div>
                        </div>

                        <form method="POST" class="task-form">
                            <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                            <select name="new_status">
                                <option value="to_do" <?= $task['status'] === 'to_do' ? 'selected' : '' ?>>To_do</option>
                                <option value="in_process" <?= $task['status'] === 'in_process' ? 'selected' : '' ?>>In_process</option>
                                <option value="done" <?= $task['status'] === 'done' ? 'selected' : '' ?>>Done</option>
                                <option value="cancelled" <?= $task['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                            <button type="submit">Обновить статус</button>
                        </form>

                        <div class="task-description">
                            <strong>Описание:</strong><br>
                            <?= nl2br(htmlspecialchars($task['description'])) ?>
                        </div>

                        <div class="task-comment">
                            <strong>Комментарий исполнителя:</strong><br>
                            <?= nl2br(htmlspecialchars($task['comment'])) ?>
                        </div>

                        <form method="POST" class="task-form">
                            <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                            <input type="hidden" name="update_comment" value="1">
                            <textarea name="new_comment" rows="3" placeholder="Введите комментарий..."></textarea>
                            <button type="submit">Сохранить комментарий</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>

    <footer class="footer">
        <div class="footer__content">
            <p>Телефон для связи: +375-17-272-49-38 | Почтовый адрес: info@tmcontact.by | Юридический адрес: г.Минск, ул.Мележа, д.5, корп.2, оф.1504</p>
        </div>
    </footer>
</body>
</html>

