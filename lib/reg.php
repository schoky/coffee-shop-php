<?php
session_start();

$name = trim(htmlspecialchars($_POST["name"]));
$email = trim(filter_var($_POST["email"], FILTER_VALIDATE_EMAIL));
$password = trim(htmlspecialchars($_POST["password"]));

if (!$email) {
    $_SESSION['reg_error'] = 'Некорректный email';
    header("Location: ../reagpage.php");
    exit;
}

if (strlen($password) < 6) {
    $_SESSION['reg_error'] = 'Пароль должен содержать минимум 6 символов';
    header("Location: ../reagpage.php");
    exit;
}

include "db.php";

$sql_check = 'SELECT id FROM users WHERE name = ? OR email = ?';
$query_check = $pdo->prepare($sql_check);
$query_check->execute([$name, $email]);

if ($query_check->rowCount() > 0) {
    $_SESSION['reg_error'] = 'Пользователь с таким именем или email уже существует';
    header("Location: ../reagpage.php");
    exit;
}

$score = 'epiulskejmcofiuhvr';
$hashed_password = md5($score . $password);

$sql = 'INSERT INTO users (name, email, password, bonus_balance, is_bonus_program) VALUES(?, ?, ?, 0, 0)';
$query = $pdo->prepare($sql);
$query->execute([$name, $email, $hashed_password]);

if ($query->rowCount() == 0) {
    $_SESSION['reg_error'] = 'Ошибка регистрации';
    header("Location: ../reagpage.php");
    exit;
} else {
    setcookie('name', $name, time() + 3600 * 24 * 7, '/');
    $_SESSION['user_name'] = $name;
    header("Location: ../index.php");
    exit;
}
?>