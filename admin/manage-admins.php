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
                // Хэширование пароля с помощью password_hash
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Добавляем нового администратора
                $sql = 'INSERT INTO admin_users (username, password, email) VALUES (?, ?, ?)';
                $query = $pdo->prepare($sql);
                $query->execute([$username, $hashed_password, $email]);
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

// Сброс пароля администратора
if (isset($_GET['reset_password'])) {
    $reset_id = intval($_GET['reset_password']);

    try {
        // Генерация временного пароля
        $temp_password = bin2hex(random_bytes(8));
        $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);

        // Обновление пароля
        $sql = 'UPDATE admin_users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?';
        $query = $pdo->prepare($sql);
        $query->execute([$hashed_password, $reset_id]);

        $success_message = "Пароль администратора сброшен. Новый пароль: <strong>$temp_password</strong>";

    } catch (PDOException $e) {
        error_log("Reset admin password error: " . $e->getMessage());
        $error_message = 'Ошибка при сбросе пароля';
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
        <div class="header-actions">
            <a href="index.php" class="btn ghost">Назад в панель</a>
            <a href="../index.php" class="btn">На сайт</a>
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
        <form method="post" class="admin-form">
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
                <button type="submit" name="add_admin" class="btn primary">Добавить администратора</button>
            </div>
        </form>
    </section>

    <section class="list-section">
        <h2>Список администраторов</h2>
        <?php if (empty($admins)): ?>
            <div class="empty-message">
                <p>Администраторов пока нет.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
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
                            <td class="admin-id"><?php echo $admin->id; ?></td>
                            <td class="admin-username">
                                <?php echo htmlspecialchars($admin->username); ?>
                                <?php if ($admin->id == $_SESSION['admin_id']): ?>
                                    <span class="current-user-badge">(Вы)</span>
                                <?php endif; ?>
                            </td>
                            <td class="admin-email"><?php echo htmlspecialchars($admin->email); ?></td>
                            <td class="admin-date"><?php echo date('d.m.Y H:i', strtotime($admin->created_at)); ?></td>
                            <td class="admin-actions">
                                <div class="action-buttons">
                                    <?php if ($admin->id != $_SESSION['admin_id']): ?>
                                        <a href="?delete=<?php echo $admin->id; ?>"
                                           class="btn-small btn-delete"
                                           onclick="return confirm('Вы уверены, что хотите удалить этого администратора?')">
                                            Удалить
                                        </a>
                                        <a href="?reset_password=<?php echo $admin->id; ?>"
                                           class="btn-small btn-reset"
                                           onclick="return confirm('Сбросить пароль этого администратора? Будет сгенерирован новый временный пароль.')">
                                            Сбросить пароль
                                        </a>
                                    <?php else: ?>
                                        <span class="current-user-label">Текущий пользователь</span>
                                        <a href="forgot-password-admin.php"
                                           class="btn-small btn-change"
                                           title="Сменить свой пароль">
                                            Сменить пароль
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="admin-notes">
            <h4>💡 Примечания:</h4>
            <ul>
                <li><strong>Сброс пароля</strong> генерирует новый временный пароль для администратора</li>
                <li><strong>Удаление</strong> полностью удаляет администратора из системы</li>
                <li>Нельзя удалить свою собственную учетную запись</li>
                <li>Для смены собственного пароля используйте <a href="forgot-password-admin.php">форму восстановления</a></li>
            </ul>
        </div>
    </section>
</main>
</body>
</html>