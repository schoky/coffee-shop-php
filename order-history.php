<?php
session_start();
if (!isset($_COOKIE['name'])) {
    header('Location: auth.php');
    exit;
}

require_once 'lib/db.php';

// Получаем историю заказов пользователя
$user_name = $_COOKIE['name'];
$orders = [];

try {
    $sql = 'SELECT o.*, u.name as user_name 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            WHERE u.name = ? 
            ORDER BY o.created_at DESC';
    $query = $pdo->prepare($sql);
    $query->execute([$user_name]);
    $orders = $query->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Order history error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Реалист — История заказов</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .orders-container {
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
        }
        .order-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce7ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }
        .order-bonus {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin-top: 12px;
        }
        .empty-orders {
            text-align: center;
            padding: 60px 20px;
        }
    </style>
</head>
<body>
<?php require "block/header.php" ?>

<main class="container">
    <div class="orders-container">
        <h1>История заказов</h1>

        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <h2>Заказов не найдено</h2>
                <p>У вас пока нет завершенных заказов</p>
                <a href="products.php" class="btn">Сделать заказ</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-id">Заказ #<?php echo $order->id; ?></div>
                        <div class="order-status status-<?php echo $order->status; ?>">
                            <?php
                            $status_names = [
                                    'pending' => 'Ожидание',
                                    'processing' => 'В обработке',
                                    'completed' => 'Завершен',
                                    'cancelled' => 'Отменен'
                            ];
                            echo $status_names[$order->status] ?? $order->status;
                            ?>
                        </div>
                    </div>

                    <div class="order-details">
                        <div>
                            <strong>Сумма:</strong> ₽<?php echo number_format($order->total_amount, 2); ?>
                        </div>
                        <div>
                            <strong>Дата заказа:</strong> <?php echo date('d.m.Y H:i', strtotime($order->created_at)); ?>
                        </div>
                        <div>
                            <strong>Способ доставки:</strong>
                            <?php
                            $delivery_names = [
                                    'courier' => 'Курьер',
                                    'pickup' => 'Самовывоз',
                                    'post' => 'Почта'
                            ];
                            echo $delivery_names[$order->delivery_method] ?? $order->delivery_method;
                            ?>
                        </div>
                        <div>
                            <strong>Статус оплаты:</strong>
                            <?php
                            $payment_status_names = [
                                    'pending' => 'Ожидание',
                                    'pending_cash' => 'Ожидание наличных',
                                    'paid' => 'Оплачено',
                                    'failed' => 'Ошибка'
                            ];
                            echo $payment_status_names[$order->payment_status] ?? $order->payment_status;
                            ?>
                        </div>
                    </div>

                    <?php if ($order->used_bonus > 0 || $order->accrued_bonus > 0): ?>
                        <div class="order-bonus">
                            <?php if ($order->used_bonus > 0): ?>
                                <div>Использовано бонусов: <?php echo number_format($order->used_bonus, 2); ?></div>
                            <?php endif; ?>
                            <?php if ($order->accrued_bonus > 0): ?>
                                <div>Начислено бонусов: <?php echo number_format($order->accrued_bonus, 2); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top: 16px;">
                        <a href="order-details.php?id=<?php echo $order->id; ?>" class="btn btn-small">Подробнее о заказе</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php require "block/footer.php" ?>
</body>
</html>