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
    <style>
        .admin-container {
            max-width: 1200px;
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: var(--white);
            padding: 32px 24px;
            border-radius: var(--radius);
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            text-align: center;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 42px;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 8px;
        }
        .stat-label {
            color: var(--muted);
            font-size: 16px;
        }
        .admin-nav {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
        }
        .nav-card {
            background: var(--white);
            padding: 32px;
            border-radius: var(--radius);
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            text-align: center;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .nav-card:hover {
            border-color: var(--accent);
            transform: translateY(-3px);
        }
        .nav-card h3 {
            color: var(--accent);
            margin-bottom: 12px;
        }
        .logout-btn {
            background: #dc3545;
            color: white;
        }
        .logout-btn:hover {
            background: #c82333;
        }
    </style>
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