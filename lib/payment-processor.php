<?php
class PaymentProcessor {
    private $pdo;
    private $api_url = 'https://api.payment-system.com/v3/';
    private $shop_id = 'shop_12345';
    private $secret_key = 'test_secret_key_12345';

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Создание платежа в платежной системе
    public function createPayment($order_id, $amount, $description, $return_url) {
        try {
            $payment_id = 'pay_' . uniqid() . '_' . $order_id;

            // Имитация случайного успеха/неудачи (70% успех, 30% неудача)
            $will_succeed = mt_rand(1, 100) <= 70;

            $payment_data = [
                'id' => $payment_id,
                'status' => 'pending',
                'amount' => [
                    'value' => $amount,
                    'currency' => 'RUB'
                ],
                'confirmation' => [
                    'type' => 'redirect',
                    'confirmation_url' => $return_url . '?payment_id=' . $payment_id . '&test_status=' . ($will_succeed ? 'success' : 'fail')
                ],
                'description' => $description,
                'metadata' => [
                    'order_id' => $order_id,
                    'will_succeed' => $will_succeed
                ]
            ];

            // Сохраняем платеж в базу
            $this->savePaymentToDB($order_id, $payment_data, $will_succeed);

            return [
                'success' => true,
                'payment_id' => $payment_data['id'],
                'confirmation_url' => $payment_data['confirmation']['confirmation_url'],
                'will_succeed' => $will_succeed
            ];

        } catch (Exception $e) {
            error_log("Payment creation error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Ошибка создания платежа'];
        }
    }

    // Сохранение платежа в базу данных
    private function savePaymentToDB($order_id, $payment_data, $will_succeed) {
        // Получаем user_id из заказа
        $sql = 'SELECT user_id FROM orders WHERE id = ?';
        $query = $this->pdo->prepare($sql);
        $query->execute([$order_id]);
        $order = $query->fetch(PDO::FETCH_OBJ);

        if (!$order) {
            throw new Exception("Order not found");
        }

        // Сохраняем платеж
        $sql = 'INSERT INTO payments (order_id, user_id, amount, payment_method, payment_system, payment_status, transaction_id, confirmation_url) 
                VALUES (?, ?, ?, "card", "yookassa", ?, ?, ?)';

        $query = $this->pdo->prepare($sql);
        $query->execute([
            $order_id,
            $order->user_id,
            $payment_data['amount']['value'],
            'pending',
            $payment_data['id'],
            $payment_data['confirmation']['confirmation_url']
        ]);

        // Обновляем заказ
        $sql = 'UPDATE orders SET payment_system_id = ?, payment_status = ?, payment_url = ? WHERE id = ?';
        $query = $this->pdo->prepare($sql);
        $query->execute([
            $payment_data['id'],
            'waiting_for_capture',
            $payment_data['confirmation']['confirmation_url'],
            $order_id
        ]);

        // Логируем ожидаемый результат для отладки
        error_log("Payment created: {$payment_data['id']}, expected success: " . ($will_succeed ? 'yes' : 'no'));
    }

    // Проверка и обработка статуса платежа
    public function processPayment($payment_id, $test_status = null) {
        try {
            // Получаем данные платежа
            $sql = 'SELECT * FROM payments WHERE transaction_id = ?';
            $query = $this->pdo->prepare($sql);
            $query->execute([$payment_id]);
            $payment = $query->fetch(PDO::FETCH_OBJ);

            if (!$payment) {
                return ['success' => false, 'error' => 'Платеж не найден'];
            }

            // Если статус уже обработан, возвращаем его
            if ($payment->payment_status !== 'pending') {
                return [
                    'success' => $payment->payment_status === 'succeeded',
                    'status' => $payment->payment_status,
                    'payment_id' => $payment_id
                ];
            }

            // Определяем результат платежа
            $success = false;

            if ($test_status !== null) {
                // Используем переданный статус из URL
                $success = ($test_status === 'success');
            } else {
                // Случайный результат (70% успех)
                $success = mt_rand(1, 100) <= 70;
            }

            $new_status = $success ? 'succeeded' : 'failed';

            // Обновляем статус платежа
            $this->updatePaymentStatus($payment_id, $new_status);

            // Обновляем статус заказа
            $this->updateOrderStatus($payment->order_id, $new_status);

            return [
                'success' => $success,
                'status' => $new_status,
                'payment_id' => $payment_id,
                'order_id' => $payment->order_id
            ];

        } catch (Exception $e) {
            error_log("Payment processing error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Ошибка обработки платежа'];
        }
    }

    // Обновление статуса платежа в базе
    private function updatePaymentStatus($payment_id, $status) {
        $sql = 'UPDATE payments SET payment_status = ?, updated_at = CURRENT_TIMESTAMP WHERE transaction_id = ?';
        $query = $this->pdo->prepare($sql);
        $query->execute([$status, $payment_id]);
    }

    // Обновление статуса заказа
    private function updateOrderStatus($order_id, $payment_status) {
        if ($payment_status === 'succeeded') {
            $sql = 'UPDATE orders SET payment_status = "paid", status = "processing" WHERE id = ?';
        } else {
            $sql = 'UPDATE orders SET payment_status = "failed", status = "cancelled" WHERE id = ?';
        }

        $query = $this->pdo->prepare($sql);
        $query->execute([$order_id]);
    }

    // Обработка webhook от платежной системы
    public function handleWebhook($data) {
        try {
            file_put_contents('../webhook.log', date('Y-m-d H:i:s') . " - Webhook received: " . json_encode($data) . "\n", FILE_APPEND);

            if (isset($data['event']) && $data['event'] === 'payment.succeeded') {
                $payment_id = $data['object']['id'];
                $this->updatePaymentStatus($payment_id, 'succeeded');

                // Получаем order_id для обновления
                $sql = 'SELECT order_id FROM payments WHERE transaction_id = ?';
                $query = $this->pdo->prepare($sql);
                $query->execute([$payment_id]);
                $payment = $query->fetch(PDO::FETCH_OBJ);

                if ($payment) {
                    $this->updateOrderStatus($payment->order_id, 'succeeded');
                }

                // Отправляем email с подтверждением заказа
                require_once 'send-order-email.php';
                $email_sent = sendOrderEmail($payment->order_id, $pdo);

                if (!$email_sent) {
                    error_log("Failed to send confirmation email for order: " . $payment->order_id);
                }

                return ['success' => true];
            }

            if (isset($data['event']) && $data['event'] === 'payment.canceled') {
                $payment_id = $data['object']['id'];
                $this->updatePaymentStatus($payment_id, 'failed');

                $sql = 'SELECT order_id FROM payments WHERE transaction_id = ?';
                $query = $this->pdo->prepare($sql);
                $query->execute([$payment_id]);
                $payment = $query->fetch(PDO::FETCH_OBJ);

                if ($payment) {
                    $this->updateOrderStatus($payment->order_id, 'failed');
                }

                return ['success' => true];
            }

            return ['success' => false, 'error' => 'Unknown event'];

        } catch (Exception $e) {
            error_log("Webhook error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>