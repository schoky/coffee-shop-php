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
    <link rel="stylesheet" href="../css/admin.css">
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