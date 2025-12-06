<?php
session_start();
// Этот файл можно удалить после создания администратора

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once '../lib/db.php';

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $email = trim($_POST['email']);

    if (!empty($username) && !empty($password)) {
        try {
            // Создаем таблицу если она не существует
            $sql = "CREATE TABLE IF NOT EXISTS `admin_users` (
                `id` int NOT NULL AUTO_INCREMENT,
                `username` varchar(50) NOT NULL,
                `password` varchar(255) NOT NULL,
                `email` varchar(100) NOT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3";
            $pdo->exec($sql);

            // Проверяем, существует ли уже администратор
            $sql = 'SELECT id FROM admin_users WHERE username = ?';
            $query = $pdo->prepare($sql);
            $query->execute([$username]);

            if ($query->rowCount() > 0) {
                $message = "Администратор с таким именем уже существует";
            } else {
                // Создаем администратора
                $sql = 'INSERT INTO admin_users (username, password, email) VALUES (?, MD5(?), ?)';
                $query = $pdo->prepare($sql);
                $query->execute([$username, $password, $email]);
                $message = "Администратор успешно создан! Логин: $username";
            }
        } catch (PDOException $e) {
            $message = "Ошибка: " . $e->getMessage();
        }
    } else {
        $message = "Заполните все поля";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание администратора</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .create-admin {
            max-width: 400px;
            margin: 100px auto;
            padding: 40px;
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
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
        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<main class="container">
    <div class="create-admin">
        <h1>Создание администратора</h1>

        <?php if (isset($message)): ?>
            <div class="message <?php echo strpos($message, 'успешно') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="username">Имя пользователя:</label>
                <input type="text" id="username" name="username" required value="admin">
            </div>
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required value="admin@coffee-shop.ru">
            </div>
            <button type="submit" class="btn" style="width: 100%;">Создать администратора</button>
        </form>

        <p style="text-align: center; margin-top: 20px; color: var(--muted);">
            <small>После создания администратора удалите этот файл (create-admin.php) для безопасности</small>
        </p>
    </div>
</main>
</body>
</html>