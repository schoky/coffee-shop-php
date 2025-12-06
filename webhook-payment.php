<?php
require_once 'lib/db.php';
require_once 'lib/payment-processor.php';

// Логирование webhook запросов
file_put_contents('webhook.log', date('Y-m-d H:i:s') . " - Webhook received\n", FILE_APPEND);

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        $data = $_POST;
    }

    file_put_contents('webhook.log', "Data: " . print_r($data, true) . "\n", FILE_APPEND);

    if ($data) {
        $pdo = new PDO('mysql:host=MySQL-8.2; dbname=coffee-shop; port=3306', 'root', '');
        $payment_processor = new PaymentProcessor($pdo);

        $result = $payment_processor->handleWebhook($data);

        if ($result['success']) {
            http_response_code(200);
            echo json_encode(['status' => 'success']);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $result['error']]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'No data received']);
    }
} catch (Exception $e) {
    error_log("Webhook processing error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
}
?>