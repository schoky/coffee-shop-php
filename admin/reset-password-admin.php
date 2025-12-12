<?php
session_start();
if (isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

require_once '../lib/db.php';
require_once '../lib/password-reset.php';

$passwordReset = new PasswordReset($pdo);
$token = $_GET['token'] ?? '';
$message = '';
$error = '';

// Проверяем токен
if (empty($token)) {
    $error = 'Неверная ссылка для сброса пароля';
} else {
    $tokenValid = $passwordReset->validateToken($token, 'admin');
    if (!$tokenValid['success']) {
        $error = $tokenValid['message'];
    }
}

// Обработка формы сброса пароля
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['password'])) {
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($password) || empty($confirm_password)) {
        $error = 'Заполните все поля';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } elseif ($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } else {
        $result = $passwordReset->resetPassword($token, $password, 'admin');

        if ($result['success']) {
            $message = $result['message'];
            $success = true;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сброс пароля администратора — Реалист</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<main class="container">
    <div class="admin-reset-container">
        <h1>Сброс пароля администратора</h1>

        <?php if ($message): ?>
            <div class="success-message">
                <?php echo $message; ?>
                <div class="reset-actions">
                    <a href="login.php" class="btn">Войти с новым паролем</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message">
                <?php echo $error; ?>
                <div class="reset-actions">
                    <a href="forgot-password-admin.php" class="btn ghost">Запросить новую ссылку</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$message && !$error && $token): ?>
            <form method="post" class="reset-form">
                <div class="form-group">
                    <label for="password">Новый пароль (минимум 6 символов):</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Подтвердите пароль:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>
                <button type="submit" class="btn full-width">Сбросить пароль</button>
            </form>
        <?php endif; ?>
    </div>
</main>
</body>
</html>