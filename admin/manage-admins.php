<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require_once '../lib/db.php';

$success_message = '';
$error_message = '';

// Добавление нового администратора
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_admin'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $email = trim($_POST['email']);

    if (!empty($username) && !empty($password) && !empty($email)) {
        try {
            // Проверяем, существует ли уже администратор с таким логином
            $sql = 'SELECT id FROM admin_users WHERE username = ?';
            $query = $pdo->prepare($sql);
            $query->execute([$username]);

            if ($query->rowCount() > 0) {
                $error_message = 'Администратор с таким логином уже существует';
            } else {
                // Добавляем нового администратора
                $sql = 'INSERT INTO admin_users (username, password, email) VALUES (?, MD5(?), ?)';
                $query = $pdo->prepare($sql);
                $query->execute([$username, $password, $email]);
                $success_message = 'Новый администратор успешно добавлен';
            }
        } catch (PDOException $e) {
            error_log("Add admin error: " . $e->getMessage());
            $error_message = 'Ошибка при добавлении администратора';
        }
    } else {
        $error_message = 'Заполните все поля';
    }
}

// Удаление администратора
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);

    // Не позволяем удалить самого себя
    if ($delete_id == $_SESSION['admin_id']) {
        $error_message = 'Нельзя удалить свою собственную учетную запись';
    } else {
        try {
            $sql = 'DELETE FROM admin_users WHERE id = ?';
            $query = $pdo->prepare($sql);
            $query->execute([$delete_id]);
            $success_message = 'Администратор успешно удален';
        } catch (PDOException $e) {
            error_log("Delete admin error: " . $e->getMessage());
            $error_message = 'Ошибка при удалении администратора';
        }
    }
}

// Получение списка администраторов
try {
    $sql = 'SELECT * FROM admin_users ORDER BY created_at DESC';
    $query = $pdo->prepare($sql);
    $query->execute();
    $admins = $query->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Get admins error: " . $e->getMessage());
    $admins = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление администраторами — Реалист</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e6e6e6;
        }
        .form-section, .list-section {
            background: var(--white);
            padding: 32px;
            border-radius: var(--radius);
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            margin-bottom: 32px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
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
        .form-actions {
            margin-top: 24px;
        }
        .admins-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .admins-table th,
        .admins-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .admins-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        .btn-small {
            padding: 6px 12px;
            font-size: 14px;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px 16px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .current-user {
            background-color: #fff3cd;
        }
    </style>
</head>
<body>
<main class="container admin-container">
    <div class="admin-header">
        <h1>Управление администраторами</h1>
        <div>
            <a href="index.php" class="btn ghost">Назад в панель</a>
            <a href="../index.php" class="btn" style="margin-left: 15px;">На сайт</a>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="success-message">✅ <?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="error-message">❌ <?php echo $error_message; ?></div>
    <?php endif; ?>

    <section class="form-section">
        <h2>Добавить нового администратора</h2>
        <form method="post">
            <div class="form-grid">
                <div class="form-group">
                    <label for="username">Имя пользователя *</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="password">Пароль *</label>
                    <input type="password" id="password" name="password" required>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-actions">
                <button type="submit" name="add_admin" class="btn">Добавить администратора</button>
            </div>
        </form>
    </section>

    <section class="list-section">
        <h2>Список администраторов</h2>
        <?php if (empty($admins)): ?>
            <p>Администраторов пока нет.</p>
        <?php else: ?>
            <table class="admins-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя пользователя</th>
                    <th>Email</th>
                    <th>Дата создания</th>
                    <th>Действия</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($admins as $admin): ?>
                    <tr class="<?php echo $admin->id == $_SESSION['admin_id'] ? 'current-user' : ''; ?>">
                        <td><?php echo $admin->id; ?></td>
                        <td>
                            <?php echo htmlspecialchars($admin->username); ?>
                            <?php if ($admin->id == $_SESSION['admin_id']): ?>
                                <span style="color: #856404; margin-left: 8px;">(Вы)</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($admin->email); ?></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($admin->created_at)); ?></td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($admin->id != $_SESSION['admin_id']): ?>
                                    <a href="?delete=<?php echo $admin->id; ?>" class="btn btn-small"
                                       onclick="return confirm('Вы уверены, что хотите удалить этого администратора?')"
                                       style="background: #dc3545;">Удалить</a>
                                <?php else: ?>
                                    <span style="color: var(--muted); font-size: 14px;">Текущий пользователь</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>
</body>
</html>