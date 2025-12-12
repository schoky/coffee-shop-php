<?php
session_start();
require_once '../lib/db.php';
require_once '../lib/password-reset.php';

$passwordReset = new PasswordReset($pdo);
$message = '';
$error = '';

// Если администратор авторизован, получаем его данные
if (isset($_SESSION['admin'])) {
    try {
        $sql = 'SELECT id, username, email FROM admin_users WHERE id = ?';
        $query = $pdo->prepare($sql);
        $query->execute([$_SESSION['admin_id']]);
        $current_admin = $query->fetch(PDO::FETCH_OBJ);
    } catch (PDOException $e) {
        error_log("Get admin data error: " . $e->getMessage());
        $current_admin = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_reset'])) {
    // Для авторизованного администратора используем email из сессии
    // Для неавторизованного - из формы
    if (isset($_SESSION['admin']) && isset($current_admin)) {
        $email = $current_admin->email;
    } else {
        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            $error = 'Введите email адрес';
        }
    }

    if (empty($error)) {
        $result = $passwordReset->generateResetToken($email, 'admin');

        if ($result['success']) {
            // Создаем ссылку для сброса
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/admin/reset-password-admin.php?token=" . $result['token'];

            // Отправляем email
            $emailSent = $passwordReset->sendResetEmail($email, $resetLink, 'admin');

            if ($emailSent) {
                $message = "Ссылка для сброса пароля отправлена на email: <strong>" . htmlspecialchars($email) . "</strong><br><br>";
                $message .= "Проверьте вашу почту и следуйте инструкциям в письме.<br>";
                $message .= "Ссылка действительна 1 час.";
            } else {
                $message = "Ссылка для сброса пароля: <a href='$resetLink' style='color: #007bff; text-decoration: underline;'>$resetLink</a><br><br>";
                $message .= "Скопируйте и перейдите по этой ссылке для сброса пароля.<br>";
                $message .= "Ссылка действительна 1 час.";
            }
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
    <title><?php echo isset($_SESSION['admin']) ? 'Смена пароля' : 'Восстановление пароля'; ?> администратора — Реалист</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<main class="container">
    <div class="admin-reset-container">
        <h1><?php echo isset($_SESSION['admin']) ? 'Смена пароля администратора' : 'Восстановление пароля администратора'; ?></h1>

        <?php if (isset($_SESSION['admin']) && isset($current_admin)): ?>
            <div class="current-admin-info">
                <p>Вы авторизованы как: <strong><?php echo htmlspecialchars($current_admin->username); ?></strong></p>
                <p>Email для отправки ссылки: <strong><?php echo htmlspecialchars($current_admin->email); ?></strong></p>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="success-message">
                <?php echo $message; ?>
                <?php if (isset($_SESSION['admin'])): ?>
                    <div class="reset-actions">
                        <a href="index.php" class="btn">Вернуться в панель</a>
                    </div>
                <?php else: ?>
                    <div class="reset-actions">
                        <a href="login.php" class="btn ghost">Вернуться к авторизации</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (!$message): ?>
            <form method="post">
                <?php if (!isset($_SESSION['admin'])): ?>
                    <div class="form-group">
                        <label for="email">Email администратора:</label>
                        <input type="email" id="email" name="email" required placeholder="admin@realist.ru">
                    </div>
                <?php else: ?>
                    <input type="hidden" name="send_reset" value="1">
                    <div class="form-group">
                        <p class="info-text">Для смены пароля будет отправлена ссылка на email вашей учетной записи.</p>
                    </div>
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit" name="send_reset" class="btn full-width">
                        <?php echo isset($_SESSION['admin']) ? 'Отправить ссылку для смены пароля' : 'Отправить ссылку для сброса'; ?>
                    </button>
                </div>
            </form>
        <?php endif; ?>

        <div class="reset-actions">
            <?php if (isset($_SESSION['admin'])): ?>
                <a href="index.php" class="btn ghost">Вернуться в панель</a>
            <?php else: ?>
                <a href="login.php" class="btn ghost">Вернуться к авторизации</a>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>