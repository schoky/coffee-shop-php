<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require_once '../lib/db.php';

// Получаем статистику
try {
    // Количество товаров
    $sql = 'SELECT COUNT(*) as count FROM photo WHERE is_active = 1';
    $query = $pdo->prepare($sql);
    $query->execute();
    $products_count = $query->fetch(PDO::FETCH_OBJ)->count;

    // Количество заказов
    $sql = 'SELECT COUNT(*) as count FROM orders';
    $query = $pdo->prepare($sql);
    $query->execute();
    $orders_count = $query->fetch(PDO::FETCH_OBJ)->count;

    // Количество новых заявок
    $sql = 'SELECT COUNT(*) as count FROM contact_requests WHERE status = "new"';
    $query = $pdo->prepare($sql);
    $query->execute();
    $new_requests_count = $query->fetch(PDO::FETCH_OBJ)->count;

    // Количество пользователей
    $sql = 'SELECT COUNT(*) as count FROM users';
    $query = $pdo->prepare($sql);
    $query->execute();
    $users_count = $query->fetch(PDO::FETCH_OBJ)->count;

} catch (PDOException $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
    $products_count = $orders_count = $new_requests_count = $users_count = 0;
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
<main class="container admin-container">
    <div class="admin-header">
        <h1>Админ панель — Реалист</h1>
        <div>
            <span>Добро пожаловать, <?php echo htmlspecialchars($_SESSION['admin']); ?></span>
            <a href="logout.php" class="btn logout-btn" style="margin-left: 15px;">Выйти</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $products_count; ?></div>
            <div class="stat-label">Товаров в каталоге</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $orders_count; ?></div>
            <div class="stat-label">Всего заказов</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $new_requests_count; ?></div>
            <div class="stat-label">Новых заявок</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $users_count; ?></div>
            <div class="stat-label">Зарегистрированных пользователей</div>
        </div>
    </div>

    <div class="admin-nav">
        <a href="products.php" class="nav-card">
            <h3>📦 Управление товарами</h3>
            <p>Добавление, редактирование и удаление товаров в каталоге</p>
        </a>

        <a href="orders.php" class="nav-card">
            <h3>📋 Управление заказами</h3>
            <p>Просмотр и управление заказами пользователей</p>
        </a>

        <a href="order-emails.php" class="nav-card">
            <h3>📧 Письма заказов</h3>
            <p>Просмотр всех писем с подтверждением заказов</p>
        </a>

        <a href="contact-requests.php" class="nav-card">
            <h3>📞 Заявки с сайта</h3>
            <p>Просмотр и обработка заявок из формы контактов</p>
        </a>

        <a href="manage-admins.php" class="nav-card">
            <h3>👥 Управление администраторами</h3>
            <p>Добавление и удаление администраторов системы</p>
        </a>

        <a href="../index.php" class="nav-card">
            <h3>🌐 Перейти на сайт</h3>
            <p>Вернуться на главную страницу сайта</p>
        </a>
    </div>
</main>
</body>
</html>