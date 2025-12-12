<?php
session_start();
if (!isset($_COOKIE['name'])) {
    header('Location: auth.php');
    exit;
}

// Проверяем параметры
if (!isset($_GET['id'])) {
    header('Location: order-emails.php');
    exit;
}

$email_id = intval($_GET['id']);

// Безопасность: проверяем, что письмо принадлежит пользователю
require_once 'lib/db.php';

$user_name = $_COOKIE['name'];
$email = null;

try {
    $sql = 'SELECT oe.* FROM order_emails oe 
            JOIN orders o ON oe.order_id = o.id 
            JOIN users u ON o.user_id = u.id 
            WHERE oe.id = ? AND u.name = ?';
    $query = $pdo->prepare($sql);
    $query->execute([$email_id, $user_name]);
    $email = $query->fetch(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Email fetch error: " . $e->getMessage());
}

if (!$email) {
    header('Location: order-emails.php');
    exit;
}

// Устанавливаем заголовки для корректного отображения HTML
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Письмо с заказом #<?php echo $email->order_id; ?></title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
            font-family: Arial, sans-serif;
        }
        .header {
            background: #6b4f2b;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .back-button {
            display: inline-block;
            margin: 20px 0;
            padding: 10px 20px;
            background: #6b4f2b;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .back-button:hover {
            background: #5a4124;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>Письмо с заказом #<?php echo $email->order_id; ?></h2>
        <p>Отправлено: <?php echo date('d.m.Y H:i', strtotime($email->created_at)); ?></p>
    </div>

    <div style="padding: 20px;">
        <?php echo $email->content; ?>
    </div>

    <div style="text-align: center; padding: 20px; border-top: 1px solid #ddd;">
        <a href="order-emails.php" class="back-button">← Вернуться к списку писем</a>
    </div>
</div>
</body>
</html>