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

// Получаем заказы пользователя
$orders = [];
try {
    $sql = 'SELECT id, created_at, total_amount, status FROM orders WHERE user_id = ? ORDER BY created_at DESC';
    $query = $pdo->prepare($sql);
    $query->execute([$user_id]);
    $orders = $query->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Orders error: " . $e->getMessage());
}

// Функция для поиска файлов писем по order_id
function findEmailFiles($order_id) {
    $emails_dir = __DIR__ . '/order_emails';
    $files = [];

    if (!file_exists($emails_dir)) {
        return $files;
    }

    $pattern = $emails_dir . "/order_{$order_id}_*.html";
    $found_files = glob($pattern);

    foreach ($found_files as $file) {
        $filename = basename($file);
        $files[] = [
            'filename' => $filename,
            'path' => $file,
            'timestamp' => filemtime($file)
        ];
    }

    // Сортируем по дате (новые сверху)
    usort($files, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });

    return $files;
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
        .order-card {
            background: var(--white);
            padding: 24px;
            border-radius: var(--radius);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f0f0f0;
        }
        .order-id {
            font-weight: 600;
            color: var(--accent);
            font-size: 18px;
        }
        .order-date {
            color: var(--muted);
        }
        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }
        .email-files {
            margin-top: 16px;
        }
        .email-file {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 8px;
        }
        .email-file-info {
            flex: 1;
        }
        .email-file-name {
            font-weight: 600;
            margin-bottom: 4px;
        }
        .email-file-date {
            color: var(--muted);
            font-size: 14px;
        }
        .email-actions {
            display: flex;
            gap: 8px;
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

        <?php if (empty($orders)): ?>
            <div class="empty-message">
                <h2>У вас пока нет заказов</h2>
                <p>Сделайте свой первый заказ, и здесь появятся письма с подтверждением</p>
                <a href="products.php" class="btn">Перейти в каталог</a>
            </div>
        <?php else: ?>
            <?php
            $has_emails = false;
            foreach ($orders as $order):
                $email_files = findEmailFiles($order->id);
                if (!empty($email_files)):
                    $has_emails = true;
                    ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <span class="order-id">Заказ #<?php echo $order->id; ?></span>
                                <span class="status-badge status-<?php echo $order->status; ?>" style="margin-left: 10px;">
                                <?php
                                $status_text = [
                                    'pending' => 'Ожидание',
                                    'processing' => 'В обработке',
                                    'completed' => 'Завершен'
                                ];
                                echo $status_text[$order->status] ?? $order->status;
                                ?>
                            </span>
                            </div>
                            <div class="order-date">
                                от <?php echo date('d.m.Y H:i', strtotime($order->created_at)); ?>
                            </div>
                        </div>

                        <div class="order-details">
                            <div>
                                <strong>Сумма заказа:</strong>
                                <p>₽<?php echo number_format($order->total_amount, 2); ?></p>
                            </div>
                            <div>
                                <strong>Статус:</strong>
                                <p>
                                <span class="status-badge status-<?php echo $order->status; ?>">
                                    <?php echo $status_text[$order->status] ?? $order->status; ?>
                                </span>
                                </p>
                            </div>
                        </div>

                        <div class="email-files">
                            <h4>Сохраненные письма:</h4>
                            <?php foreach ($email_files as $file): ?>
                                <div class="email-file">
                                    <div class="email-file-info">
                                        <div class="email-file-name">Письмо с подтверждением заказа</div>
                                        <div class="email-file-date">
                                            Создано: <?php echo date('d.m.Y H:i', $file['timestamp']); ?>
                                        </div>
                                    </div>
                                    <div class="email-actions">
                                        <a href="view-order-email.php?file=<?php echo urlencode($file['filename']); ?>&order_id=<?php echo $order->id; ?>"
                                           target="_blank" class="btn btn-small">📧 Открыть письмо</a>
                                        <a href="order-details.php?id=<?php echo $order->id; ?>" class="btn btn-small ghost">📋 Детали заказа</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php
                endif;
            endforeach;

            if (!$has_emails):
                ?>
                <div class="empty-message">
                    <h2>Письма не найдены</h2>
                    <p>Для ваших заказов пока не созданы письма с подтверждением</p>
                    <p><small>Письма создаются автоматически при оформлении новых заказов</small></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php require "block/footer.php" ?>
</body>
</html>