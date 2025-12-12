<?php
session_start();
if (isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (!empty($login) && !empty($password)) {
        require_once '../lib/db.php';

        try {
            // Ищем администратора по username ИЛИ email
            $sql = 'SELECT * FROM admin_users WHERE (username = ? OR email = ?) AND password = MD5(?)';
            $query = $pdo->prepare($sql);
            $query->execute([$login, $login, $password]);
            $admin = $query->fetch(PDO::FETCH_OBJ);

            if ($admin) {
                $_SESSION['admin'] = $admin->username;
                $_SESSION['admin_id'] = $admin->id;
                $_SESSION['admin_email'] = $admin->email;
                header('Location: index.php');
                exit;
            } else {
                $error = 'Неверное имя пользователя, email или пароль';
            }
        } catch (PDOException $e) {
            error_log("Admin login error: " . $e->getMessage());
            $error = 'Ошибка при подключении к базе данных';
        }
    } else {
        $error = 'Заполните все поля';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Реалист — Админ панель</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<main class="container">
    <div class="admin-login">
        <h1>Админ панель</h1>
        <p style="text-align: center; color: var(--muted); margin-bottom: 30px;">Кофейня "Реалист"</p>

        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="username">Имя пользователя или Email:</label>
                <input type="text" id="username" name="username" required placeholder="Введите username или email">
            </div>
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="login-hint">
                <small>Можно использовать как имя пользователя, так и email</small>
            </div>

            <button type="submit" class="btn" style="width: 100%; margin-top: 20px;">Войти</button>
        </form>

        <div class="back-to-site">
            <a href="../index.php" class="btn ghost" style="margin-top: 15px;">← Вернуться на сайт</a>
        </div>
    </div>
</main>
</body>
</html>