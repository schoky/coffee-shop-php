<?php
session_start();
require_once 'lib/db.php';
require_once 'lib/password-reset.php';

$passwordReset = new PasswordReset($pdo);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error = 'Введите email адрес';
    } else {
        $result = $passwordReset->generateResetToken($email, 'user');

        if ($result['success']) {
            // Создаем ссылку для сброса
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=" . $result['token'];

            // Отправляем email (временная заглушка с отображением ссылки)
            $message = "Ссылка для сброса пароля: <a href='$resetLink' style='color: #007bff; text-decoration: underline; font-weight: bold;'>$resetLink</a><br><br>";
            $message .= "Скопируйте и перейдите по этой ссылке для сброса пароля.<br>";
            $message .= "Ссылка действительна 1 час.";

            // Логируем для отладки
            error_log("Reset link for user {$email}: {$resetLink}");
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
    <title>Восстановление пароля — Реалист</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require "block/header.php" ?>
<main class="container">
    <div class="reset-container">
        <div class="reset-form-card">
            <h1>Восстановление пароля</h1>

            <?php if ($message): ?>
                <div class="success-message">
                    <?php echo $message; ?>
                    <div class="reset-actions">
                        <a href="auth.php" class="btn">Вернуться к авторизации</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (!$message): ?>
                <form method="post">
                    <div class="form-group">
                        <p>Введите email, указанный при регистрации.</p>
                        <p>Мы отправим вам ссылку для сброса пароля.</p>
                    </div>

                    <div class="form-group">
                        <label for="email">Ваш Email:</label>
                        <input type="email" id="email" name="email" required placeholder="example@email.com">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn full-width">Отправить ссылку для сброса</button>
                    </div>
                </form>
            <?php endif; ?>

            <div class="reset-links">
                <a href="auth.php" class="text-link">Вернуться к авторизации</a>
            </div>
        </div>
    </div>
</main>
<?php require "block/footer.php" ?>
</body>
</html>