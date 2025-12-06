<?php
session_start();
if (!isset($_COOKIE['name'])) {
    header('Location: auth.php');
    exit;
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Получаем информацию о заказе для отображения способа оплаты
$order_info = null;
if ($order_id > 0) {
    require_once 'lib/db.php';
    try {
        $sql = 'SELECT o.*, u.name as user_name 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.id = ? AND u.name = ?';
        $query = $pdo->prepare($sql);
        $query->execute([$order_id, $_COOKIE['name']]);
        $order_info = $query->fetch(PDO::FETCH_OBJ);
    } catch (PDOException $e) {
        error_log("Order success page error: " . $e->getMessage());
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Реалист — Заказ принят</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .success-container {
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
            padding: 40px 20px;
        }
        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }
        .order-details {
            background: var(--white);
            padding: 24px;
            border-radius: var(--radius);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin: 24px 0;
            text-align: left;
        }
        .action-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 24px;
            flex-wrap: wrap;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 16px;
        }
        .info-item {
            margin-bottom: 12px;
        }
        .info-item label {
            font-weight: 600;
            color: var(--muted);
            display: block;
            margin-bottom: 4px;
            font-size: 14px;
        }
        .info-item p {
            margin: 0;
            font-size: 16px;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        .status-processing {
            background: #cce7ff;
            color: #004085;
        }
        .payment-status-pending_cash {
            background: #e2e3e5;
            color: #383d41;
        }
    </style>
</head>
<body>
<?php require "block/header.php" ?>

<main class="container">
    <div class="success-container">
        <div class="success-icon">✅</div>
        <h1>Заказ успешно оформлен!</h1>

        <?php if ($order_info && $order_info->payment_method == 'cash'): ?>
            <p>Спасибо за ваш заказ! Подготовим его к выдаче. Оплата производится при получении.</p>
        <?php else: ?>
            <p>Спасибо за ваш заказ! Мы свяжемся с вами в ближайшее время для подтверждения.</p>
        <?php endif; ?>

        <?php if ($order_id > 0): ?>
            <div class="order-details">
                <h3>Детали заказа</h3>
                <p><strong>Номер заказа:</strong> #<?php echo $order_id; ?></p>

                <?php if ($order_info): ?>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Статус заказа:</label>
                            <p>
                                <span class="status-badge status-processing">В обработке</span>
                            </p>
                        </div>
                        <div class="info-item">
                            <label>Способ оплаты:</label>
                            <p>
                                <?php
                                $payment_text = [
                                        'card' => '💳 Картой онлайн',
                                        'cash' => '💵 Наличными при получении'
                                ];
                                echo $payment_text[$order_info->payment_method] ?? $order_info->payment_method;
                                ?>
                            </p>
                        </div>
                        <div class="info-item">
                            <label>Способ доставки:</label>
                            <p>
                                <?php
                                $delivery_text = [
                                        'courier' => '🚚 Курьерская доставка',
                                        'pickup' => '🏪 Самовывоз',
                                        'post' => '📮 Почта России'
                                ];
                                echo $delivery_text[$order_info->delivery_method] ?? $order_info->delivery_method;
                                ?>
                            </p>
                        </div>
                        <div class="info-item">
                            <label>Сумма заказа:</label>
                            <p><strong>₽<?php echo number_format($order_info->total_amount, 2); ?></strong></p>
                        </div>
                    </div>

                    <?php if ($order_info->accrued_bonus > 0): ?>
                        <div style="margin-top: 16px; padding: 12px; background: #f8f9fa; border-radius: 8px; text-align: center;">
                            <strong>💎 После оплаты вы получите: <?php echo number_format($order_info->accrued_bonus, 2); ?> бонусов</strong>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p><strong>Статус:</strong> Обрабатывается</p>
                    <p>Мы отправили подтверждение на вашу электронную почту.</p>
                <?php endif; ?>

                <div style="margin-top: 20px; text-align: center;">
                    <a href="order-details.php?id=<?php echo $order_id; ?>" class="btn">
                        📋 Посмотреть детали заказа
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div class="action-buttons">
            <a href="order-details.php?id=<?php echo $order_id; ?>" class="btn">Посмотреть заказ</a>
            <a href="products.php" class="btn">Продолжить покупки</a>
            <a href="index.php" class="btn ghost">На главную</a>
        </div>

        <?php if ($order_info && $order_info->payment_method == 'cash'): ?>
            <div style="margin-top: 24px; padding: 16px; background: #f8f9fa; border-radius: 8px; text-align: left;">
                <h4>💡 Что дальше?</h4>
                <p>Мы уведомим вас о готовности заказа. При получении вы сможете оплатить его наличными.</p>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php require "block/footer.php" ?>
</body>
</html>