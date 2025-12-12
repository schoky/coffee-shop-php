<?php
session_start();
if (!isset($_COOKIE['name'])) {
    header('Location: auth.php');
    exit;
}

require_once 'lib/db.php';

// Получаем ID текущего пользователя
$user_name = $_COOKIE['name'];
$user_id = 0;

try {
    $sql = 'SELECT id FROM users WHERE name = ?';
    $query = $pdo->prepare($sql);
    $query->execute([$user_name]);
    $user = $query->fetch(PDO::FETCH_OBJ);
    if ($user) {
        $user_id = $user->id;
    }
} catch (PDOException $e) {
    error_log("User ID error: " . $e->getMessage());
}

// Получаем письма пользователя из базы данных
$emails = [];
try {
    $sql = 'SELECT oe.*, o.created_at as order_date, o.total_amount, o.status 
            FROM order_emails oe 
            JOIN orders o ON oe.order_id = o.id 
            WHERE o.user_id = ? 
            ORDER BY oe.created_at DESC';
    $query = $pdo->prepare($sql);
    $query->execute([$user_id]);
    $emails = $query->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Emails error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Реалист — Мои письма с заказами</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .emails-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .email-card {
            background: var(--white);
            padding: 24px;
            border-radius: var(--radius);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .email-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f0f0f0;
        }
        .email-order-id {
            font-weight: 600;
            color: var(--accent);
            font-size: 18px;
        }
        .email-date {
            color: var(--muted);
        }
        .email-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }
        .email-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
        }
        .btn-small {
            padding: 6px 12px;
            font-size: 14px;
        }
        .empty-message {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce7ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .subject {
            font-size: 18px;
            font-weight: 600;
            margin: 12px 0 8px 0;
        }
        .recipient {
            color: var(--muted);
            font-size: 14px;
        }
    </style>
</head>
<body>
<?php require "block/header.php" ?>

<main class="container">
    <div class="emails-container">
        <h1>Мои письма с заказами</h1>
        <p style="color: var(--muted); margin-bottom: 32px;">
            Здесь вы можете просмотреть все письма с подтверждением заказов, которые были отправлены на вашу почту.
        </p>

        <?php if (empty($emails)): ?>
            <div class="empty-message">
                <h2>У вас пока нет писем</h2>
                <p>Письма с подтверждением заказов появятся здесь после оформления заказов</p>
                <a href="products.php" class="btn">Перейти в каталог</a>
            </div>
        <?php else: ?>
            <?php foreach ($emails as $email): ?>
                <div class="email-card">
                    <div class="email-header">
                        <div>
                            <span class="email-order-id">Заказ #<?php echo $email->order_id; ?></span>
                            <span class="status-badge status-<?php echo $email->status; ?>" style="margin-left: 10px;">
                                <?php
                                $status_text = [
                                        'pending' => 'Ожидание',
                                        'processing' => 'В обработке',
                                        'completed' => 'Завершен'
                                ];
                                echo $status_text[$email->status] ?? $email->status;
                                ?>
                            </span>
                        </div>
                        <div class="email-date">
                            <?php echo date('d.m.Y H:i', strtotime($email->created_at)); ?>
                        </div>
                    </div>

                    <div class="subject"><?php echo htmlspecialchars($email->subject); ?></div>
                    <div class="recipient">Адрес получателя: <?php echo htmlspecialchars($email->recipient_email); ?></div>

                    <?php if ($email->sent_at): ?>
                        <div class="recipient" style="margin-top: 4px;">
                            Отправлено: <?php echo date('d.m.Y H:i', strtotime($email->sent_at)); ?>
                        </div>
                    <?php endif; ?>

                    <div class="email-details">
                        <div>
                            <strong>Сумма заказа:</strong>
                            <p>₽<?php echo number_format($email->total_amount, 2); ?></p>
                        </div>
                        <div>
                            <strong>Статус заказа:</strong>
                            <p>
                                <span class="status-badge status-<?php echo $email->status; ?>">
                                    <?php echo $status_text[$email->status] ?? $email->status; ?>
                                </span>
                            </p>
                        </div>
                        <div>
                            <strong>Дата заказа:</strong>
                            <p><?php echo date('d.m.Y H:i', strtotime($email->order_date)); ?></p>
                        </div>
                    </div>

                    <div class="email-actions">
                        <a href="view-order-email.php?id=<?php echo $email->id; ?>"
                           target="_blank" class="btn btn-small">📧 Открыть письмо</a>
                        <a href="order-details.php?id=<?php echo $email->order_id; ?>"
                           class="btn btn-small ghost">📋 Детали заказа</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php require "block/footer.php" ?>
</body>
</html>