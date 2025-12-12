<?php
session_start();

$name = trim(htmlspecialchars($_POST["name"]));
$password = trim(htmlspecialchars($_POST["password"]));

include "db.php";

$score = 'epiulskejmcofiuhvr';
$hashed_password = md5($score . $password);

$sql = 'SELECT id FROM users WHERE name = ? AND password = ?';
$query = $pdo->prepare($sql);
$query->execute([$name, $hashed_password]);

if ($query->rowCount() == 0) {
    // Сохраняем ошибку в сессии
    $_SESSION['auth_error'] = 'Неверное имя пользователя или пароль';
    header("Location: ../auth.php");
    exit;
} else {
    // Устанавливаем куки на 7 дней
    setcookie('name', $name, time() + 3600 * 24 * 7, '/');
    $_SESSION['user_name'] = $name;
    header("Location: ../index.php");
    exit;
}
?>