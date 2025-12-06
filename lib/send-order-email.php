<?php
function sendOrderEmail($order_id, $pdo) {
    // Получаем данные заказа
    $sql = 'SELECT o.*, u.email as user_email, u.name as user_name 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?';
    $query = $pdo->prepare($sql);
    $query->execute([$order_id]);
    $order = $query->fetch(PDO::FETCH_OBJ);

    if (!$order) {
        error_log("Order not found for email: " . $order_id);
        return false;
    }

    // Получаем товары заказа
    $sql = 'SELECT oi.*, p.image 
            FROM order_items oi 
            LEFT JOIN photo p ON oi.product_id = p.id 
            WHERE oi.order_id = ?';
    $query = $pdo->prepare($sql);
    $query->execute([$order_id]);
    $order_items = $query->fetchAll(PDO::FETCH_OBJ);

    // Формируем тему письма
    $subject = "Реалист — Подтверждение заказа #" . $order_id;

    // Формируем тело письма в HTML
    $message = generateEmailContentForSending($order, $order_items, $order_id);

    // Для отправки HTML-письма устанавливаем Content-type
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: Реалист <noreply@coffee-shop>' . "\r\n";
    $headers .= 'Reply-To: spb@realist-coffee.ru' . "\r\n";

    // Пытаемся отправить письмо
    $to = $order->user_email;

    $mail_sent = mail($to, $subject, $message, $headers);

    // СОХРАНЯЕМ ПИСЬМО В БАЗУ ДАННЫХ
    saveEmailToDatabase($pdo, $order_id, $order->user_id, $subject, $message, $to, $mail_sent);

    // Логируем попытку отправки
    logEmailAttempt($order_id, $to, $mail_sent, $message);

    return $mail_sent;
}

// НОВАЯ ФУНКЦИЯ ДЛЯ СОХРАНЕНИЯ ПИСЕМ В БАЗУ ДАННЫХ
function saveEmailToDatabase($pdo, $order_id, $user_id, $subject, $content, $recipient_email, $sent_success) {
    try {
        $sql = 'INSERT INTO order_emails (order_id, user_id, subject, content, recipient_email, sent_at, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())';
        $query = $pdo->prepare($sql);
        $result = $query->execute([$order_id, $user_id, $subject, $content, $recipient_email]);

        if ($result) {
            error_log("Email saved to database for order #" . $order_id);
            return true;
        } else {
            error_log("Failed to save email to database for order #" . $order_id);
            return false;
        }
    } catch (PDOException $e) {
        error_log("Error saving email to database: " . $e->getMessage());
        return false;
    }
}

function generateEmailContentForSending($order, $order_items, $order_id) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Подтверждение заказа</title>
        <style>
            body {
                font-family: "Segoe UI", Roboto, Arial, sans-serif;
                line-height: 1.6;
                color: #222;
                background: #f7f5f2;
                margin: 0;
                padding: 20px;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                background: white;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            .header {
                background: #6b4f2b;
                color: white;
                padding: 30px 20px;
                text-align: center;
            }
            .logo {
                font-size: 28px;
                font-weight: 700;
                margin-bottom: 10px;
            }
            .content {
                padding: 30px;
            }
            .order-card {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
            }
            .order-items {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            .order-items th, .order-items td {
                padding: 10px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }
            .order-items th {
                background: #e9ecef;
                font-weight: 600;
                color: #6b4f2b;
            }
            .total {
                font-size: 18px;
                font-weight: bold;
                text-align: right;
                color: #6b4f2b;
                margin-top: 15px;
                padding-top: 15px;
                border-top: 2px solid #6b4f2b;
            }
            .button {
                display: inline-block;
                padding: 12px 25px;
                background: #6b4f2b;
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                margin: 15px 0;
            }
            .info-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
                margin: 15px 0;
            }
            .bonus-positive {
                color: #28a745;
                font-weight: 600;
            }
            .bonus-negative {
                color: #dc3545;
                font-weight: 600;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                padding: 20px;
                background: #f8f9fa;
                color: #6c757d;
                border-top: 1px solid #dee2e6;
            }
            @media (max-width: 600px) {
                .info-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
    <div class="container">
        <div class="header">
            <div class="logo">Реалист</div>
            <div>Проводник в мире кофе</div>
        </div>

        <div class="content">
            <h2 style="color: #6b4f2b; margin-top: 0;">Ваш заказ подтвержден!</h2>
            <p>Здравствуйте, <?php echo htmlspecialchars($order->full_name); ?>! Спасибо за ваш заказ. Мы уже начали его готовить.</p>

            <div class="order-card">
                <h3 style="color: #6b4f2b; margin-top: 0;">Детали заказа #<?php echo $order_id; ?></h3>
                <table class="order-items">
                    <thead>
                    <tr>
                        <th>Товар</th>
                        <th>Кол-во</th>
                        <th>Цена</th>
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
                            <td><?php echo htmlspecialchars($item->product_name); ?></td>
                            <td><?php echo $item->quantity; ?></td>
                            <td>₽<?php echo number_format($item->price, 2); ?></td>
                            <td>₽<?php echo number_format($item_total, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="total">Итого: ₽<?php echo number_format($order->total_amount, 2); ?></div>
            </div>

            <div class="order-card">
                <h3 style="color: #6b4f2b; margin-top: 0;">Информация о доставке</h3>
                <div class="info-grid">
                    <div><strong>ФИО:</strong><br><?php echo htmlspecialchars($order->full_name); ?></div>
                    <div><strong>Телефон:</strong><br><?php echo htmlspecialchars($order->phone); ?></div>
                    <div><strong>Email:</strong><br><?php echo htmlspecialchars($order->email); ?></div>
                    <div><strong>Город:</strong><br><?php echo htmlspecialchars($order->city); ?></div>
                    <div><strong>Адрес:</strong><br><?php echo htmlspecialchars($order->address); ?></div>
                    <div><strong>Способ доставки:</strong><br><?php echo getDeliveryMethodTextForEmail($order->delivery_method); ?></div>
                    <div><strong>Способ оплаты:</strong><br><?php echo getPaymentMethodTextForEmail($order->payment_method); ?></div>
                    <?php if ($order->postal_code): ?>
                        <div><strong>Индекс:</strong><br><?php echo htmlspecialchars($order->postal_code); ?></div>
                    <?php endif; ?>
                </div>

                <?php if ($order->comments): ?>
                    <div style="margin-top: 15px;">
                        <strong>Комментарий к заказу:</strong><br>
                        <?php echo htmlspecialchars($order->comments); ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($order->used_bonus > 0 || $order->accrued_bonus > 0): ?>
                <div class="order-card">
                    <h3 style="color: #6b4f2b; margin-top: 0;">Бонусная программа</h3>
                    <?php if ($order->used_bonus > 0): ?>
                        <div class="bonus-negative">Использовано бонусов: <?php echo number_format($order->used_bonus, 2); ?></div>
                    <?php endif; ?>
                    <?php if ($order->accrued_bonus > 0): ?>
                        <div class="bonus-positive">Начислено бонусов: <?php echo number_format($order->accrued_bonus, 2); ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div style="text-align: center; margin: 25px 0;">
                <a href="https://coffee-shop/order-details.php?id=<?php echo $order_id; ?>" class="button">Посмотреть заказ на сайте</a>
            </div>
        </div>

        <div class="footer">
            <p><strong>Кофейня "Реалист"</strong></p>
            <p>Санкт-Петербург, Исаакиевская площадь, 4</p>
            <p>📞 +7 (812) 123-45-67 | ✉️ spb@realist-coffee.ru</p>
            <p>🕒 Пн-Пт: 8:00-22:00, Сб-Вс: 9:00-23:00</p>
            <p><a href="https://coffee-shop/" style="color: #6b4f2b;">https://coffee-shop/</a></p>
        </div>
    </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

function getDeliveryMethodTextForEmail($method) {
    $methods = [
            'courier' => 'Курьерская доставка',
            'pickup' => 'Самовывоз',
            'post' => 'Почта России'
    ];
    return $methods[$method] ?? $method;
}

function getPaymentMethodTextForEmail($method) {
    $methods = [
            'card' => 'Картой онлайн',
            'cash' => 'Наличными при получении'
    ];
    return $methods[$method] ?? $method;
}

function logEmailAttempt($order_id, $to, $success, $message) {
    $log_dir = __DIR__ . '/../email_logs';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }

    $status = $success ? 'SUCCESS' : 'FAILED';
    $log_message = "[" . date('Y-m-d H:i:s') . "] Order #$order_id - To: $to - Status: $status\n";

    file_put_contents($log_dir . '/email.log', $log_message, FILE_APPEND);

    // Также сохраняем HTML письма для отладки
    if (!$success) {
        file_put_contents($log_dir . "/order_{$order_id}_" . date('Y-m-d_H-i-s') . ".html", $message);
    }
}
?>