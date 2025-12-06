<?php
session_start();
if (!isset($_COOKIE['name'])) {
    header('Location: ../auth.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'db.php';
    require_once 'payment-processor.php';

    try {
        $full_name = trim(htmlspecialchars($_POST['full_name']));
        $phone = trim(htmlspecialchars($_POST['phone']));
        $email = trim(htmlspecialchars($_POST['email']));
        $address = trim(htmlspecialchars($_POST['address']));
        $city = trim(htmlspecialchars($_POST['city']));
        $postal_code = trim(htmlspecialchars($_POST['postal_code']));
        $delivery_method = trim(htmlspecialchars($_POST['delivery_method']));
        $payment_method = trim(htmlspecialchars($_POST['payment_method']));
        $comments = trim(htmlspecialchars($_POST['comments']));
        $use_bonus = isset($_POST['use_bonus']) ? floatval($_POST['use_bonus']) : 0;

        $errors = [];

        // Валидация длины полей
        if (strlen($full_name) > 100) {
            $errors[] = 'ФИО слишком длинное (максимум 100 символов)';
        }
        if (strlen($phone) > 20) {
            $errors[] = 'Телефон слишком длинный (максимум 20 символов)';
        }
        if (strlen($email) > 255) {
            $errors[] = 'Email слишком длинный (максимум 255 символов)';
        }
        if (strlen($address) > 500) {
            $errors[] = 'Адрес слишком длинный (максимум 500 символов)';
        }
        if (strlen($city) > 50) {
            $errors[] = 'Название города слишком длинное (максимум 50 символов)';
        }
        if (strlen($postal_code) > 10) {
            $errors[] = 'Почтовый индекс слишком длинный (максимум 10 символов)';
        }
        if (strlen($comments) > 1000) {
            $errors[] = 'Комментарий слишком длинный (максимум 1000 символов)';
        }

        // Проверка обязательных полей
        if (empty($full_name)) {
            $errors[] = 'ФИО обязательно для заполнения';
        }
        if (empty($phone)) {
            $errors[] = 'Телефон обязателен для заполнения';
        }
        if (empty($email)) {
            $errors[] = 'Email обязателен для заполнения';
        }
        if (empty($address)) {
            $errors[] = 'Адрес обязателен для заполнения';
        }
        if (empty($city)) {
            $errors[] = 'Город обязателен для заполнения';
        }
        if (empty($delivery_method)) {
            $errors[] = 'Способ доставки обязателен для выбора';
        }
        if (empty($payment_method)) {
            $errors[] = 'Способ оплаты обязателен для выбора';
        }

        if (!empty($errors)) {
            $_SESSION['checkout_errors'] = $errors;
            $_SESSION['checkout_form_data'] = [
                'full_name' => $full_name,
                'phone' => $phone,
                'email' => $email,
                'address' => $address,
                'city' => $city,
                'postal_code' => $postal_code,
                'delivery_method' => $delivery_method,
                'payment_method' => $payment_method,
                'comments' => $comments,
                'use_bonus' => $use_bonus
            ];
            header('Location: ../checkout.php?error=validation');
            exit;
        }

        $user_name = $_COOKIE['name'];

        // Получаем данные пользователя
        $sql = 'SELECT id, bonus_balance, is_bonus_program FROM users WHERE name = ?';
        $query = $pdo->prepare($sql);
        $query->execute([$user_name]);
        $user = $query->fetch(PDO::FETCH_OBJ);

        if (!$user) {
            header('Location: ../auth.php');
            exit;
        }

        // Получаем товары из корзины
        $sql = 'SELECT c.quantity, p.id as product_id, p.text, p.price, p.image 
                FROM cart c 
                JOIN photo p ON c.product_id = p.id 
                WHERE c.user_id = ?';
        $query = $pdo->prepare($sql);
        $query->execute([$user->id]);
        $cart_items = $query->fetchAll(PDO::FETCH_OBJ);

        if (empty($cart_items)) {
            header('Location: ../cart.php?error=empty_cart');
            exit;
        }

        // Рассчитываем общую сумму
        $total_amount = 0;
        foreach ($cart_items as $item) {
            $total_amount += $item->price * $item->quantity;
        }

        // Добавляем стоимость доставки
        $delivery_cost = 0;
        switch ($delivery_method) {
            case 'courier':
                $delivery_cost = 300;
                break;
            case 'post':
                $delivery_cost = 200;
                break;
            case 'pickup':
                $delivery_cost = 0;
                break;
        }
        $total_amount += $delivery_cost;

        // Обработка бонусов
        $used_bonus = 0;
        $accrued_bonus = 0;

        if ($user->is_bonus_program) {
            $max_bonus_use = min($user->bonus_balance, $total_amount * 0.5, $use_bonus);
            $used_bonus = $max_bonus_use;
            $total_amount -= $used_bonus;

            if ($total_amount < 0) {
                $total_amount = 0;
            }

            $accrued_bonus = $total_amount * 0.05;

            // Обновляем бонусный баланс пользователя
            $new_bonus_balance = $user->bonus_balance - $used_bonus + $accrued_bonus;

            $sql = 'UPDATE users SET bonus_balance = ? WHERE id = ?';
            $query = $pdo->prepare($sql);
            $query->execute([$new_bonus_balance, $user->id]);
        }

        // Создаем заказ в базе данных
        $sql = 'INSERT INTO orders (user_id, full_name, phone, email, address, city, postal_code, delivery_method, payment_method, comments, total_amount, used_bonus, accrued_bonus, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending")';
        $query = $pdo->prepare($sql);
        $query->execute([
            $user->id, $full_name, $phone, $email, $address, $city,
            $postal_code, $delivery_method, $payment_method, $comments, $total_amount, $used_bonus, $accrued_bonus
        ]);

        $order_id = $pdo->lastInsertId();

        // Сохраняем товары заказа в order_items
        foreach ($cart_items as $item) {
            $sql = 'INSERT INTO order_items (order_id, product_id, product_name, quantity, price) 
                    VALUES (?, ?, ?, ?, ?)';
            $query = $pdo->prepare($sql);
            $query->execute([
                $order_id,
                $item->product_id,
                $item->text,
                $item->quantity,
                $item->price
            ]);
        }

        // Создаем HTML письмо и сохраняем в файл (альтернативное решение)
        $email_content = generateOrderEmailContent($order_id, $pdo);
        saveOrderEmailToFile($order_id, $email_content);

        // Также пытаемся отправить email через стандартную функцию
        if (file_exists('send-order-email.php')) {
            require_once 'send-order-email.php';
            $email_sent = sendOrderEmail($order_id, $pdo);

            if (!$email_sent) {
                error_log("Failed to send confirmation email for order: " . $order_id);
            }
        }

        // Если выбран онлайн-платеж, создаем платеж в платежной системе
        if ($payment_method == 'card') {
            $payment_processor = new PaymentProcessor($pdo);

            // Формируем URL для возврата после оплаты
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $return_url = $protocol . '://' . $host . '/payment-success.php';

            $description = "Оплата заказа #{$order_id} в кофейне Реалист";

            $payment_result = $payment_processor->createPayment(
                $order_id,
                $total_amount,
                $description,
                $return_url
            );

            if ($payment_result['success']) {
                // Для карты: товары уже в order_items, корзину НЕ очищаем до успешной оплаты
                // Перенаправляем на страницу оплаты
                header('Location: ' . $payment_result['confirmation_url']);
                exit;
            } else {
                // Ошибка создания платежа - удаляем заказ и товары заказа
                $sql = 'DELETE FROM order_items WHERE order_id = ?';
                $query = $pdo->prepare($sql);
                $query->execute([$order_id]);

                $sql = 'DELETE FROM orders WHERE id = ?';
                $query = $pdo->prepare($sql);
                $query->execute([$order_id]);

                $_SESSION['checkout_errors'] = ['Ошибка при создании платежа. Пожалуйста, попробуйте еще раз или выберите другой способ оплаты.'];
                $_SESSION['checkout_form_data'] = [
                    'full_name' => $full_name,
                    'phone' => $phone,
                    'email' => $email,
                    'address' => $address,
                    'city' => $city,
                    'postal_code' => $postal_code,
                    'delivery_method' => $delivery_method,
                    'payment_method' => $payment_method,
                    'comments' => $comments,
                    'use_bonus' => $use_bonus
                ];
                header('Location: ../checkout.php?error=payment_error');
                exit;
            }
        } else {
            // Для наличной оплаты сразу очищаем корзину
            $sql = 'DELETE FROM cart WHERE user_id = ?';
            $query = $pdo->prepare($sql);
            $query->execute([$user->id]);

            // Обновляем статус заказа для наличной оплаты
            $sql = 'UPDATE orders SET payment_status = "pending_cash", status = "processing" WHERE id = ?';
            $query = $pdo->prepare($sql);
            $query->execute([$order_id]);

            header('Location: ../order-success.php?order_id=' . $order_id);
            exit;
        }

    } catch (PDOException $e) {
        error_log("Order processing error: " . $e->getMessage());

        $_SESSION['checkout_errors'] = ['Произошла ошибка при обработке заказа. Пожалуйста, попробуйте позже.'];
        header('Location: ../checkout.php?error=server_error');
        exit;
    } catch (Exception $e) {
        error_log("General order processing error: " . $e->getMessage());

        $_SESSION['checkout_errors'] = ['Произошла непредвиденная ошибка. Пожалуйста, попробуйте позже.'];
        header('Location: ../checkout.php?error=server_error');
        exit;
    }
} else {
    header('Location: ../cart.php');
    exit;
}

// Функция для генерации содержимого письма
function generateOrderEmailContent($order_id, $pdo) {
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
                    <div><strong>Способ доставки:</strong><br><?php echo getDeliveryMethodText($order->delivery_method); ?></div>
                    <div><strong>Способ оплаты:</strong><br><?php echo getPaymentMethodText($order->payment_method); ?></div>
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

// Функция для сохранения письма в файл
function saveOrderEmailToFile($order_id, $content) {
    $emails_dir = __DIR__ . '/../order_emails';
    if (!file_exists($emails_dir)) {
        mkdir($emails_dir, 0777, true);
    }

    $filename = $emails_dir . "/order_{$order_id}_" . date('Y-m-d_H-i-s') . ".html";
    if (file_put_contents($filename, $content)) {
        error_log("Order email saved to file: " . $filename);
    } else {
        error_log("Failed to save order email to file: " . $filename);
    }

    // Также создаем текстовую версию для удобства просмотра
    $text_content = strip_tags($content);
    file_put_contents($emails_dir . "/order_{$order_id}_" . date('Y-m-d_H-i-s') . ".txt", $text_content);
}

// Вспомогательные функции для преобразования способов доставки и оплаты в текст
function getDeliveryMethodText($method) {
    $methods = [
        'courier' => 'Курьерская доставка',
        'pickup' => 'Самовывоз',
        'post' => 'Почта России'
    ];
    return $methods[$method] ?? $method;
}

function getPaymentMethodText($method) {
    $methods = [
        'card' => 'Картой онлайн',
        'cash' => 'Наличными при получении'
    ];
    return $methods[$method] ?? $method;
}
?>