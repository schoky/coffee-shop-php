<?php
session_start();

// Проверка авторизации администратора
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require_once '../lib/db.php';

// Получаем все заявки
try {
    $sql = 'SELECT * FROM contact_requests ORDER BY created_at DESC';
    $query = $pdo->prepare($sql);
    $query->execute();
    $requests = $query->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Contact requests error: " . $e->getMessage());
    $requests = [];
}

// Обновление статуса заявки
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_id'])) {
    $request_id = intval($_POST['request_id']);
    $status = trim(htmlspecialchars($_POST['status']));
    try {
        $sql = 'UPDATE contact_requests SET status = ? WHERE id = ?';
        $query = $pdo->prepare($sql);
        $query->execute([$status, $request_id]);
        header('Location: contact-requests.php?success=updated');
        exit;
    } catch (PDOException $e) {
        error_log("Update contact request error: " . $e->getMessage());
        header('Location: contact-requests.php?error=update_failed');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Реалист — Заявки с сайта</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e6e6e6;
        }
        .requests-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .requests-table th,
        .requests-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .requests-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .status-new { background-color: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 4px; }
        .status-processed { background-color: #d1ecf1; color: #0c5460; padding: 4px 8px; border-radius: 4px; }
        .status-completed { background-color: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; }
        .message-preview {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .action-form {
            display: inline;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px 16px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
<main class="container admin-container">
    <div class="admin-header">
        <h1>Заявки с формы контактов</h1>
        <div>
            <a href="index.php" class="btn ghost">Назад в панель</a>
            <a href="../index.php" class="btn" style="margin-left: 15px;">На сайт</a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="success-message">
            ✅ Статус заявки успешно обновлен
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="error-message">
            ❌ Ошибка при обновлении статуса
        </div>
    <?php endif; ?>

    <?php if (empty($requests)): ?>
        <p>Заявок пока нет.</p>
    <?php else: ?>
        <table class="requests-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Имя</th>
                <th>Email</th>
                <th>Телефон</th>
                <th>Сообщение</th>
                <th>Статус</th>
                <th>Дата</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($requests as $request): ?>
                <tr>
                    <td><?php echo $request->id; ?></td>
                    <td><?php echo htmlspecialchars($request->name); ?></td>
                    <td><?php echo htmlspecialchars($request->email); ?></td>
                    <td><?php echo htmlspecialchars($request->phone ?: '—'); ?></td>
                    <td class="message-preview" title="<?php echo htmlspecialchars($request->message); ?>">
                        <?php echo htmlspecialchars($request->message); ?>
                    </td>
                    <td>
                            <span class="status-<?php echo $request->status; ?>">
                                <?php
                                $status_names = [
                                        'new' => 'Новая',
                                        'processed' => 'В работе',
                                        'completed' => 'Завершена'
                                ];
                                echo $status_names[$request->status] ?? $request->status;
                                ?>
                            </span>
                    </td>
                    <td><?php echo date('d.m.Y H:i', strtotime($request->created_at)); ?></td>
                    <td>
                        <form class="action-form" method="post">
                            <input type="hidden" name="request_id" value="<?php echo $request->id; ?>">
                            <select name="status" onchange="this.form.submit()">
                                <option value="new" <?php echo $request->status == 'new' ? 'selected' : ''; ?>>Новая</option>
                                <option value="processed" <?php echo $request->status == 'processed' ? 'selected' : ''; ?>>В работе</option>
                                <option value="completed" <?php echo $request->status == 'completed' ? 'selected' : ''; ?>>Завершена</option>
                            </select>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
</body>
</html>