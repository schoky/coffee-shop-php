<?php
session_start();
if (isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Используем username вместо login
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
    <style>
        .admin-login {
            max-width: 400px;
            margin: 100px auto;
            padding: 40px;
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        .admin-login h1 {
            text-align: center;
            color: var(--accent);
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--muted);
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e6e6e6;
            border-radius: 8px;
            font-size: 16px;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .back-to-site {
            text-align: center;
            margin-top: 20px;
        }
        .login-hint {
            text-align: center;
            font-size: 14px;
            color: var(--muted);
            margin-top: 10px;
        }
    </style>
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