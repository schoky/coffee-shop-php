<?php
session_start();
if (!isset($_COOKIE['name'])) {
    header('Location: auth.php');
    exit;
}

require_once 'lib/db.php';
require_once 'lib/payment-processor.php';

$payment_id = $_GET['payment_id'] ?? '';
$test_status = $_GET['test_status'] ?? null;
$success = false;
$order_id = null;
$error_message = '';

if ($payment_id) {
    try {
        $pdo = new PDO('mysql:host=MySQL-8.2; dbname=coffee-shop; port=3306', 'root', '');
        $payment_processor = new PaymentProcessor($pdo);

        // Обрабатываем платеж
        $payment_result = $payment_processor->processPayment($payment_id, $test_status);

        if (isset($payment_result['error'])) {
            $error_message = $payment_result['error'];
        } else {
            $success = $payment_result['success'];
            $order_id = $payment_result['order_id'] ?? null;

            // Если платеж успешен и это карта, очищаем корзину
            if ($success && $order_id) {
                // Получаем метод оплаты заказа
                $sql = 'SELECT payment_method FROM orders WHERE id = ?';
                $query = $pdo->prepare($sql);
                $query->execute([$order_id]);
                $order = $query->fetch(PDO::FETCH_OBJ);

                if ($order && $order->payment_method == 'card') {
                    // Очищаем корзину только для успешных карточных платежей
                    $sql = 'DELETE FROM cart WHERE user_id = (SELECT id FROM users WHERE name = ?)';
                    $query = $pdo->prepare($sql);
                    $query->execute([$_COOKIE['name']]);
                }
            }

            // Если платеж не удался и это карта, оставляем корзину как есть
            // (товары уже сохранены в order_items, но пользователь может попробовать снова)
        }

    } catch (PDOException $e) {
        error_log("Payment success page error: " . $e->getMessage());
        $error_message = 'Ошибка базы данных при обработке платежа';
    } catch (Exception $e) {
        error_log("Payment success page error: " . $e->getMessage());
        $error_message = 'Ошибка при обработке платежа';
    }
} else {
    $error_message = 'Не указан идентификатор платежа';
}
?>

<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Реалист — Результат оплаты</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .payment-result {
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
        .error-icon {
            font-size: 80px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .payment-details {
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
    </style>
</head>
<body>
<?php require "block/header.php" ?>

<main class="container">
    <div class="payment-result">
        <?php if ($error_message): ?>
            <div class="error-icon">❌</div>
            <h1>Ошибка обработки платежа</h1>
            <p><?php echo htmlspecialchars($error_message); ?></p>

        <?php elseif ($success): ?>
            <div class="success-icon">✅</div>
            <h1>Оплата прошла успешно!</h1>
            <p>Ваш заказ оплачен и передан в обработку.</p>

            <?php if ($order_id): ?>
                <div class="payment-details">
                    <h3>Детали платежа</h3>
                    <p><strong>Номер заказа:</strong> #<?php echo $order_id; ?></p>
                    <p><strong>ID платежа:</strong> <?php echo htmlspecialchars($payment_id); ?></p>
                    <p><strong>Статус:</strong> Оплачено</p>
                    <div style="margin-top: 16px;">
                        <a href="order-details.php?id=<?php echo $order_id; ?>" class="btn small">Посмотреть детали заказа</a>
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="error-icon">❌</div>
            <h1>Оплата не завершена</h1>
            <p>При обработке платежа произошла ошибка. Пожалуйста, попробуйте еще раз.</p>

            <?php if ($payment_id): ?>
                <div class="payment-details">
                    <p><strong>ID платежа:</strong> <?php echo htmlspecialchars($payment_id); ?></p>
                    <p>Если списание средств произошло, но вы видите это сообщение, свяжитесь с нашей поддержкой.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="action-buttons">
            <?php if ($success): ?>
                <a href="order-details.php?id=<?php echo $order_id; ?>" class="btn">Посмотреть заказ</a>
                <a href="index.php" class="btn ghost">На главную</a>
            <?php else: ?>
                <a href="checkout.php" class="btn">Попробовать снова</a>
                <a href="cart.php" class="btn ghost">Вернуться в корзину</a>
            <?php endif; ?>
            <a href="products.php" class="btn ghost">Вернуться в магазин</a>
        </div>
    </div>
</main>
<?php require "block/footer.php" ?>
</body>
</html>