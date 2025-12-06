<?php
session_start();
if (!isset($_COOKIE['name'])) {
    header('Location: auth.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: order-history.php');
    exit;
}

$order_id = intval($_GET['id']);
require_once 'lib/db.php';

// Получаем данные заказа
$order = null;
$order_items = [];

try {
    // Проверяем, что заказ принадлежит текущему пользователю
    $sql = 'SELECT o.*, u.name as user_name 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.id = ? AND u.name = ?';
    $query = $pdo->prepare($sql);
    $query->execute([$order_id, $_COOKIE['name']]);
    $order = $query->fetch(PDO::FETCH_OBJ);

    if ($order) {
        // Получаем товары заказа
        $sql = 'SELECT oi.*, p.image 
                FROM order_items oi 
                LEFT JOIN photo p ON oi.product_id = p.id 
                WHERE oi.order_id = ?';
        $query = $pdo->prepare($sql);
        $query->execute([$order_id]);
        $order_items = $query->fetchAll(PDO::FETCH_OBJ);
    }
} catch (PDOException $e) {
    error_log("Order details error: " . $e->getMessage());
}

if (!$order) {
    header('Location: order-history.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Реалист — Детали заказа #<?php echo $order_id; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .order-details-container {
            max-width: 800px;
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
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f0f0f0;
        }
        .order-id {
            font-weight: 600;
            color: var(--accent);
            font-size: 24px;
        }
        .order-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce7ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .order-items {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .order-items th, .order-items td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .order-items th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        .info-item {
            padding: 12px 0;
        }
        .info-item label {
            font-weight: 600;
            color: var(--muted);
            display: block;
            margin-bottom: 4px;
        }
        .total {
            font-size: 20px;
            font-weight: bold;
            text-align: right;
            color: var(--accent);
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid var(--accent);
        }
    </style>
</head>
<body>
<?php require "block/header.php" ?>

<main class="container">
    <div class="order-details-container">
        <div style="margin-bottom: 20px;">
            <a href="order-history.php" class="btn ghost">← Назад к истории заказов</a>
        </div>

        <div class="order-card">
            <div class="order-header">
                <div class="order-id">Заказ #<?php echo $order_id; ?></div>
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

            <h3>Товары в заказе</h3>
            <table class="order-items">
                <thead>
                <tr>
                    <th>Товар</th>
                    <th>Цена</th>
                    <th>Кол-во</th>
                    <th>Сумма</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $total = 0;
                foreach ($order_items as $item):
                    $item_total = $item->quantity * $item->price;
                    $total += $item_total;
                    ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <?php if ($item->image): ?>
                                    <img src="image/<?php echo $item->image; ?>" alt="<?php echo htmlspecialchars($item->product_name); ?>" class="product-image">
                                <?php endif; ?>
                                <div><?php echo htmlspecialchars($item->product_name); ?></div>
                            </div>
                        </td>
                        <td>₽<?php echo number_format($item->price, 2); ?></td>
                        <td><?php echo $item->quantity; ?></td>
                        <td>₽<?php echo number_format($item_total, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div class="total">
                Итого: ₽<?php echo number_format($order->total_amount, 2); ?>
            </div>
        </div>

        <div class="order-card">
            <h3>Информация о доставке</h3>
            <div class="info-grid">
                <div class="info-item">
                    <label>ФИО</label>
                    <div><?php echo htmlspecialchars($order->full_name); ?></div>
                </div>
                <div class="info-item">
                    <label>Телефон</label>
                    <div><?php echo htmlspecialchars($order->phone); ?></div>
                </div>
                <div class="info-item">
                    <label>Email</label>
                    <div><?php echo htmlspecialchars($order->email); ?></div>
                </div>
                <div class="info-item">
                    <label>Город</label>
                    <div><?php echo htmlspecialchars($order->city); ?></div>
                </div>
                <div class="info-item">
                    <label>Адрес</label>
                    <div><?php echo htmlspecialchars($order->address); ?></div>
                </div>
                <div class="info-item">
                    <label>Способ доставки</label>
                    <div>
                        <?php
                        $delivery_names = [
                                'courier' => 'Курьер',
                                'pickup' => 'Самовывоз',
                                'post' => 'Почта'
                        ];
                        echo $delivery_names[$order->delivery_method] ?? $order->delivery_method;
                        ?>
                    </div>
                </div>
                <div class="info-item">
                    <label>Способ оплаты</label>
                    <div>
                        <?php
                        $payment_methods = [
                                'card' => 'Картой онлайн',
                                'cash' => 'Наличными'
                        ];
                        echo $payment_methods[$order->payment_method] ?? $order->payment_method;
                        ?>
                    </div>
                </div>
                <div class="info-item">
                    <label>Статус оплаты</label>
                    <div>
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
                <?php if ($order->postal_code): ?>
                    <div class="info-item">
                        <label>Почтовый индекс</label>
                        <div><?php echo htmlspecialchars($order->postal_code); ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($order->comments): ?>
                <div class="info-item">
                    <label>Комментарий к заказу</label>
                    <div><?php echo htmlspecialchars($order->comments); ?></div>
                </div>
            <?php endif; ?>

            <?php if ($order->used_bonus > 0 || $order->accrued_bonus > 0): ?>
                <div style="margin-top: 20px; padding: 16px; background: #f8f9fa; border-radius: 8px;">
                    <h4>Бонусная программа</h4>
                    <?php if ($order->used_bonus > 0): ?>
                        <div style="color: #dc3545; margin-bottom: 8px;">
                            Использовано бонусов: <?php echo number_format($order->used_bonus, 2); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($order->accrued_bonus > 0): ?>
                        <div style="color: #28a745;">
                            Начислено бонусов: <?php echo number_format($order->accrued_bonus, 2); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require "block/footer.php" ?>
</body>
</html>