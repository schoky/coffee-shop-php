<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile.css">
    <title>Реалист — Личный профиль</title>
</head>
<body>
<?php
session_start();
if (!isset($_COOKIE['name'])) {
    header('Location: auth.php');
    exit;
}

require_once 'lib/db.php';

$user_data = null;
$bonus_balance = 0;
$is_bonus_program = 0;

try {
    $sql = 'SELECT name, email, bonus_balance, is_bonus_program, created_at FROM users WHERE name = ?';
    $query = $pdo->prepare($sql);
    $query->execute([$_COOKIE['name']]);
    $user_data = $query->fetch(PDO::FETCH_OBJ);

    if ($user_data) {
        $bonus_balance = $user_data->bonus_balance;
        $is_bonus_program = $user_data->is_bonus_program;
    }
} catch (PDOException $e) {
    error_log("User data error: " . $e->getMessage());
}
?>
<?php require "block/header.php" ?>

<?php
if (isset($_SESSION['profile_error'])) {
    echo '<div class="container"><div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin: 20px 0; border: 1px solid #f5c6cb;">';
    echo $_SESSION['profile_error'];
    echo '</div></div>';
    unset($_SESSION['profile_error']);
}

if (isset($_SESSION['profile_success'])) {
    echo '<div class="container"><div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin: 20px 0; border: 1px solid #c3e6cb;">';
    echo $_SESSION['profile_success'];
    echo '</div></div>';
    unset($_SESSION['profile_success']);
}
?>

<main class="container">
    <section class="profile">
        <div class="avatar-section">
            <div class="avatar-container">
                <img class="avatar" src="/image/avatar.png" alt="Аватар пользователя" id="userAvatar">
                <div class="avatar-overlay">
                    <label for="avatarInput" class="avatar-change-btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                            <circle cx="12" cy="13" r="4"></circle>
                        </svg>
                        Сменить фото
                    </label>
                </div>
            </div>
            <input type="file" id="avatarInput" accept="image/*" style="display: none;">
            <p class="avatar-hint">Нажмите на аватар для изменения</p>
        </div>

        <div class="profile-info">
            <div class="name-field">
                <span class="name-label">Имя:</span>
                <span class="name-value" id="userName"><?php echo htmlspecialchars($user_data->name ?? ''); ?></span>
            </div>
            <div class="bonus-balance">
                <span class="name-label">Бонусный баланс:</span>
                <span class="name-value" style="color: #28a745;"><?php echo number_format($bonus_balance, 2); ?> бонусов</span>
            </div>
            <?php if (!$is_bonus_program): ?>
                <div class="bonus-program" style="margin-top: 15px;">
                    <button class="btn small" id="joinBonusProgram">Присоединиться к бонусной программе</button>
                </div>
            <?php else: ?>
                <div class="bonus-program" style="margin-top: 15px;">
                    <span style="color: #28a745; font-weight: 600;">✓ Участник бонусной программы</span>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="info">
        <h3>Контактная информация</h3>
        <div class="info-grid">
            <div class="info-item">
                <label>Имя пользователя:</label>
                <p id="userNameInfo"><?php echo htmlspecialchars($user_data->name ?? ''); ?></p>
            </div>
            <div class="info-item">
                <label>Email:</label>
                <p id="userEmail"><?php echo htmlspecialchars($user_data->email ?? ''); ?></p>
            </div>
            <div class="info-item">
                <label>Бонусный баланс:</label>
                <p id="userBonus" style="color: #28a745; font-weight: 600;"><?php echo number_format($bonus_balance, 2); ?> бонусов</p>
            </div>
            <div class="info-item">
                <label>Статус программы:</label>
                <p><?php echo $is_bonus_program ? 'Участник бонусной программы' : 'Не участвует'; ?></p>
            </div>
            <div class="info-item">
                <label>Дата регистрации:</label>
                <p><?php echo date('d.m.Y', strtotime($user_data->created_at ?? '')); ?></p>
            </div>
        </div>
    </section>

    <section class="data">
        <button class="btn" id="editProfile">Изменить профиль</button>
        <a href="order-history.php" class="btn ghost">История заказов</a>
    </section>

    <!-- Модальное окно для редактирования профиля -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeModal">&times;</span>
            <h3>Редактирование профиля</h3>
            <form id="profileForm" action="lib/update-profile.php" method="post">
                <div class="form-group">
                    <label for="editName">Имя пользователя:</label>
                    <input type="text" id="editName" name="name" value="<?php echo htmlspecialchars($user_data->name ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="editEmail">Email:</label>
                    <input type="email" id="editEmail" name="email" value="<?php echo htmlspecialchars($user_data->email ?? ''); ?>" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn" id="confirmSave">Сохранить</button>
                    <button type="button" class="btn ghost" id="cancelEdit">Отмена</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require "block/footer.php" ?>
<script src="js/script.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const joinBonusBtn = document.getElementById('joinBonusProgram');
        if (joinBonusBtn) {
            joinBonusBtn.addEventListener('click', function() {
                if (confirm('Хотите присоединиться к бонусной программе? Вы будете получать 5% бонусами от каждой покупки и сможете оплачивать до 50% стоимости заказа бонусами.')) {
                    fetch('lib/join-bonus-program.php')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Вы успешно присоединились к бонусной программе!');
                                location.reload();
                            } else {
                                alert('Ошибка: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Произошла ошибка при подключении к бонусной программе');
                        });
                }
            });
        }
    });
</script>
</body>
</html>