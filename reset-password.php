<?php
session_start();
require_once 'lib/db.php';
require_once 'lib/password-reset.php';

$passwordReset = new PasswordReset($pdo);
$token = $_GET['token'] ?? '';
$message = '';
$error = '';

// Проверяем токен
if (empty($token)) {
    $error = 'Неверная ссылка для сброса пароля';
} else {
    $tokenValid = $passwordReset->validateToken($token, 'user');
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
        $result = $passwordReset->resetPassword($token, $password, 'user');

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
    <title>Сброс пароля — Реалист</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .reset-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .reset-form {
            background: var(--white);
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e6e6e6;
            border-radius: 8px;
            font-size: 16px;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<?php require "block/header.php" ?>
<main class="container">
    <div class="reset-container">
        <h1>Сброс пароля</h1>

        <?php if ($message): ?>
            <div class="success-message">
                <?php echo $message; ?>
                <div style="margin-top: 10px;">
                    <a href="auth.php" class="btn">Войти с новым паролем</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message">
                <?php echo $error; ?>
                <div style="margin-top: 10px;">
                    <a href="forgot-password.php" class="btn ghost">Запросить новую ссылку</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$message && !$error && $token): ?>
            <div class="reset-form">
                <p>Введите новый пароль для вашего аккаунта.</p>

                <form method="post">
                    <div class="form-group">
                        <label for="password">Новый пароль:</label>
                        <input type="password" id="password" name="password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Подтвердите пароль:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                    <button type="submit" class="btn" style="width: 100%;">Сбросить пароль</button>
                </form>

                <div class="login-link">
                    <a href="auth.php">Вернуться к авторизации</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php require "block/footer.php" ?>
</body>
</html>