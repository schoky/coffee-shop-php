<?php
session_start();
if (!isset($_COOKIE['name'])) {
    header('Location: auth.php');
    exit;
}

require_once 'lib/db.php';
require_once 'lib/password-reset.php';

$passwordReset = new PasswordReset($pdo);
$message = '';
$error = '';

// Получаем ID пользователя
$user_name = $_COOKIE['name'];
$user_id = 0;

try {
    $sql = 'SELECT id FROM users WHERE name = ?';
    $query = $pdo->prepare($sql);
    $query->execute([$user_name]);
    $user = $query->fetch(PDO::FETCH_OBJ);
    if ($user) {
        $user_id = $user->id;
    }
} catch (PDOException $e) {
    error_log("User ID error: " . $e->getMessage());
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['current_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Заполните все поля';
    } elseif (strlen($new_password) < 6) {
        $error = 'Новый пароль должен быть не менее 6 символов';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Новые пароли не совпадают';
    } else {
        $result = $passwordReset->changePassword($user_id, $current_password, $new_password, 'user');

        if ($result['success']) {
            $message = $result['message'];
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
    <title>Смена пароля — Реалист</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require "block/header.php" ?>
<main class="container">
    <div class="form-card">
        <h2>Смена пароля</h2>

        <?php if ($message): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <label>Текущий пароль
                <input type="password" name="current_password" required>
            </label>
            <label>Новый пароль
                <input type="password" name="new_password" required minlength="6">
            </label>
            <label>Подтвердите новый пароль
                <input type="password" name="confirm_password" required minlength="6">
            </label>
            <div class="modal-actions">
                <button type="submit" class="btn">Сменить пароль</button>
                <a href="Userpage.php" class="btn ghost">Отмена</a>
            </div>
        </form>
    </div>
</main>
<?php require "block/footer.php" ?>
</body>
</html>