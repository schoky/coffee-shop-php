<?php
session_start();
// Проверка авторизации администратора
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require_once '../lib/db.php';

// Отладочная информация
$debug_info = [];
$table_exists = false;

// Проверяем существование таблицы order_emails
try {
    $result = $pdo->query("SELECT 1 FROM order_emails LIMIT 1");
    $table_exists = true;
    $debug_info[] = "Таблица order_emails существует";
} catch (PDOException $e) {
    $debug_info[] = "Таблица order_emails не существует: " . $e->getMessage();
}

// Получаем все письма для администратора
$emails = [];
try {
    if ($table_exists) {
        $sql = 'SELECT oe.*, o.total_amount, o.status, u.name as user_name 
                FROM order_emails oe 
                JOIN orders o ON oe.order_id = o.id 
                JOIN users u ON oe.user_id = u.id 
                ORDER BY oe.created_at DESC';
        $query = $pdo->prepare($sql);
        $query->execute();
        $emails = $query->fetchAll(PDO::FETCH_OBJ);
        $debug_info[] = "Найдено писем: " . count($emails);
    }
} catch (PDOException $e) {
    $error_message = "Order emails error: " . $e->getMessage();
    error_log($error_message);
    $debug_info[] = "Ошибка при загрузке писем: " . $e->getMessage();
}

// Если запрошено просмотр конкретного письма
$view_email = null;
if (isset($_GET['view'])) {
    $email_id = intval($_GET['view']);
    try {
        $sql = 'SELECT oe.*, o.total_amount, o.status, u.name as user_name 
                FROM order_emails oe 
                JOIN orders o ON oe.order_id = o.id 
                JOIN users u ON oe.user_id = u.id 
                WHERE oe.id = ?';
        $query = $pdo->prepare($sql);
        $query->execute([$email_id]);
        $view_email = $query->fetch(PDO::FETCH_OBJ);

        if (!$view_email) {
            $debug_info[] = "Письмо с ID $email_id не найдено";
        }
    } catch (PDOException $e) {
        $error_message = "View email error: " . $e->getMessage();
        error_log($error_message);
        $debug_info[] = "Ошибка при загрузке письма: " . $e->getMessage();
    }
}

// Проверяем структуру таблицы (для отладки)
if (!$table_exists) {
    $debug_info[] = "РЕКОМЕНДАЦИЯ: Создайте таблицу order_emails с помощью SQL: CREATE TABLE order_emails (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT, user_id INT, subject VARCHAR(255), content TEXT, recipient_email VARCHAR(255), sent_at DATETIME, created_at DATETIME)";
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Реалист — Письма с заказами</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<main class="container admin-container">
    <div class="admin-header">
        <h1>Письма с заказами</h1>
        <div>
            <a href="index.php" class="btn ghost">Назад в панель</a>
            <a href="../index.php" class="btn" style="margin-left: 15px;">На сайт</a>
        </div>
    </div>

    <p style="color: var(--muted); margin-bottom: 32px;">
        Просмотр всех писем с подтверждением заказов пользователей.
    </p>

    <?php if ($view_email): ?>
        <div style="margin-bottom: 20px;">
            <a href="order-emails.php" class="btn ghost">← Назад к списку</a>
        </div>

        <div class="email-meta">
            <h3>Информация о письме</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <strong>Тема:</strong> <?php echo htmlspecialchars($view_email->subject); ?>
                </div>
                <div>
                    <strong>Заказ:</strong> #<?php echo $view_email->order_id; ?>
                </div>
                <div>
                    <strong>Пользователь:</strong> <?php echo htmlspecialchars($view_email->user_name); ?>
                </div>
                <div>
                    <strong>Сумма заказа:</strong> ₽<?php echo number_format($view_email->total_amount, 2); ?>
                </div>
                <div>
                    <strong>Статус заказа:</strong>
                    <span class="status-badge status-<?php echo $view_email->status; ?>">
                            <?php
                            $status_text = [
                                    'pending' => 'Ожидание',
                                    'processing' => 'В обработке',
                                    'completed' => 'Завершен'
                            ];
                            echo $status_text[$view_email->status] ?? $view_email->status;
                            ?>
                        </span>
                </div>
                <div>
                    <strong>Создано:</strong> <?php echo date('d.m.Y H:i', strtotime($view_email->created_at)); ?>
                </div>
            </div>
        </div>

        <div class="email-content">
            <?php echo $view_email->content; ?>
        </div>

    <?php else: ?>
        <?php if (empty($emails)): ?>
            <div class="empty-message">
                <h2>Письма не найдены</h2>
                <p>Письма с подтверждением заказов пока не созданы или не сохранены в базу данных</p>
                <p><small>Возможные причины:</small></p>
                <ul style="text-align: left; display: inline-block; margin-top: 10px;">
                    <li>Таблица order_emails не существует</li>
                    <li>Функция отправки писем не вызывается</li>
                    <li>Ошибка при сохранении письма в базу данных</li>
                    <li>Нет оформленных заказов</li>
                </ul>
            </div>
        <?php else: ?>
            <table class="emails-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Пользователь</th>
                    <th>Тема</th>
                    <th>Заказ</th>
                    <th>Сумма</th>
                    <th>Статус заказа</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($emails as $email): ?>
                    <tr>
                        <td><?php echo $email->id; ?></td>
                        <td><?php echo htmlspecialchars($email->user_name); ?></td>
                        <td><?php echo htmlspecialchars($email->subject); ?></td>
                        <td>#<?php echo $email->order_id; ?></td>
                        <td>₽<?php echo number_format($email->total_amount, 2); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $email->status; ?>">
                                <?php
                                $status_text = [
                                        'pending' => 'Ожидание',
                                        'processing' => 'В обработке',
                                        'completed' => 'Завершен'
                                ];
                                echo $status_text[$email->status] ?? $email->status;
                                ?>
                            </span>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($email->created_at)); ?></td>
                        <td>
                            <a href="order-emails.php?view=<?php echo $email->id; ?>" class="btn btn-small">📧 Просмотр</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</main>
</body>
</html>