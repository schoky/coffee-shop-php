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
            JOIN users u ON oe.user_id = u.id 
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

// Выводим содержимое письма
echo $email->content;
?>